<template>
  <td class="border-t bg-gray-100">
    <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
      <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
        <downloading-icon />
      </div>
      <div class="col-span-7 py-6 px-0" tabindex="-1">
        <fwb-progress :progress="progress" :label="packet.file_name" />
      </div>
      <div class="col-span-3 py-6 px-0 place-self-center" tabindex="-1">
        <save-download-button
          context="browse-table"
          :download="download"
          :settings="settings"
          @call:saveDownloadDestination="saveDownloadDestination"
        />
        <button
          ref="cancel"
          type="button"
          class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
          :disabled="cancelDisabled"
          @click="cancel()"
        >
          <cancel-icon />
        </button>
      </div>
    </div>
  </td>
</template>

<script>
import { FwbProgress } from 'flowbite-vue';
import DownloadingIcon from '@/Components/DownloadingIcon.vue';
import CancelIcon from '@/Components/CancelIcon.vue';
import SaveDownloadButton from '@/Components/SaveDownloadButton.vue';

export default {
  components: {
    CancelIcon,
    DownloadingIcon,
    FwbProgress,
    SaveDownloadButton,
  },
  props: {
    download: Object,
    settings: Object,
    packet: Object,
  },
  data() {
    return {
      cancelDisabled: false,
    };
  },
  computed: {
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100;
    },
  },
  methods: {
    cancel() {
      this.cancelDisabled = true;
      this.$emit('call:requestCancel', this.download);
    },
    saveDownloadDestination(download, uri) {
      this.$emit('call:saveDownloadDestination', download, uri);
    },
  },
  emits: ['call:requestCancel', 'call:saveDownloadDestination'],
};
</script>
