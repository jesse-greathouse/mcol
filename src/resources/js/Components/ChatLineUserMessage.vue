<template>
    <div class="flex items-start w-full mr-4">
        <div class="inline-flex items-start mr-1">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass" >{{ nick }}</span>&colon;
        </div>
        <div class="inline-flex items-start">{{ content }}</div>
    </div>
</template>

<script>
import { parseChatMessage } from '@/chat'

export default {
  components: {
  },
  props: {
    message: String,
    color: {
        type: String,
        default: 'green',
    },
  },
  data() {
    const{nick, content} = parseChatMessage(this.message)
    return {
        nick,
        content,
    }
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
  }
}
</script>
