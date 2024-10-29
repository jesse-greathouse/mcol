<template>
    <tr :key="`file-${file.uri}`" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <td class="cursor-default select-none px-6 py-4 text-sm"
            @dblclick="openDir" >
            <span  class="inline-flex whitespace-nowrap">
                <directory-browser-icon :file="file" /> {{ name }}
            </span>
        </td>
        <td class="cursor-default select-none px-6 py-4 text-sm "
            @dblclick="openDir" >
            <span class="inline-flex whitespace-nowrap">{{ size }}</span>
        </td>
        <td class="cursor-default select-none px-6 py-4 text-sm"
            @dblclick="openDir" >
            <span class="inline-flex whitespace-nowrap">{{ date }}</span>
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
        openDir() {
            if (this.file.is_dir) {
                this.$emit('call:openDir', this.file.uri)
            }
        },
    },
    emits: ['call:openDir'],
}
</script>
