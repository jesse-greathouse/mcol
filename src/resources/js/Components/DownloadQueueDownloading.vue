<template>
  <div class="grid grid-cols-12 gap-0 w-full">
    <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
      <downloading-icon />
    </div>
    <div class="py-5 place-self-stretch" tabindex="-1">
      <p class="px-5 text-right font-semibold text-blue-800">{{ progressSize }}</p>
    </div>
    <div class="py-5 place-self-stretch" tabindex="-1">
      <p class="px-5 font-semibold text-slate-600">{{ fileSize }}</p>
    </div>
    <div class="col-span-6 py-5 place-self-stretch" tabindex="-1">
      <fwb-progress :progress="progress" :label="download.packet.file_name"  />
    </div>
    <div class="col-span-2 py-5 place-self-left" tabindex="-1">
      <p class="px-8 whitespace-nowrap">
        <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
          {{ download.nick }}
        </span>
      </p>
    </div>
    <div class="py-5 place-self-center" tabindex="-1">
      <button ref="cancel" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
        :disabled="cancelDisabled"
        @click="cancel()" >
        <svg class="fill-white w-5 h-5" aria-hidden="true" viewBox="0 0 460.775 460.775" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M285.08,230.397L456.218,59.27c6.076-6.077,6.076-15.911,0-21.986L423.511,4.565c-2.913-2.911-6.866-4.55-10.992-4.55 c-4.127,0-8.08,1.639-10.993,4.55l-171.138,171.14L59.25,4.565c-2.913-2.911-6.866-4.55-10.993-4.55 c-4.126,0-8.08,1.639-10.992,4.55L4.558,37.284c-6.077,6.075-6.077,15.909,0,21.986l171.138,171.128L4.575,401.505 c-6.074,6.077-6.074,15.911,0,21.986l32.709,32.719c2.911,2.911,6.865,4.55,10.992,4.55c4.127,0,8.08-1.639,10.994-4.55 l171.117-171.12l171.118,171.12c2.913,2.911,6.866,4.55,10.993,4.55c4.128,0,8.081-1.639,10.992-4.55l32.709-32.719 c6.074-6.075,6.074-15.909,0-21.986L285.08,230.397z"></path> </g>
        </svg>
        <span class="sr-only">Cancel Download</span>
      </button>
    </div>
  </div>
</template>
  
<script>
import { FwbProgress } from 'flowbite-vue'
import { formatSize } from '@/fileSize'
import DownloadingIcon from '@/Components/DownloadingIcon.vue'

export default {
  components: {
    DownloadingIcon,
    FwbProgress,
  },
  props: {
    download: Object,
    settings: Object,
  },
  data() {
    return {
      cancelDisabled: false,
    }
  },
  computed: {
    progressSize() {
      return formatSize(this.download.progress_bytes)
    },
    fileSize() {
      return formatSize(this.download.file_size_bytes)
    },
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100
    },
  },
  methods: {
    cancel() {
      this.cancelDisabled = true
      this.$emit('call:requestCancel', this.download)
    }
  },
  emits: ['call:requestCancel'],
}
</script>
