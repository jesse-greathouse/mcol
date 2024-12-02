<template>
    <packet
        :settings="settings"
        :content="content"
        :packet="download"
        :isDownloadLocked="isDownloadLocked"
        @call:xdccSend="xdccSend"/>
</template>

<script>
import Packet from '@/Components/ChatMessageContent/Packet.vue'

export default {
  components: {
    Packet,
  },
  props: {
    download: Object,
    content: String,
    isDownloadLocked: {
        type: Boolean,
        default: false,
    },
  },
  methods: {
    xdccSend() {
        if (!this.isDownloadLocked) {
            this.$emit('call:xdccSend', this.download)
        }
    },
  },
  emits: ['call:xdccSend'],
}
</script>
