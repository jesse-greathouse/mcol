<template>
  <div class="py-6">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">
        <Head title="Chat" />
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
          <ul
            class="flex flex-wrap -mb-px text-sm font-medium text-center"
            ref="networkTabs"
            id="network-tabs"
            role="tablist"
          >
            <li
              v-for="network in networks"
              :key="`chat-network-${network}-tab`"
              :ref="`${network}-tab`"
              role="presentation"
            >
              <button
                type="button"
                :ref="`${network}-trigger`"
                role="tab"
                :aria-controls="`${network}-tab`"
                aria-selected="false"
                class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
              >
                {{ network }}
              </button>
            </li>
          </ul>
        </div>
        <div>
          <div
            v-for="network in networks"
            :key="`chat-network-${network}-panel`"
            :ref="`${network}-target`"
            role="tabpanel"
            :aria-labelledby="`${network}-tab`"
            class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800"
          >
            <chat-client
              v-bind="getClientProps(network)"
              @update:channelTab="handleChannelTabUpdate"
              @call:checkDownloadQueue="checkDownloadQueue"
              @call:removeCompleted="removeCompleted"
              @call:requestCancel="requestCancel"
              @call:requestRemove="requestRemove"
              @call:saveDownloadDestination="saveDownloadDestination"
              @call:showNetwork="showNetwork"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { initFlowbite, Tabs } from 'flowbite';
import Multiselect from '@vueform/multiselect';
import { saveDownloadDestination } from '@/Clients/download-destination';
import { has } from '@/funcs';

// local imports
import { fetchLocks } from '@/Clients/browse';
import { fetchNetworkClients } from '@/Clients/network';
import { fetchDownloadQueue } from '@/Clients/download-queue';
import { makeDownloadIndexFromQueue } from '@/download-queue';
import { removeCompleted, requestRemove, requestCancel } from '@/Clients/rpc';
import { cleanChannelName } from '@/format';
import AppLayout from '@/Layouts/AppLayout.vue';
import ChatClient from '@/Components/ChatClient.vue';

// composables
import { useFlowbiteTabs } from '@/Composables/useFlowbiteTabs';
import { usePageStateSync } from '@/Composables/usePageStateSync';

const clientsInterval = 10000; // Check network connections every 10 seconds.
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
    const urlParams = new URLSearchParams(window.location.search);
    const queryTabId = urlParams.get('activeTabId');
    const defaultTabId =
      queryTabId && this.networks.includes(queryTabId.replace(/-tab$/, '')) ? queryTabId : null;

    const { state: chatState, saveState } = usePageStateSync('chat', {
      activeTabId: defaultTabId,
      channelTabs: {},
    });

    return {
      clients: this.instances,
      downloadQueue: this.queue,
      downloads: {},
      tabs: null,
      activeTab: { id: null },
      downloadLocks: this.locks,
      clientsIntervalId: null,
      downloadQueueIntervalId: null,
      locksIntervalId: null,
      chatState,
      saveState,
    };
  },
  mounted() {
    initFlowbite();
    this.startPollingLoops();

    const urlParams = new URLSearchParams(window.location.search);
    const queryTabId = urlParams.get('activeTabId');
    if (queryTabId && this.networks.includes(queryTabId.replace(/-tab$/, ''))) {
      this.chatState.activeTabId = queryTabId;
    }

    nextTick(() => {
      this.tabs = this.makeTabs();
    });
  },
  beforeUnmount() {
    this.clearAllIntervals();
  },
  watch: {
    downloadQueue: {
      deep: true,
      handler: function () {
        this.downloads = makeDownloadIndexFromQueue(this.downloadQueue);
      },
    },
  },
  methods: {
    startPollingLoops() {
      if (this.clientsIntervalId === null) {
        this.fetchClients();
        this.clientsIntervalId = setInterval(() => {
          if (this.onChatPage()) {
            this.fetchClients();
          }
        }, clientsInterval);
      }

      if (this.downloadQueueIntervalId === null) {
        this.fetchDownloadQueue();
        this.downloadQueueIntervalId = setInterval(() => {
          if (this.onChatPage()) {
            this.fetchDownloadQueue();
          }
        }, downloadQueueInterval);
      }

      if (this.locksIntervalId === null) {
        this.fetchLocks();
        this.locksIntervalId = setInterval(() => {
          if (this.onChatPage()) {
            this.fetchLocks();
          }
        }, locksInterval);
      }
    },
    clearAllIntervals() {
      clearInterval(this.clientsIntervalId);
      clearInterval(this.downloadQueueIntervalId);
      clearInterval(this.locksIntervalId);

      this.clientsIntervalId = null;
      this.downloadQueueIntervalId = null;
      this.locksIntervalId = null;
    },
    showNetwork(network) {
      if (this.tabs) {
        this.tabs.show(`${network}-tab`);
      }
    },
    getClientProps(network) {
      return {
        settings: this.settings,
        downloads: this.downloads,
        downloadLocks: this.downloadLocks,
        network,
        client: this.clients[network],
        channels: this.listChannels(this.clients[network]),
        chatState: this.chatState,
        saveState: this.saveState,
        isActive: `${network}-tab` === this.activeTab.id,
      };
    },
    async fetchClients() {
      const { data, error } = await fetchNetworkClients();
      if (null === error) {
        this.clients = data;
      } else {
        console.log(error);
      }
    },
    async fetchDownloadQueue() {
      const { data, error } = await fetchDownloadQueue();
      if (null === error) {
        this.downloadQueue = data;
      } else {
        console.log(error);
      }
    },
    async fetchLocks(packetList) {
      const { data, error } = await fetchLocks(packetList);

      if (null === error) {
        const { locks } = data;
        this.downloadLocks = locks;

        if (locks.length <= 0) {
          clearTimeout(this.locksTimeoutId);
        }
      }
    },
    async saveDownloadDestination(download, uri) {
      const body = {
        destination_dir: uri,
        download: download.id,
      };

      // Use put instead of post if dd already exists.
      if (null !== download.destination) {
        body.id = download.destination.id;
      }

      const { error } = await saveDownloadDestination(body);

      if (null === error) {
        this.fetchDownloadQueue();
      }
    },
    async requestRemove(packetId) {
      const { data, error } = await requestRemove(packetId);

      if (null === error) {
        const fileName = data.result.packet.file_name;
        const locksIndex = this.locks.indexOf(fileName);
        if (0 <= locksIndex) {
          delete this.locks[locksIndex];
        }

        if (has(this.queued, fileName)) {
          delete this.queued[fileName];
        }

        if (has(this.downloadQueue.queued, fileName)) {
          delete this.downloadQueue.queued[fileName];
        }
      }
    },
    async requestCancel(download) {
      const { error } = await requestCancel(download);

      if (null === error) {
        this.fetchLocks();
        this.fetchDownloadQueue();
      }
    },
    async removeCompleted(download) {
      const { error } = await removeCompleted(download);

      if (null === error) {
        this.fetchLocks();
        this.fetchDownloadQueue();
      }
    },
    listChannels(network) {
      const channels = [];
      if (has(network, 'channels')) {
        Object.keys(network.channels).forEach((key) => {
          channels.push(cleanChannelName(key));
        });
      }
      return channels;
    },
    makeTabs() {
      const tabElements = this.networks
        .map((network) => {
          const trigger = this.$refs[`${network}-trigger`]?.[0];
          const target = this.$refs[`${network}-target`]?.[0];
          return trigger && target
            ? { id: `${network}-tab`, triggerEl: trigger, targetEl: target }
            : null;
        })
        .filter(Boolean);

      return useFlowbiteTabs({
        tabContainerRef: this.$refs.networkTabs,
        tabElements,
        defaultTabId: this.chatState.activeTabId,
        onShow: (tabs) => {
          this.activeTab = tabs.getActiveTab();
          this.chatState.activeTabId = this.activeTab.id;
          this.saveState();
          const url = new URL(window.location.href);
          url.searchParams.set('activeTabId', this.activeTab.id);
          window.history.replaceState({}, '', url);
        },
      });
    },
    onChatPage() {
      return this.$page.url.startsWith('/chat');
    },
    handleChannelTabUpdate({ network, tabId }) {
      if (!this.chatState.channelTabs) {
        this.chatState.channelTabs = {};
      }

      this.chatState.channelTabs[network] = tabId;
      this.saveState();
    },
  },
};
</script>

<style>
@import '@vueform/multiselect/themes/tailwind.css';
</style>
