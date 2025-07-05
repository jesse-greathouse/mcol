<template>
  <div class="flex flex-col gap-1">
    <div
      class="flex flex-col w-full leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700"
    >
      <div class="flex items-start my-2.5 bg-gray-50 dark:bg-gray-600 rounded-xl p-2">
        <div class="me-2 width-full">
          <download-queue-completed
            v-if="isCompleted"
            :download="download"
            :settings="settings"
            @call:removeCompleted="removeCompleted"
            @call:saveDownloadDestination="saveDownloadDestination"
          />
          <download-queue-downloading
            v-if="isDownloading"
            :download="download"
            :settings="settings"
            @call:requestCancel="requestCancel"
            @call:saveDownloadDestination="saveDownloadDestination"
          />
          <download-queue-queued
            v-if="isQueued"
            :download="download"
            :settings="settings"
            @call:requestRemove="requestRemove"
            @call:saveDownloadDestination="saveDownloadDestination"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { trim } from '@/funcs';
import {
  DOWNLOAD_STATE_COMPLETED,
  DOWNLOAD_STATE_INCOMPLETE,
  DOWNLOAD_STATE_QUEUED,
} from '@/download-queue';
import DownloadQueueDownloading from '@/Components/DownloadQueueDownloading.vue';
import DownloadQueueCompleted from '@/Components/DownloadQueueCompleted.vue';
import DownloadQueueQueued from '@/Components/DownloadQueueQueued.vue';

export default {
  inheritAttrs: false,
  components: {
    DownloadQueueDownloading,
    DownloadQueueCompleted,
    DownloadQueueQueued,
  },
  props: {
    settings: Object,
    download: Object,
    content: String,
  },
  data() {
    return {
      ext: this.getFileExtension(),
    };
  },
  computed: {
    isCompleted() {
      return this.download.status === DOWNLOAD_STATE_COMPLETED ? true : false;
    },
    isDownloading() {
      return this.download.status === DOWNLOAD_STATE_INCOMPLETE ? true : false;
    },
    isQueued() {
      return this.download.status === DOWNLOAD_STATE_QUEUED ? true : false;
    },
  },
  methods: {
    getFileExtension() {
      let ext = '';
      const fileName = this.download.file_name;
      const lastIndex = fileName.lastIndexOf('.');
      if (0 <= lastIndex) {
        ext = trim(fileName.substring(lastIndex + 1));
      }

      return ext;
    },
    removeCompleted(download) {
      this.$emit('call:removeCompleted', download);
    },
    requestCancel(download) {
      this.$emit('call:requestCancel', download);
    },
    requestRemove(packetId) {
      this.$emit('call:requestRemove', packetId);
    },
    saveDownloadDestination(download, uri) {
      this.$emit('call:saveDownloadDestination', download, uri);
    },
  },
  emits: [
    'call:requestCancel',
    'call:requestRemove',
    'call:removeCompleted',
    'call:saveDownloadDestination',
  ],
};
</script>
