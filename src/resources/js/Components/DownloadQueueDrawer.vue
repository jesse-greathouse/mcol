<template>
  <!-- drawer component -->
  <div ref="queueDrawer" class="shadow-[0_30px_40px_15px_rgba(0,0,0,0.3)] fixed z-50 w-full overflow-y-auto bg-white border-t border-gray-200 rounded-t-lg dark:border-gray-700 dark:bg-gray-800 transition-transform translate-y-full bottom-0 left-0 right-0 top-1/2 bottom-[80px]"
       id="drawer-swipe"
       tabindex="-1" >
    <div ref="drawerHeader" class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
         data-drawer-toggle="drawer-swipe">
      <span class="absolute w-8 h-1 -translate-x-1/2 bg-gray-300 rounded-lg top-3 left-1/2 dark:bg-gray-600"></span>
      <h5 id="drawer-swipe-label" class="font-semibold text-slate-600 inline-flex items-center text-base text-gray-500 dark:text-gray-400 font-medium text-nowrap">
        <svg viewBox="0 0 24 24" class="mr-4 fill-sky-400 h-8" xmlns="http://www.w3.org/2000/svg">
          <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
          <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
          <g id="SVGRepo_iconCarrier">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12ZM12 6.25C12.4142 6.25 12.75 6.58579 12.75 7V12.1893L14.4697 10.4697C14.7626 10.1768 15.2374 10.1768 15.5303 10.4697C15.8232 10.7626 15.8232 11.2374 15.5303 11.5303L12.5303 14.5303C12.3897 14.671 12.1989 14.75 12 14.75C11.8011 14.75 11.6103 14.671 11.4697 14.5303L8.46967 11.5303C8.17678 11.2374 8.17678 10.7626 8.46967 10.4697C8.76256 10.1768 9.23744 10.1768 9.53033 10.4697L11.25 12.1893V7C11.25 6.58579 11.5858 6.25 12 6.25ZM8 16.25C7.58579 16.25 7.25 16.5858 7.25 17C7.25 17.4142 7.58579 17.75 8 17.75H16C16.4142 17.75 16.75 17.4142 16.75 17C16.75 16.5858 16.4142 16.25 16 16.25H8Z"></path>
          </g>
        </svg>
        Download Queue
      </h5>
    </div>
    <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-1">
      <div v-if="hasCompelted" class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
          <div class="font-semibold text-left text-gray-400 dark:text-gray-400">Completed</div>
          <div v-for="download in queue.completed" :key="`download-${download.id}`" class="width-full">
            <download-queue-completed :download="download" :settings="settings" @call:removeCompleted="removeCompleted" />
          </div>
      </div>
      <div v-if="hasDownloading" class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
          <div class="font-semibold text-left text-gray-400 dark:text-gray-400">Downloading</div>
          <div v-for="download in queue.incomplete" :key="`download-${download.id}`" class="width-full">
            <download-queue-downloading :download="download" :settings="settings" @call:requestCancel="requestCancel" />
          </div>
      </div>
      <div v-if="hasQueued" class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700">
          <div class="font-semibold text-left text-gray-400 dark:text-gray-400">Queued</div>
          <div v-for="download in queue.queued" :key="`download-${download.id}`" class="width-full">
            <download-queue-queued :download="download" :settings="settings" @call:requestRemove="requestRemove" />
          </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Drawer } from 'flowbite'
import DownloadQueueDownloading from '@/Components/DownloadQueueDownloading.vue'
import DownloadQueueCompleted from '@/Components/DownloadQueueCompleted.vue'
import DownloadQueueQueued from '@/Components/DownloadQueueQueued.vue'

export default {
  components: {
    DownloadQueueDownloading,
    DownloadQueueCompleted,
    DownloadQueueQueued,
  },
  props: {
    queue: Object,
    settings: Object,
  },
  data() {
    return {
      queueDrawer: null,
    }
  },
  mounted() {
    const drawerOptions = {
        placement: 'bottom',
        bodyScrolling: true,
        edge: true,
        edgeOffset: 'bottom-[80px]',
        backdrop: true,
        backdropClasses:
        'bg-gradient-to-t from-slate-500/50 from-40% via-slate-300/40 via-60% to-sky-300/01 to-100% fixed inset-0',
        //'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30',
    }

    const instanceOptions = {
      id: 'drawer-swipe',
      override: true
    };

    this.queueDrawer = new Drawer(this.$refs.queueDrawer, drawerOptions, instanceOptions)
  },
  computed: {
    hasCompelted() {
      return (0 < this.queue.completed.length) ? true : false
    },
    hasDownloading() {
      return (0 < this.queue.incomplete.length) ? true : false
    },
    hasQueued() {
      return (0 < this.queue.queued.length) ? true : false
    }
  },
  methods: {
    toggle() {
      this.queueDrawer.toggle()
    },
    isVisible() {
      return this.queueDrawer.isVisible()
    },
    hide() {
      this.queueDrawer.hide()
    },
    show() {
      this.queueDrawer.show()
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
  },
  emits: ['call:requestCancel', 'call:requestRemove', 'call:removeCompleted'],
}
</script>
