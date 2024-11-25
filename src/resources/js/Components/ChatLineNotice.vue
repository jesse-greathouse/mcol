<template>
    <div class="inline-flex items-start grow text-green-600 italic font-semibold"> {{ message }} </div>
</template>

<script>
export default {
  components: {
  },
  props: {
    message: String
  },
}
</script>
