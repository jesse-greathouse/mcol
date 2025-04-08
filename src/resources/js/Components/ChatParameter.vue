<template>
    <div>
        <input type="text"
            class="text-base font-medium block w-full rounded-md border transition ease-in-out focus:ring-1 border-gray-300 border-solid py-2 px-3 text-gray-700 placeholder-gray-400 focus:border-blue-200 focus:ring-blue-500 focus:outline-none"
            v-model="selected" @change="onChange" @input="onChange" />
        <ul class="mt-1 border-2 border-slate-50 overflow-auto  shadow-lg rounded list-none" v-if="isOpen">
            <li :class="['hover:bg-blue-100 hover:text-blue-800', 'w-full list-none text-left py-2 px-3 cursor-pointer']"
                v-for="option in options" :key="`parameter-option-${option}`" @click="onSelect(option)">
                {{ option }}
            </li>
        </ul>
    </div>
</template>

<script>
import { inList } from '@/funcs'

export default {
    components: {
    },
    props: {
        parameter: String,
        optionList: Array,
    },
    data() {
        return {
            selected: this.parameter,
            isOpen: false,
            options: [],
        }
    },
    mounted() { },
    methods: {
        onSelect(option) {
            this.selected = option
            if (inList(this.selected, this.optionList)) {
                this.isOpen = false
            }
        },
        onChange() {
            this.$emit('update:selected', this.selected)

            if (inList(this.selected, this.optionList)) {
                this.isOpen = false
                return
            }

            this.isOpen = true
            this.filterOptions()
        },
        filterOptions() {
            this.options = this.optionList.filter((item) => {
                return item.toLowerCase().indexOf(this.selected) > -1
            })
        }
    },
    emits: [
        'update:selected',
    ],
}
</script>
