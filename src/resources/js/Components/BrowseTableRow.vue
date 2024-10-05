<template>
  <td class="border-t">
      <span class="flex items-center px-6 py-4  hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)">
          <media-icon :media="packet.media_type" />
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)">
          <packet-date :date="packet.created_at" /> 
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4  hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)">
          {{ packet.gets }}
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)" tabindex="-1">
        {{ packet.file_name }}
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)" tabindex="-1">
        {{ packet.size }}
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)" tabindex="-1">
        {{ packet.network }}
      </span>
  </td>
  <td class="border-t">
      <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="$emit('call:requestDownload', packet.id)" tabindex="-1">
        {{ packet.nick }}
      </span>
  </td>
</template>
  
<script>
import MediaIcon from '@/Components/MediaIcon.vue'
import PacketDate from '@/Components/PacketDate.vue'

export default {
  components: {
    MediaIcon,
    PacketDate,
  },
  props: {
    packet: Object,
  },
  emits: ['call:requestDownload'],
}
</script>
