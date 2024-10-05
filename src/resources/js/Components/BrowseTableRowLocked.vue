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
  <browse-table-cell-completed v-if="status === 'completed'" :packet="packet" :download="download" />
  <browse-table-cell-downloading v-else-if="status === 'incomplete'" :packet="packet" :download="download" />
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
import BrowseTableCellLocked from '@/Components/BrowseTableCellLocked.vue'
import MediaIcon from '@/Components/MediaIcon.vue'
import PacketDate from '@/Components/PacketDate.vue'

export default {
  components: {
    BrowseTableCellCompleted,
    BrowseTableCellDownloading,
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
    let download = {}
    let status = 'locked'
    if (_.has(this.completed, this.packet.file_name)) {
      status = 'completed'
      download = this.completed[this.packet.file_name]
    } else if (_.has(this.incomplete, this.packet.file_name)) {
      status = 'incomplete'
      download = _.get(this.incomplete, this.packet.file_name)
    } else if (_.has(this.queued, this.packet.file_name,)) {
      status = 'queued'
      download = _.get(this.queued, this.packet.file_name)
    }
  
    return {
      status: status,
      download: download,
      queued: this.queued,
      completed: this.completed,
      incomplete: this.incomplete,
      packet: this.packet,
    }
  },
  methods: {
  }
}
</script>
