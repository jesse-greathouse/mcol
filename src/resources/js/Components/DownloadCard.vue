<template>
    <div
      :id="download.packet.file_name"
      class="relative bg-gray-50 border border-gray-200 rounded-lg p-2 shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-opacity duration-500 ease-in-out"
      :style="{
        width: '800px',
        height: '200px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
      }"
    >
      <div :ref="`download-card-${download.packet.file_name}`" v-html="svg"></div>

      <!-- Hover Buttons Panel -->
      <div class="absolute right-2 top-1/2 transform -translate-y-1/2 flex flex-col items-center space-y-2">
        <SaveDownloadButton
          context="card"
          :download="download"
          :settings="settings"
          @call:saveDownloadDestination="saveDownloadDestination"
        />
        <button
          type="button"
          class="text-white bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500 me-2"
          @click="$emit('call:requestRemove', download.packet.id)"
        >
          <svg class="fill-white w-6 h-6" viewBox="0 0 460.775 460.775" xmlns="http://www.w3.org/2000/svg">
            <path d="M285.08,230.397L456.218,59.27c6.076-6.077,6.076-15.911,0-21.986L423.511,4.565c-2.913-2.911-6.866-4.55-10.992-4.55c-4.127,0-8.08,1.639-10.993,4.55L230.388,175.705 59.25,4.565C56.337,1.654,52.384,0.015,48.258,0.015c-4.126,0-8.08,1.639-10.992,4.55L4.558,37.284c-6.077,6.075-6.077,15.909,0,21.986l171.138,171.128L4.575,401.505c-6.074,6.077-6.074,15.911,0,21.986l32.709,32.719c2.911,2.911,6.865,4.55,10.992,4.55c4.127,0,8.08-1.639,10.994-4.55l171.117-171.12l171.118,171.12c2.913,2.911,6.866,4.55,10.993,4.55c4.128,0,8.081-1.639,10.992-4.55l32.709-32.719c6.074-6.075,6.074-15.909,0-21.986L285.08,230.397z" />
          </svg>
          <span class="sr-only">Remove Queue</span>
        </button>
      </div>
    </div>
  </template>

  <script>
  import SaveDownloadButton from '@/Components/SaveDownloadButton.vue';
  import DirectoryBrowser from '@/Components/DirectoryBrowser.vue';

  export default {
    components: {
      SaveDownloadButton,
      DirectoryBrowser,
    },
    props: {
      download: Object,
      svg: String,
      settings: Object,
    },
    data() {
      return {
        destinationForm: {
          root: '',
          uri: '',
        },
      }
    },
    mounted() {
    },
    methods: {
      toggleBrowser() {
        if (this.modal.isHidden()) {
          this.modal.show()
        } else {
          this.modal.hide()
        }
      },
      saveDownloadDestination(download, uri) {
        this.$emit('call:saveDownloadDestination', download, uri)
      },
    },
    emits: ['call:saveDownloadDestination', 'call:requestRemove'],
  };
  </script>
