<template>
    <div class="fixed top-0 left-0 w-full h-full pointer-events-none">
        <div
            class="m-2 absolute bottom-20 left-10 z-50 inline-block max-w-5xl break-words transition-opacity duration-2000"
            :class="{
                'opacity-0 pointer-events-none': !visible || faded,
                'opacity-100 pointer-events-auto': visible && !faded
            }"
        >
            <component v-bind:is="card" :routingKey="routingKey" :msg="msg" :network="network" :target="target" />
        </div>
    </div>
</template>

<script>
import { trim } from '@/funcs'
import DefaultCard from '@/Components/Cards/SystemMessage.vue'
import NoticeCard from '@/Components/Cards/Notice.vue'
import MsgCard from '@/Components/Cards/Msg.vue'
import { streamSystemMessage } from '@/Clients/stream'
import { parseSystemMessage } from '@/system-message'

const cardTypeMap = {
    notice: 'NoticeCard',
    msg: 'MsgCard',
    default: 'DefaultCard',
}

const refreshSystemMessagesInterval = 3000 // Check system messages every 3 seconds.
let refreshSystemMessagesId
const clearSystemMessageInterval = function () {
    clearTimeout(refreshSystemMessagesId)
}

const displaySystemMessagesInterval = 1000 // Display a system message only at this interval.
let displaySystemMessagesId
const clearDisplayInterval = function () {
    clearTimeout(displaySystemMessagesId)
}

export default {
    components: {
        DefaultCard,
        NoticeCard,
        MsgCard,
    },
    props: {
        queue: String,
    },
    data() {
        let cardType = cardTypeMap.default

        return {
            card: cardType,
            routingKey: '',
            msg: '',
            target: '',
            network: '',
            systemMessages: [],
            visible: false, // controls fade in
            faded: false, // triggering fade-out
        }
    },
    mounted() {
        this.refreshSystemMessages()
        this.displaySystemMessages()
    },
    methods: {
        displaySystemMessages() {
            if (this.systemMessages.length > 0) {
                let sm = this.systemMessages.shift();

                this.card = this.mapCardType(sm.routingKey)
                this.msg = sm.msg
                this.routingKey = sm.routingKey
                this.visible = false
                this.faded = false

                // Force reflow to reset transition
                requestAnimationFrame(() => {
                    this.visible = true

                    // Start fade after 3 seconds
                    setTimeout(() => {
                        this.faded = true
                    }, 5000)
                })
            }

            clearDisplayInterval()
            displaySystemMessagesId = setTimeout(this.displaySystemMessages, displaySystemMessagesInterval)
        },
        mapCardType(routingKey) {
            let cardType = cardTypeMap.default

            let [network, channel, target] = routingKey.split('.')

            let channelIndex = Object.keys(cardTypeMap).indexOf(channel)
            if (channelIndex > -1) {
                this.network = network
                this.target = target
                cardType = cardTypeMap[channel]

            }

            return cardType
        },
        async refreshSystemMessages() {
            await this.streamSystemMessage()
            clearSystemMessageInterval()
            refreshSystemMessagesId = setTimeout(this.refreshSystemMessages, refreshSystemMessagesInterval)
        },
        async streamSystemMessage() {
            await streamSystemMessage(this.queue, async (chunk) => {
                let lines = chunk.split("\n")
                lines.forEach((line) => {
                    if (trim(line) === '') return;

                    const sysMsg = parseSystemMessage(line, this.queue)

                    if (null === sysMsg.error) {
                        this.systemMessages.push(sysMsg)
                    } else {
                        console.error(sysMsg.error)
                    }
                })
            })
        },
    }
}
</script>
