<template>
    <a :href=lnk>
        <div :ref="`${network}-${routingKey}-download-card`" v-html="card"></div>
    </a>
</template>

<script>
import { fetchDownloadCard } from '@/Clients/download-card'

export default {
    props: {
        network: String,
        routingKey: String,
        msg: String,
    },
    data() {
        return {
            lnk: 'download',
            card: '',
        }
    },
    mounted() {
        this.fetchCard()
    },
    updated() {
        this.fetchCard()
    },
    methods: {
        wait(ms) {
            return new Promise(resolve => setTimeout(resolve, ms))
        },
        async fetchCard() {
            const parts = this.msg.split('fileName=')
            if (parts.length < 2 ) return

            const valStr = parts[1]
            const valParts = valStr.split('&')
            const fileName = valParts[0]
            const refKey = `${this.network}-${this.routingKey}-download-card`
            const card = this.$refs[refKey]

            for (let attempt = 1; attempt <= 3; attempt++) {
                try {
                    const svg = await fetchDownloadCard(fileName)
                    if (card) {
                        this.lnk = `download#${fileName}`
                        card.innerHTML = svg
                    }
                    return // Success, exit the method
                } catch (error) {
                    console.error(`Attempt ${attempt} failed for ${fileName}:`, error)
                    if (attempt < 3) {
                        await this.wait(500)
                    }
                }
            }

            // If we got here, all 3 attempts failed
            const ref = this.$refs[refKey]
            if (ref && ref.parentNode) {
                ref.parentNode.removeChild(ref)
            }
        }
    }
}
</script>
