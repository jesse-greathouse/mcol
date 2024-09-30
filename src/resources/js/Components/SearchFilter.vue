<template>
  <div class="flex items-center">
    <button type="button" class="mr-3 p-3 rounded border border-solid text-gray-500 hover:text-gray-700 focus:text-indigo-500 text-sm" @click="$emit('reset')">Clear</button>
    <div class="flex w-full bg-white shadow">
        <input class="relative px-6 py-3 w-full rounded focus:shadow-outline" autocomplete="off" type="text" name="search" placeholder="Search…" :value="model" @input="$emit('update:searchString', $event.target.value)" />
    </div>
  </div>
</template>
  
<script>
export default {
  props: {
    model: String,
    maxWidth: {
      type: Number,
      default: 300,
    },
  },
  emits: ['update:searchString', 'reset'],
  }
  </script>
