<template>
    <div class="flex flex-row" >
        <!-- Start Navigation Area -->
        <nav class="relative flex flex-col items-center justify-between p-0 bg-nav order-first w-48 mr-2">
            <div class="flex w-full flex-col border-white border-opacity-10">
                <ul class="flex flex-col items-center justify-start gap-1 w-full" ref="channelTabs" id="channel-tabs" role="tablist">
                    <li class="w-full block" role="presentation" :ref="`${network}-tab`">
                        <button type="button" ref="consoleTrigger" role="tab" :aria-controls="`${network}-tab`" aria-selected="false" class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" >
                            {{ network }}
                        </button>
                    </li>
                    <li v-for="channel in channels" :ref="`${channel}-tab`" role="presentation" class="w-full block">
                        <button type="button" :ref="`${channel}-trigger`" role="tab" :aria-controls="`${channel}-tab`" aria-selected="false" class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" >
                            #{{ channel }}
                        </button>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- End Navigation Area -->

        <!-- Start Chat Area -->
        <div ref="consoleTarget" role="tabpanel" aria-labelledby="console-tab" class="flex flex-col w-full h-full inset-0 border-x border-gray-100">
            <chat-console
                :user="client.user"
                :network="network"
                :notice="notice"
                :isActive="'console-tab' === activeTab.id" />
        </div>

        <div v-for="channel in channels" :key="`${channel}`" :ref="`${channel}-target`" role="tabpanel" :aria-labelledby="`${channel}-tab`" class="flex flex-col w-full h-full max-h-full inset-0 border-x border-gray-100 overflow-x-hidden">
            <chat-channel
                :user="client.user"
                :network="network"
                :notice="notice"
                :connection="client.connection"
                :channel="client.channels[`#${channel}`]"
                :isActive="`${channel}-tab` === activeTab.id" />
        </div>
        <!-- End Chat Area -->

    </div>
</template>

<script>
import _ from 'lodash'
import { Tabs } from 'flowbite'
import { streamNotice } from '@/Clients/stream'
import { cleanChannelName, parseChatLog } from '@/format'
import ChatChannel from '@/Components/ChatChannel.vue'
import ChatConsole from '@/Components/ChatConsole.vue'

const maxNoticeBuffer = 1000 // Maximum 1000 lines so we don't crash the browser.
const noticeInterval = 1000 // Check chat messages every 1 seconds.
let noticeTimeoutId
const clearNoticeInterval = function () {
    clearTimeout(noticeTimeoutId)
}

const clearAllIntervals = function() {
    clearNoticeInterval()
}

export default {
  components: {
    ChatChannel,
    ChatConsole,
  },
  props: {
    network: String,
    client: Object,
    channels: Array,
    isActive: Boolean,
  },
  data() {
    return {
        tabs: null,
        notice: [],
        noticeOffset: 0,
        activeTab: { id: null },
    }
  },
  watch: {
  },
  mounted() {
    this.tabs = this.makeTabs()
    this.streamNotice()
  },
  methods: {
    addNotice(notice) {
        this.notice = [...this.notice, ...notice]
    },
    pruneNotice() {
        const noticeTotal = this.notice.length
        if (noticeTotal > maxNoticeBuffer) {
            const overBuffer = maxNoticeBuffer - noticeTotal
            this.notice = this.notice.slice(overBuffer)
        }
    },
    reseNoticetInterval() {
        noticeTimeoutId = setTimeout(this.streamNotice, noticeInterval);
    },
    async streamNotice() {
        await streamNotice(this.network, this.noticeOffset, async (chunk) => {
            const {lines, meta, parseError} = await parseChatLog(chunk)
            if (null !== parseError) return

            this.addNotice(lines)

            if (_.has(meta, 'offset')) {
                this.noticeOffset = meta.offset
            }

            this.reseNoticetInterval()
        })
    },
    makeTabs() {
        const tabElements = [{
            id: 'console-tab',
            triggerEl: this.$refs.consoleTrigger,
            targetEl: this.$refs.consoleTarget,
        }]

        this.channels.forEach((channel) => {
            tabElements.push({
                id: `${channel}-tab`,
                triggerEl: this.$refs[`${channel}-trigger`][0],
                targetEl: this.$refs[`${channel}-target`][0],
            })
        });

        return new Tabs(this.$refs.channelTabs, tabElements, {
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
                id: 'channel-tabs',
                override: true
            }
        );
      },
  },
  emits: [],
}
</script>
