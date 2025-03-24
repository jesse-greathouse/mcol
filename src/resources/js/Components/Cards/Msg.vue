<template>
    <div ref="systemMessageCard" class="max-w-5xl p-4 bg-white border border-blue-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-blue-700 drop-shadow-md">
        <!-- System Message Component -->
        <component v-bind:is="partial" :routingKey="routingKey" :msg="msg" :network="network" :target="target" />
    </div>
</template>

<script>

import DefaultMsg from '@/Components/Cards/Partials/Msg.vue'
import MsgDccSend from '@/Components/Cards/Partials/MsgDccSend.vue'

const partialMap = {
    default:    'DefaultMsg',
    dccsend:   'MsgDccSend',
}

export default {
    components: {
        DefaultMsg,
        MsgDccSend,
    },
    props: {
        network: String,
        target: String,
        routingKey: String,
        msg: String,
    },
    data() {
        const partial = this.getPartial()

        return {
            partial: partial,
        }
    },
    methods: {
        getPartial() {
            if (this.msg.indexOf('DCC SEND') > -1) {
                return partialMap.dccsend
            }

            return partialMap.default
        },
    }
}
</script>
