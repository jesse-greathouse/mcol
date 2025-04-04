<template>
    <div class="flex flex-row">
        <!-- Start Navigation Area -->
        <nav class="relative flex flex-col items-center justify-between p-0 bg-nav order-first w-48 mr-2">
            <div class="flex w-full flex-col border-white border-opacity-10">
                <ul class="flex flex-col items-center justify-start gap-1 w-full" ref="channelTabs" id="channel-tabs"
                    role="tablist">
                    <li class="w-full block" role="presentation" :ref="`${network}-tab`">
                        <button type="button" ref="consoleTrigger" role="tab" :aria-controls="`${network}-tab`"
                            aria-selected="false"
                            class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300">
                            {{ network }}
                        </button>
                    </li>
                    <li v-for="channel in channels" :ref="`${channel}-tab`" role="presentation" class="w-full block">
                        <button type="button" :ref="`${channel}-trigger`" role="tab" :aria-controls="`${channel}-tab`"
                            aria-selected="false"
                            class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300">
                            #{{ channel }}
                        </button>
                    </li>
                    <li v-for="nick in privmsgTabs" :ref="`${nick}-tab`" role="presentation" class="w-full block">
                        <button type="button" :ref="`${nick}-trigger`" role="tab" :aria-controls="`${nick}-tab`"
                            aria-selected="false"
                            class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300">
                            {{ nick }}
                        </button>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- End Navigation Area -->

        <!-- Start Chat Area -->
        <div ref="consoleTarget" role="tabpanel" aria-labelledby="console-tab"
            class="flex flex-col w-full h-full inset-0 border-x border-gray-100">
            <chat-console :settings="settings" :user="client.user" :network="network" :notice="notice"
                :isActive="'console-tab' === activeTab.id" />
        </div>

        <div v-for="channel in channels" :key="`${channel}`" :ref="`${channel}-target`" role="tabpanel"
            :aria-labelledby="`${channel}-tab`"
            class="flex flex-col w-full h-full max-h-full inset-0 border-x border-gray-100 overflow-x-hidden">
            <chat-channel :settings="settings" :downloads="downloads" :downloadLocks="downloadLocks" :user="client.user"
                :network="network" :notice="notice" :connection="client.connection"
                :channel="client.channels[`#${channel}`]" :isActive="`${channel}-tab` === activeTab.id"
                @call:xdccSend="xdccSend" @call:removeCompleted="removeCompleted" @call:requestCancel="requestCancel"
                @call:requestRemove="requestRemove" @call:saveDownloadDestination="saveDownloadDestination" />
        </div>

        <div v-for="nick in privmsgTabs" :key="`${nick}`" :ref="`${nick}-target`" role="tabpanel"
            :aria-labelledby="`${nick}-tab`"
            class="flex flex-col w-full h-full max-h-full inset-0 border-x border-gray-100 overflow-x-hidden"
            :class="classTabHidden(`${nick}-tab`)">
            <chat-privmsg :settings="settings" :downloads="downloads" :downloadLocks="downloadLocks" :user="client.user"
                :network="network" :nick="nick" :privmsgs="privmsg[nick]" :isActive="`${nick}-tab` === activeTab.id"
                @call:xdccSend="xdccSend" @call:removeCompleted="removeCompleted" @call:requestCancel="requestCancel"
                @call:requestRemove="requestRemove" @call:saveDownloadDestination="saveDownloadDestination" />
        </div>
        <!-- End Chat Area -->

    </div>
</template>

<script>
import { Tabs } from 'flowbite'
import { saveOperation } from '@/Clients/operation'
import { streamNotice, streamPrivmsg } from '@/Clients/stream'
import { COMMAND, parseChatLog, parseChatLine, parseChatMessage, makeIrcCommand } from '@/chat'
import { formatISODate } from '@/format'
import { has, throttle } from '@/funcs'
import ChatChannel from '@/Components/ChatChannel.vue'
import ChatConsole from '@/Components/ChatConsole.vue'
import ChatPrivmsg from '@/Components/ChatPrivmsg.vue'
import { usePageStateSync } from '@/Composables/usePageStateSync'

const noticeInterval = 1000 // Check chat messages every 1 seconds.
const privmsgInterval = 1000 // Check privmsg every 1 seconds.

export default {
    components: {
        ChatChannel,
        ChatConsole,
        ChatPrivmsg,
    },
    props: {
        settings: Object,
        downloads: Object,
        downloadLocks: Array,
        network: String,
        client: Object,
        channels: Array,
        isActive: Boolean,
    },
    data() {
        return {
            tabs: null,
            notice: [],
            privmsg: {},
            privmsgCount: {},
            privmsgIndex: {},
            privmsgTabs: [],
            noticeOffset: 0,
            privmsgOffset: 0,
            firstPrivmsgLoad: true,
            activeTab: { id: null },
            privmsgTimeoutId: null,
            noticeTimeoutId: null,
        }
    },
    watch: {
        privmsg: {
            deep: true,
            handler: throttle(function () {
                this.updatePrivmsgTabs()
            }, 150),
        },
    },
    mounted() {
        this.makeTabs()
        this.streamNotice()
        this.streamPrivmsg()
    },
    beforeUnmount() {
        this.clearAllIntervals()
    },
    methods: {
        clearAllIntervals() {
            clearTimeout(this.privmsgTimeoutId)
            clearTimeout(this.noticeTimeoutId)
        },
        addNotice(notice) {
            this.notice = [...this.notice, ...notice]
        },
        addPrivmsg(lines) {
            lines.forEach((line) => {
                const { date, message, error } = parseChatLine(line)

                if (null !== error) return

                this.divertPrivmsg(date, message)
            })
        },
        classTabHidden(id) {
            if (id !== this.activeTab.id) {
                return [
                    'overflow-x-hidden',
                    'hidden',
                ]
            } else {
                return []
            }
        },
        divertPrivmsg(date, message) {
            const timestamp = formatISODate(date, 'MM/dd/yyyy HH:mm:ss')
            const { nick, content, error } = parseChatMessage(message)

            if (null !== error) return

            if (!has(this.privmsgCount, nick)) {
                this.privmsgCount[nick] = 0
            }

            if (!has(this.privmsg, nick)) {
                this.privmsg[nick] = []
            }

            if (!has(this.privmsgIndex, nick)) {
                this.privmsgIndex[nick] = 0
            }

            this.privmsgCount[nick]++
            this.privmsg[nick].push({ timestamp, content })
        },
        updatePrivmsgTabs() {
            Object.keys(this.privmsg).forEach(nick => {
                const i = this.privmsgTabs.indexOf(nick)
                const numMsgs = this.privmsg[nick].length

                // Don't start pushing privmsg tabs until after its loaded the first time.
                // If privmsgs with the user is not in the list of tabs.
                // And if there is new private messages, add it to the list of tabs.
                if (!this.firstPrivmsgLoad && 0 > i && (numMsgs > this.privmsgIndex[nick])) {
                    this.privmsgTabs.push(nick)
                }

                this.privmsgIndex[nick] = numMsgs
            })

            this.firstPrivmsgLoad = false

            // In case scrolling isn't complete after 1 second.
            setTimeout(() => {
                // If we're not still on the chat page, then bail...
                if (!this.$page.url.startsWith('/chat')) return

                const activeTabId = this.activeTab.id
                this.makeTabs()
                this.tabs.show(activeTabId)
            }, 1000);
        },
        resetNoticetInterval() {
            clearTimeout(this.noticeTimeoutId)

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            this.noticeTimeoutId = setTimeout(this.streamNotice, noticeInterval);
        },
        resetPrivmsgInterval() {
            clearTimeout(this.privmsgTimeoutId)

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            this.privmsgTimeoutId = setTimeout(this.streamPrivmsg, privmsgInterval);
        },
        removeCompleted(download) {
            this.$emit('call:removeCompleted', download)
        },
        requestCancel(download) {
            this.$emit('call:requestCancel', download)
        },
        requestRemove(packetId) {
            this.$emit('call:requestRemove', packetId)
        },
        saveDownloadDestination(download, uri) {
            this.$emit('call:saveDownloadDestination', download, uri)
        },
        async streamNotice() {
            await streamNotice(this.network, this.noticeOffset, async (chunk) => {
                const { lines, meta, parseError } = await parseChatLog(chunk)
                if (null !== parseError) return

                this.addNotice(lines)

                if (has(meta, 'offset')) {
                    this.noticeOffset = meta.offset
                }

                this.resetNoticetInterval()
            })
        },
        async streamPrivmsg() {
            await streamPrivmsg(this.network, this.privmsgOffset, async (chunk) => {
                const { lines, meta, parseError } = await parseChatLog(chunk)
                if (null !== parseError) return

                this.addPrivmsg(lines)

                if (has(meta, 'offset')) {
                    this.privmsgOffset = meta.offset
                }

                this.resetPrivmsgInterval()
            })
        },
        async saveOperation(command) {
            const network = this.network
            const { error } = await saveOperation({ command, network })

            if (null === error) {
                return true
            }

            return false
        },
        async xdccSend(packet, nick) {
            const command = makeIrcCommand(`XDCC SEND ${packet.num}`, nick, COMMAND.PRIVMSG)
            if (await this.saveOperation(command)) {
                this.$emit('call:checkDownloadQueue')
            }
        },
        makeTabs() {
            if (null !== this.tabs) {
                this.tabs.destroy()
            }

            const { state: chatState, saveState } = usePageStateSync('chat', { channelTabs: {} })

            // Override from query string if available
            const urlParams = new URLSearchParams(window.location.search)
            const key = `channelTabs[${this.network}]`
            const queryTabId = urlParams.get(key)

            const defaultTabId = queryTabId || chatState.channelTabs?.[this.network] || 'console-tab'

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

            this.privmsgTabs.forEach((nick) => {
                tabElements.push({
                    id: `${nick}-tab`,
                    triggerEl: this.$refs[`${nick}-trigger`][0],
                    targetEl: this.$refs[`${nick}-target`][0],
                })
            });

            this.tabs = new Tabs(this.$refs.channelTabs, tabElements, {
                defaultTabId,
                activeClasses:
                    'text-blue-600 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-400 border-blue-600 dark:border-blue-500',
                inactiveClasses:
                    'text-gray-500 hover:text-gray-600 dark:text-gray-400 border-gray-100 hover:border-gray-300 dark:border-gray-700 dark:hover:text-gray-300',
                onShow: (tabs) => {
                    this.activeTab = tabs.getActiveTab()

                    if (!chatState.channelTabs) {
                        chatState.channelTabs = {}
                    }

                    chatState.channelTabs[this.network] = this.activeTab.id
                    saveState()

                    // Update only this network's channelTabs value in query string
                    const url = new URL(window.location.href)
                    const key = `channelTabs[${this.network}]`
                    url.searchParams.set(key, this.activeTab.id)
                    window.history.replaceState({}, '', url)
                },
            },
                {
                    id: 'channel-tabs',
                    override: true
                }
            )
        },
    },
    emits: [
        'call:checkDownloadQueue',
        'call:showNetwork',
        'call:requestCancel',
        'call:requestRemove',
        'call:removeCompleted',
        'call:saveDownloadDestination'
    ],
}
</script>
