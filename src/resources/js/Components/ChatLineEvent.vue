<template>
  <div class="flex items-start w-full mr-4">
    <div class="inline-flex items-start text-sky-500 italic font-semibold">{{ message }}</div>
  </div>
</template>

<script>
export default {
  components: {},
  props: {
    message: String,
  },
};
</script>
