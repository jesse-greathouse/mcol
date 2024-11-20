<template>
    <!-- Start Topic Pane -->
    <div class="relative flex flex-row grow-0" >
        <div class="flex p-4" >
            <button class="flex items-center justify-center mr-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5m3 12h18"></path>
                </svg>
            </button>
            <div class="w-12 h-12 shrink-0">
                <img class="w-full h-full rounded-full overflow-hidden object-cover"
                    width="64"
                    height="64"
                    src="https://plus.unsplash.com/premium_photo-1663076389306-44e78d5f2b82?ixlib=rb-4.0.3.&ix"
                    alt="User"
                />
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
        <div ref="chatPane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3" :style="{ maxHeight: chatPaneHeight }" >
            <message-line v-for="(line, i) in lines" :key="`line-${i}`" :showDate="showDate" :line="line" />
        </div>
        <!-- End Chat Pane -->

        <!-- Start User List-->
        <div class="flex flex-col overflow-y-auto overflow-x-hidden w-96 px-3" :style="{ maxHeight: chatPaneHeight }" >
            <ul class="flex flex-col items-center justify-start gap-1 w-full">
                <li v-for="user in userList" class="w-full block">
                    <button type="button" aria-selected="false" class="block px-3 w-full text-left rounded-md border border-gray-400 hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" >
                        {{ user }}
                    </button>
                </li>
            </ul>
        </div>
        <!-- End User List-->
    </div>

    <!-- Start Chat Input -->
    <chat-input
        :network="network"
        :target="channel.name"
        :default="COMMAND.PRIVMSG"
        @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import _ from 'lodash'
import throttle from 'lodash/throttle'
import { streamMessage, streamEvent } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { cleanChannelName, parseChatLog, makeChatLogDate } from '@/format'
import { COMMAND } from '@/chat'
import MessageLine from '@/Components/ChatMessageLine.vue'
import ChatInput from '@/Components/ChatInput.vue'

const maxMessageLineBuffer = 1000 // Maximum 1000 lines so we don't crash the browser.
const chatPaneScale = .62

const messageInterval = 1000 // Check chat messages every 1 seconds.
let messageTimeoutId
const clearMessageInterval = function () {
    clearTimeout(messageTimeoutId)
}

const eventInterval = 1500 // Check channel events every 1.5 seconds.
let eventTimeoutId
const clearEventInterval = function () {
    clearTimeout(eventTimeoutId)
}

const clearAllIntervals = function() {
    clearMessageInterval()
    clearEventInterval()
}

export default {
  components: {
    MessageLine,
    ChatInput,
  },
  props: {
    user: String,
    network: String,
    notice: Array,
    channel: Object,
    connection: Object,
    isActive: Boolean,
  },
  data() {
    return {
        COMMAND: COMMAND,
        cleanChannelName: cleanChannelName(this.channel.name),
        chatPaneHeight: this.scaleToViewportHeight(chatPaneScale),
        lines: [],
        messageOffset: 0,
        eventOffset: 0,
        showDate: true,
        userList: [],
        shouldScrollToBottom: true,
        noticeIndex: 0,
    }
  },
  watch: {
    isActive: {
        handler: function() {
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
        deep:true,
        handler: function() {
            // Skip all the notices that came before.
            if (0 < this.noticeIndex) {
                // Break off any new notices and add them to lines
                const diff = this.notice.length - this.noticeIndex
                if (0 < diff) {
                    const lines = this.notice.slice(this.noticeIndex)
                    const objects = lines.map(str => ({ type: 'notice', line: str }));
                    this.addLines(objects)
                }
            }
            this.noticeIndex = this.notice.length
        },
    },
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
    this.$refs.chatPane.addEventListener('scroll', this.handleScroll);
    this.userList = this.makeUserList()
    this.scrollToBottom()
    this.streamMessages()
    this.streamEvents()
  },
  updated() {
    if (this.shouldScrollToBottom) {
        this.scrollToBottom()
    }

    this.pruneLines()
  },
  beforeUnmount() {
    this.$refs.chatPane.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.handleResize);
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
    addLines(lines) {
        lines.forEach((line) => {
            this.lines.push(line)
        })
    },
    pruneLines() {
        const linesTotal = this.lines.length
        if (linesTotal > maxMessageLineBuffer) {
            const overBuffer = maxMessageLineBuffer - linesTotal
            this.lines = this.lines.slice(overBuffer)
        }
    },
    resetMessageInterval() {
        messageTimeoutId = setTimeout(this.streamMessages, messageInterval);
    },
    resetEventInterval() {
        eventTimeoutId = setTimeout(this.streamEvents, eventInterval);
    },
    isScrolledToBottom() {
        const chatPane = this.$refs.chatPane
        const scrollTop = chatPane.scrollTop
        const clientHeight = chatPane.clientHeight
        const scrollHeight = chatPane.scrollHeight

        // Adjust for horizontal scrollbar width
        const horizontalScrollbarWidth = chatPane.offsetWidth - chatPane.clientWidth

        return scrollTop + clientHeight >= scrollHeight - horizontalScrollbarWidth
    },
    handleScroll() {
        this.shouldScrollToBottom = this.isScrolledToBottom()
    },
    scrollToBottom() {
        const chatPane = this.$refs.chatPane
        chatPane.scrollTop = chatPane.scrollHeight;

        // Set it to scroll again to the bottom after 1 second.
        // In case scrolling isn't complete after 1 second.
        let timeoutId = setTimeout(() => {
            const refreshPane = this.$refs.chatPane
            refreshPane.scrollTop = refreshPane.scrollHeight;
        }, 1000);
    },
    handleOperation(operation, command, target) {
        // If its a PRIVMSG targeting this channel, add it to lines.
        // IRC protocol does not add user input to the output feeds.
        if (command === COMMAND.PRIVMSG && target === this.channel.name) {
            const [, , ...parts] = operation.command.split(' ')
            const msg = parts.join(' ')
            const date = makeChatLogDate()

            const line = {
                type: 'usermessage',
                line: `[${date}] ${this.user}: ${msg}`
            }

            this.addLines([line])
        }

        // Send the operation up to the client for further post-processing.
        this.$emit('call:handleOperation', operation, command, target)
    },
    async streamMessages() {
        await streamMessage(this.network, this.cleanChannelName, this.messageOffset, async (chunk) => {
            const {lines, meta, parseError} = await parseChatLog(chunk)
            if (null !== parseError) return

            const objects = lines.map(str => ({ type: 'message', line: str }));

            this.addLines(objects)

            if (_.has(meta, 'offset')) {
                this.messageOffset = meta.offset
            }

            this.resetMessageInterval()
        })


    },
    async streamEvents() {
        await streamEvent(this.network, this.cleanChannelName, this.eventOffset, async (chunk) => {
            const {lines, meta, parseError} = await parseChatLog(chunk)
            if (null !== parseError) return

            const objects = lines.map(str => ({ type: 'event', line: str }));

            // Skip adding old events.
            if (0 < this.eventOffset) {
                this.addLines(objects)
            }

            if (_.has(meta, 'offset')) {
                this.eventOffset = meta.offset
            }

            this.resetEventInterval()
        })
    },
    scaleToViewportHeight,
    handleResize() {
        this.chatPaneHeight = this.scaleToViewportHeight(chatPaneScale)
    },
    makeUserList() {
        const list = []
        const user = []
        const op = []
        const voice = []

        this.channel.users.forEach((name) => {
            if (this.user === name) return

            const opIndex = this.channel.op.indexOf(name)
            if (0 <= opIndex) {
                op.push(`@${name}`)
                return
            }

            const voiceIndex = this.channel.voice.indexOf(name)
            if (0 <= voiceIndex) {
                voice.push(`+${name}`)
                return
            }

            user.push(name)
        })

        return list.concat(op, voice, user)
    },
  },
  emits: [
    'call:handleOperation'
  ],
}
</script>
