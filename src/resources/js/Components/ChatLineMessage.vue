<template>
    <div class="flex items-start w-full mr-4">
        <div class="inline-flex items-start mr-1">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass" >{{ nick }}</span>&colon;
        </div>
        <div class="inline-flex items-start">
            <component
                v-bind:is="contentComponent"
                :settings="settings"
                :content="content"
                :packet="packet"
                :download="download"
                :isDownloadLocked="isDownloadLocked"
                @call:xdccSend="xdccSend"
                @call:removeCompleted="removeCompleted"
                @call:requestCancel="requestCancel"
                @call:requestRemove="requestRemove"
                @call:saveDownloadDestination="saveDownloadDestination" />
        </div>
    </div>
</template>

<script>
import { parseChatMessage, parsePacket } from '@/chat'
import { has, trim } from '@/funcs'
import Generic from '@/Components/ChatMessageContent/Generic.vue'
import Packet from '@/Components/ChatMessageContent/Packet.vue'
import Download from '@/Components/ChatMessageContent/Download.vue'

const locksInterval = 2000; // Check download locks every 2 seconds.
const lockTimeout = 180000 // A Lock times out after 3 minutes.

const colorMap = {
  op: 'amber',
  voice: 'blue',
  default: 'gray',
}

export default {
  components: {
    Generic,
    Packet,
    Download,
  },
  props: {
    settings: Object,
    downloads: Object,
    downloadLocks: Array,
    message: String,
    channel: Object,
  },
  data() {
    let lockTime
    let locksTimeoutId
    let contentComponent = 'Generic'
    let download = {}
    let isDownloadLocked = false
    const{nick, content} = parseChatMessage(this.message)

    const packet = parsePacket(content)
    if (this.isPacketAnnouncement(packet)) {
        contentComponent = 'Packet'

        if (0 <= this.downloadLocks.indexOf(packet.fileName)) {
            isDownloadLocked = true
        }

        if (has(this.downloads, packet.fileName)) {
            download = this.downloads[packet.fileName]
            contentComponent = 'Download'
        }
    }

    return {
        lockTime,
        locksTimeoutId,
        nick,
        content,
        contentComponent,
        packet,
        download,
        isDownloadLocked,
    }
  },
  mounted() {
  },
  methods: {
    clearLocksInterval() {
        clearTimeout(this.locksTimeoutId)
    },
    isPacketAnnouncement(packet) {
       return (null === packet.error && null !== packet.num && null !== packet.fileName)
    },
    closeDownload() {
        this.isDownloadLocked = false
        this.download = {}
        this.contentComponent = 'Packet'
        this.clearLocksInterval()
    },
    resetLockTimer() {
        const d = new Date()
        this.lockTime = d.getMilliseconds()
    },
    checkLock() {
        this.clearLocksInterval()
        const fileName = trim(this.packet.fileName)

        // handle in download queue
        if (has(this.downloads, fileName)) {
            this.download = this.downloads[fileName]
            this.contentComponent = 'Download'
        } else if (0 > this.downloadLocks.indexOf(fileName)) {
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
    xdccSend(packet) {
        this.$emit('call:xdccSend', packet, this.nick)
        this.isDownloadLocked = true
        this.resetLockTimer()
        // In case scrolling isn't complete after 1 second.
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
        let color = colorMap.default
        const opIndex = this.channel.op.indexOf(this.nick)
        if (0 <= opIndex) {
            color = colorMap.op
        }

        const voiceIndex = this.channel.voice.indexOf(this.nick)
        if (0 <= voiceIndex) {
            color = colorMap.voice
        }

        return [
            `bg-${color}-100`,
            `text-${color}-800`,
            `border-${color}-400`,
            `dark:bg-${color}-700`,
            `dark:text-${color}-400`,
            `dark:border-${color}-400`,
        ]
    },
  },
  emits: ['call:xdccSend', 'call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
