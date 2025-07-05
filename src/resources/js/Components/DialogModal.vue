<script setup>
import AppModal from './AppModal.vue';

const emit = defineEmits(['close']);

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: '2xl',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
});

const close = () => {
  emit('close');
};
</script>

<template>
  <AppModal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
    <div class="px-6 py-4">
      <div class="text-lg font-medium text-gray-900">
        <slot name="title" />
      </div>

      <div class="mt-4 text-sm text-gray-600">
        <slot name="content" />
      </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 text-end">
      <slot name="footer" />
    </div>
  </AppModal>
</template>
