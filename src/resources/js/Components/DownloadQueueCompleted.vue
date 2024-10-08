<template>
  <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
    <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
      <completed-icon />
    </div>
    <div class="col-span-10 py-6 px-0" tabindex="-1">
      <fwb-progress :progress="100" color="green" :label="download.packet.file_name"  />
    </div>
  </div>
</template>
  
<script>
import { FwbProgress } from 'flowbite-vue'
import CompletedIcon from '@/Components/CompletedIcon.vue'

export default {
  components: {
    CompletedIcon,
    FwbProgress,
  },
  props: {
    download: Object,
  },
  methods: {
  }
}
</script>
