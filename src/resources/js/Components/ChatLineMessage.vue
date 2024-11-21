<template>
    <div class="w-full mr-4">
        <div class="inline-flex">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass" >{{ nick }}</span>&colon;
        </div>
        <div class="inline-flex">{{ content }}</div>
    </div>
</template>

<script>
import { parseChatMessage } from '@/chat'

const colorMap = {
  op: 'amber',
  voice: 'blue',
  default: 'gray',
}

export default {
  components: {
  },
  props: {
    message: String,
    channel: Object,
  },
  data() {
    const{nick, content} = parseChatMessage(this.message)
    return {
        nick,
        content,
    }
  },
  mounted() {
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
        `dark:bg--${color}-700`,
        `dark:text-${color}-400`,
        `border-${color}-400`
      ]
    },
  }
}
</script>
