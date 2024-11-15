<template>
 <div class="flex font-mono text-xs text-nowrap">
    <div class="inline-flex grow-0 w-32 mr-6 text-nowrap" v-if="showDate"> {{ dateFormatted }} </div>
    <div class="inline-flex grow"> {{ messageFormatted }} </div>
 </div>
</template>

<script>
import _ from 'lodash'
import { formatChatLine, formatISODate } from '@/format'
import throttle from 'lodash/throttle'

export default {
  components: {
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
            timestamp = formatISODate(this.date, 'MM/dd/yyyy H:mm:ss')
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
            [this.date, this.message] = formatChatLine(this.line)
        } catch(error) {
            // Sometimes the lines get chunked in a way thats impossible to parse.
            // Just make due with whatever chunk is there.
            const date = new Date();
            this.date = date.toISOString()
            this.message = this.line
        }
    }
  },
  emits: [],
}
</script>
