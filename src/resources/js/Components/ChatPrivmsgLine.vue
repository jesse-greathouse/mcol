<template>
 <div class="flex font-mono text-base max-w-full mb-1">
    <line-date v-if="showDate" :date="timestamp" />
    <line-privmsg v-if="type === 'privmsg'"
        :settings="settings"
        :downloads="downloads"
        :downloadLocks="downloadLocks"
        :nick="nick"
        :message="content"
        @call:removeCompleted="removeCompleted"
        @call:requestCancel="requestCancel"
        @call:requestRemove="requestRemove"
        @call:saveDownloadDestination="saveDownloadDestination" />
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
    downloads: Object,
    downloadLocks: Array,
    settings: Object,
    type: String,
    content: String,
    nick: String,
    timestamp: String,
    showDate: Boolean,
  },
  methods: {
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
  emits: ['call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
