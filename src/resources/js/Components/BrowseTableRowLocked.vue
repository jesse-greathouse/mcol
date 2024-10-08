<template>
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4" >
          <media-icon :media="packet.media_type" />
      </span>
  </td>
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4" >
          <packet-date :date="packet.created_at" /> 
      </span>
  </td>
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4 " >
          {{ packet.gets }}
      </span>
  </td>
  <browse-table-cell-completed v-if="isStatus('completed')" :packet="packet" :download="getDownload('completed')" />
  <browse-table-cell-downloading v-else-if="isStatus('incomplete')" :packet="packet" :download="getDownload('incomplete')" />
  <browse-table-cell-queued v-else-if="isStatus('queued')" :packet="packet" :download="getDownload('queued')" />
  <browse-table-cell-locked v-else :packet="packet" />
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4" tabindex="-1">
        {{ packet.size }}
      </span>
  </td>
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4" tabindex="-1">
        {{ packet.network }}
      </span>
  </td>
  <td class="border-t bg-gray-100">
      <span class="flex items-center px-6 py-4" tabindex="-1">
        {{ packet.nick }}
      </span>
  </td>
</template>
  
<script>
import _ from 'lodash'
import BrowseTableCellCompleted from '@/Components/BrowseTableCellCompleted.vue'
import BrowseTableCellDownloading from '@/Components/BrowseTableCellDownloading.vue'
import BrowseTableCellQueued from '@/Components/BrowseTableCellQueued.vue'
import BrowseTableCellLocked from '@/Components/BrowseTableCellLocked.vue'
import MediaIcon from '@/Components/MediaIcon.vue'
import PacketDate from '@/Components/PacketDate.vue'

export default {
  components: {
    BrowseTableCellCompleted,
    BrowseTableCellDownloading,
    BrowseTableCellQueued,
    BrowseTableCellLocked,
    MediaIcon,
    PacketDate,
  },
  props: {
    completed: Object,
    incomplete: Object,
    queued: Object,
    packet: Object,
  },
  data() {
    return {
      completed: this.completed,
      incomplete: this.incomplete,
      queued: this.queued,
      packet: this.packet
    }
  },
  methods: {
    isStatus(status) {
      return _.has(this[status], this.packet.file_name)
    },
    getDownload(status) {
      return this[status][this.packet.file_name]
    }
  }
}
</script>
