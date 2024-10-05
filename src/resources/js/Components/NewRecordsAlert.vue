<script setup>
  import { FwbAlert } from 'flowbite-vue'
</script>

<template>
  <fwb-alert type="success">
    <template #icon>
      <svg
        class="flex-shrink-0 w-4 h-4 mr-2"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
      </svg>
      <span class="sr-only">Info</span>
    </template>
    <template #title>
      <h3 class="text-lg font-medium">
        New Packets Found
      </h3>
    </template>
    <template #default="{ onCloseClick }">
      <div class="mt-2 mb-4 text-sm">
        {{ count }} new Packets have been found.
      </div>
      <div class="flex">
        <button
          type="button"
          class="text-white bg-blue-800 hover:bg-blue-900 focus:ring-4 focus:outline-none focus:ring-blue-200 font-medium rounded-lg text-xs px-3 py-1.5 mr-2 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
          @click="$emit('refresh')"
        >
          <svg
            class="-ml-0.5 mr-2 h-3 w-3"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="currentColor"
            viewBox="0 0 20 14"
          >
            <path d="M10 0C4.612 0 0 5.336 0 7c0 1.742 3.546 7 10 7 6.454 0 10-5.258 10-7 0-1.664-4.612-7-10-7Zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z" />
          </svg>
          View more
        </button>
        <button
          type="button"
          class="text-blue-800 bg-transparent border border-blue-800 hover:bg-blue-900 hover:text-white focus:ring-4 focus:outline-none focus:ring-blue-200 font-medium rounded-lg text-xs px-3 py-1.5 text-center dark:hover:bg-blue-600 dark:border-blue-600 dark:text-blue-400 dark:hover:text-white dark:focus:ring-blue-800"
          data-dismiss-target="#alert-additional-content-1"
          aria-label="Close"
          @click="onCloseClick"
        >
          Dismiss
        </button>
      </div>
    </template>
  </fwb-alert>
</template>
  
<script>
export default {
  props: {
    count: {
      type: Number,
      default: 0,
    },
  },
  methods: {
    close: () => {
      this.$emit('close')
    }
  },
  emits: ['refresh'],
}
</script>
