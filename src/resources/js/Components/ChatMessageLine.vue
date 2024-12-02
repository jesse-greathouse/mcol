<template>
 <div class="flex font-mono text-base max-w-full mb-1">
    <line-date v-if="showDate" :date="dateFormatted" />
    <line-message v-if="line.type === 'message'"
        :settings="settings"
        :downloads="downloads"
        :downloadLocks="downloadLocks"
        :message="messageFormatted"
        :channel="channel"
        @call:xdccSend="xdccSend"
        @call:removeCompleted="removeCompleted"
        @call:requestCancel="requestCancel"
        @call:requestRemove="requestRemove"
        @call:saveDownloadDestination="saveDownloadDestination" />
    <line-event v-if="line.type === 'event'" :message="messageFormatted" />
    <line-notice v-if="line.type === 'notice'" :message="messageFormatted" />
    <line-user v-if="line.type === 'usermessage'" :message="messageFormatted" />
 </div>
</template>

<script>
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
    settings: Object,
    downloads: Object,
    downloadLocks: Array,
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
    xdccSend(packet, nick) {
      this.$emit('call:xdccSend', packet, nick)
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
  emits: ['call:xdccSend', 'call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
