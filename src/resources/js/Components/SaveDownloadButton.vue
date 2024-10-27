<template>
    <button ref="saveFile" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-blue-400 hover:bg-green-500 focus:ring-4 focus:outline-none focus:ring-blue-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-blue-400 dark:hover:bg-green-400 dark:focus:ring-blue-500"
            :class="saveClass"
            :disabled="disableSaveFile"
            @click="saveDownloadDestination()" >
        <svg class="stroke-white fill-none stroke-2 w-6 h-6" aria-hidden="true" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M9 13H15M15 13L13 11M15 13L13 15M12.0627 6.06274L11.9373 5.93726C11.5914 5.59135 11.4184 5.4184 11.2166 5.29472C11.0376 5.18506 10.8425 5.10425 10.6385 5.05526C10.4083 5 10.1637 5 9.67452 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V15.8C3 16.9201 3 17.4802 3.21799 17.908C3.40973 18.2843 3.71569 18.5903 4.09202 18.782C4.51984 19 5.07989 19 6.2 19H17.8C18.9201 19 19.4802 19 19.908 18.782C20.2843 18.5903 20.5903 18.2843 20.782 17.908C21 17.4802 21 16.9201 21 15.8V10.2C21 9.0799 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H14.3255C13.8363 7 13.5917 7 13.3615 6.94474C13.1575 6.89575 12.9624 6.81494 12.7834 6.70528C12.5816 6.5816 12.4086 6.40865 12.0627 6.06274Z" stroke-linecap="round" stroke-linejoin="round"></path> </g>
        </svg>
        <span class="sr-only">Save File</span>
    </button>

    <div data-popover class="invisible absolute inline-block w-auto rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-sm transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
        ref="destinationPop"
        :id="destinationPopId"
        role="tooltip"
    >
    <div class="p-3">
        <div class="flex flex-row p-3">
        <div>
            <p class="mb-1 text-base font-semibold leading-none text-gray-900 dark:text-white">
                <a href="#" class="hover:underline">Save File</a>
            </p>
        </div>
        </div>
        <div class="flex flex-row p-3">
        <div class="basis-1/3 px-3">
            <multiselect tabindex="-1" class="min-w-48 p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
                v-model="destinationForm.root"
                :options="destinationRoots"
                :canClear="false"
                ref="destinationRoot"
            />
        </div>
        <div class="basis-2/3">
            <input type="text" class="min-w-60 relative px-6 py-3 w-full rounded focus:shadow-outline"
                v-model="destinationForm.uri"
                ref="destinationUri"
            />
        </div>
        </div>
        <div class="flex flex-row p-3 justify-end">
        <div class="flex">
            <button type="button" class="focus:ring-4 focus:outline-none inline-flex items-center me-2 text-center text-white font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 bg-blue-400 hover:bg-blue-500 focus:ring-blue-200 font-medium rounded-lg text-sm p-2.5 dark:bg-blue-400 dark:hover:bg-blue-400 dark:focus:ring-blue-500"
                @click="toggleBrowser()">
                Browse Files
            </button>
            <button type="button" class="focus:ring-4 focus:outline-none inline-flex items-center me-2 text-center text-white font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 bg-green-400 hover:bg-green-500 focus:ring-green-200 font-medium rounded-lg text-sm p-2.5 dark:bg-green-400 dark:hover:bg-green-400 dark:focus:ring-green-500"
                @click="saveDownloadDestination()">
                Save
            </button>
        </div>
        </div>
    </div>
    <div data-popper-arrow></div>
    </div>

    <!--Modal -->
    <div tabindex="-1" aria-hidden="true" class="fixed hidden overflow-y-hidden overflow-x-hidden z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] h-5/6"
        ref="directoryBrowserModal"
        :id="modalId"
        >
        <div class="relative p-4 w-full max-w-5xl h-full">
            <!-- Modal content -->
            <directory-browser
                ref="directoryBrowser"
                :settings="settings"
                :mediaType="download.media_type"
                :root="destinationForm.root"
                :uri="destinationForm.uri"
                @call:toggleBrowser="toggleBrowser"

            />
        </div>
    </div>
</template>

<script>
import _ from 'lodash'
import { Popover, Modal } from 'flowbite';
import {
    shouldDisableFileSave,
    suggestDownloadDestination,
    getDownloadDestinationRoots,
    splitDestinationDir
} from '@/download-queue'
import DirectoryBrowser from '@/Components/DirectoryBrowser.vue'
import DownloadingIcon from '@/Components/DownloadingIcon.vue'
import Multiselect from '@vueform/multiselect'

export default {
  components: {
    DirectoryBrowser,
    DownloadingIcon,
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
      mediaType: this.download.media_type,
      modalId: `file-browser-modal-${this.download.id}`,
      modal: null,
      modalDisplayUri: null,
    }
  },
  mounted() {
    this.disableSaveFile = (shouldDisableFileSave(this.download, this.settings)) ? true : false

    if (!this.disableSaveFile) {
        this.setDownloadDestinationForm()
    }

    this.destinationPop = new Popover(this.$refs.destinationPop, this.$refs.saveFile, {placement: 'left'}, {
        id: this.destinationPopId,
        override: true,
    })

    const modalOptions = {
        placement: 'center-center',
        backdrop: 'dynamic',
        backdropClasses:
            'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
        closable: true,
        onShow: () => {
            this.$refs.directoryBrowser.refreshDir()
        },
    }

    this.modal = new Modal(this.$refs.directoryBrowserModal, modalOptions, {
        id: this.modalId,
        override: true
    })
  },
  watch: {
    disableSaveFile: {
      handler: function () {
        if (!this.disableSaveFile) {
            this.setDownloadDestinationForm()
        }
      },
    },
  },
  computed: {
    saveClass() {
      let color = 'blue'
      if (null !== this.download.destination) {
        switch(this.download.destination.status) {
            case 'incomplete':
                color = 'amber'
                this.disableSaveFile = true
                break;
            case 'completed':
                color = 'gray'
                this.disableSaveFile = true
                break;
            default:
                color = 'green'
                break;
        }
      }

      return [
        `bg-${color}-400`,
        `dark:bg-${color}-500`,
        `focus:ring-${color}-200`,
        `dark:focus:ring-${color}-400`
      ]
    },
  },
  methods: {
    setDownloadDestinationForm() {
      this.destinationRoots = getDownloadDestinationRoots(this.download, this.settings)
      const {root, uri} = splitDestinationDir(this.download.destination, this.destinationRoots)

      if (null !== root && null !== uri) {
        this.destinationForm.root = root
        this.destinationForm.uri = uri
      } else {
        this.destinationForm.root = this.destinationRoots[0]
        this.destinationForm.uri = suggestDownloadDestination(this.download)
      }
    },
    saveDownloadDestination() {
      const uri = this.destinationForm.root + this.destinationForm.uri
      this.$emit('call:saveDownloadDestination', this.download, uri)
    },
    toggleBrowser(uri) {
        if (this.modal.isHidden()) {
            this.modal.show()
        } else {
            console.log(uri)
            this.modal.hide()
        }
    },
  },
  emits: ['call:saveDownloadDestination'],
}
</script>
