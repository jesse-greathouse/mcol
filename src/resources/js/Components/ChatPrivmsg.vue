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
                <span class="font-bold text-xl">{{ nick }}</span>
            </div>
        </div>
    </div>
    <!-- End Topic Pane-->

    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Chat Pane -->
        <div ref="privmsgPane" class="flex flex-col content-end overflow-y-auto scroll-smooth w-full max-w-full mr-3"
            :style="{ maxHeight: privmsgPaneHeight }">
            <div ref="bufferContainer">
                <privmsg-line v-for="(line, i) in lines" :settings="settings" :downloads="downloads"
                    :downloadLocks="downloadLocks" :key="`line-${i}`" :showDate="showDate" :type="line.type"
                    :nick="line.nick" :timestamp="line.timestamp" :content="line.content"
                    @call:removeCompleted="removeCompleted" @call:requestCancel="requestCancel"
                    @call:requestRemove="requestRemove" @call:saveDownloadDestination="saveDownloadDestination" />
            </div>
        </div>
        <!-- End Chat Pane -->
    </div>

    <!-- Start Chat Input -->
    <chat-input :network="network" :defaultTarget="nick" :defaultCommand="COMMAND.PRIVMSG"
        @call:handleOperation="handleOperation" />
    <!-- End Chat Input -->
</template>

<script>
import { nextTick } from 'vue'
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
            COMMAND,
            privmsgPaneHeight: this.scaleToViewportHeight(privmsgPaneScale),
            lines: [],
            showDate: true,
            shouldScrollToBottom: true,
            privmsgIndex: 0,
            pendingStorageSave: false,
            saveRequestId: null,
        }
    },
    watch: {
        isActive(val) {
            if (val) this.scrollToBottom()
        },
        privmsgs: {
            deep: true,
            handler: function () {
                this.handlePrivmsgs()
            },
        },
    },
    mounted() {
        this.getLinesFromStorage(200)
        this.handlePrivmsgs()
        window.addEventListener('resize', this.handleResize)
        this.$refs.privmsgPane.addEventListener('scroll', this.handleScroll)
        this.scrollToBottom()
    },
    updated() {
        if (this.shouldScrollToBottom) {
            nextTick(() => this.scrollToBottom())
        }
    },
    beforeUnmount() {
        if (this.saveRequestId) {
            if ('cancelIdleCallback' in window) {
                cancelIdleCallback(this.saveRequestId)
            } else {
                clearTimeout(this.saveRequestId)
            }
        }

        window.removeEventListener('resize', this.handleResize)
        this.$refs.privmsgPane?.removeEventListener('scroll', this.handleScroll)
    },
    methods: {
        handlePrivmsgs() {
            const diff = this.privmsgs.length - this.privmsgIndex
            if (diff > 0) {
                const lines = this.privmsgs.slice(this.privmsgIndex)
                const objects = lines.map(privmsg => ({
                    type: 'privmsg',
                    content: privmsg.content,
                    nick: this.nick,
                    timestamp: privmsg.timestamp,
                }))
                this.addLines(objects)
            }

            this.privmsgIndex = this.privmsgs.length
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
                    this.saveLinesToStorage(200)
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
        getLinesFromStorage(max = 200) {
            const stored = localStorage.getItem(`chat:buffer:${this.network}:${this.nick}`)
            if (stored) {
                try {
                    const parsed = JSON.parse(stored)
                    if (Array.isArray(parsed)) {
                        this.lines = parsed.slice(0, max)
                    }
                } catch (err) {
                    console.warn('Failed to parse chat buffer JSON', err)
                }
            }
        },
        saveLinesToStorage(max = 200) {
            const recentLines = this.lines.slice(-max)
            try {
                localStorage.setItem(`chat:buffer:${this.network}:${this.nick}`, JSON.stringify(recentLines))
            } catch (e) {
                console.warn('Failed to save chat buffer:', e)
            }
        },
        isScrolledToBottom() {
            const privmsgPane = this.$refs.privmsgPane
            if (!privmsgPane) return false

            const scrollTop = privmsgPane.scrollTop
            const clientHeight = privmsgPane.clientHeight
            const scrollHeight = privmsgPane.scrollHeight
            const scrollbarWidth = privmsgPane.offsetWidth - privmsgPane.clientWidth

            return scrollTop + clientHeight >= scrollHeight - scrollbarWidth
        },
        handleScroll() {
            this.shouldScrollToBottom = this.isScrolledToBottom()
        },
        scrollToBottom() {
            const pane = this.$refs.privmsgPane
            if (!pane) return
            pane.scrollTop = pane.scrollHeight

            setTimeout(() => {
                if (!this.$page.url.startsWith('/chat')) return
                this.$refs.privmsgPane.scrollTop = this.$refs.privmsgPane.scrollHeight
            }, 1000)
        },
        handleOperation(operation, command, target) {
            if (command === COMMAND.PRIVMSG && target === this.nick) {
                const [, , ...parts] = operation.command.split(' ')
                const msg = parts.join(' ')
                const date = makeChatLogDate()
                const timestamp = formatISODate(date, 'MM/dd/yyyy HH:mm:ss')

                const line = {
                    type: 'usermessage',
                    content: msg,
                    nick: this.user,
                    timestamp,
                }

                this.addLines([line])
            }

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
        'call:handleOperation',
        'call:requestCancel',
        'call:requestRemove',
        'call:removeCompleted',
        'call:saveDownloadDestination',
    ],
}
</script>
