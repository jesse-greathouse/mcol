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

export default {
  components: {
  },
  props: {
    message: String
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
        const color = 'pink'
        return [
            `bg-${color}-100`,
            `text-${color}-800`,
            `border-${color}-800`,
            `dark:bg-${color}-700`,
            `dark:text-${color}-400`,
            `dark:border-${color}-400`,
        ]
    },
  }
}
</script>
