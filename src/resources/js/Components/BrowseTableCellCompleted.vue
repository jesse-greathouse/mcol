<template>
  <td class="border-t bg-gray-100 flex items-center place-content-center">
    <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
      <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
        <completed-icon />
      </div>
      <div class="col-span-7 py-6 px-0" tabindex="-1">
        <fwb-progress :progress="100" color="green" :label="packet.file_name" />
      </div>
      <div class="col-span-3 py-6 px-0 place-self-center" tabindex="-1">
        <save-download-button
          context="browse-table"
          :download="download"
          :settings="settings"
          @call:saveDownloadDestination="saveDownloadDestination"
        />
        <cancel-completed-download-button :download="download" @call:removeCompleted="remove" />
      </div>
    </div>
  </td>
</template>

<script>
import { FwbProgress } from 'flowbite-vue';
import CompletedIcon from '@/Components/CompletedIcon.vue';
import SaveDownloadButton from '@/Components/SaveDownloadButton.vue';
import CancelCompletedDownloadButton from '@/Components/CancelCompletedDownloadButton.vue';

export default {
  components: {
    CancelCompletedDownloadButton,
    CompletedIcon,
    FwbProgress,
    SaveDownloadButton,
  },
  props: {
    download: Object,
    packet: Object,
    settings: Object,
  },
  data() {
    return {
      confirmRemoveId: `table-popup-remove-confirm-${this.download.id}`,
      cancelDisabled: false,
    };
  },
  methods: {
    saveDownloadDestination(download, uri) {
      this.$emit('call:saveDownloadDestination', download, uri);
    },
    remove() {
      this.cancelDisabled = true;
      this.$emit('call:removeCompleted', this.download);
    },
  },
  emits: ['call:removeCompleted', 'call:saveDownloadDestination'],
};
</script>
