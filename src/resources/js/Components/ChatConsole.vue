<template>
    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Console Pane -->
        <div ref="consolePane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3" :style="{ maxHeight: consolePaneHeight }" >
            <console-line v-for="(line, i) in lines" :key="`line-${i}`" :showDate="showDate" :line="line" />
        </div>
        <!-- End Console Pane -->
    </div>

        <!-- Start Chat Input -->
        <chat-input
            :network="network"
            :defaultCommand="COMMAND.JOIN"
            @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import { has } from '@/funcs'
import { streamConsole } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { COMMAND, parseChatLog } from '@/chat'
import ConsoleLine from '@/Components/ChatConsoleLine.vue'
import ChatInput from '@/Components/ChatInput.vue'

const maxMessageLineBuffer = 1000 // Maximum 1000 lines so we don't crash the browser.
const consolePaneScale = .70
const consoleInterval = 60000 // Check console every 60 seconds.

export default {
  components: {
    ConsoleLine,
    ChatInput,
  },
  props: {
    settings: Object,
    user: String,
    network: String,
    notice: Array,
    isActive: Boolean,
  },
  data() {
    return {
        COMMAND: COMMAND,
        lines: [],
        consoleOffset: 0,
        consolePaneHeight: this.scaleToViewportHeight(consolePaneScale),
        showDate: true,
        shouldScrollToBottom: true,
        noticeIndex: 0,
        consoleTimeoutId: null,
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
    this.$refs.consolePane.addEventListener('scroll', this.handleScroll);
    this.scrollToBottom()
    this.streamConsole()
  },
  updated() {
    if (this.shouldScrollToBottom) {
        this.scrollToBottom()
    }

    this.pruneLines()
  },
  beforeUnmount() {
    this.$refs.consolePane.removeEventListener('scroll', this.handleScroll);
    window.removeEventListener('resize', this.handleResize);
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
    clearConsoleInterval() {
        clearTimeout(this.consoleTimeoutId)
    },
    resetConsoleInterval() {
        this.clearConsoleInterval()
        this.consoleTimeoutId = setTimeout(this.streamConsole, consoleInterval);
    },
    isScrolledToBottom() {
        const consolePane = this.$refs.consolePane
        const scrollTop = consolePane.scrollTop
        const clientHeight = consolePane.clientHeight
        const scrollHeight = consolePane.scrollHeight

        // Adjust for horizontal scrollbar width
        const horizontalScrollbarWidth = consolePane.offsetWidth - consolePane.clientWidth

        return scrollTop + clientHeight >= scrollHeight - horizontalScrollbarWidth
    },
    scrollToBottom() {
        const consolePane = this.$refs.consolePane
        const lastChildElement = consolePane.lastElementChild
        lastChildElement?.scrollIntoView({
            behavior: 'smooth',
        })
    },
    handleScroll() {
        this.shouldScrollToBottom = this.isScrolledToBottom()
    },
    scrollToBottom() {
        const consolePane = this.$refs.consolePane
        consolePane.scrollTop = consolePane.scrollHeight;

        // Set it to scroll again to the bottom after 1 second.
        // In case scrolling isn't complete after 1 second.
        let timeoutId = setTimeout(() => {
            const refreshPane = this.$refs.consolePane
            refreshPane.scrollTop = refreshPane.scrollHeight;
        }, 1000);
    },
    async streamConsole() {
        this.shouldScrollToBottom = this.isScrolledToBottom()
        await streamConsole(this.network, this.consoleOffset, async (chunk) => {
            const {lines, meta, parseError} = await parseChatLog(chunk)
            if (null !== parseError) return

            const objects = lines.map(str => ({ type: 'console', line: str }));

            this.addLines(objects)

            if (has(meta, 'offset')) {
                this.consoleOffset = meta.offset
            }

            this.resetConsoleInterval()
        })
    },
    scaleToViewportHeight,
    handleResize() {
        this.consolePaneHeight = this.scaleToViewportHeight(consolePaneScale)
    },
  },
  emits: [],
}
</script>
