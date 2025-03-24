<template>
    <div class="flex flex-row">
        <div class="flex flex-row items-center">
            <span class="flex w-3 h-3 m-2 bg-blue-500 rounded-full"></span>
            <span class="text-blue-800 text-medium font-bold m-2 px-2.5 py-0.5 rounded-sm dark:bg-blue-900 dark:text-blue-300">
                {{ network }}
            </span>
            <span class="text-blue-800 text-medium font-bold me-2 px-2.5 py-0.5 dark:bg-blue-900 dark:text-blue-300">{{ target }}</span>
        </div>
        <div class="max-w-5xl m-2 overflow-x-hidden">
            {{ msg }}
        </div>
    </div>
</template>

<script>
export default {
  props: {
    network: String,
    target: String,
    routingKey: String,
    msg: String,
  },
}
</script>
