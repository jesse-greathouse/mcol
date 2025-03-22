<template>
    <div class="py-6">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">
          <Head title="Download" />
          <section class="bg-white dark:bg-gray-900">
            <div class="py-4 px-4 w-full">
                <div class="flex flex-wrap gap-4 justify-start">
                <div
                    v-for="(card, fileName) in downloadCards"
                    :key="fileName"
                    class="bg-gray-50 border border-gray-200 rounded-lg p-2 shadow-sm dark:bg-gray-800 dark:border-gray-700 transition-opacity duration-500 ease-in-out"
                    :style="{
                        width: '800px',
                        height: '200px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    }" >
                    <div
                        :ref="`download-card-${fileName}`"
                        v-html=card ></div>
                    </div>
                </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { toRaw } from 'vue';
  import { Head, Link } from '@inertiajs/vue3'
  import { initFlowbite } from 'flowbite'
  import Multiselect from '@vueform/multiselect'
  import { has } from '@/funcs'

  // local imports
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { fetchDownloadCard } from '@/Clients/download-card'
  import { fetchLocks } from '@/Clients/browse'
  import { fetchDownloadQueue } from '@/Clients/download-queue'
  import { makeDownloadIndexFromQueue } from '@/download-queue'
  import { removeCompleted, requestRemove, requestCancel } from '@/Clients/rpc'
  import { saveDownloadDestination } from '@/Clients/download-destination'

  const refreshDashboardInterval = 10000 // Check download queue every 10 seconds.
  let refreshDashboardId
  const refreshDashboard = function () {
    clearTimeout(refreshDashboardId)
  }

  const locksInterval = 10000 // locks every 10 seconds.
  let locksTimeoutId;
  const clearLocksInterval = function () {
    clearTimeout(locksTimeoutId)
  }

  export default {
    components: {
      Head,
      Link,
      Multiselect,
    },
    layout: AppLayout,
    props: {
      queue: Object,
      settings: Object,
      networks: Array,
      locks: Array,
    },
    data() {
        return {
            downloadLocks: this.locks,
            downloadQueue: this.queue,
            downloads: {},
            downloadCards: {},
        }
    },
    mounted() {
        initFlowbite()
        this.refreshDashboard()
        this.checkLocks()
    },
    watch: {
        downloadQueue: {
            deep: true,
            handler() {
                this.downloads = makeDownloadIndexFromQueue(this.downloadQueue)
                this.updateDownloadCards()
            },
        },
        downloadCards: {
            handler(newDownloadCards, oldDownloadCards) {
                this.transitionCards(newDownloadCards, oldDownloadCards)
            },
            immediate: true,
        },
    },
    methods: {
      updateDownloadCards() {
        // deleteManifest is to remove elements from downloadCards that are no longer in the downloadQueue
        let deleteManifest = Object.keys(this.downloadCards)
        let fileNameIndex;

        Object.keys(this.downloads).forEach((fileName) => {
            // Remove from the delete manifest.
            fileNameIndex = deleteManifest.indexOf(fileName)
            if (-1 < fileNameIndex) {
                deleteManifest.splice(fileNameIndex, 1)
            }

            if (!has(this.downloadCards, fileName)) {
                this.downloadCards[fileName] = null
            }

            fetchDownloadCard(fileName).then((svg) => {
                this.downloadCards[fileName] = svg
            }).catch((error) => {
                console.error('fetchDownloadCard Error:', error)
            })
        })

        // Remove any cards that are no longer in the download queue.
        deleteManifest.forEach((fileName) =>{
            // Remove from the delete manifest.
            if (this.downloadCards[fileName]) {
                delete this.downloadCards[fileName]
            }
        })
      },
      transitionCards(newDownloadCards, oldDownloadCards) {
        if (newDownloadCards === oldDownloadCards) return;

        // Update new and existing cards.
        Object.keys(newDownloadCards).forEach(fileName => {
            const newCardList = toRaw(newDownloadCards[fileName])
            const oldCardList = toRaw(oldDownloadCards[fileName])

            if (newCardList === oldCardList) return

            svg = newDownloadCards[fileName]

            if (null === svg) return

            this.transitionCard(fileName, svg);
        });
      },
      transitionCard(key, svg) {
        const ref = this.$refs[`download-card-${key}`]

        if (ref) {
            ref[0].innerHTML = svg;
        }
      },
      async fetchDownloadCard(fileName = '') {
        const {data, error} = await fetchDownloadCard(fileName)
        if (null === error) {
            return data
        } else {
          console.log(error)
        }
      },
      async refreshDashboard() {
        await this.fetchDownloadQueue()
        refreshDashboard()
        refreshDashboardId = setTimeout(this.refreshDashboard, refreshDashboardInterval)
      },
      async checkLocks() {
        await this.fetchLocks()
        clearLocksInterval()
        locksTimeoutId = setTimeout(this.checkLocks, locksInterval)
      },
      async fetchDownloadQueue() {
        const {data, error} = await fetchDownloadQueue()
        if (null === error) {
          this.downloadQueue = data
        } else {
          console.log(error)
        }
      },
      async fetchLocks(packetList) {
        const { data, error } = await fetchLocks(packetList)

        if (null === error) {
            const { locks } = data
            this.downloadLocks = locks

            if (locks.length <= 0) {
                clearLocksInterval()
            }
        }
      },
      async saveDownloadDestination(download, uri) {
        const body = {
            destination_dir: uri,
            download: download.id
        }

        // Use put instead of post if dd already exists.
        if (null !== download.destination) {
            body.id = download.destination.id
        }

        const {error} = await saveDownloadDestination(body)

        if (null === error) {
            this.fetchDownloadQueue()
        }
      },
      async requestRemove(packetId) {
        const {data, error} = await requestRemove(packetId)

        if (null === error) {
            const fileName = data.result.packet.file_name
            const locksIndex = this.locks.indexOf(fileName)
            if (0 <= locksIndex) {
                delete this.locks[locksIndex]
            }

            if (has(this.queued, fileName)) {
                delete this.queued[fileName]
            }

            if (has(this.downloadQueue.queued, fileName)) {
                delete this.downloadQueue.queued[fileName]
            }
        }
      },
      async requestCancel(download) {
        const { error } = await requestCancel(download)

        if (null === error) {
            this.fetchLocks()
            this.fetchDownloadQueue()
        }
      },
      async removeCompleted(download) {
        const { error } = await removeCompleted(download)

        if (null === error) {
            this.fetchLocks()
            this.fetchDownloadQueue()
        }
      },
    },
  }
  </script>

<style> @import '@vueform/multiselect/themes/tailwind.css' </style>
