<template>
  <div class="py-6">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">
        <Head title="Chat" />
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" ref="networkTabs" id="network-tabs" role="tablist">
                    <li v-for="network in networks" :ref="`${network}-tab`" role="presentation">
                        <button type="button" :ref="`${network}-trigger`" role="tab" :aria-controls="`${network}-tab`" aria-selected="false" class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" >
                            {{ network }}
                        </button>
                    </li>
                </ul>
            </div>
            <div>
                <div v-for="network in networks" :ref="`${network}-target`" role="tabpanel" :aria-labelledby="`${network}-tab`" class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" >
                    <chat-client
                        :settings="settings"
                        :downloads="downloads"
                        :downloadLocks="downloadLocks"
                        :network="network"
                        :client="clients[network]"
                        :channels="listChannels(clients[network])"
                        :isActive="`${network}-tab` === activeTab.id"
                        @call:checkDownloadQueue="checkDownloadQueue"
                        @call:removeCompleted="removeCompleted"
                        @call:requestCancel="requestCancel"
                        @call:requestRemove="requestRemove"
                        @call:saveDownloadDestination="saveDownloadDestination" />
                </div>
            </div>
      </div>
    </div>
  </div>
  </template>

  <script>
  import { Head, Link } from '@inertiajs/vue3'
  import { initFlowbite, Tabs } from 'flowbite'
  import Multiselect from '@vueform/multiselect'
  import { saveDownloadDestination } from '@/Clients/download-destination'
  import { has } from '@/funcs'

  // local imports
  import { fetchLocks } from '@/Clients/browse'
  import { fetchNetworkClients } from '@/Clients/network'
  import { fetchDownloadQueue } from '@/Clients/download-queue'
  import { makeDownloadIndexFromQueue } from '@/download-queue'
  import { removeCompleted, requestRemove, requestCancel } from '@/Clients/rpc'
  import { cleanChannelName } from '@/format'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import ChatClient from '@/Components/ChatClient.vue'

  const clientsInterval = 10000 // Check network connections every 10 seconds.
  let clientsTimeoutId
  const clearClientsInterval = function () {
    clearTimeout(clientsTimeoutId)
  }

  const downloadQueueInterval = 5000; // Check download queue every 5 seconds.
  let downloadQueueTimeoutId;
  const clearDownloadQueueInterval = function () {
    clearTimeout(downloadQueueTimeoutId)
  }

  const locksInterval = 5000; // Check download locks every 5 seconds.
  let locksTimeoutId;
  const clearLocksInterval = function () {
    clearTimeout(locksTimeoutId)
  }

  export default {
    components: {
      Head,
      Link,
      Multiselect,
      ChatClient,
    },
    layout: AppLayout,
    props: {
      queue: Object,
      settings: Object,
      networks: Array,
      instances: Object,
      locks: Array,
    },
    data() {
        return {
            clients: this.instances,
            downloadQueue: this.queue,
            downloads: {},
            tabs: null,
            activeTab: { id: null },
            downloadLocks: this.locks,
        }
    },
    mounted() {
        initFlowbite()
        this.checkDownloadQueue()
        this.checkClients()
        this.checkLocks()
        this.tabs = this.makeTabs()
    },
    watch: {
        downloadQueue: {
            deep: true,
            handler: function() {
                this.downloads = makeDownloadIndexFromQueue(this.downloadQueue)
            },
        },
    },
    methods: {
      async checkDownloadQueue() {
        await this.fetchDownloadQueue()
        clearDownloadQueueInterval()
        downloadQueueTimeoutId = setTimeout(this.checkDownloadQueue, downloadQueueInterval)
      },
      async checkClients() {
        await this.fetchClients()
        clearClientsInterval()
        clientsTimeoutId = setTimeout(this.checkClients, clientsInterval)
      },
      async checkLocks() {
        await this.fetchLocks()
        clearLocksInterval()
        locksTimeoutId = setTimeout(this.checkLocks, locksInterval)
      },
      async fetchClients() {
        const {data, error} = await fetchNetworkClients()
        if (null === error) {
          this.clients = data
        } else {
          console.log(error)
        }
      },
      async fetchDownloadQueue() {
        const {data, error} = await fetchDownloadQueue()
        if (null === error) {
          this.downloadQueue = data
        } else {
          console.log(error)
        }
      },
      async fetchLocks(packetList) {
        const { data, error } = await fetchLocks(packetList)

        if (null === error) {
            const { locks } = data
            this.downloadLocks = locks

            if (locks.length <= 0) {
                clearLocksInterval()
            }
        }
      },
      async saveDownloadDestination(download, uri) {
        const body = {
            destination_dir: uri,
            download: download.id
        }

        // Use put instead of post if dd already exists.
        if (null !== download.destination) {
            body.id = download.destination.id
        }

        const {error} = await saveDownloadDestination(body)

        if (null === error) {
            this.fetchDownloadQueue()
        }
      },
      async requestRemove(packetId) {
        const {data, error} = await requestRemove(packetId)

        if (null === error) {
            const fileName = data.result.packet.file_name
            const locksIndex = this.locks.indexOf(fileName)
            if (0 <= locksIndex) {
                delete this.locks[locksIndex]
            }

            if (has(this.queued, fileName)) {
                delete this.queued[fileName]
            }

            if (has(this.downloadQueue.queued, fileName)) {
                delete this.downloadQueue.queued[fileName]
            }
        }
      },
      async requestCancel(download) {
        const { error } = await requestCancel(download)

        if (null === error) {
            this.fetchLocks()
            this.fetchDownloadQueue()
        }
      },
      async removeCompleted(download) {
        const { error } = await removeCompleted(download)

        if (null === error) {
            this.fetchLocks()
            this.fetchDownloadQueue()
        }
      },
      listChannels(network) {
        const channels = []
        if (has(network, 'channels')) {
            Object.keys(network.channels).forEach((key) => {
                channels.push(cleanChannelName(key))
            })
        }
        return channels
      },
      makeTabs() {
        const tabElements = []
        this.networks.forEach((network) => {
            tabElements.push({
                id: `${network}-tab`,
                triggerEl: this.$refs[`${network}-trigger`][0],
                targetEl: this.$refs[`${network}-target`][0],
            })
        })

        return new Tabs(this.$refs.networkTabs, tabElements, {
                defaultTabId: tabElements[0].id,
                activeClasses:
                    'text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-400 border-blue-600 dark:border-blue-500',
                inactiveClasses:
                    'text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300',
                onShow: (tabs) => {
                    this.activeTab = tabs.getActiveTab()
                },
            },
            {
                id: 'network-tabs',
                override: true
            }
        );
      },
    },
  }
  </script>

<style> @import '@vueform/multiselect/themes/tailwind.css' </style>
