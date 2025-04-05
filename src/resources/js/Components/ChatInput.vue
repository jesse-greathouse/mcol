<template>
    <div class="relative flex flex-row width-full pr-4 py-2">
        <chat-command class="!relative left-5 top-4 z-10" :target="targetId" :selected="command"
            @update:selected="selected => { command = selected }" />

        <chat-parameter v-for="(parameter, index) in parameters" :key="index" class="multiselect-gray"
            :parameter="parameter" :optionList="parameterLists[index]"
            @update:selected="value => { parameters[index] = value }" />

        <button type="submit"
            class="!absolute right-5 top-3 z-10 select-none rounded text-white bg-blue-700 hover:bg-blue-800 focus:ring-none focus:outline-none font-medium text-center px-2 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
            @click="sendMessage()">
            <svg class="size-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M3.3938 2.20468C3.70395 1.96828 4.12324 1.93374 4.4679 2.1162L21.4679 11.1162C21.7953 11.2895 22 11.6296 22 12C22 12.3704 21.7953 12.7105 21.4679 12.8838L4.4679 21.8838C4.12324 22.0662 3.70395 22.0317 3.3938 21.7953C3.08365 21.5589 2.93922 21.1637 3.02382 20.7831L4.97561 12L3.02382 3.21692C2.93922 2.83623 3.08365 2.44109 3.3938 2.20468ZM6.80218 13L5.44596 19.103L16.9739 13H6.80218ZM16.9739 11H6.80218L5.44596 4.89699L16.9739 11Z"
                        fill="#ffffff"></path>
                </g>
            </svg>
        </button>
        <input type="text"
            class="block w-full p-4 pl-32 font-mono text-base text-gray-900 border border-gray-300 rounded bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
            ref="command" :placeholder="placeholder" v-model="message" @input="parse($event.target.value)"
            @keyup.enter="sendMessage()" />
    </div>
</template>

<script>
import { isChannelName, cleanChannelName } from '@/format'
import { COMMAND_MASK, makeIrcCommand, makeParameterLists, parseInput } from '@/chat'
import { saveOperation } from '@/Clients/operation'
import ChatCommand from '@/Components/ChatCommand.vue'
import ChatParameter from '@/Components/ChatParameter.vue'

export default {
    components: {
        ChatCommand,
        ChatParameter,
    },
    props: {
        users: {
            type: Array,
            default: [],
        },
        channels: {
            type: Array,
            default: [],
        },
        servers: {
            type: Array,
            default: [],
        },
        defaultCommand: {
            type: [String, null],
            default: null,
        },
        defaultTarget: {
            type: [String, null],
            default: null,
        },
        network: String,
    },
    data() {
        return {
            commands: COMMAND_MASK,
            parameters: [this.defaultTarget],
            parameterLists: makeParameterLists(this.defaultCommand, {
                user: this.users,
                channel: this.channels,
                server: this.server,
            }),
            command: this.defaultCommand,
            message: '',
        }
    },
    methods: {
        async sendMessage() {
            if (await this.saveOperation()) {
                this.message = ''
            }

            console.log(this.defaultCommand)

            this.command = this.defaultCommand
        },
        async saveOperation() {
            if ('' === this.message) return false
            const network = this.network
            const command = makeIrcCommand(this.message, this.parameters, this.command)

            if (null === command) return false

            const { data, error } = await saveOperation({ command, network })

            if (null === error) {
                this.$emit('call:handleOperation', data, this.command, this.defaultTarget)
                return true
            }

            return false
        },
        parse(input) {
            const { command, parameters, message, parameterLists, error } = parseInput(input, this.commands, this.command, this.parameters, {
                user: this.users,
                channel: this.channels,
                server: this.server,
            })

            if (null !== error) return

            this.command = (null !== command) ? command : this.defaultCommand
            this.parameters = (null !== parameters) ? parameters : this.parameters
            this.parameterLists = (null !== parameterLists) ? parameterLists : this.parameterLists
            this.message = message

            const paramStr = JSON.stringify(parameters)
            const paramListStr = JSON.stringify(parameterLists)
            console.log(`command: ${this.command}`)
            console.log(`paramStr: ${paramStr}`)
            console.log(`message: ${this.message}`)
            console.log(`paramListStr: ${paramListStr}`)
        }
    },
    computed: {
        targetId() {
            if (null === this.defaultTarget) return this.network

            if (isChannelName(this.defaultTarget)) return cleanChannelName(this.defaultTarget)

            return this.defaultTarget
        },
        placeholder() {
            let placeholder = ''

            if (null !== this.defaultTarget) placeholder = `${placeholder} ${this.defaultTarget}`

            return placeholder
        },
    },
    emits: [
        'call:handleOperation',
    ],
}
</script>
