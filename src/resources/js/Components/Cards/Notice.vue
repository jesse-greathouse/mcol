<template>
    <div ref="systemMessageCard" class="z-50 p-4 bg-white border rounded-lg shadow-sm drop-shadow-md dark:bg-gray-800"
        :class="getCardClass()">
        <!-- System Message Component -->
        <component v-bind:is="partial" :routingKey="routingKey" :msg="msg" :network="network" :target="target" />
    </div>
</template>

<script>

import DefaultNotice from '@/Components/Cards/Partials/Notice.vue'
import NoticeBullitin from '@/Components/Cards/Partials/NoticeBullitin.vue'
import NoticeDownloadCard from '@/Components/Cards/Partials/DownloadCard.vue'
import NoticeQueued from '@/Components/Cards/Partials/Queued.vue'
import ChatConsole from '../ChatConsole.vue'

const partialMap = {
    default: DefaultNotice,
    bullitin: NoticeBullitin,
    queued: NoticeQueued,
    card: NoticeDownloadCard,
}

export default {
    components: {
        DefaultNotice,
        NoticeBullitin,
        NoticeDownloadCard,
        NoticeQueued,
    },
    props: {
        network: String,
        target: String,
        routingKey: String,
        msg: String,
    },
    data() {
        const [partial, color] = this.getPartial()

        return {
            color: color,
            partial: partial,
        }
    },
    watch: {
        msg: {
            deep: false,
            handler() {
                const [partial, color] = this.getPartial()
                this.partial = partial
                this.color = color
            },
        },
    },
    methods: {
        getPartial() {
            const defaultColor = 'green'
            let [card, color] = [partialMap.default, defaultColor]

            if (this.msg.indexOf('Queued') > -1) {
                // If this is a queued message, shift the color to amber.
                return [partialMap.queued, 'yellow']
            }

            if (this.msg.indexOf('fileName=') > -1) {
                return [partialMap.card, color]
            }

            let hasDoubleStar = this.msg.indexOf('**') > -1
            let hasStarSpaceStar = this.msg.indexOf('* *') > -1

            if (hasDoubleStar || hasStarSpaceStar) {
                return [partialMap.bullitin, color]
            }

            return [card, color]
        },
        getCardClass() {
            return [`border-${this.color}-200`, `dark:border-${this.color}-700`,]
        }
    }
}
</script>
