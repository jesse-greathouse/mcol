<template>
  <td class="border-t bg-gray-100 flex items-center">
    <div class="grid grid-cols-11 gap-0 justify-start items-stretch w-full">
      <div class="place-self-stretch h-full w-full py-5" tabindex="-1">
        <locked-icon />
      </div>
      <div class="col-span-10 py-6 px-0" tabindex="-1">
        {{ packet.file_name }}
      </div>
    </div>
  </td>
</template>

<script>
import LockedIcon from '@/Components/LockedIcon.vue';

export default {
  components: {
    LockedIcon,
  },
  props: {
    packet: Object,
  },
};
</script>
