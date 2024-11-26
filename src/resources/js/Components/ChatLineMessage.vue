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
import { has } from '@/funcs'
import Generic from '@/Components/ChatMessageContent/Generic.vue'
import Packet from '@/Components/ChatMessageContent/Packet.vue'
import Download from '@/Components/ChatMessageContent/Download.vue'

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
    message: String,
    channel: Object,
  },
  data() {
    let contentComponent = 'Generic'
    let download = {}
    const{nick, content} = parseChatMessage(this.message)

    const packet = parsePacket(content)
    if (this.isPacketAnnouncement(packet)) {
        contentComponent = 'Packet'

        if (has(this.downloads, packet.fileName)) {
            download = this.downloads[packet.fileName]
            contentComponent = 'Download'
        }
    }

    return {
        nick,
        content,
        contentComponent,
        packet,
        download,
    }
  },
  mounted() {
  },
  methods: {
    isPacketAnnouncement(packet) {
       return (null === packet.error && null !== packet.num && null !== packet.fileName)
    },
    xdccSend(packet) {
        this.$emit('call:xdccSend', packet, this.nick)
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
