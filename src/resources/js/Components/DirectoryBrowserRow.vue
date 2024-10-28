<template>
    <tr :key="`file-${file.uri}`" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <td class="cursor-default px-6 py-4 text-sm text-gray-900 dark:text-white">
            <span class="inline-flex whitespace-nowrap">
                <directory-browser-icon :file="file" /> {{ name }}
            </span>
        </td>
        <td class="cursor-default px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
            {{ size }}
        </td>
        <td class="cursor-default px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
            {{ date }}
        </td>
    </tr>
</template>

<script>
import { formatSize } from '@/file-size'
import { formatISODate, formatTruncate } from '@/format'
import DirectoryBrowserIcon from '@/Components/DirectoryBrowserIcon.vue'

export default {
    components: {
        DirectoryBrowserIcon
    },
    props: {
        settings: Object,
        file: Object,
    },
    data() {
    },
    mounted() {
    },
    computed: {
        name() {
            return formatTruncate(this.file.basename, 70, 60, '[...]')
        },
        size() {
            return (!this.file.size) ? '' : formatSize(this.file.size)
        },
        date() {
            return formatISODate(this.file.modified)
        },
    },
    methods: {
    },
    emits: ['call:openDir'],
}
</script>
