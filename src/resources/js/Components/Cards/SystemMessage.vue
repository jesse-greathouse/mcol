<template>
    <!-- System Message Component -->
    <div ref="systemMessageCard" class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 drop-shadow-md">
        <a href="#">
            <h5 class="m-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ routingKey }}</h5>
        </a>
        <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ msg }}</p>
    </div>
</template>

<script>
export default {
  props: {
    routingKey: String,
    msg: String,
  },
}
</script>
