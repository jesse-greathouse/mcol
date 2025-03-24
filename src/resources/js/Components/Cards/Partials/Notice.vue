<template>
    <div class="flex flex-row items-center">
        <div class="flex flex-row items-start m-3">
            <a href="#"><h5 class="text-2xl font-bold tracking-tight text-green-900 dark:text-green">Notice</h5></a>
            <span class="m-2 bg-blue-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded-sm dark:bg-green-900 dark:text-green-300">{{ network }}</span>
        </div>
        <div class="mb-3 font-normal text-gray-700 dark:text-gray-400">
            {{ msg }}
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
}
</script>
