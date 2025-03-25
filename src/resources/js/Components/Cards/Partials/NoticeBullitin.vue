<template>
    <div class="flex flex-row items-start gap-3">
        <div class="flex flex-row items-center gap-3">
            <span class="flex w-3 h-3 m-2 bg-green-500 rounded-full"></span>
            <span class="text-green-800 dark:text-green-300 text-medium font-bold py-2">
                {{ network }}
            </span>
        </div>
        <div class="m-2 font-normal">
            <span class="text-green-900 dark:text-green break-words overflow-hidden tracking-tight font-medium">
                {{ cleaned }}
            </span>
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
        return {
            cleaned: this.clean(),
        }
    },
    watch: {
        msg: {
            deep: false,
            handler() {
                this.cleaned = this.clean()
            },
        },
    },
    methods: {
        clean() {
            let cleaned = this.msg
            cleaned = cleaned.replace(/\*\*/g, "")
            cleaned = cleaned.replace(/\* \*/g, "")
            return cleaned.trim()
        }
    }
}
</script>
