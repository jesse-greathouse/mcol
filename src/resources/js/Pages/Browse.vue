<template>
  <div class="py-12">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">
        <Head title="Browse" />
        <div class="flex items-start justify-start mb-4">
          <div class="relative flex w-1/5 min-w-72 m-0 mr-4">
            <search-filter :model="form.search_string" class="flex w-full" @update:searchString="updateSearchString" @reset="reset" />
            <div class="absolute inset-y-0 start-16 flex items-center ps-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
              </svg>
            </div>
            <button v-if="searchStringActive" type="button" @click="resetSearchString" class="text-white absolute end-2 bottom-2 bg-slate-200 hover:bg-slate-300 focus:ring-1 focus:outline-none focus:ring-slate-50 font-medium rounded-lg text-sm px-4 py-2 dark:bg-slate-400 dark:hover:bg-slate-500 dark:focus:ring-slate-300">Clear</button>
          </div>
            <span class="mx-2 w-48 min-w-48">
              <Multiselect placeholder="Media" mode='multiple' @change="updateMediaType" class="p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
                :multipleLabel="mediaLabel"
                v-model="form.in_media_type"
                :options="media_types"
                ref="media"
              />
            </span>
            <span class="mx-2 w-48  min-w-48" v-if="filteringVideoFormat">
              <Multiselect  placeholder="Resolution" mode='multiple' @change="updateResolution" class="p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
                :multipleLabel="resolutionLabel"
                v-model="form.in_resolution"
                :options="resolutions"
                ref="resolution"
              />
            </span>

            <span class="flex items-start justify-start mx-2" v-if="filteringVideoFormat">
              <dynamic-range-filter ref="dynamic-ranges" @update:dynamicRanges="updateDynamicRanges"  @update:excludeDynamicRange="updateExcludeDynamicRanges"
                :exclude="exclude_dynamic_ranges"
                :in_dynamic_range="form.in_dynamic_range"
                :out_dynamic_range="form.out_dynamic_range"
                :dynamic_ranges="dynamic_ranges"
              />
            </span>
        </div>

        <div class="flex items-start justify-start mb-4">
            <span class="mx-2 w-48 min-w-48">
              <Multiselect placeholder="Networks" mode='multiple' @change="updateNetwork" class="p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
                :multipleLabel="networkLabel"
                v-model="form.in_networks"
                :options="networks"
                ref="networks"
              />
            </span>

          <language-filter ref="languages" class="w-full max-w-md" @update:languages="updateLanguages"  @update:excludeLanguage="updateExcludeLanguages"
            :exclude="exclude_languages"
            :in_language="form.in_language"
            :out_language="form.out_language"
            :languages="languages"
          />

          <div class="flex items-center mx-6 min-w-52">
            <vue-tailwind-datepicker ref="fromDate" v-model="form.start_date" as-single
              placeholder="Search From"
              :formatter="{
                date: 'YYYY-MM-DD',
                month: 'MMMM',
              }"
            />
          </div>
          <div v-show="showQueue" class="text-center">
            <button ref="downloadsButton" type="button" class="text-white bg-sky-400 hover:bg-sky-500 focus:ring-4 focus:ring-sky-300 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 dark:bg-sky-600 dark:hover:bg-sky-700 focus:outline-none dark:focus:ring-sky-800"
              @click="toggleQueue()" >
                Downloads
            </button>
          </div>
        </div>

        <div class="flex items-start justify-start mb-6">
          <pagination :links="pagination_nav" @call:navigateToPage="navigateToPage" />
        </div>

        <div class="bg-white rounded-md shadow overflow-x-auto">
          <table class="w-full whitespace-nowrap">
            <browse-table-head :currentOrder="form.order" :currentDirection="form.direction" @call:toggleSort="toggleSort" />
            <browse-table-body
              :packets="packets"
              :locks="locks"
              :completed="completed"
              :incomplete="incomplete"
              :queued="queued"
              :settings="settings"
              @call:requestDownload="requestDownload"
              @call:removeCompleted="removeCompleted"
              @call:requestRemove="requestRemove"
              @call:requestCancel="requestCancel"
              @call:saveDownloadDestination="saveDownloadDestination"
            />
          </table>
        </div>
        <pagination
            class="mt-6"
            :links="pagination_nav"
            @call:navigateToPage="navigateToPage"
        />
        <div v-show="showQueue">
          <download-queue-drawer ref="queue"
            :queue="downloadQueue"
            :settings="settings"
            @call:removeCompleted="removeCompleted"
            @call:requestRemove="requestRemove"
            @call:requestCancel="requestCancel"
            @call:saveDownloadDestination="saveDownloadDestination"
          />
        </div>
        <div v-if="0 < new_records_count" class="z-50 fixed bottom-6 right-6 shadow-lg">
            <new-records-alert ref="newRecordAlert" :count="new_records_count" @refresh="refresh" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import axios from 'axios';
  import { Head, Link } from '@inertiajs/vue3'
  import { initFlowbite } from 'flowbite'
  import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core'
  import Multiselect from '@vueform/multiselect'
  import VueTailwindDatepicker from "vue-tailwind-datepicker"
  // local imports
  import { fetchLocks } from '@/Clients/browse'
  import { saveDownloadDestination } from '@/Clients/download-destination'
  import { fetchDownloadQueue } from '@/Clients/download-queue'
  import { removeCompleted, requestDownload, requestRemove, requestCancel } from '@/Clients/rpc'
  import { formatDate } from '@/format'
  import { has, intersection, mapValues, pickBy, throttle } from '@/funcs'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import BrowseTableBody from '@/Components/BrowseTableBody.vue'
  import BrowseTableHead from '@/Components/BrowseTableHead.vue'
  import DownloadQueueDrawer from '@/Components/DownloadQueueDrawer.vue'
  import DynamicRangeFilter from '@/Components/DynamicRangeFilter.vue'
  import Icon from '@/Components/ApplicationMark.vue'
  import LanguageFilter from '@/Components/LanguageFilter.vue'
  import Pagination from '@/Components/Pagination.vue'
  import NewRecordsAlert from '@/Components/NewRecordsAlert.vue'
  import SearchFilter from '@/Components/SearchFilter.vue'
  import SortButtons from '@/Components/SortButtons.vue'

  const totalPacketsInterval = 60000; // Check total packets every 60 seconds.
  const locksInterval = 10000; // Check download locks every 10 seconds.
  const queueInterval = 10000; // Check download queue every 10 seconds.

  // Default sort direction per column.
  const defaultDirection = {
    created: 'desc',
    gets: 'desc',
    name: 'asc',
  }

  // Dynamically shrink the truncation of each option to make the label.
  const dynamicLabel = (selected, maxLen = 6) => {
    let sub = ''
    const abbr = []
    const values = []

    selected.forEach((option) => {
      values.push(option.value)
    })

    let trunc = maxLen - values.length
    if (1 > trunc) trunc = 1
    values.forEach((o) => {
      sub = o.substring(0, trunc)
      if (0 > abbr.indexOf(sub)) abbr.push(sub)
    })

    return abbr.sort().join(', ').substring(0, 16)
  }

  export default {
    components: {
      Head,
      Icon,
      Link,
      BrowseTableBody,
      BrowseTableHead,
      DownloadQueueDrawer,
      Pagination,
      DynamicRangeFilter,
      LanguageFilter,
      NewRecordsAlert,
      SearchFilter,
      SortButtons,
      Multiselect,
      VueTailwindDatepicker,
    },
    layout: AppLayout,
    props: {
      settings: Object,
      filters: Object,
      packets: Object,
      path: String,
      current_page: Number,
      from_record: Number,
      to_record: Number,
      per_page: Number,
      last_page: Number,
      total_packets: Number,
      first_page_url: String,
      last_page_url: String,
      prev_page_url: String,
      next_page_url: String,
      pagination_nav: Object,
      dynamic_ranges: Array,
      media_types: Array,
      networks: Array,
      resolutions: Array,
      languages: Array,
      packet_list: Array,
      locks: Array,
      queue: Object,
      completed: Object,
      incomplete: Object,
      queued: Object,
    },
    mounted() {
      if (this.shouldRefresh) {
        this.refresh()
      }

      initFlowbite()
      this.showQueue = (this.hasQueue()) ? true : false
      this.resetIntervals()
    },
    updated() {
      // In case we have arrived at a narrower result set
      // the page might be out of bounds from the previous result set
      if (this.form.page > this.last_page) {
        this.navigateToPage(1)
      }
    },
    data() {
      // form
      // * controls the browsing mechanism in realtime.
      // * being maniuplated by the UI components.
      // * configures the parameters being sent to the browse API
      // * has a watcher, changes to the form object will spawn a this.refresh()
      // * is being saved to local storage every time it's changed.
      let form = {
        start_date: formatDate(this.filters.start_date),
        end_date: formatDate(this.filters.end_date),
        page: this.filters.page,
        order: this.filters.order,
        direction: this.filters.direction,
        search_string: this.filters.search_string,
        in_media_type: this.filters.in_media_type,
        out_media_type: this.filters.out_media_type,
        in_language: this.filters.in_language,
        out_language: this.filters.out_language,
        in_resolution: this.filters.in_resolution,
        in_dynamic_range: this.filters.in_dynamic_range,
        out_dynamic_range: this.filters.out_dynamic_range,
        in_network: this.filters.in_network,
      }

      // Injects saved state from local storage; if it exists
      const [merged, shouldRefresh] = this.injectBrowseState({form: form}, false)

      // toggle bits for inclusion/exclusion (languages, dynamic ranges)
      let exclude_languages = false
      let exclude_dynamic_ranges = false

      // If nothing is in the in_language list and the out_language list has items
      // start in exclusion mode.
      if ((1 > merged.form.in_language.length) && (0 < merged.form.out_language.length)) {
        exclude_languages = true
      }

      // If nothing is in the in_dynamic_range list and the out_dynamic_range list has items
      // start in exclusion mode.
      if ((1 > merged.form.in_dynamic_range.length) && (0 < merged.form.out_dynamic_range.length)) {
        exclude_dynamic_ranges = true
      }

      return {
        shouldRefresh,
        exclude_languages,
        exclude_dynamic_ranges,
        form: merged.form,
        packet_list: this.packet_list,
        locks: this.locks,
        completed: this.completed,
        incomplete: this.incomplete,
        queued: this.queued,
        total: this.total_packets,
        downloadQueue: this.queue,
        showQueue: false,
        new_records_count: 0,
        lastTotalPacketsCount: this.total_packets,
        totalPacketsTimeoutId: null,
        locksTimeoutId: null,
        queueTimeoutId: null,
      }
    },
    computed: {
      contentClass() {
        const style = []

        if (this.showQueue) {
            style.push('mb-12')
        }

        return style
      },
      searchStringActive() {
        return this.form.search_string && this.form.search_string.length > 0
      },
      filteringVideoFormat() {
        return Array.isArray(this.form.in_media_type) &&
            this.form.in_media_type.some(type => ['movie', 'tv episode', 'tv season'].includes(type))
      },
    },
    watch: {
        form: {
            deep: true,
            handler: throttle(function () {
                this.refresh()
            }, 150),
      },
      downloadQueue: {
        deep: true,
        handler() {
          if (this.hasQueue()) {
            this.showQueue = true
          } else {
            this.$refs.queue.hide()
            this.showQueue = false
          }
        }
      },
      locks: {
        deep: true,
        handler: throttle(function (set) {
          this.locks = set
        }, 150),
      },
      completed: {
        deep: true,
        handler(set) {
          this.completed = set
        }
      },
      incomplete: {
        deep: true,
        handler(set) {
          this.incomplete = set
        },
      },
      queued: {
        deep: true,
        handler(set) {
          this.queued = set
        }
      },
    },
    methods: {
      resetIntervals() {
        this.clearAllIntervals()
        this.totalPacketsTimeoutId = setTimeout(this.checkTotalPackets, totalPacketsInterval);
        this.locksTimeoutId = setTimeout(this.checkLocks, locksInterval);
        this.queueTimeoutId = setTimeout(this.checkQueue, queueInterval);
      },
      clearTotalPacketsInterval() {
        clearTimeout(this.totalPacketsTimeoutId)
      },
      clearLocksInterval() {
        clearTimeout(this.locksTimeoutId)
      },
      clearQueueInterval() {
        clearTimeout(this.queueTimeoutId)
      },
      clearAllIntervals() {
        this.clearTotalPacketsInterval()
        this.clearLocksInterval()
        this.clearQueueInterval()
      },
      getBrowseState() {
        return {
            form: this.form,
        }
      },
      injectBrowseState(state, shouldRefresh) {
        const savedState = this.recallBrowseState()

        if (!savedState) {
            return [state, shouldRefresh]
        }

        if (has(savedState, 'form')) {
            if (JSON.stringify(savedState.form) !== JSON.stringify(state.form)) {
                state.form = savedState.form
                shouldRefresh = true
            }
        }

        return [state, shouldRefresh]
      },
      saveBrowseState() {
        localStorage.setItem('browse', JSON.stringify(this.getBrowseState()))
      },
      recallBrowseState() {
        const raw = localStorage.getItem('browse')
        return raw ? JSON.parse(raw) : null
      },
      checkTotalPackets() {
        this.fetchBrowse(this.updateTotalPackets)
        this.clearTotalPacketsInterval()
        this.totalPacketsTimeoutId = setTimeout(this.checkTotalPackets, totalPacketsInterval);
      },
      checkLocks() {
        this.fetchLocks(this.packet_list)
        this.clearLocksInterval()
        if (
          this.locks.length > 0 ||
          this.queued.length > 0 ||
          this.incomplete.length > 0 ||
          this.completed.length > 0
        ) {
          this.locksTimeoutId = setTimeout(this.checkLocks, locksInterval)
        }
      },
      checkQueue() {
        this.fetchQueue()
        this.clearQueueInterval()
        this.queueTimeoutId = setTimeout(this.checkQueue, queueInterval)
      },
      hasQueue() {
        return (
          (has(this.downloadQueue, 'completed') && 0 < this.downloadQueue.completed.length) ||
          (has(this.downloadQueue, 'incomplete') && 0 < this.downloadQueue.incomplete.length) ||
          (has(this.downloadQueue, 'queued') && 0 < this.downloadQueue.queued.length)
        )
      },
      toggleQueue() {
        this.$refs.queue.toggle()
      },
      updateTotalPackets(data) {
        this.total = data.meta.total
        if (null !== this.lastTotalPacketsCount) {
          let newPacketsCount = (this.total - this.lastTotalPacketsCount)
          // normalize to zero if negative.
          this.new_records_count = (newPacketsCount < 0) ? 0 : newPacketsCount
        } else {
          this.new_records_count = 0
          this.lastTotalPacketsCount = this.total
        }
      },
      toggleSort(order) {
        if (order === this.form.order) {
          this.form.direction = (this.form.direction === 'asc') ? 'desc' : 'asc'
        } else {
          this.form.order = order
          this.form.direction = defaultDirection[order]
        }
      },
      navigateToPage(number) {
        this.form.page = number
      },
      refresh() {
        // refresh the current results.
        this.new_records_count = 0

        // Update lastTotalPacketsCount
        this.lastTotalPacketsCount = null

        // Full inertia server page xhr request of form
        this.$inertia.get('/browse', pickBy(this.form), { preserveState: true })

        // Save the state of form to localstorage
        this.saveBrowseState()

         // Close the downloads queue
         if (has(this.$refs, 'queue')) {
            this.$refs.queue.hide()
        }

        // Reset the timers
        this.resetIntervals()
      },
      reset() {
        this.$refs.media.clear() // reset the media dropdownlist
        this.$refs.fromDate.clearPicker()
        this.form = mapValues(this.form, () => null)
        this.form.page = 1
        // datePicker prefers empty dates to be strings.
        this.form.end_date = ''
        this.form.start_date = ''
        this.updateExcludeLanguages(false)
        this.updateExcludeDynamicRanges(false)
      },
      resetSearchString() {
        this.form.search_string = null
      },
      updateSearchString(searchString) {
        if (searchString.length > 0) {
          this.search_string_active = true
          if (searchString.length > 3) {
            this.form.search_string = searchString
          }
        } else {
          this.search_string_active = false
        }
      },
      updateMediaType(mediaTypes) {
        // Check to see if mediaTypes has any video formats.
        const videoFormats = intersection(mediaTypes, ['movie', 'tv episode', 'tv season'])

        // If there are no video formats, the other form elements should let go of resolution and DR values.
        if (0 >= videoFormats.length) {
            this.updateResolution([])
            this.updateDynamicRanges('hdr', false)
            this.updateDynamicRanges('dovi', false)
        }

        this.form.in_media_type = mediaTypes
      },
      updateNetwork(networks) {
        this.form.in_network = networks
      },
      mediaLabel(values) {
        const tvStr = 'tv '
        const cleanedValues = []

        // Remove the "tv" from strings
        values.forEach((option) => {
          if (0 <= option.value.indexOf(tvStr)) {
            cleanedValues.push({
              value: option.value.split(tvStr)[1]
            })
          } else {
            cleanedValues.push(option)
          }
        })

        const label = dynamicLabel(cleanedValues, 6)
        return `${label} ...`
      },
      updateResolution(resolutions) {
        this.form.in_resolution = resolutions
      },
      networkLabel(values) {
        const label = dynamicLabel(values, 6)
        return `${label} ...`
      },
      resolutionLabel(values) {
        const label = dynamicLabel(values, 6)
        return `${label} ...`
      },
      updateLanguages(language, checked) {
        const set = (this.exclude_languages) ? 'out_language' : 'in_language'
        let i = -1
        if (has(this.form, set) && 0 < this.form[set].length) {
          i = this.form[set].indexOf(language)
        }

        if (checked && (0 > i)) {
          this.form[set].push(language)
        } else if (!checked && (0 <= i)) {
          delete this.form[set][i]
        }
      },
      updateExcludeLanguages(checked) {
        this.form.in_language = []
        this.form.out_language = []
        this.exclude_languages = checked
      },
      updateDynamicRanges(dynamic_range, checked) {
        const set = (this.exclude_dynamic_ranges) ? 'out_dynamic_range' : 'in_dynamic_range'
        let i = -1
        if (has(this.form, set) && 0 < this.form[set].length) {
          i = this.form[set].indexOf(dynamic_range)
        }

        if (checked && (0 > i)) {
          this.form[set].push(dynamic_range)
        } else if (!checked && (0 <= i)) {
          delete this.form[set][i]
        }
      },
      updateExcludeDynamicRanges(checked) {
        this.form.in_dynamic_range = []
        this.form.out_dynamic_range = []
        this.exclude_dynamic_ranges = checked
      },
      async requestDownload(packetId) {
        const {data, error} = await requestDownload(packetId)

        if (null === error) {
            this.locks.push(data.result.packet.file_name)
            // Schedule the next reload
            this.clearLocksInterval()
            this.locksTimeoutId = setTimeout(this.checkLocks, locksInterval)
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
            this.fetchQueue()
        }
      },
      async removeCompleted(download) {
        const { error } = await removeCompleted(download)

        if (null === error) {
            this.fetchLocks()
            this.fetchQueue()
        }
      },
      async fetchLocks(packetList) {
        const { data, error } = await fetchLocks(packetList)

        if (null === error) {
            const { locks, queued, incomplete, completed } = data
            this.locks = locks
            this.queued = queued
            this.incomplete = incomplete
            this.completed = completed

            if (
                locks.length <= 0 &&
                queued.length <= 0 &&
                incomplete.length <= 0 &&
                completed.length <= 0
            ) {
                this.clearLocksInterval()
            }
        }
      },
      async fetchBrowse(withData) {
        const [_href, _data] = mergeDataIntoQueryString('get', '/api/browse', pickBy(this.form), 'brackets')
        const url = hrefToUrl(_href)
        const headers = {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        };

        try {
          const response = await axios.get(url, { headers: headers })
          withData(response.data)
        } catch (error) {
          console.error(error)
        }
      },
      async fetchQueue() {
        const {data, error} = await fetchDownloadQueue()
        if (null === error) {
          this.downloadQueue = data
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
            this.fetchQueue()
        } else {
            console.error(error)
        }
      },
    },
  }
  </script>

<style>
  @import '@vueform/multiselect/themes/tailwind.css'
</style>
