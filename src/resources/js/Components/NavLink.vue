<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { pickBy } from '@/funcs'
import { loadStoredPageState } from '@/Composables/usePageStateSync'

const props = defineProps({
    href: String,
    active: Boolean,
})

const classes = computed(() => {
    return props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out'
})

function handleClick(event) {
    const pageKey = extractPageKey(props.href)

    const saved = loadStoredPageState(pageKey)
    if (!saved) {
        return // <-- Should allow Inertia <Link> to proceed normally
    }

    const queryData = pickBy(saved.form || saved)

    event.preventDefault()
    router.visit(props.href, {
        data: queryData,
        method: 'get',
        preserveState: false,
        preserveScroll: false,
        replace: true,
    })
}


function extractPageKey(href) {
    try {
        const url = new URL(href, window.location.origin)
        return url.pathname.replace(/^\/+/, '').split('/')[0]
    } catch {
        return href.replace(/^\/+/, '').split('/')[0]
    }
}
</script>


<template>
    <Link :href="href" :class="classes" @click="handleClick">
    <slot />
    </Link>
</template>
