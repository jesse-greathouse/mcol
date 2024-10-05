<template>
  <td class="border-t bg-gray-100 flex items-center">
      <span class="flex items-center h-16 w-auto py-2" tabindex="-1">
        <locked-icon />
      </span>
      <span class="flex flex items-center px-6 py-4" tabindex="-1">
        {{ packet.file_name }}
      </span>
  </td>
</template>
  
<script>
import LockedIcon from '@/Components/LockedIcon.vue'

export default {
  components: {
    LockedIcon,
  },
  props: {
    download: Object,
    packet: Object,
  },
  methods: {
  }
}
</script>
