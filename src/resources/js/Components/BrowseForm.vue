<script setup>
import { reactive } from 'vue';
import SearchFilter from '@/Components/SearchFilter.vue';

const form = reactive({
  search_string: null,
});
</script>

<template>
  <search-filter v-model="form.search_string" class="mr-4 w-full max-w-md" @reset="reset">
    <label class="block text-gray-700">Trashed:</label>
    <select v-model="form.trashed" class="form-select mt-1 w-full">
      <option :value="null" />
      <option value="with">With Trashed</option>
      <option value="only">Only Trashed</option>
    </select>
  </search-filter>
</template>
