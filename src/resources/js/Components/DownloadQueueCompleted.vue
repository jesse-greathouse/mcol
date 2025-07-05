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
      <fwb-progress :progress="100" color="green" :label="download.packet.file_name" />
    </div>
    <div class="col-span-2 py-5 place-self-left" tabindex="-1">
      <p class="px-8 whitespace-nowrap">
        <span
          class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400"
        >
          {{ download.nick }}
        </span>
      </p>
    </div>
    <div class="py-5 place-self-center" tabindex="-1">
      <save-download-button
        context="queue-drawer"
        :download="download"
        :settings="settings"
        @call:saveDownloadDestination="saveDownloadDestination"
      />
      <button
        ref="cancel"
        type="button"
        class="text-white disabled:opacity-75 disabled:bg-gray-700 bg-red-400 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-red-200 font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-red-400 dark:hover:bg-red-400 dark:focus:ring-red-500"
        :disabled="cancelDisabled"
        @click="toggleModal()"
      >
        <svg
          class="fill-white w-6 h-6"
          aria-hidden="true"
          viewBox="0 0 460.775 460.775"
          version="1.1"
          xmlns="http://www.w3.org/2000/svg"
          xmlns:xlink="http://www.w3.org/1999/xlink"
        >
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
          <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
          <g id="SVGRepo_iconCarrier">
            <path
              d="M285.08,230.397L456.218,59.27c6.076-6.077,6.076-15.911,0-21.986L423.511,4.565c-2.913-2.911-6.866-4.55-10.992-4.55 c-4.127,0-8.08,1.639-10.993,4.55l-171.138,171.14L59.25,4.565c-2.913-2.911-6.866-4.55-10.993-4.55 c-4.126,0-8.08,1.639-10.992,4.55L4.558,37.284c-6.077,6.075-6.077,15.909,0,21.986l171.138,171.128L4.575,401.505 c-6.074,6.077-6.074,15.911,0,21.986l32.709,32.719c2.911,2.911,6.865,4.55,10.992,4.55c4.127,0,8.08-1.639,10.994-4.55 l171.117-171.12l171.118,171.12c2.913,2.911,6.866,4.55,10.993,4.55c4.128,0,8.081-1.639,10.992-4.55l32.709-32.719 c6.074-6.075,6.074-15.909,0-21.986L285.08,230.397z"
            ></path>
          </g>
        </svg>
        <span class="sr-only">Cancel Download</span>
      </button>
    </div>
  </div>

  <!--Modal -->
  <div
    :id="modalId"
    tabindex="-1"
    aria-hidden="true"
    class="fixed hidden overflow-y-hidden overflow-x-hidden z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] h-5/6"
    ref="directoryBrowserModal"
  >
    <div class="relative p-4 w-full max-w-md max-h-full">
      <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
        <button
          type="button"
          class="absolute top-3 end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg
            class="w-3 h-3"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 14 14"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
            />
          </svg>
          <span class="sr-only">Close modal</span>
        </button>
        <div class="p-4 md:p-5 text-center">
          <svg
            class="mx-auto mb-4 text-gray-400 w-12 h-12 dark:text-gray-200"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 20 20"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
            />
          </svg>
          <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
            Are you sure you want to delete this completed download.
          </h3>
          <button
            type="button"
            class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center"
            @click="remove()"
          >
            Yes, I'm sure
          </button>
          <button
            type="button"
            class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
          >
            No, cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'flowbite';
import { FwbProgress } from 'flowbite-vue';
import { formatSize } from '@/file-size';
import { shouldDisableFileSave } from '@/download-queue';
import CompletedIcon from '@/Components/CompletedIcon.vue';
import SaveDownloadButton from '@/Components/SaveDownloadButton.vue';

export default {
  components: {
    CompletedIcon,
    FwbProgress,
    SaveDownloadButton,
  },
  props: {
    download: Object,
    settings: Object,
  },
  data() {
    return {
      cancelDisabled: false,
      modalId: `popup-remove-confirm-${this.download.id}`,
      modal: null,
    };
  },
  mounted() {
    const modalOptions = {
      placement: 'center-center',
      backdrop: 'dynamic',
      backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-40',
      closable: true,
    };

    this.modal = new Modal(this.$refs.directoryBrowserModal, modalOptions, {
      id: this.modalId,
      override: true,
    });
  },
  computed: {
    fileSize() {
      return formatSize(this.download.file_size_bytes);
    },
    progress() {
      return (this.download.progress_bytes / this.download.file_size_bytes) * 100;
    },
    saveFileDisabled() {
      return shouldDisableFileSave(this.download, this.settings);
    },
  },
  methods: {
    saveDownloadDestination(download, uri) {
      this.$emit('call:saveDownloadDestination', download, uri);
    },
    remove() {
      this.cancelDisabled = true;
      this.toggleModal();
      this.$emit('call:removeCompleted', this.download);
    },
    toggleModal() {
      if (this.modal.isHidden()) {
        this.modal.show();
      } else {
        this.modal.hide();
      }
    },
  },
  emits: ['call:removeCompleted', 'call:saveDownloadDestination'],
};
</script>
