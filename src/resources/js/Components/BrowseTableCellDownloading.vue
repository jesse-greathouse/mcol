<template>
  <td class="border-t bg-gray-100">
    <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
      <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
        <downloading-icon />
      </div>
      <div class="col-span-10 py-6 px-0" tabindex="-1">
        <fwb-progress :progress="progress" :label="packet.file_name"  />
      </div>
    </div>
  </td>
</template>
  
<script>
import { FwbProgress } from 'flowbite-vue'
import DownloadingIcon from '@/Components/DownloadingIcon.vue'

export default {
  components: {
    DownloadingIcon,
    FwbProgress,
  },
  props: {
    download: Object,
    packet: Object,
  },
  computed: {
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100
    },
  }
}
</script>
