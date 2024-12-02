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
                <span class="font-bold text-xl">{{ nick }}</span>
            </div>
        </div>
    </div>
    <!-- End Topic Pane-->

    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Chat Pane -->
        <div ref="privmsgPane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3" :style="{ maxHeight: privmsgPaneHeight }" >
            <privmsg-line v-for="(line, i) in lines"
                :settings="settings"
                :downloads="downloads"
                :downloadLocks="downloadLocks"
                :key="`line-${i}`"
                :showDate="showDate"
                :type="line.type"
                :nick="line.nick"
                :timestamp="line.timestamp"
                :content="line.content"
                @call:removeCompleted="removeCompleted"
                @call:requestCancel="requestCancel"
                @call:requestRemove="requestRemove"
                @call:saveDownloadDestination="saveDownloadDestination" />
        </div>
        <!-- End Chat Pane -->
    </div>

    <!-- Start Chat Input -->
    <chat-input
        :network="network"
        :target="nick"
        :default="COMMAND.PRIVMSG"
        @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import { scaleToViewportHeight } from '@/style'
import { makeChatLogDate, formatISODate } from '@/format'
import { COMMAND } from '@/chat'
import PrivmsgLine from '@/Components/ChatPrivmsgLine.vue'
import ChatInput from '@/Components/ChatInput.vue'

const privmsgPaneScale = .62

export default {
  components: {
    PrivmsgLine,
    ChatInput,
  },
  props: {
    settings: Object,
    downloads: Object,
    downloadLocks: Array,
    user: String,
    network: String,
    nick: Object,
    privmsgs: Array,
    isActive: Boolean,
  },
  data() {
    return {
        COMMAND: COMMAND,
        privmsgPaneHeight: this.scaleToViewportHeight(privmsgPaneScale),
        lines: [],
        showDate: true,
        userList: [],
        shouldScrollToBottom: true,
        privmsgIndex: 0,
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
    privmsgs: {
        deep: true,
        handler: function() {
            this.handlePrivmsgs()
        },
    },
  },
  mounted() {
    this.handlePrivmsgs()
    window.addEventListener('resize', this.handleResize);
    this.$refs.privmsgPane.addEventListener('scroll', this.handleScroll);
    this.scrollToBottom()
  },
  updated() {
    if (this.shouldScrollToBottom) {
        this.scrollToBottom()
    }
  },
  beforeUnmount() {
    this.$refs.privmsgPane.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.handleResize);
  },
  computed: {
  },
  methods: {
    handlePrivmsgs() {
        // Break off any new notices and add them to lines
        const diff = this.privmsgs.length - this.privmsgIndex
        if (0 < diff) {
            const lines = this.privmsgs.slice(this.privmsgIndex)
            const objects = lines.map(privmsg => ({
                type: 'privmsg',
                content: privmsg.content,
                nick: this.nick,
                timestamp: privmsg.timestamp
            }))

            this.addLines(objects)
        }

        this.privmsgIndex = this.privmsgs.length
    },
    addLines(lines) {
        lines.forEach((line) => {
            this.lines.push(line)
        })
    },
    isScrolledToBottom() {
        const privmsgPane = this.$refs.privmsgPane
        const scrollTop = privmsgPane.scrollTop
        const clientHeight = privmsgPane.clientHeight
        const scrollHeight = privmsgPane.scrollHeight

        // Adjust for horizontal scrollbar width
        const horizontalScrollbarWidth = privmsgPane.offsetWidth - privmsgPane.clientWidth

        return scrollTop + clientHeight >= scrollHeight - horizontalScrollbarWidth
    },
    handleScroll() {
        this.shouldScrollToBottom = this.isScrolledToBottom()
    },
    scrollToBottom() {
        const privmsgPane = this.$refs.privmsgPane
        privmsgPane.scrollTop = privmsgPane.scrollHeight;

        // Set it to scroll again to the bottom after 1 second.
        // In case scrolling isn't complete after 1 second.
        let timeoutId = setTimeout(() => {
            const refreshPane = this.$refs.privmsgPane
            refreshPane.scrollTop = refreshPane.scrollHeight;
        }, 1000);
    },
    handleOperation(operation, command, target) {
        // If its a PRIVMSG targeting this channel, add it to lines.
        // IRC protocol does not add user input to the output feeds.
        if (command === COMMAND.PRIVMSG && target === this.nick) {
            const [, , ...parts] = operation.command.split(' ')
            const msg = parts.join(' ')
            const date = makeChatLogDate()
            const timestamp = formatISODate(date, 'MM/dd/yyyy HH:mm:ss')

            const line = {
                type: 'usermessage',
                content: msg,
                nick: this.user,
                timestamp: timestamp,
            }

            this.addLines([line])
        }

        // Send the operation up to the client for further post-processing.
        this.$emit('call:handleOperation', operation, command, target)
    },
    scaleToViewportHeight,
    handleResize() {
        this.privmsgPaneHeight = this.scaleToViewportHeight(privmsgPaneScale)
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
    'call:handleOperation', 'call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'
  ],
}
</script>
