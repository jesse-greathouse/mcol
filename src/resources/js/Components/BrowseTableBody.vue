<template>
  <tbody>
    <tr v-for="packet in packets" :key="`packet-${packet.id}`" class="hover:bg-gray-100 focus-within:bg-gray-100">
      <browse-table-row-locked v-if="isLocked(packet)"
        :packet="packet"
        :completed="completed"
        :incomplete="incomplete"
        :queued="queued"
      />
      <browse-table-row v-else :packet="packet" @call:requestDownload="requestDownload" />
    </tr>
    <tr v-if="packets.length === 0">
      <td class="px-6 py-4 border-t" colspan="4">No Packets Found.</td>
    </tr>
  </tbody>
</template>
  
<script>
import BrowseTableRow from '@/Components/BrowseTableRow.vue'
import BrowseTableRowLocked from '@/Components/BrowseTableRowLocked.vue'

export default {
  components: {
    BrowseTableRow,
    BrowseTableRowLocked,
  },
  props: {
    locks: Array,
    completed: Object,
    incomplete: Object,
    queued: Object,
    packets: Object,
  },
  methods: {
    isLocked(packet) {
      const i = this.locks.indexOf(packet.file_name)

      if (0 > i) {
        return false
      }

      return true
    },
    requestDownload(packetId) {
      this.$emit('call:requestDownload', packetId)
    },

  },
  emits: ['call:requestDownload'],
}
</script>
