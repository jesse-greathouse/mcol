<template>
    <div class="flex flex-col gap-1">
        <div class="flex flex-col w-full leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700">
            <div class="flex items-start my-2.5 bg-gray-50 dark:bg-gray-600 rounded-xl p-2">
                <div class="me-2 width-full">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white pb-2">
                        <file-icon :extension="ext" /> {{ packet.fileName }}
                    </div>
                    <div class="flex items-center width-full">
                        <download-queue-completed v-if="isCompleted"
                            :download="download"
                            :settings="settings"
                            @call:removeCompleted="removeCompleted"
                            @call:saveDownloadDestination="saveDownloadDestination" />
                        <download-queue-downloading v-if="isDownloading"
                            :download="download"
                            :settings="settings"
                            @call:requestCancel="requestCancel"
                            @call:saveDownloadDestination="saveDownloadDestination" />
                        <download-queue-queued v-if="isQueued"
                            :download="download"
                            :settings="settings"
                            @call:requestRemove="requestRemove"
                            @call:saveDownloadDestination="saveDownloadDestination" />
                    </div>
                </div>
                <div class="inline-flex self-center items-center">
                <button type="button" tabindex="-1" class="inline-flex self-center items-center p-2 text-sm font-medium text-center text-gray-900 bg-gray-50 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none dark:text-white focus:ring-gray-50 dark:bg-gray-600 dark:hover:bg-gray-500 dark:focus:ring-gray-600"
                    @click="xdccSend()" >
                    <svg class="w-4 h-4 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"/>
                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                    </svg>
                </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { trim } from '@/funcs'
import { DOWNLOAD_STATE_COMPELTED, DOWNLOAD_STATE_INCOMPLETE, DOWNLOAD_STATE_QUEUED } from '@/download-queue'
import FileIcon from '@/Components/FileIcon.vue'
import DownloadQueueDownloading from '@/Components/DownloadQueueDownloading.vue'
import DownloadQueueCompleted from '@/Components/DownloadQueueCompleted.vue'
import DownloadQueueQueued from '@/Components/DownloadQueueQueued.vue'

export default {
  components: {
    FileIcon,
    DownloadQueueDownloading,
    DownloadQueueCompleted,
    DownloadQueueQueued,
  },
  props: {
    download: Object,
    content: String,
  },
  data() {
    return {
        ext: this.getFileExtension(),
    }
  },
  computed: {
    isCompleted() {
      return (this.download.status === DOWNLOAD_STATE_COMPELTED) ? true : false
    },
    isDownloading() {
      return (this.download.status === DOWNLOAD_STATE_INCOMPLETE) ? true : false
    },
    isQueued() {
      return (this.download.status === DOWNLOAD_STATE_QUEUED) ? true : false
    }
  },
  methods: {
    getFileExtension() {
        let ext = '';
        const fileName = this.download.packet.fileName
        const lastIndex = fileName.lastIndexOf('.')
        if (0 <= lastIndex) {
            ext = trim(fileName.substring(lastIndex + 1))
        }

        return ext
    },
    removeCompleted(download) {
      this.$emit('call:removeCompleted', download)
    },
    requestCancel(download) {
      this.$emit('call:requestCancel', download)
    },
    requestRemove(packetId) {
      this.$emit('call:requestRemove', packetId)
    },
    saveDownloadDestination(download, uri) {
      this.$emit('call:saveDownloadDestination', download, uri)
    },
  },
  emits: ['call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
