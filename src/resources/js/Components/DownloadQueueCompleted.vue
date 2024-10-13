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
      <button ref="saveFile" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-blue-400 hover:bg-blue-500 focus:ring-4 focus:outline-none focus:ring-blue-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-blue-400 dark:hover:bg-blue-400 dark:focus:ring-blue-500"
        :disabled="saveFileDisabled"
        @click="saveFile()" >
        <svg class="stroke-white fill-none stroke-2 w-6 h-6" aria-hidden="true" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 13H15M15 13L13 11M15 13L13 15M12.0627 6.06274L11.9373 5.93726C11.5914 5.59135 11.4184 5.4184 11.2166 5.29472C11.0376 5.18506 10.8425 5.10425 10.6385 5.05526C10.4083 5 10.1637 5 9.67452 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V10.2C21 9.0799 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H14.3255C13.8363 7 13.5917 7 13.3615 6.94474C13.1575 6.89575 12.9624 6.81494 12.7834 6.70528C12.5816 6.5816 12.4086 6.40865 12.0627 6.06274Z" stroke-linecap="round" stroke-linejoin="round"></path> </g>
        </svg>
        <span class="sr-only">Save File</span>
      </button>
    </div>
  </div>
</template>
  
<script>
import _ from 'lodash'
import { FwbProgress } from 'flowbite-vue'
import { formatSize } from '@/fileSize'
import CompletedIcon from '@/Components/CompletedIcon.vue'

// maps a media type to a store
const mediaTypeToStoreMap = {
  movie: 'movies',
  'tv episode': 'tv',
  'tv series': 'tv',
  book: 'books',
  music: 'music',
  game: 'game',
  application: 'application',
}

export default {
  components: {
    CompletedIcon,
    FwbProgress,
  },
  props: {
    download: Object,
    settings: Object,
  },
  data() {
    return {
      // TODO: functions for placing file.
    }
  },
  methods: {
    saveFile() {
      console.log('picking save location...')
      console.log(this.download)
    },
  },
  computed: {
    fileSize() {
      return formatSize(this.download.file_size_bytes)
    },
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100
    },
    saveFileDisabled() {
      // This complicated algorithm ...
      // If any conditions are not met, don't enable file save.

      // To have the ability to save a file:
      // Must have the "media_store" section of settings
      if (!_.has(this.settings, 'media_store')) return true

      // Must have packet.media_type in mediaTypeToStoreMap
      if (!_.has(mediaTypeToStoreMap, this.download.packet.media_type)) return true

      // Must have the mapped media store in settings.media_store
      const mediaStore = mediaTypeToStoreMap[this.download.packet.media_type]
      if (!_.has(this.settings.media_store, mediaStore)) return true

      // settings.media_store[mediaStore][] cannot be emnpty
      if (0 >= this.settings.media_store[mediaStore].length) return true

      // None of the conditions failed, so file can be saved.
      return false
    },
  },
}
</script>
