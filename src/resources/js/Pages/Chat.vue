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
                        :network="network"
                        :client="clients[network]"
                        :channels="listChannels(clients[network])"
                        :isActive="`${network}-tab` === activeTab.id" />
                </div>
            </div>
      </div>
    </div>
  </div>
  </template>

  <script>
  import axios from 'axios';
  import _ from 'lodash'
  import { Head, Link } from '@inertiajs/vue3'
  import { initFlowbite, Tabs } from 'flowbite'
  import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core'
  import Multiselect from '@vueform/multiselect'
  import pickBy from 'lodash/pickBy'
  import throttle from 'lodash/throttle'
  import mapValues from 'lodash/mapValues'

  // local imports
  import { fetchNetworkClients } from '@/Clients/network'
  import { formatDate, cleanChannelName } from '@/format'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import ChatClient from '@/Components/ChatClient.vue'

  const clientsInterval = 10000 // Check network connections every 10 seconds.
  let clientsTimeoutId
  const clearClientsInterval = function () {
    clearClientsInterval(clientsTimeoutId)
  }

  const clearAllIntervals = function() {
    clearClientsInterval()
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
      settings: Object,
      networks: Array,
      instances: Object,
    },
    data() {
        return {
            clients: this.instances,
            tabs: null,
            activeTab: { id: null },
        }
    },
    mounted() {
        initFlowbite()
        this.resetIntervals()
        this.tabs = this.makeTabs()
    },
    methods: {
      async refreshClients() {
        const {data, error} = await fetchNetworkClients()
        if (null === error) {
          this.clients = data
        } else {
          console.log(error)
        }
        this.resetIntervals()
      },
      listChannels(network) {
        const channels = []
        Object.keys(network.channels).forEach((key) => {
            channels.push(cleanChannelName(key))
        })
        return channels
      },
      resetIntervals() {
        clearAllIntervals()
        clientsTimeoutId = setTimeout(this.refreshClients, clientsInterval);
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
