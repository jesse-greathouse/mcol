<template>
    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Console Pane -->
        <div ref="consolePane" class="flex flex-col content-end overflow-auto w-full mr-3" :style="{ maxHeight: chatPaneHeight }" >
            <console-line v-for="(line, i) in lines" :key="`line-${i}`" :showDate="showDate" :line="line" />
        </div>
        <!-- End Console Pane -->
    </div>

    <!-- Start Console Input -->
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
    <!-- End Console Input -->
</template>

<script>
import _ from 'lodash'
import throttle from 'lodash/throttle'
import { fetchConsole } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { parseChatLog } from '@/format'
import ConsoleLine from '@/Components/ChatConsoleLine.vue'

const chatPaneScale = .72
const consoleInterval = 60000 // Check console every 60 seconds.
let consoleTimeoutId
const clearConsoleInterval = function () {
    clearConsoleInterval(consoleTimeoutId)
}

const clearAllIntervals = function() {
    clearConsoleInterval()
}

export default {
  components: {
    ConsoleLine,
  },
  props: {
    user: String,
    network: String,
    isActive: Boolean,
  },
  data() {
    return {
        lines: [],
        consoleOffset: 0,
        chatPaneHeight: this.scaleToViewportHeight(chatPaneScale),
        showDate: true,
    }
  },
  watch: {
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
    this.refreshConsole()
  },
  updated() {
    this.scrollToBottom()
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize);
  },
  methods: {
    resetIntervals() {
        clearAllIntervals()
        consoleTimeoutId = setTimeout(this.refreshConsole, consoleInterval);
    },
    isScrolledToBottom() {
        return true
    },
    scrollToBottom() {
        const consolePane = this.$refs.consolePane
        const lastChildElement = consolePane.lastElementChild
        lastChildElement?.scrollIntoView({
            behavior: 'smooth',
        })
    },
    async refreshConsole() {
        const {data, error} = await fetchConsole(this.network, this.consoleOffset)

        if (null !== error) return

        const {lines, meta, parseError} = await parseChatLog(data)

        if (null !== parseError) return

        this.lines = [...this.lines, ...lines]

        if (_.has(meta, 'offset')) {
            this.consoleOffset = meta.offset
        }

        this.resetIntervals()
    },
    scaleToViewportHeight,
    handleResize() {
        this.chatPaneHeight = this.scaleToViewportHeight(chatPaneScale)
    },
  },
  emits: [],
}
</script>
