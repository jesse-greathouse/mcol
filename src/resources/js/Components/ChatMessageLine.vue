<template>
 <div class="flex font-mono text-base max-w-full">
    <line-date v-if="showDate" :date="dateFormatted" />
    <line-message v-if="line.type === 'message'" :message="messageFormatted" :channel="channel" />
    <line-event v-if="line.type === 'event'" :message="messageFormatted" />
    <line-notice v-if="line.type === 'notice'" :message="messageFormatted" />
    <line-user v-if="line.type === 'usermessage'" :message="messageFormatted" />
 </div>
</template>

<script>
import _ from 'lodash'
import { formatISODate } from '@/format'
import { parseChatLine } from '@/chat'
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
    showDate: Boolean,
    channel: Object,
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
        const {date, message, error} = parseChatLine(this.line.line)

        if (null === error) {
            this.date = date
            this.message = message
            return
        }

        // Sometimes the lines get chunked in a way thats impossible to parse.
        // Just make due with whatever chunk is there.
        const dateNow = new Date();
        this.date = dateNow.toISOString()
        this.message = this.line.line
    },
  },
  emits: [],
}
</script>
