<template>
<div class="relative shadow-xl bg-white rounded-lg shadow dark:bg-gray-700">
    <!-- header -->
    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
        <div class="w-full grid gap-3 grid-cols-12 grid-rows-3">
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
                    :disabled="true"
                    v-model="displayUri"
                    ref="displayUri" />
            </div>
            <div class="col-span-12">
                <div class="inline-flex">
                    <button ref="up" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-100 focus:outline-none font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                        :disabled="(displayUri === selectedRoot)"
                        @click="openDir(parent)" >
                        <up-directory-icon />
                        <span class="sr-only">Up to {{ parent }}</span>
                    </button>
                    <button ref="new" type="button" class="text-white disabled:opacity-75 disabled:bg-gray-100 focus:outline-none font-medium rounded-lg text-sm p-2.5 text-center inline-flex items-center me-2 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                        @click="createDirPop.show()" >
                        <new-directory-icon />
                        <span class="sr-only">Create Directory</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- body -->
    <div class="relative overflow-x-auto overflow-y-auto shadow-md sm:rounded-lg max-h-132 min-h-48">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 ">
            <thead class="sticky top-0 text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="cursor-pointer px-6 py-3">
                        File Name
                    </th>
                    <th scope="col" class="cursor-pointer px-6 py-3">
                        Size
                    </th>
                    <th scope="col" class="cursor-pointer py-3">
                        Modified
                    </th>
                </tr>
            </thead>
            <tbody>
                <directory-browser-row v-for="file in directoryTable"
                    :settings="settings"
                    :file="file"
                    @call:openDir="openDir" />
            </tbody>
        </table>
        <div v-if="isLoading" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
            <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- footer -->
    <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600">
        <button type="button" class="py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
            @click="toggle()" >
            Cancel</button>
        <button type="button" class="text-white bg-green-500 hover:bg-green-600 focus:ring-4 focus:outline-none focus:ring-green-200 font-medium rounded-lg text-sm px-5 py-2.5 ms-3 text-center dark:bg-green-400 dark:hover:bg-green-500 dark:focus:ring-green-600"
            @click="setDownloadDestinationForm()" >
            Save in
            <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-green-400 border border-green-400">
                {{ displayUri }}
            </span>
        </button>
    </div>
</div>

<!-- popover -->
<div data-popover class="invisible absolute inline-block z-50 w-auto rounded-lg border border-gray-200 bg-white text-sm text-gray-500 opacity-0 shadow-sm transition-opacity duration-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400"
        ref="createDirPop"
        :id="createDirPopId"
        role="tooltip" >
        <div class="p-3">
            <div class="flex flex-row p-3">
            <div>
                <p class="mb-1 text-base font-semibold leading-none text-gray-900 dark:text-white">
                    <a href="#" class="hover:underline">Create Directory</a>
                </p>
            </div>
            </div>
            <div class="flex flex-row p-3">
            <div class="basis-1/3 px-3">
                <input type="text" class="min-w-60 relative px-6 py-3 w-full rounded focus:shadow-outline"
                    v-model="displayUri"
                    :disabled="true"
                />
            </div>
            <div class="basis-2/3">
                <input type="text" class="min-w-60 relative px-6 py-3 w-full rounded focus:shadow-outline"
                    v-model="createDirName"
                    ref="createDirName"
                />
            </div>
            </div>
            <div class="flex flex-row p-3 justify-end">
            <div class="flex">
                <button type="button" class="inline-flex items-center me-2 text-center font-medium rounded-lg text-sm focus:outline-none font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 inline-flex items-center focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:z-10 focus:ring-0 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700"
                    @click="createDirPop.hide()">
                    Cancel
                </button>
                <button type="button" class="focus:ring-4 focus:outline-none inline-flex items-center me-2 text-center text-white font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 bg-green-400 hover:bg-green-500 focus:ring-green-200 font-medium rounded-lg text-sm p-2.5 dark:bg-green-400 dark:hover:bg-green-400 dark:focus:ring-green-500"
                    @click="createDir()">
                    Create
                </button>
            </div>
            </div>
        </div>
        <div data-popper-arrow></div>
    </div>
</template>

<script>
import { Popover } from 'flowbite';
import {
    fetchStoreRoot,
    fetchUri,
    mkDir,
    rmDir,
    mediaStores
} from '@/Clients/media-store'
import { mediaTypeToStoreMap } from '@/download-queue'
import Multiselect from '@vueform/multiselect'
import DirectoryBrowserRow from '@/Components/DirectoryBrowserRow.vue'
import NewDirectoryIcon from '@/Components/NewDirectoryIcon.vue'
import UpDirectoryIcon from '@/Components/UpDirectoryIcon.vue'

export default {
  components: {
    DirectoryBrowserRow,
    Multiselect,
    NewDirectoryIcon,
    UpDirectoryIcon,
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
        createDirPopId: `create-dir-pop-${this.uri}`,
        createDirPop: null,
        createDirName: '',
        isLoading: true,
        parent: selectedRoot,
        displayUri: selectedRoot,
        selectedStore: selectedStore,
        mediaStoreOptions: mediaStores,
        storeRootsOptions: storeRootsOptions,
        selectedRoot: selectedRoot,
        directoryTable: []
    }
  },
  mounted() {
    this.createDirPop = new Popover(this.$refs.createDirPop, this.$refs.new, {
        placement: 'bottom',
        triggerType: 'none',
    }, {
        id: this.createDirPopId,
        override: true,
    })
  },
  watch: {
    selectedStore: {
        handler: function () {
            this.storeRootsOptions = this.settings.media_store[this.selectedStore]
            this.selectedRoot = this.storeRootsOptions[0]
            this.parent = this.selectedRoot
            this.displayUri = this.selectedRoot
        }
    },
    selectedRoot: {
        handler: function () {
            this.parent = this.selectedRoot
            this.displayUri = this.selectedRoot
        }
    },
    displayUri: {
        handler: function () {
            this.isLoading = true
            this.refreshDir()
        }
    },
    root: {
        handler: function () {
            this.formDisplayUri()
        }
    },
    uri: {
        handler: function () {
            this.formDisplayUri()
        }
    },
  },
  computed: {
  },
  methods: {
    async refreshDir() {
        const {data, error} = await fetchUri(this.displayUri)
        this.isLoading = false
        if (null === error) {
            this.directoryTable = data
        } else {
            if (error.code === 'ERR_BAD_REQUEST') {
                console.log(`"${this.displayUri}" has not been created yet.`)
            } else {
                console.error(error)
            }

            this.openDir(this.parent)
        }
    },
    formDisplayUri() {
        this.displayUri = `${this.root}${this.uri}`
    },
    setDownloadDestinationForm() {
        this.$emit('call:setDownloadDestinationFormAndSave', this.displayUri, this.selectedStore)
        this.toggle()
    },
    toggle() {
        this.$emit('call:toggleBrowser', this.displayUri)
    },
    async createDir() {
        // If Value is blank, do nothing.
        if (this.createDirName === '') return

        this.isLoading = true
        const DS = this.settings.system.DS // DIRECTORY_SEPARATOR
        const uri = `${this.displayUri}${DS}${this.createDirName}`

        const {data, error} = await mkDir(uri)
        this.isLoading = false

        // Reset the Text input.
        this.createDirName = ''

        if (null === error) {
            this.openDir(data.uri)
        } else {
            // Refresh the display uri.
            this.openDir(this.displayUri)
            console.log(error)
        }

        this.createDirPop.hide()
    },
    openDir(uri) {
        this.parent = this.findParent(uri)
        this.displayUri = uri
    },
    findParent(uri) {
        const DS = this.settings.system.DS // DIRECTORY_SEPARATOR
        let lastSlash;

        lastSlash = uri.lastIndexOf(DS)

        if (0 > lastSlash) {
            return this.selectedRoot
        }

        return uri.substring(0, lastSlash)
    },
  },
  emits: ['call:toggleBrowser', 'call:setDownloadDestinationFormAndSave'],
}
</script>
