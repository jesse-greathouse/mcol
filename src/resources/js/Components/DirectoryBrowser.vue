<template>
<div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
    <!-- header -->
    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
        <div class="w-full grid gap-3 grid-cols-12 grid-rows-2">
            <div class="col-span-3">
                <multiselect tabindex="-1" class="hover:text-gray-700 focus:text-indigo-500 text-sm"
                    v-model="selectedStore"
                    :options="mediaStoreOptions"
                    :canClear="false"
                    ref="mediaStores"
                />
            </div>
            <div class="col-span-8">
                <multiselect tabindex="-1" class="hover:text-gray-700 focus:text-indigo-500 text-sm"
                    v-model="selectedRoot"
                    :options="storeRootsOptions"
                    :canClear="false"
                    ref="storeRoots"
                />
            </div>
            <div class="text-right">
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                    @click="toggle()" >
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <div class="col-span-12">
                <input type="text" class="min-w-60 relative px-6 py-3 w-full rounded focus:shadow-outline"
                    v-model="displayUri"
                    ref="displayUri" />
            </div>
        </div>
    </div>
    <!-- body -->
    <div class="relative overflow-x-auto overflow-y-auto shadow-md sm:rounded-lg max-h-96 min-h-48">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="sticky top-0 text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        File Name
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Size
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Modified Date
                    </th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Select</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="file in directoryTable" :key="`file-${file.uri}`" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ file.basename }}
                    </th>
                    <td class="px-6 py-4">
                        {{ size }}
                    </td>
                    <td class="px-6 py-4">
                        {{ modified }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Select</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- footer -->
    <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
        <button type="button" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            @click="toggle()" >
            Save</button>
        <button type="button" class="py-2.5 px-5 ms-3 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
            @click="toggle()" >
            Cancel
        </button>
    </div>
</div>
</template>

<script>
import _ from 'lodash'
import {
    fetchStoreRoot,
    fetchUri,
    mkDir,
    rmDir,
    mediaStores
} from '@/Clients/media-store'
import { mediaTypeToStoreMap } from '@/download-queue'
import Multiselect from '@vueform/multiselect'

export default {
  components: {
    Multiselect,
  },
  props: {
    settings: Object,
    mediaType: String,
    root: String,
    uri: String,
  },
  data() {
    const selectedStore = mediaTypeToStoreMap[this.mediaType]
    const storeRootsOptions = this.settings.media_store[selectedStore]
    const selectedRoot = storeRootsOptions[0]
    return {
        displayUri: selectedRoot,
        selectedStore: selectedStore,
        mediaStoreOptions: mediaStores,
        storeRootsOptions: storeRootsOptions,
        selectedRoot: selectedRoot,
        directoryTable: []
    }
  },
  mounted() {
    // this.refreshDir()
  },
  watch: {
    selectedStore: {
        handler: function () {
            this.storeRootsOptions = this.settings.media_store[this.selectedStore]
            this.selectedRoot = this.storeRootsOptions[0]
            this.displayUri = this.selectedRoot
        }
    },
    selectedRoot: {
        handler: function () {
            this.displayUri = this.selectedRoot
        }
    },
    displayUri: {
        handler: function () {
            this.refreshDir()
        }
    },
  },
  computed: {
  },
  methods: {
    async refreshDir() {
        const {data, error} = await fetchUri(this.displayUri)
        if (null === error) {
            this.directoryTable = data
        } else {
            console.log(error)
        }
    },
    formDisplayUri() {
        this.displayUri = `${this.root}${this.uri}`
    },
    toggle() {
        this.$emit('call:toggleBrowser', this.displayUri)
    }
  },
  emits: ['call:toggleBrowser'],
}
</script>
