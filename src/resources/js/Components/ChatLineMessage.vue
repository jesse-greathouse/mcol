<template>
    <div class="flex items-start w-full mr-4">
        <div class="inline-flex items-start mr-1">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass" >{{ nick }}</span>&colon;
        </div>
        <div class="inline-flex items-start">
            <component :content="content" :packet="packet" v-bind:is="contentComponent"></component>
        </div>
    </div>
</template>

<script>
import { parseChatMessage, parsePacket } from '@/chat'
import Generic from '@/Components/ChatMessageContent/Generic.vue'
import Packet from '@/Components/ChatMessageContent/Packet.vue'

const colorMap = {
  op: 'amber',
  voice: 'blue',
  default: 'gray',
}

export default {
  components: {
    Generic,
    Packet,
  },
  props: {
    message: String,
    channel: Object,
  },
  data() {
    let contentComponent = 'Generic'
    const{nick, content} = parseChatMessage(this.message)

    const packet = parsePacket(content)
    if (this.isPacketAnnouncement(packet)) {
         contentComponent = 'Packet'
    }

    return {
        nick,
        content,
        contentComponent,
        packet,
    }
  },
  mounted() {
  },
  methods: {
    isPacketAnnouncement(packet) {
       return (null === packet.error && null !== packet.num && null !== packet.fileName)
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
  }
}
</script>
