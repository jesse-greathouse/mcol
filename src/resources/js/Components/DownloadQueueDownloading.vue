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
        <button ref="saveFile" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-blue-400 hover:bg-blue-500 focus:ring-4 focus:outline-none focus:ring-blue-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-blue-400 dark:hover:bg-blue-400 dark:focus:ring-blue-500"
        :disabled="disableSaveFile"
        @click="saveFile()" >
        <svg class="stroke-white fill-none stroke-2 w-6 h-6" aria-hidden="true" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 13H15M15 13L13 11M15 13L13 15M12.0627 6.06274L11.9373 5.93726C11.5914 5.59135 11.4184 5.4184 11.2166 5.29472C11.0376 5.18506 10.8425 5.10425 10.6385 5.05526C10.4083 5 10.1637 5 9.67452 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V10.2C21 9.0799 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H14.3255C13.8363 7 13.5917 7 13.3615 6.94474C13.1575 6.89575 12.9624 6.81494 12.7834 6.70528C12.5816 6.5816 12.4086 6.40865 12.0627 6.06274Z" stroke-linecap="round" stroke-linejoin="round"></path> </g>
        </svg>
        <span class="sr-only">Save File</span>
      </button>
      <button ref="cancel" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
        :disabled="cancelDisabled"
        @click="cancel()" >
        <svg class="fill-white w-6 h-6" aria-hidden="true" viewBox="0 0 460.775 460.775" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M285.08,230.397L456.218,59.27c6.076-6.077,6.076-15.911,0-21.986L423.511,4.565c-2.913-2.911-6.866-4.55-10.992-4.55 c-4.127,0-8.08,1.639-10.993,4.55l-171.138,171.14L59.25,4.565c-2.913-2.911-6.866-4.55-10.993-4.55 c-4.126,0-8.08,1.639-10.992,4.55L4.558,37.284c-6.077,6.075-6.077,15.909,0,21.986l171.138,171.128L4.575,401.505 c-6.074,6.077-6.074,15.911,0,21.986l32.709,32.719c2.911,2.911,6.865,4.55,10.992,4.55c4.127,0,8.08-1.639,10.994-4.55 l171.117-171.12l171.118,171.12c2.913,2.911,6.866,4.55,10.993,4.55c4.128,0,8.081-1.639,10.992-4.55l32.709-32.719 c6.074-6.075,6.074-15.909,0-21.986L285.08,230.397z"></path> </g>
        </svg>
        <span class="sr-only">Cancel Download</span>
      </button>
    </div>
  </div>

  <div data-popover class="invisible absolute inline-block w-auto rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-sm transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
      ref="destinationPop"
      :id="destinationPopId"
      role="tooltip"
  >
    <div class="p-3">
      <div class="flex flex-row">
        <div class="basis-1/3">
            <a href="#" class="block p-2 bg-gray-100 rounded-lg dark:bg-gray-700">
            <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/logo.svg" alt="Flowbite logo">
        </a>
        </div>
        <div class="basis-2/3">
          <p class="mb-1 text-base font-semibold leading-none text-gray-900 dark:text-white">
              <a href="#" class="hover:underline">Save File</a>
          </p>
        </div>
        <div class="flex flex-row">
          <div class="basis-1/2">
            <multiselect tabindex="-1" class="p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
              v-model="destinationForm.root"
              :options="destinationRoots"
              ref="destinationRoot"
            />
          </div>
          <div class="basis-1/2">
            <input type="text" class="relative px-6 py-3 w-full rounded focus:shadow-outline"
              v-model="destinationForm.uri"
              ref="destinationUri"
            />
          </div>
          <div class="flex flex-row">
            <div class="basis-1/2">
              <button type="button" class="inline-flex items-center justify-center w-full px-5 py-2 me-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                Save Download Here
              </button>
            </div>
            <div class="basis-1/2">
              <button type="button" class="inline-flex items-center justify-center w-full px-5 py-2 me-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                Choose Another Destination
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div data-popper-arrow></div>
  </div>
</template>

<script>
import { Popover } from 'flowbite';
import { FwbProgress } from 'flowbite-vue'
import { formatSize } from '@/file-size'
import {
    shouldDisableFileSave,
    suggestDownloadDestination,
    getDownloadDestinationRoots
} from '@/download-queue'
import DownloadingIcon from '@/Components/DownloadingIcon.vue'
import Multiselect from '@vueform/multiselect'

export default {
  components: {
    DownloadingIcon,
    FwbProgress,
    Multiselect,
  },
  props: {
    download: Object,
    settings: Object,
  },
  data() {
    return {
      destinationPopId: `destination-pop-${this.download.id}`,
      destinationPop: null,
      destinationForm: {
        root: '',
        uri: '',
      },
      destination: this.download.destination,
      destinationRoots: [],
      disableSaveFile: true,
      cancelDisabled: false,
    }
  },
  mounted() {
    this.disableSaveFile = (shouldDisableFileSave(this.download, this.settings)) ? true : false

    if (!this.disableSaveFile) {
      this.destinationRoots = getDownloadDestinationRoots(this.download, this.settings)
      this.destinationForm.root = this.destinationRoots[0]
      this.destinationForm.uri = suggestDownloadDestination(this.download)
    }

    this.destinationPop = new Popover(this.$refs.destinationPop, this.$refs.saveFile, {placement: 'left'}, {
      id: this.destinationPopId,
      override: true,
    })
  },
  watch: {
    disableSaveFile: {
      handler: function () {
        if (!this.disableSaveFile) {
          this.destinationRoots = getDownloadDestinationRoots(this.download, this.settings)
          this.destinationForm.uri = suggestDownloadDestination(this.download)
        }
      },
    },
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
    saveFile() {
      console.log('picking save location...')
      console.log(this.download)
    },
    cancel() {
      this.cancelDisabled = true
      this.$emit('call:requestCancel', this.download)
    }
  },
  emits: ['call:requestCancel'],
}
</script>
