<template>
    <div class="flex flex-row items-start gap-3">
        <div class="flex flex-row items-center gap-3">
            <h5 class="text-1xl font-bold tracking-tight text-green-900 dark:text-green">Notice</h5>
            <span class="text-green-800 dark:text-green-300 text-medium font-bold py-2">
                {{ network }}
            </span>
        </div>
        <div class="m-2 font-normal">
            <span class="text-slate-800 dark:text-slate-200 break-words overflow-hidden tracking-tight font-medium">
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

            // Change the text of this message because it's confusing.
            // The message is intended for a bot to tell the user that the transfer has ended.
            if (cleaned.indexOf("You don't have a transfer running") > -1) {
                cleaned = 'Transfer Terminated'
            }

            return cleaned.trim()
        }
    }
}
</script>
