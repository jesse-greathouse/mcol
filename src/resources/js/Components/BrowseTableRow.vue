<template>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
    >
      <media-icon :media="packet.media_type" />
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
    >
      <packet-date :date="packet.created_at" />
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
    >
      {{ packet.gets }}
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
      tabindex="-1"
    >
      {{ packet.file_name }}
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
      tabindex="-1"
    >
      {{ packet.size }}
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
      tabindex="-1"
    >
      {{ packet.network }}
    </span>
  </td>
  <td class="border-t">
    <span
      class="flex items-center px-6 py-4 hover:cursor-pointer"
      @click="$emit('call:requestDownload', packet.id)"
      tabindex="-1"
    >
      <span
        class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-400 border border-gray-400"
      >
        {{ packet.nick }}
      </span>
    </span>
  </td>
</template>

<script>
import MediaIcon from '@/Components/MediaIcon.vue';
import PacketDate from '@/Components/PacketDate.vue';

export default {
  components: {
    MediaIcon,
    PacketDate,
  },
  props: {
    packet: Object,
  },
  emits: ['call:requestDownload'],
};
</script>
