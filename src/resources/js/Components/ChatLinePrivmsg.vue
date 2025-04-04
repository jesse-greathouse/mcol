<template>
    <div class="flex items-start w-full mr-4">
        <div class="inline-flex items-start mr-1">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass">
                {{ nick }}
            </span>&colon;
        </div>
        <div class="inline-flex items-start">
            <component v-bind:is="contentComponent" :settings="settings" :content="message" :download="download"
                :isDownloadLocked="isDownloadLocked" @call:removeCompleted="removeCompleted"
                @call:requestCancel="requestCancel" @call:requestRemove="requestRemove"
                @call:saveDownloadDestination="saveDownloadDestination" />
        </div>
    </div>
</template>

<script>

import { parseDownload } from '@/chat'
import { has, trim } from '@/funcs'
import Generic from '@/Components/ChatMessageContent/Generic.vue'
import ReDownload from '@/Components/ChatMessageContent/ReDownload.vue'
import Download from '@/Components/ChatMessageContent/Download.vue'
import Locked from '@/Components/ChatMessageContent/Locked.vue'

const locksInterval = 2000; // Check download locks every 2 seconds.
const lockTimeout = 180000 // A Lock times out after 3 minutes.

export default {
    components: {
        Generic,
        Download,
        Locked,
        ReDownload,
    },
    props: {
        settings: Object,
        downloads: Object,
        nick: String,
        downloadLocks: Array,
        message: String,
        color: {
            type: String,
            default: 'gray',
        },
    },
    data() {
        let lockTime
        let locksTimeoutId
        let contentComponent = 'Generic'
        let download = {}
        let isDownloadLocked = false

        const downloadMessage = parseDownload(this.message)
        //console.log(downloadMessage)
        if (this.isDownloadMessage(downloadMessage)) {
            const fileName = trim(downloadMessage.fileName)
            download = this.initDownload(fileName)
            contentComponent = 'Locked'
            let scheduled = false

            if (0 <= this.downloadLocks.indexOf(fileName)) {
                contentComponent = 'Locked'
                isDownloadLocked = true
                scheduled = true
            }

            if (has(this.downloads, fileName)) {
                contentComponent = 'Download'
                download = this.downloads[fileName]
                scheduled = true
            }

            if (scheduled) {
                this.locksTimeoutId = setTimeout(this.checkLock, locksInterval)
            }
        }

        return {
            lockTime,
            locksTimeoutId,
            contentComponent,
            download,
            isDownloadLocked,
        }
    },
    mounted() {
    },
    methods: {
        initDownload(fileName) {
            return {
                file_name: fileName,
                gets: 0,
                size: 0,
                num: 0,
            }
        },
        clearLocksInterval() {
            clearTimeout(this.locksTimeoutId)
        },
        isDownloadMessage(downloadMessage) {
            return (null === downloadMessage.error && null !== downloadMessage.fileName)
        },
        closeDownload() {
            this.contentComponent = 'ReDownload'
            this.isDownloadLocked = false
            this.clearLocksInterval()
        },
        resetLockTimer() {
            const d = new Date()
            this.lockTime = d.getMilliseconds()
        },
        checkLock() {
            this.clearLocksInterval()

            // If we're not still on the chat page, then bail...
            if (!this.$page.url.startsWith('/chat')) return

            const downloadMessage = parseDownload(this.message)
            const fileName = trim(downloadMessage.fileName)

            // handle in download queue
            if (has(this.downloads, fileName)) {
                this.download = this.downloads[fileName]
                this.contentComponent = 'Download'
            } else if (0 > this.downloadLocks.indexOf(fileName)) {
                this.contentComponent = 'Locked'
                // Check if the lock timed out.
                const d = new Date()
                const nowMs = d.getMilliseconds()
                if ((nowMs - this.lockTime) >= lockTimeout) {
                    this.closeDownload()
                    return
                }
            }

            this.locksTimeoutId = setTimeout(this.checkLock, locksInterval)
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
    computed: {
        nickClass() {
            return [
                `bg-${this.color}-100`,
                `text-${this.color}-800`,
                `border-${this.color}-800`,
                `dark:bg-${this.color}-700`,
                `dark:text-${this.color}-400`,
                `dark:border-${this.color}-400`,
            ]
        },
    },
    emits: ['call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
