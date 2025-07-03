<template>
  <div class="flex flex-row items-start gap-3">
    <div class="flex flex-row items-center gap-3">
      <span class="flex w-3 h-3 m-2 bg-blue-500 rounded-full"></span>
      <span class="text-blue-800 dark:text-blue-300 text-medium font-bold py-2">
        {{ network }}
      </span>
      <span class="text-blue-800 dark:text-blue-300 text-medium font-bold py-2 break-keep">{{
        target
      }}</span>
    </div>
    <div class="m-2 font-normal">
      <span
        class="text-blue-800 dark:text-blue-200 break-words overflow-hidden tracking-tight font-medium"
      >
        {{ msg }}
      </span>
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
};
</script>
