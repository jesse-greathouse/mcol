<template>
    <!-- Start Topic Pane -->
    <div class="relative flex flex-row grow-0">
        <div class="flex p-4">
            <button class="flex items-center justify-center mr-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5m3 12h18"></path>
                </svg>
            </button>
            <div class="w-12 h-12 shrink-0">
                <img class="w-full h-full rounded-full overflow-hidden object-cover" width="64" height="64"
                    src="https://plus.unsplash.com/premium_photo-1663076389306-44e78d5f2b82?ixlib=rb-4.0.3.&ix"
                    alt="User" />
            </div>
            <div class="flex flex-col ml-4">
                <span class="font-bold text-xl">{{ channel.topic }}</span>
                <span class="text-xs text-gray-400"> {{ channelStatus }}</span>
            </div>
        </div>
    </div>
    <!-- End Topic Pane-->

    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Chat Pane -->
        <div ref="chatPane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3"
            :style="{ maxHeight: chatPaneHeight }">
            <div v-if="isLoading" class="p-4 text-gray-400 text-sm">Loading chat...</div>
            <div ref="bufferContainer">
                <message-line v-for="line in lines" :key="line.id" :settings="settings" :downloads="downloads"
                    :downloadLocks="downloadLocks" :showDate="showDate" :line="line" :channel="channel"
                    @call:xdccSend="xdccSend" @call:removeCompleted="removeCompleted"
                    @call:requestCancel="requestCancel" @call:requestRemove="requestRemove"
                    @call:saveDownloadDestination="saveDownloadDestination" />
            </div>
        </div>
        <!-- End Chat Pane -->

        <!-- Start User List-->
        <div class="flex flex-col overflow-y-auto overflow-x-hidden w-96 px-3" :style="{ maxHeight: chatPaneHeight }">
            <ul class="flex flex-col items-center justify-start gap-1 w-full">
                <li v-for="user in userList" :key="user" class="w-full block">
                    <button type="button" aria-selected="false"
                        class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300">
                        {{ user }}
                    </button>
                </li>
            </ul>
        </div>
        <!-- End User List-->
    </div>

    <!-- Start Chat Input -->
    <chat-input ref="chatInput" :network="network" :channels="[channel.name]" :users="userList"
        :defaultTarget="channel.name" :defaultCommand="COMMAND.PRIVMSG" @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import { nextTick } from 'vue'
import { has, throttle } from '@/funcs'
import { streamMessage, streamEvent } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { parseChatLog } from '@/chat'
import { cleanChannelName, makeChatLogDate } from '@/format'
import { COMMAND } from '@/chat'
import MessageLine from '@/Components/ChatMessageLine.vue'
import ChatInput from '@/Components/ChatInput.vue'
import { usePageStateSync } from '@/Composables/usePageStateSync'

const maxMessageLineBuffer = 1000
const messageInterval = 1000
const eventInterval = 1500
const chatPaneScale = 0.62

export default {
    components: {
        MessageLine,
        ChatInput,
    },
    props: {
        settings: Object,
        downloads: Object,
        downloadLocks: Array,
        user: String,
        network: String,
        notice: Array,
        channel: Object,
        connection: Object,
        isActive: Boolean,
    },
    data() {
        const cleaned = cleanChannelName(this.channel.name)

        const offsetKey = `chat:offset:${this.network}:${cleaned}`
        const {
            state: offsetState,
            saveState: saveOffsetState
        } = usePageStateSync(offsetKey, {
            messageOffset: 0,
            eventOffset: 0,
        })

        return {
            offsetState,
            saveOffsetState,
            messageOffset: offsetState.messageOffset ?? 0,
            eventOffset: offsetState.eventOffset ?? 0,
            COMMAND: COMMAND,
            cleanChannelName: cleaned,
            chatPaneHeight: this.scaleToViewportHeight(chatPaneScale),
            lines: [],
            pendingStorageSave: false,
            isLoading: true,
            showDate: true,
            userList: [],
            shouldScrollToBottom: true,
            noticeIndex: 0,
            messageIntervalId: null,
            eventIntervalId: null,
            saveRequestId: null, // for canceling the idle callback if needed
        }
    },
    watch: {
        isActive: {
            handler: function () {
                if (this.isActive) {
                    this.scrollToBottom()
                }
            },
        },
        channel: {
            deep: true,
            handler: throttle(function () {
                this.userList = this.makeUserList()
            }, 150),
        },
        notice: {
            deep: true,
            handler: function () {
                if (0 < this.noticeIndex) {
                    const diff = this.notice.length - this.noticeIndex
                    if (0 < diff) {
                        const lines = this.notice.slice(this.noticeIndex)
                        const ts = Date.now()
                        const objects = lines.map((str, idx) => ({
                            id: `${this.network}-${this.cleanChannelName}-notice-${ts}-${idx}`,
                            type: 'notice',
                            line: str,
                        }))
                        this.addLines(objects)
                    }
                }
                this.noticeIndex = this.notice.length
            },
        },
    },
    mounted() {
        this.getLinesFromStorage(30)
        window.addEventListener('resize', this.handleResize)
        this.$refs.chatPane.addEventListener('scroll', this.handleScroll)

        this.userList = this.makeUserList()
        this.scrollToBottom()

        this.streamMessages() // initial fetch
        this.streamEvents()   // initial fetch

        this.messageIntervalId = setInterval(() => {
            if (this.onChatPage()) {
                this.streamMessages()
            }
        }, messageInterval)

        this.eventIntervalId = setInterval(() => {
            if (this.onChatPage()) {
                this.streamEvents()
            }
        }, eventInterval)
    },
    beforeUnmount() {
        this.clearAllIntervals()
    },
    updated() {
        if (this.shouldScrollToBottom) {
            nextTick(() => {
                this.scrollToBottom()
            })
        }

        this.pruneLines()
    },
    beforeUnmount() {
        this.clearAllIntervals();
        window.removeEventListener('resize', this.handleResize);
        this.$refs.chatPane?.removeEventListener('scroll', this.handleScroll);
    },
    computed: {
        channelStatus() {
            let message = 'disconnected'

            if (this.connection.is_connected) {
                const server = this.connection.server
                const numUsers = this.channel.users.length
                const numOp = this.channel.op.length
                const numVoice = this.channel.voice.length
                message = `connected to: ${server} -- ${numUsers} users, ${numOp} op, ${numVoice} voice`
            }

            return message
        },
    },
    methods: {
        clearAllIntervals() {
            clearInterval(this.messageIntervalId)
            clearInterval(this.eventIntervalId)

            if (this.saveRequestId) {
                if ('cancelIdleCallback' in window) {
                    cancelIdleCallback(this.saveRequestId)
                } else {
                    clearTimeout(this.saveRequestId)
                }
            }
        },
        addLines(newLines) {
            if (!newLines?.length) return;

            const wasAtBottom = this.shouldScrollToBottom
            this.lines.push(...newLines)

            if (wasAtBottom) {
                nextTick(() => this.scrollToBottom())
            }

            // Schedule a save if not already scheduled
            if (!this.pendingStorageSave) {
                this.pendingStorageSave = true;

                const saveFn = () => {
                    this.saveLinesToStorage(30);
                    this.pendingStorageSave = false;
                    this.saveRequestId = null;
                };

                if ('requestIdleCallback' in window) {
                    this.saveRequestId = requestIdleCallback(saveFn, { timeout: 2000 });
                } else {
                    // Fallback: throttle via setTimeout if idleCallback not supported
                    this.saveRequestId = setTimeout(saveFn, 1000);
                }
            }
        },
        getBufferHtml() {
            return this.$refs.bufferContainer?.innerHTML || ''
        },
        getLinesFromStorage(max = 30) {
            // Restore lines from storage
            const stored = localStorage.getItem(`chat:buffer:${this.network}:${this.cleanChannelName}`)
            if (stored) {
                try {
                    const parsed = JSON.parse(stored)
                    if (Array.isArray(parsed)) {
                        this.lines = parsed.slice(0, max) // default 30
                        this.isLoading = false // since lines are already showing
                    }
                } catch (err) {
                    console.warn('Failed to parse chat buffer JSON', err)
                }
            }
        },
        saveLinesToStorage(max = 30) {
            const recentLines = this.lines.slice(-max);
            try {
                localStorage.setItem(`chat:buffer:${this.network}:${this.cleanChannelName}`, JSON.stringify(recentLines));
            } catch (e) {
                console.warn('Failed to save chat buffer:', e);
            }
        },
        pruneLines() {
            const linesTotal = this.lines.length
            if (linesTotal > maxMessageLineBuffer) {
                const overBuffer = linesTotal - maxMessageLineBuffer
                if (overBuffer > 0) {
                    this.lines = this.lines.slice(overBuffer)
                }
            }
        },
        isScrolledToBottom() {
            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            if (!has(this.$refs, 'chatPane') || !this.$refs.chatPane) {
                return false
            }

            const chatPane = this.$refs.chatPane
            const scrollTop = chatPane.scrollTop
            const clientHeight = chatPane.clientHeight
            const scrollHeight = chatPane.scrollHeight

            const horizontalScrollbarWidth = chatPane.offsetWidth - chatPane.clientWidth

            return scrollTop + clientHeight >= scrollHeight - horizontalScrollbarWidth
        },
        handleScroll() {
            this.shouldScrollToBottom = this.isScrolledToBottom()
        },
        scrollToBottom() {
            const chatPane = this.$refs.chatPane

            if (chatPane) {
                chatPane.scrollTop = chatPane.scrollHeight

                setTimeout(() => {
                    // If we're not still on the chat page, then bail...
                    if (!this.$page.url.startsWith('/chat')) return

                    const refreshPane = this.$refs.chatPane
                    if (refreshPane) {
                        refreshPane.scrollTop = refreshPane.scrollHeight
                    }
                }, 1000)
            }
        },
        handleOperation(operation, command, target) {
            if (command === COMMAND.PRIVMSG && target === this.channel.name) {
                const [, , ...parts] = operation.command.split(' ')
                const msg = parts.join(' ')
                const date = makeChatLogDate()
                const ts = Date.now()

                const line = {
                    id: `${this.network}-${this.user}-usermessage-${ts}`,
                    type: 'usermessage',
                    line: `[${date}] ${this.user}: ${msg}`
                }

                this.addLines([line])
            }

            this.$emit('call:handleOperation', operation, command, target)
        },
        async streamMessages() {
            await streamMessage(this.network, this.cleanChannelName, this.messageOffset, async (chunk) => {
                const { lines, meta, parseError } = await parseChatLog(chunk)
                if (parseError !== null) return

                const objects = lines.map((str, idx) => ({
                    id: `${this.network}-${this.cleanChannelName}-message-${this.messageOffset}-${idx}`,
                    type: 'message',
                    line: str,
                }))

                this.addLines(objects)

                if (this.isLoading) {
                    this.isLoading = false
                }

                if (has(meta, 'offset')) {
                    this.messageOffset = meta.offset
                    this.offsetState.messageOffset = meta.offset
                    this.saveOffsetState()
                }
            })
        },
        async streamEvents() {
            await streamEvent(this.network, this.cleanChannelName, this.eventOffset, async (chunk) => {
                const { lines, meta, parseError } = await parseChatLog(chunk)
                if (parseError !== null) return
                const ts = Date.now()
                const objects = lines.map((str, idx) => ({
                    id: `${this.network}-${this.cleanChannelName}-event-${ts}-${idx}`,
                    type: 'event',
                    line: str,
                }))

                if (this.eventOffset > 0) {
                    this.addLines(objects)
                }

                if (has(meta, 'offset')) {
                    this.eventOffset = meta.offset
                    this.offsetState.eventOffset = meta.offset
                    this.saveOffsetState()
                }
            })
        },
        scaleToViewportHeight,
        handleResize() {
            this.chatPaneHeight = this.scaleToViewportHeight(chatPaneScale)
        },
        onChatPage() {
            return this.$page.url.startsWith('/chat')
        },
        makeUserList() {
            const list = []
            const user = []
            const op = []
            const voice = []

            this.channel.users.forEach((name) => {
                if (this.user === name) return

                if (this.channel.op.includes(name)) {
                    op.push(`@${name}`)
                    return
                }

                if (this.channel.voice.includes(name)) {
                    voice.push(`+${name}`)
                    return
                }

                user.push(name)
            })

            return list.concat(op, voice, user)
        },
        xdccSend(packet, nick) {
            this.$emit('call:xdccSend', packet, nick)
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
    },
    emits: [
        'call:handleOperation',
        'call:xdccSend',
        'call:requestCancel',
        'call:requestRemove',
        'call:removeCompleted',
        'call:saveDownloadDestination'
    ],
}
</script>
