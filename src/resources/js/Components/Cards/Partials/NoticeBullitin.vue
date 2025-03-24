<template>
    <div class="flex flex-row">
        <div class="flex flex-row items-start">
            <span class="flex w-3 h-3 m-2 bg-green-500 rounded-full"></span>
            <span class="text-green-800 text-medium font-bold me-2 px-2.5 py-0.5 rounded-sm dark:bg-green-900 dark:text-green-300">
                {{ network }}
            </span>
        </div>
        <div class="max-w-4xl">
            <p class="mb-2 text-lg font-medium tracking-tight text-green-900 dark:text-green break-words overflow-hidden">
                {{ cleaned }}
            </p>
        </div>
    </div>
</template>

<script>

export default {
    props: {
        network: String,
        routingKey: String,
        msg: String,
    },
    data() {
        const cleaned = this.clean()

        return {
            cleaned: cleaned,
        }
    },
    methods: {
        clean() {
            let cleaned = this.msg.replace(/\*\*/g, "")
            cleaned = cleaned.replace(/\* \*/g, "")
            return cleaned.trim()
        }
    }
}
</script>
