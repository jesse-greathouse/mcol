<template>
    <div class="py-6">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">

                <Head title="Download" />
                <section class="bg-white dark:bg-gray-900">
                    <div class="py-4 px-4 w-full">
                        <div v-if="hasCards()" class="flex flex-wrap gap-4 justify-start">
                            <DownloadCard v-for="(card, fileName) in downloadCards" :key="fileName"
                                :download="downloads[fileName]" :svg="card" :settings="settings"
                                @call:removeCompleted="removeCompleted" @call:requestRemove="requestRemove"
                                @call:requestCancel="requestCancel"
                                @call:saveDownloadDestination="saveDownloadDestination" />
                        </div>

                        <!-- If there are not any current downloads, show the Donate Hero Banner -->
                        <div v-if="!hasCards() && hasFetchedDownloadCards" v-html="hero" class="flex justify-center">
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import { toRaw } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { has } from '@/funcs';
import { initFlowbite } from 'flowbite';

import AppLayout from '@/Layouts/AppLayout.vue';
import { fetchDownloadCard } from '@/Clients/download-card';
import { fetchLocks } from '@/Clients/browse';
import { fetchDownloadQueue } from '@/Clients/download-queue';
import { makeDownloadIndexFromQueue } from '@/download-queue';
import { removeCompleted, requestRemove, requestCancel } from '@/Clients/rpc';
import { saveDownloadDestination } from '@/Clients/download-destination';
import DownloadCard from '@/Components/DownloadCard.vue';

const refreshDashboardInterval = 10000;
let refreshDashboardId;
const refreshDashboard = function () {
    clearTimeout(refreshDashboardId);
};

const locksInterval = 10000;
let locksTimeoutId;
const clearLocksInterval = function () {
    clearTimeout(locksTimeoutId);
};

export default {
    components: {
        Head,
        Link,
        DownloadCard,
    },
    layout: AppLayout,
    props: {
        settings: Object,
        queue: Object,
        networks: Array,
        locks: Array,
        hero: String,
    },
    data() {
        return {
            downloadLocks: this.locks,
            downloadQueue: this.queue,
            downloads: {},
            downloadCards: {},
            hasFetchedDownloadCards: false,
        };
    },
    mounted() {
        initFlowbite()
        this.refreshDashboard()
        this.checkLocks()
    },
    updated() {
        initFlowbite()
    },
    watch: {
        downloadQueue: {
            deep: true,
            handler() {
                this.downloads = makeDownloadIndexFromQueue(this.downloadQueue);
                this.updateDownloadCards();
            },
        },
        downloadCards: {
            handler(newDownloadCards, oldDownloadCards) {
                this.transitionCards(newDownloadCards, oldDownloadCards);
            },
            immediate: true,
        },
    },
    methods: {
        hasCards() {
            return this.downloadCards && Object.keys(this.downloadCards).length > 0;
        },
        updateDownloadCards() {
            let deleteManifest = Object.keys(this.downloadCards);
            Object.keys(this.downloads).forEach((fileName) => {
                const idx = deleteManifest.indexOf(fileName);
                if (idx !== -1) deleteManifest.splice(idx, 1);

                if (!has(this.downloadCards, fileName)) {
                    this.downloadCards[fileName] = null;
                }

                fetchDownloadCard(fileName)
                    .then((svg) => {
                        this.downloadCards[fileName] = svg;
                    })
                    .catch((error) => {
                        console.error('fetchDownloadCard Error:', error);
                    });
            });

            deleteManifest.forEach((fileName) => {
                if (this.downloadCards[fileName]) {
                    delete this.downloadCards[fileName];
                }
            });

            this.hasFetchedDownloadCards = true;
        },
        transitionCards(newDownloadCards, oldDownloadCards) {
            if (newDownloadCards === oldDownloadCards) return;
            Object.keys(newDownloadCards).forEach((fileName) => {
                const newCard = toRaw(newDownloadCards[fileName]);
                const oldCard = toRaw(oldDownloadCards[fileName]);
                if (newCard !== oldCard && newCard !== null) {
                    this.transitionCard(fileName, newCard);
                }
            });
        },
        transitionCard(key, svg) {
            const ref = this.$refs[`download-card-${key}`];
            if (ref) {
                ref[0].innerHTML = svg;
            }
        },
        async fetchDownloadCard(fileName = '') {
            const { data, error } = await fetchDownloadCard(fileName);
            if (error === null) return data;
            else console.log(error);
        },
        async refreshDashboard() {
            await this.fetchDownloadQueue();
            refreshDashboard();
            refreshDashboardId = setTimeout(this.refreshDashboard, refreshDashboardInterval);
        },
        async checkLocks() {
            await this.fetchLocks();
            clearLocksInterval();
            locksTimeoutId = setTimeout(this.checkLocks, locksInterval);
        },
        async fetchDownloadQueue() {
            const { data, error } = await fetchDownloadQueue();
            if (error === null) this.downloadQueue = data;
            else console.log(error);
        },
        async fetchLocks(packetList) {
            const { data, error } = await fetchLocks(packetList);
            if (error === null) {
                this.downloadLocks = data.locks;
                if (data.locks.length <= 0) clearLocksInterval();
            }
        },
        async saveDownloadDestination(download, uri) {
            const body = {
                destination_dir: uri,
                download: download.id,
            };
            if (download.destination !== null) {
                body.id = download.destination.id;
            }
            const { error } = await saveDownloadDestination(body);
            if (error === null) this.fetchDownloadQueue();
        },
        async requestRemove(packetId) {
            const { data, error } = await requestRemove(packetId);
            if (error === null) {
                const fileName = data.result.packet.file_name;
                const locksIndex = this.locks.indexOf(fileName);
                if (locksIndex >= 0) delete this.locks[locksIndex];
                if (has(this.queued, fileName)) delete this.queued[fileName];
                if (has(this.downloadQueue.queued, fileName)) delete this.downloadQueue.queued[fileName];
            }
        },
        async requestCancel(download) {
            const { error } = await requestCancel(download);
            if (error === null) {
                this.fetchLocks();
                this.fetchDownloadQueue();
            }
        },
        async removeCompleted(download) {
            const { error } = await removeCompleted(download);
            if (error === null) {
                this.fetchLocks();
                this.fetchDownloadQueue();
            }
        },
    },
};
</script>

<style>
@import '@vueform/multiselect/themes/tailwind.css';
</style>
