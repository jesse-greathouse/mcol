<template>
    <div
    :id="download.packet.file_name"
        class="relative z-10 bg-gray-50 border border-gray-200 rounded-lg p-2 shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-opacity duration-500 ease-in-out"
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
        <save-download-button
            context="download-card"
            :download="download"
            :settings="settings"
            @call:saveDownloadDestination="saveDownloadDestination" />

        <!-- Queued State -->
        <button v-if="isQueued" ref="remove" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
            :disabled="cancelDisabled"
            @click="remove()" >
                <cancel-icon />
                <span class="sr-only">Remove Queue</span>
        </button>

        <!-- Incomplete State -->
        <button v-if="isDownloading" ref="cancel" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
            :disabled="cancelDisabled"
            @click="cancel()" >
                <cancel-icon />
                <span class="sr-only">Cancel Download</span>
        </button>

        <!-- Completed State -->
        <cancel-completed-download-button v-if="isCompleted"
            :disabled="cancelDisabled"
            :download="download"
            @call:removeCompleted="removeCompleted" />
      </div>
    </div>
  </template>

<script>
  import { DOWNLOAD_STATE_COMPLETED, DOWNLOAD_STATE_INCOMPLETE, DOWNLOAD_STATE_QUEUED } from '@/download-queue'
  import CancelIcon from '@/Components/CancelIcon.vue';
  import SaveDownloadButton from '@/Components/SaveDownloadButton.vue';
  import DirectoryBrowser from '@/Components/DirectoryBrowser.vue';
  import CancelCompletedDownloadButton from '@/Components/CancelCompletedDownloadButton.vue'


  export default {
    components: {
      CancelIcon,
      CancelCompletedDownloadButton,
      SaveDownloadButton,
      DirectoryBrowser,
    },
    props: {
      download: Object,
      settings: Object,
      svg: String,
    },
    data() {
        return {
            cancelDisabled: false,
        }
    },
    computed: {
      isCompleted() {
        return (this.download.status === DOWNLOAD_STATE_COMPLETED) ? true : false
      },
      isDownloading() {
        return (this.download.status === DOWNLOAD_STATE_INCOMPLETE) ? true : false
      },
      isQueued() {
        return (this.download.status === DOWNLOAD_STATE_QUEUED) ? true : false
      },
  },
    methods: {
      remove() {
        this.cancelDisabled = true
        this.$emit('call:requestRemove', this.download.packet.id)
      },
      removeCompleted() {
        this.cancelDisabled = true
        this.$emit('call:removeCompleted', this.download)
      },
      cancel() {
        this.cancelDisabled = true
        this.$emit('call:requestCancel', this.download)
      },
      saveDownloadDestination(download, uri) {
        this.$emit('call:saveDownloadDestination', download, uri)
      },
    },
    emits: ['call:saveDownloadDestination', 'call:requestRemove', 'call:requestCancel', 'call:removeCompleted'],
  };
  </script>
