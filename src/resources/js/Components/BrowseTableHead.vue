<template>
  <thead>
    <tr class="text-left font-bold">
      <th class="p-4 align-middle items-center">Media</th>
      <th
        class="p-4 align-middle items-center hover:cursor-pointer hover:shadow-inner hover:bg-gradient-to-b from-white via-white to-sky-50"
        @click="$emit('call:toggleSort', 'created')"
      >
        <sort-buttons
          order="created"
          :currentOrder="currentOrder"
          :currentDirection="currentDirection"
        />
        <span class="inline-block pt-1 align-middle">Created</span>
      </th>
      <th
        class="p-4 align-middle items-center hover:cursor-pointer hover:shadow-inner hover:bg-gradient-to-b from-white via-white to-sky-50"
        @click="$emit('call:toggleSort', 'gets')"
      >
        <sort-buttons
          order="gets"
          :currentOrder="currentOrder"
          :currentDirection="currentDirection"
        />
        <span class="inline-block pt-1 align-middle">Gets</span>
      </th>
      <th
        class="p-4 align-middle items-center hover:cursor-pointer hover:shadow-inner hover:bg-gradient-to-b from-white via-white to-sky-50"
        @click="$emit('call:toggleSort', 'name')"
      >
        <sort-buttons
          order="name"
          :currentOrder="currentOrder"
          :currentDirection="currentDirection"
        />
        <span class="inline-block pt-1 align-middle">File Name</span>
      </th>
      <th class="p-4 align-middle items-center">Size</th>
      <th class="p-4 align-middle items-center">Network</th>
      <th class="p-4 align-middle items-center">
        <span class="flex items-center pl-20"> Bot </span>
      </th>
    </tr>
  </thead>
</template>

<script>
import SortButtons from '@/Components/SortButtons.vue';

export default {
  components: {
    SortButtons,
  },
  props: {
    currentOrder: String,
    currentDirection: String,
  },
  emits: ['call:toggleSort'],
};
</script>
