<template>
 <div class="flex font-mono text-base max-w-full">
    <line-date v-if="showDate" :date="dateFormatted" />
    <line-message v-if="line.type === 'message'" :message="messageFormatted" />
    <line-event v-if="line.type === 'event'" :message="messageFormatted" />
    <line-notice v-if="line.type === 'notice'" :message="messageFormatted" />
    <line-user v-if="line.type === 'usermessage'" :message="messageFormatted" />
 </div>
</template>

<script>
import _ from 'lodash'
import { formatChatLine, formatISODate } from '@/format'
import LineDate from '@/Components/ChatLineDate.vue'
import LineEvent from '@/Components/ChatLineEvent.vue'
import LineMessage from '@/Components/ChatLineMessage.vue'
import LineNotice from '@/Components/ChatLineNotice.vue'
import LineUser from '@/Components/ChatLineUserMessage.vue'


export default {
  components: {
    LineDate,
    LineMessage,
    LineNotice,
    LineEvent,
    LineUser,
  },
  props: {
    line: String,
    showDate: Boolean
  },
  data() {
    return {
        date: null,
        message: null,
    }
  },
  watch: {
  },
  computed: {
    dateFormatted() {
        let timestamp = ''
        if (null === this.date) {
            this.parseLine()
        }

        try {
            timestamp = formatISODate(this.date, 'MM/dd/yyyy HH:mm:ss')
        } catch(error) {
            console.log(`error formatting date: ${this.date} error: ${error}`)
        }

        return timestamp
    },
    messageFormatted() {
        if (null === this.message) {
            this.parseLine()
        }

        return this.message
    },
  },
  mounted() {
  },
  methods: {
    parseLine() {
        try {
            [this.date, this.message] = formatChatLine(this.line.line)
        } catch(error) {
            // Sometimes the lines get chunked in a way thats impossible to parse.
            // Just make due with whatever chunk is there.
            const date = new Date();
            this.date = date.toISOString()
            this.message = this.line.line
            console.log(`couldn't parse line: ${this.line.line}`)
            console.log(error)
        }
    },
  },
  emits: [],
}
</script>
