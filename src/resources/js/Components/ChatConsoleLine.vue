<template>
 <div class="flex font-mono text-xs">
    <div class="inline-flex grow-0 w-32 mr-6" v-if="showDate"> {{ dateFormatted }} </div>
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
            [this.date, this.message] = formatChatLine(this.line)
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
            [this.date, this.message] = formatChatLine(this.line)
        }

        return this.message
    },
  },
  mounted() {
  },
  methods: {
  },
  emits: [],
}
</script>
