<template>
  <div class="flex flex-col gap-1">
    <div
      class="flex flex-col w-full leading-1.5 p-4 border-gray-200 bg-gray-100 rounded-e-xl rounded-es-xl dark:bg-gray-700"
    >
      <div class="flex items-start my-2.5 bg-gray-50 dark:bg-gray-600 rounded-xl p-2">
        <div class="me-2">
          <span
            class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white pb-2"
          >
            <file-icon :extension="ext" /> {{ packet.fileName }}
          </span>
          <span class="flex text-xs font-normal text-gray-500 dark:text-gray-400 gap-2">
            {{ packet.gets }} gets
            <svg
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
              class="self-center"
              width="3"
              height="4"
              viewBox="0 0 3 4"
              fill="none"
            >
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280" />
            </svg>
            {{ packet.size }}
            <svg
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
              class="self-center"
              width="3"
              height="4"
              viewBox="0 0 3 4"
              fill="none"
            >
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280" />
            </svg>
            {{ ext }}
            <svg
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
              class="self-center"
              width="3"
              height="4"
              viewBox="0 0 3 4"
              fill="none"
            >
              <circle cx="1.5" cy="2" r="1.5" fill="#6B7280" />
            </svg>
            #{{ packet.num }}
          </span>
        </div>
        <div class="inline-flex self-center items-center">
          <button
            type="button"
            tabindex="-1"
            :class="downloadButtonClass"
            class="inline-flex self-center items-center p-2 text-sm font-medium text-center rounded-lg focus:outline-none text-gray-900 dark:text-white"
            @click="xdccSend()"
            :disabled="isDownloadLocked === true"
          >
            <locked-icon v-if="isDownloadLocked === true" height="8" />
            <downloading-icon v-if="isDownloadLocked === false" height="8" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { trim } from '@/funcs';
import FileIcon from '@/Components/FileIcon.vue';
import DownloadingIcon from '@/Components/DownloadingIcon.vue';
import LockedIcon from '@/Components/LockedIcon.vue';

export default {
  inheritAttrs: false,
  components: {
    DownloadingIcon,
    FileIcon,
    LockedIcon,
  },
  props: {
    packet: Object,
    content: String,
    isDownloadLocked: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      ext: this.getFileExtension(),
    };
  },
  methods: {
    getFileExtension() {
      let ext = '';
      const fileName = this.packet.fileName;
      const lastIndex = fileName.lastIndexOf('.');
      if (0 <= lastIndex) {
        ext = trim(fileName.substring(lastIndex + 1));
      }

      return ext;
    },
    xdccSend() {
      if (!this.isDownloadLocked) {
        this.$emit('call:xdccSend', this.packet);
      }
    },
  },
  computed: {
    downloadButtonClass() {
      const normal = [
        'bg-gray-50',
        'hover:bg-gray-100',
        'focus:ring-4',
        'focus:ring-gray-50',
        'dark:bg-gray-600',
        'dark:hover:bg-gray-500',
        'dark:focus:ring-gray-600',
      ];

      const disabled = ['bg-gray-200', 'focus:ring-0', 'dark:bg-gray-400'];

      return this.isDownloadLocked ? disabled : normal;
    },
  },
  emits: ['call:xdccSend'],
};
</script>
