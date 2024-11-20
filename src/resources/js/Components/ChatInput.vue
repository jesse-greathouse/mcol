<template>
    <div class="relative flex flex-row width-full pr-4 py-2">
        <button type="submit" class="!absolute right-5 top-3 z-10 select-none rounded text-white bg-blue-700 hover:bg-blue-800 focus:ring-none focus:outline-none font-medium text-center px-2 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            @click="sendMessage()" >
            <svg class="size-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.3938 2.20468C3.70395 1.96828 4.12324 1.93374 4.4679 2.1162L21.4679 11.1162C21.7953 11.2895 22 11.6296 22 12C22 12.3704 21.7953 12.7105 21.4679 12.8838L4.4679 21.8838C4.12324 22.0662 3.70395 22.0317 3.3938 21.7953C3.08365 21.5589 2.93922 21.1637 3.02382 20.7831L4.97561 12L3.02382 3.21692C2.93922 2.83623 3.08365 2.44109 3.3938 2.20468ZM6.80218 13L5.44596 19.103L16.9739 13H6.80218ZM16.9739 11H6.80218L5.44596 4.89699L16.9739 11Z" fill="#ffffff"></path>
                </g>
            </svg>
        </button>
        <input type="text" class="block w-full p-4 font-mono text-base text-gray-900 border border-gray-300 rounded bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            ref="command"
            :placeholder="placeholder"
            v-model="message"
            @keyup.enter="sendMessage()" />
    </div>
</template>

<script>
import _ from 'lodash'
import { COMMAND, getCmdMask, makeIrcCommand } from '@/chat'
import { saveOperation } from '@/Clients/operation'

export default {
  components: {
  },
  props: {
    default: {
      type: [String, null],
      default: null,
    },
    target: {
      type: [String, null],
      default: null,
    },
    network: String,
  },
  data() {
    return {
        command: this.default,
        message: '',
    }
  },
  mounted() {
  },
  computed: {
    placeholder() {
        let placeholder = ''

        if (null !== this.command) {
            const mask = getCmdMask(this.command)
            if (null !== mask) placeholder = `${mask}`
            if (null !== this.target) placeholder = `${placeholder} ${this.target}`
        }

        return placeholder
    }
  },
  methods: {
    async sendMessage() {
        if (await this.saveOperation()) {
            this.message = ''
            this.command = this.default
        }
    },
    async saveOperation() {
        if ('' === this.message) return false
        const network = this.network
        const command = makeIrcCommand(this.message, this.target, this.command)

        if (null === command) return false

        const {data, error} = await saveOperation({command, network})

        if (null === error) {
            this.$emit('call:handleOperation', data, this.command, this.target)
            return true
        }

        return false
    }
  },
  emits: [
    'call:handleOperation',
  ],
}
</script>
