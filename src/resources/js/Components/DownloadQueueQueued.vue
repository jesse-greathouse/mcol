<template>
  <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
    <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
      <queued-icon />
    </div>
    <div class="col-span-10 py-6 px-0" tabindex="-1">
      {{ download.packet.file_name }} {{ download.queued_status }} / {{ total }}
    </div>
  </div>
</template>
  
<script>
import QueuedIcon from '@/Components/QueuedIcon.vue'

export default {
  components: {
    QueuedIcon,
  },
  props: {
    download: Object,
  },
  computed: {
    total() {
      return (null === this.download.queued_status) ? '?' : this.download.queued_status
    },
  },
  emits: ['call:requestRemove'],
}
</script>
