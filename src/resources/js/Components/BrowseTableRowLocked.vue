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
  <browse-table-cell-completed v-if="isStatus('completed')"
    :packet="packet"
    :settings="settings"
    :download="getDownload('completed')"
    @call:removeCompleted="removeCompleted"
    @call:saveDownloadDestination="saveDownloadDestination" />
  <browse-table-cell-downloading v-else-if="isStatus('incomplete')"
    :packet="packet"
    :settings="settings"
    :download="getDownload('incomplete')"
    @call:requestCancel="requestCancel"
    @call:saveDownloadDestination="saveDownloadDestination" />
  <browse-table-cell-queued v-else-if="isStatus('queued')"
    :packet="packet"
    :settings="settings"
    :download="getDownload('queued')"
    @call:requestRemove="requestRemove"
    @call:saveDownloadDestination="saveDownloadDestination" />
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
import { has } from '@/funcs'
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
    settings: Object,
  },
  computed: {
    nickClass() {
      let color = colorMap.default
      const status = this.getStatus()
      if (has(colorMap, status)) {
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
  },
  methods: {
        getStatus() {
            if (has(this.completed, this.packet.file_name)) {
                return 'completed'
            } else if (has(this.incomplete, this.packet.file_name)) {
                return 'incomplete'
            } else if (has(this.queued, this.packet.file_name)) {
                return 'queued'
            }

            return 'default'
        },
        isStatus(status) {
            return has(this[status], this.packet.file_name)
        },
        getDownload(status) {
            return this[status][this.packet.file_name]
        },
        removeCompleted(download) {
            this.$emit('call:removeCompleted', download)
        },
        requestCancel(download) {
            this.$emit('call:requestCancel', download)
        },
        requestRemove(packetId) {
            this.$emit('call:requestRemove', packetId)
        },
        saveDownloadDestination(download, uri) {
            this.$emit('call:saveDownloadDestination', download, uri)
        },
    },
    emits: ['call:requestCancel', 'call:requestRemove', 'call:removeCompleted', 'call:saveDownloadDestination'],
}
</script>
