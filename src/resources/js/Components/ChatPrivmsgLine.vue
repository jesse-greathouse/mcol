<template>
 <div class="flex font-mono text-base max-w-full mb-1">
    <line-date v-if="showDate" :date="timestamp" />
    <line-privmsg v-if="type === 'privmsg'" :nick="nick" :message="content" />
    <line-notice v-if="type === 'notice'" :message="content" />
    <line-privmsg v-if="type === 'usermessage'" color="pink" :nick="nick" :message="content" />
 </div>
</template>

<script>
import LineDate from '@/Components/ChatLineDate.vue'
import LinePrivmsg from '@/Components/ChatLinePrivmsg.vue'
import LineNotice from '@/Components/ChatLineNotice.vue'

export default {
  components: {
    LineDate,
    LinePrivmsg,
    LineNotice,
  },
  props: {
    type: String,
    content: String,
    nick: String,
    timestamp: String,
    showDate: Boolean,
  },
}
</script>
