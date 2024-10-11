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
        <span class="text-xs font-medium me-2 px-2.5 py-0.5 rounded border"
              :class="nickClass" >
          {{ packet.nick }}
        </span>
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

const colorMap = {
  completed: 'green',
  incomplete: 'blue',
  queued: 'amber',
  default: 'gray',
}

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
  methods: {
    getStatus() {
      if (_.has(this.completed, this.packet.file_name)) {
        return 'completed'
      } else if (_.has(this.incomplete, this.packet.file_name)) {
        return 'incomplete'
      } else if (_.has(this.queued, this.packet.file_name)) {
        return 'queued'
      }

      return 'default'
    },
    isStatus(status) {
      return _.has(this[status], this.packet.file_name)
    },
    getDownload(status) {
      return this[status][this.packet.file_name]
    }
  },
  computed: {
    nickClass() {
      let color = colorMap.default
      const status = this.getStatus()
      if (_.has(colorMap, status)) {
        color = colorMap[status]
      }

      return [
        `bg-${color}-100`,
        `text-${color}-800`,
        `dark:bg--${color}-700`,
        `dark:text-${color}-400`,
        `border-${color}-400`
      ]
    },
  }
}
</script>
