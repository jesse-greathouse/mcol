<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppWelcome from '@/Components/AppWelcome.vue';
</script>

<template>
  <AppLayout title="Dashboard">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <AppWelcome />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
