<template>
    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">

                <Head title="Chat" />
                <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" ref="networkTabs"
                        id="network-tabs" role="tablist">
                        <li v-for="network in networks" :ref="`${network}-tab`" role="presentation">
                            <button type="button" :ref="`${network}-trigger`" role="tab"
                                :aria-controls="`${network}-tab`" aria-selected="false"
                                class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300">
                                {{ network }}
                            </button>
                        </li>
                    </ul>
                </div>
                <div>
                    <div v-for="network in networks" :ref="`${network}-target`" role="tabpanel"
                        :aria-labelledby="`${network}-tab`" class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <chat-client :settings="settings" :downloads="downloads" :downloadLocks="downloadLocks"
                            :network="network" :client="clients[network]" :channels="listChannels(clients[network])"
                            :isActive="`${network}-tab` === activeTab.id" @call:checkDownloadQueue="checkDownloadQueue"
                            @call:removeCompleted="removeCompleted" @call:requestCancel="requestCancel"
                            @call:requestRemove="requestRemove" @call:saveDownloadDestination="saveDownloadDestination"
                            @call:showNetwork="showNetwork" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref } from 'vue'
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
import { usePageStateSync, STATE_VERSION } from '@/Composables/usePageStateSync'

const clientsInterval = 10000 // Check network connections every 10 seconds.
const downloadQueueInterval = 5000; // Check download queue every 5 seconds.
const locksInterval = 5000; // Check download locks every 5 seconds.

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
        const urlParams = new URLSearchParams(window.location.search)
        const queryTabId = urlParams.get('activeTabId')
        const defaultTabId = (queryTabId && this.networks.includes(queryTabId.replace(/-tab$/, '')))
            ? queryTabId
            : null

        const { state: chatState, saveState } = usePageStateSync('chat', {
            activeTabId: defaultTabId
        })

        return {
            clients: this.instances,
            downloadQueue: this.queue,
            downloads: {},
            tabs: null,
            activeTab: { id: null },
            downloadLocks: this.locks,
            clientsTimeoutId: null,
            downloadQueueTimeoutId: null,
            locksTimeoutId: null,
            chatState,
            saveChatState: saveState,
        }
    },
    mounted() {
        initFlowbite()
        this.checkDownloadQueue()
        this.checkClients()
        this.checkLocks()

        const urlParams = new URLSearchParams(window.location.search)
        const queryTabId = urlParams.get('activeTabId')

        if (queryTabId && this.networks.includes(queryTabId.replace(/-tab$/, ''))) {
            this.chatState.activeTabId = queryTabId
        }

        this.tabs = this.makeTabs()
    },
    beforeUnmount() {
        this.clearAllIntervals()
    },
    watch: {
        downloadQueue: {
            deep: true,
            handler: function () {
                this.downloads = makeDownloadIndexFromQueue(this.downloadQueue)
            },
        },
    },
    methods: {
        clearAllIntervals() {
            clearTimeout(this.clientsTimeoutId)
            clearTimeout(this.downloadQueueTimeoutId)
            clearTimeout(this.locksTimeoutId)
        },
        showNetwork(network) {
            if (this.tabs) {
                this.tabs.show(`${network}-tab`)
            }
        },
        async checkDownloadQueue() {
            clearTimeout(this.downloadQueueTimeoutId)

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            await this.fetchDownloadQueue()

            this.downloadQueueTimeoutId = setTimeout(this.checkDownloadQueue, downloadQueueInterval)
        },
        async checkClients() {
            clearTimeout(this.clientsTimeoutId)

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            await this.fetchClients()

            this.clientsTimeoutId = setTimeout(this.checkClients, clientsInterval)
        },
        async checkLocks() {
            clearTimeout(this.locksTimeoutId)

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            await this.fetchLocks()

            this.locksTimeoutId = setTimeout(this.checkLocks, locksInterval)
        },
        async fetchClients() {
            const { data, error } = await fetchNetworkClients()
            if (null === error) {
                this.clients = data
            } else {
                console.log(error)
            }
        },
        async fetchDownloadQueue() {
            const { data, error } = await fetchDownloadQueue()
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
                    clearTimeout(this.locksTimeoutId)
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

            const { error } = await saveDownloadDestination(body)

            if (null === error) {
                this.fetchDownloadQueue()
            }
        },
        async requestRemove(packetId) {
            const { data, error } = await requestRemove(packetId)

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

            const tabsInstance = new Tabs(this.$refs.networkTabs, tabElements, {
                defaultTabId: this.chatState.activeTabId || tabElements[0].id,
                activeClasses: '...',
                inactiveClasses: '...',
                onShow: (tabs) => {
                    this.activeTab = tabs.getActiveTab()
                    this.chatState.activeTabId = this.activeTab.id
                    this.saveChatState()

                    const url = new URL(window.location.href)
                    url.searchParams.set('activeTabId', this.activeTab.id)
                    window.history.replaceState({}, '', url)
                },
            }, {
                id: 'network-tabs',
                override: true,
            })

            // Ensure correct tab is shown manually to avoid race condition
            // const initialTabId = this.chatState.activeTabId || tabElements[0].id
            // tabsInstance.show(initialTabId)

            return tabsInstance
        },
    },
}
</script>

<style>
@import '@vueform/multiselect/themes/tailwind.css'
</style>
