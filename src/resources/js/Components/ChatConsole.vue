<template>
    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Console Pane -->
        <div ref="consolePane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3"
            :style="{ maxHeight: consolePaneHeight }">
            <div ref="bufferContainer">
                <console-line v-for="(line, i) in lines" :key="`line-${i}`" :showDate="showDate" :line="line" />
            </div>
        </div>
        <!-- End Console Pane -->
    </div>

    <!-- Start Chat Input -->
    <chat-input :network="network" :defaultCommand="COMMAND.JOIN" @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import { nextTick } from 'vue'
import { has } from '@/funcs'
import { streamConsole } from '@/Clients/stream'
import { scaleToViewportHeight } from '@/style'
import { COMMAND, parseChatLog } from '@/chat'
import ConsoleLine from '@/Components/ChatConsoleLine.vue'
import ChatInput from '@/Components/ChatInput.vue'
import { usePageStateSync } from '@/Composables/usePageStateSync'

const maxMessageLineBuffer = 1000
const consolePaneScale = .70
const consoleInterval = 60000

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
        const offsetKey = `chat:offset:${this.network}:console`
        const {
            state: offsetState,
            saveState: saveOffsetState
        } = usePageStateSync(offsetKey, {
            consoleOffset: 0,
        })

        return {
            offsetState,
            saveOffsetState,
            consoleOffset: offsetState.consoleOffset ?? 0,
            COMMAND: COMMAND,
            lines: [],
            consolePaneHeight: this.scaleToViewportHeight(consolePaneScale),
            showDate: true,
            shouldScrollToBottom: true,
            noticeIndex: 0,
            consoleTimeoutId: null,
            pendingStorageSave: false,
            saveRequestId: null,
        }
    },
    watch: {
        isActive(val) {
            if (val) this.scrollToBottom()
        },
        notice: {
            deep: true,
            handler() {
                if (this.noticeIndex > 0) {
                    const diff = this.notice.length - this.noticeIndex
                    if (diff > 0) {
                        const lines = this.notice.slice(this.noticeIndex)
                        const objects = lines.map(str => ({ type: 'notice', line: str }))
                        this.addLines(objects)
                    }
                }
                this.noticeIndex = this.notice.length
            },
        },
    },
    mounted() {
        this.getLinesFromStorage(100)
        window.addEventListener('resize', this.handleResize)
        this.$refs.consolePane.addEventListener('scroll', this.handleScroll)
        this.scrollToBottom()
        this.streamConsole()
    },
    beforeUnmount() {
        this.clearAllIntervals()

        if (this.saveRequestId) {
            if ('cancelIdleCallback' in window) {
                cancelIdleCallback(this.saveRequestId)
            } else {
                clearTimeout(this.saveRequestId)
            }
        }

        this.$refs.consolePane?.removeEventListener('scroll', this.handleScroll)
        window.removeEventListener('resize', this.handleResize)
    },
    updated() {
        if (this.shouldScrollToBottom) {
            nextTick(() => this.scrollToBottom())
        }

        this.pruneLines()
    },
    methods: {
        clearAllIntervals() {
            clearTimeout(this.consoleTimeoutId)
        },
        addLines(newLines) {
            if (!newLines?.length) return

            const wasAtBottom = this.shouldScrollToBottom
            this.lines.push(...newLines)

            if (wasAtBottom) {
                nextTick(() => this.scrollToBottom())
            }

            if (!this.pendingStorageSave) {
                this.pendingStorageSave = true

                const saveFn = () => {
                    this.saveLinesToStorage(100)
                    this.pendingStorageSave = false
                    this.saveRequestId = null
                }

                if ('requestIdleCallback' in window) {
                    this.saveRequestId = requestIdleCallback(saveFn, { timeout: 2000 })
                } else {
                    this.saveRequestId = setTimeout(saveFn, 1000)
                }
            }
        },
        pruneLines() {
            const linesTotal = this.lines.length
            const overBuffer = linesTotal - maxMessageLineBuffer
            if (overBuffer > 0) {
                this.lines = this.lines.slice(overBuffer)
            }
        },
        getBufferHtml() {
            return this.$refs.bufferContainer?.innerHTML || ''
        },
        getLinesFromStorage(max = 100) {
            const stored = localStorage.getItem(`chat:buffer:${this.network}:console`)
            if (stored) {
                try {
                    const parsed = JSON.parse(stored)
                    if (Array.isArray(parsed)) {
                        this.lines = parsed.slice(0, max)
                    }
                } catch (err) {
                    console.warn('Failed to parse console buffer JSON', err)
                }
            }
        },
        saveLinesToStorage(max = 100) {
            const recentLines = this.lines.slice(-max)
            try {
                localStorage.setItem(`chat:buffer:${this.network}:console`, JSON.stringify(recentLines))
            } catch (e) {
                console.warn('Failed to save console buffer:', e)
            }
        },
        resetConsoleInterval() {
            this.clearConsoleInterval()
            if (!this.$page.url.startsWith('/chat')) return
            this.consoleTimeoutId = setTimeout(this.streamConsole, consoleInterval)
        },
        clearConsoleInterval() {
            clearTimeout(this.consoleTimeoutId)
        },
        isScrolledToBottom() {
            const consolePane = this.$refs.consolePane
            if (!consolePane) return false

            const scrollTop = consolePane.scrollTop
            const clientHeight = consolePane.clientHeight
            const scrollHeight = consolePane.scrollHeight
            const scrollbarWidth = consolePane.offsetWidth - consolePane.clientWidth

            return scrollTop + clientHeight >= scrollHeight - scrollbarWidth
        },
        scrollToBottom() {
            const pane = this.$refs.consolePane
            if (!pane) return
            pane.scrollTop = pane.scrollHeight

            setTimeout(() => {
                if (!this.$page.url.startsWith('/chat')) return
                const refreshPane = this.$refs.consolePane
                if (refreshPane) {
                    refreshPane.scrollTop = refreshPane.scrollHeight
                }
            }, 1000)
        },
        handleScroll() {
            this.shouldScrollToBottom = this.isScrolledToBottom()
        },
        async streamConsole() {
            this.shouldScrollToBottom = this.isScrolledToBottom()
            await streamConsole(this.network, this.consoleOffset, async (chunk) => {
                const { lines, meta, parseError } = await parseChatLog(chunk)
                if (parseError !== null) return

                const objects = lines.map(str => ({ type: 'console', line: str }))
                this.addLines(objects)

                if (has(meta, 'offset')) {
                    this.consoleOffset = meta.offset
                    this.offsetState.consoleOffset = meta.offset
                    this.saveOffsetState()
                }

                this.resetConsoleInterval()
            })
        },
        scaleToViewportHeight,
        handleResize() {
            this.consolePaneHeight = this.scaleToViewportHeight(consolePaneScale)
        },
        handleOperation(operation, command, target) {
            this.$emit('call:handleOperation', operation, command, target)
        },
    },
    emits: ['call:handleOperation'],
}
</script>
