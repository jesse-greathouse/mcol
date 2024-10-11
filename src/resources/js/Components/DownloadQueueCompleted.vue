<template>
  <div class="grid grid-cols-12 gap-0 w-full">
    <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
      <completed-icon />
    </div>
    <div class="py-5 place-self-stretch" tabindex="-1">
      <p class="px-5 text-right font-semibold text-green-800">{{ fileSize }}</p>
    </div>
    <div class="py-5 place-self-stretch" tabindex="-1">
      <p class="px-5 font-semibold text-slate-600">{{ fileSize }}</p>
    </div>
    <div class="col-span-6 py-5 place-self-stretch" tabindex="-1">
      <fwb-progress :progress="100" color="green" :label="download.packet.file_name"  />
    </div>
    <div class="col-span-2 py-5 place-self-left" tabindex="-1">
      <p class="px-8 whitespace-nowrap">
        <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">
          {{ download.nick }}
        </span>
      </p>
    </div>
    <div class="py-5 place-self-center" tabindex="-1">
      <button ref="cancel" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
        :disabled="true"
        @click="cancel()" >
        <svg class="stroke-white fill-none stroke-2 w-6 h-6" aria-hidden="true" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 13H15M15 13L13 11M15 13L13 15M12.0627 6.06274L11.9373 5.93726C11.5914 5.59135 11.4184 5.4184 11.2166 5.29472C11.0376 5.18506 10.8425 5.10425 10.6385 5.05526C10.4083 5 10.1637 5 9.67452 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V10.2C21 9.0799 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H14.3255C13.8363 7 13.5917 7 13.3615 6.94474C13.1575 6.89575 12.9624 6.81494 12.7834 6.70528C12.5816 6.5816 12.4086 6.40865 12.0627 6.06274Z" stroke-linecap="round" stroke-linejoin="round"></path> </g>
        </svg>
        <span class="sr-only">Save File</span>
      </button>
    </div>
  </div>
</template>
  
<script>
import { FwbProgress } from 'flowbite-vue'
import CompletedIcon from '@/Components/CompletedIcon.vue'

const bytesInGB = 1e+9
const bytesInMB = 1e+6
const bytesInKB = 1000

function shouldShowIn(bytes, unit) {
  return (unit <= Number(bytes)) ? true : false
}

function formatSizeBy(bytes, unit, label = null, precision = 1) {
  const size = Number(bytes) / unit
  const display = size.toFixed(precision)
  if (null !== label) {
    return `${display} ${label}`
  } else {
    return display
  }
}

function formatSize(bytes) {
  if (shouldShowIn(bytes, bytesInGB)) {
    return formatSizeBy(bytes, bytesInGB, 'G')
  } else if (shouldShowIn(bytes, bytesInMB)) {
    return formatSizeBy(bytes, bytesInMB, 'M')
  } else if (shouldShowIn(bytes, bytesInKB)) {
    return formatSizeBy(bytes, bytesInKB, 'K')
  } else {
    return `${bytes} B`
  }
}

export default {
  components: {
    CompletedIcon,
    FwbProgress,
  },
  props: {
    download: Object,
  },
  data() {
    return {
      // TODO: functions for placing file.
    }
  },
  computed: {
    fileSize() {
      return formatSize(this.download.file_size_bytes)
    },
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100
    },
  },
}
</script>
