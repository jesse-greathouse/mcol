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
        <div ref="chatPane" class="flex flex-col content-end overflow-auto w-full mr-3" :style="{ maxHeight: chatPaneHeight }" >
            <message-line v-for="(line, i) in lines" :key="`line-${i}`" :showDate="showDate" :line="line" />
        </div>
        <!-- End Chat Pane -->

        <!-- Start User List-->
        <div class="flex flex-col overflow-y-auto w-72 px-3" :style="{ maxHeight: chatPaneHeight }" >
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
    <div class="flex flex-row items-center p-4 grow-0">
        <button class="shrink-0 text-gray-400" type="button" aria-label="Add media to message">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>
        <div class="relative flex w-full max-h-24 overflow-hidden">
            <div
                class="w-full outline-0"
                contenteditable="true"
                tabindex="0"
                dir="ltr"
                spellcheck="false"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
            >
                Type your message here...
            </div>
        </div>
        <button
            class="flex items-center justify-center shrink-0 w-12 h-12 bg-nav rounded-full overflow-hidden"
            type="button"
            aria-label="Submit" >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
            </svg>
        </button>
    </div>
    <!-- End Chat Input -->
</template>

<script>
import _ from 'lodash'
import throttle from 'lodash/throttle'
import { fetchMessage, streamMessage } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { cleanChannelName, parseChatLog } from '@/format'
import MessageLine from '@/Components/ChatMessageLine.vue'

const maxMessageLineBuffer = 1000 // Maximum 1000 lines so we don't crash the browser.
const chatPaneScale = .66
const messageInterval = 1000 // Check chat messages every 1 seconds.
let messageTimeoutId
const clearMessageInterval = function () {
    clearMessageInterval(messageTimeoutId)
}

const clearAllIntervals = function() {
    clearMessageInterval()
}

export default {
  components: {
    MessageLine,
  },
  props: {
    user: String,
    network: String,
    channel: Object,
    connection: Object,
    isActive: Boolean,
  },
  data() {
    return {
        chatPaneHeight: this.scaleToViewportHeight(chatPaneScale),
        lines: [],
        messageOffset: 0,
        showDate: true,
        userList: [],
    }
  },
  watch: {
    channel: {
        deep: true,
        handler: throttle(function () {
            this.userList = this.makeUserList()
        }, 150),
    },
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
    this.userList = this.makeUserList()
    this.streamMessages()
  },
  updated() {
    this.scrollToBottom()
    this.pruneLines()
  },
  beforeUnmount() {
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
        this.lines = [...this.lines, ...lines]
    },
    pruneLines() {
        const linesTotal = this.lines.length
        if (linesTotal > maxMessageLineBuffer) {
            const overBuffer = maxMessageLineBuffer - linesTotal
            this.lines = this.lines.slice(overBuffer)
        }
    },
    resetIntervals() {
        clearAllIntervals()
        messageTimeoutId = setTimeout(this.streamMessages, messageInterval);
    },
    isScrolledToBottom() {
        return true
    },
    scrollToBottom() {
        const chatPane = this.$refs.chatPane
        const lastChildElement = chatPane.lastElementChild
        lastChildElement?.scrollIntoView({
            behavior: 'smooth',
        })
    },
    async refreshMessages() {
        const channelName = cleanChannelName(this.channel.name)
        const {data, error} = await fetchMessage(this.network, channelName, this.messageOffset)

        if (null !== error) return

        const {lines, meta, parseError} = await parseChatLog(data)

        if (null !== parseError) return

        this.addLines(lines)

        if (_.has(meta, 'offset')) {
            this.messageOffset = meta.offset
        }

        this.resetIntervals()
    },
    async streamMessages() {
        const channelName = cleanChannelName(this.channel.name)

        await streamMessage(this.network, channelName, this.messageOffset, async (chunk) => {
            const {lines, meta, parseError} = await parseChatLog(chunk)
            if (null !== parseError) return

            this.addLines(lines)

            if (_.has(meta, 'offset')) {
                this.messageOffset = meta.offset
            }
        })

        this.resetIntervals()
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
  emits: [],
}
</script>
