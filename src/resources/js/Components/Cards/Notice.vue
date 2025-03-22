<template>
    <!-- System Message Component -->
    <div ref="systemMessageCard" class="max-w-sm p-6 bg-white border border-green-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-green-700">
        <a href="#">
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-green-900 dark:text-green">Notice</h5>

            <span class="bg-blue-100 text-green-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-green-900 dark:text-green-300">{{ network }}</span>
        </a>
        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ msg }}</p>
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
