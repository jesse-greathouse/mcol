<template>
    <div class="flex w-auto items-center m-3">
        <label class="inline-flex items-center cursor-pointer">
            <input v-bind:checked="exclude" @input="$emit('update:excludeLanguage', $event.target.checked)"
                type="checkbox" value="" class="sr-only peer">
            <div
                class="relative w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-200 dark:peer-focus:ring-red-300 dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-400">
            </div>
        </label>
    </div>

    <ul
        class="items-center w-auto text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg sm:flex dark:bg-gray-700 dark:border-gray-600 dark:text-white">
        <li v-for="language in languages" :key="`language-option-${language}`" ref="items"
            class="w-full border-b border-gray-200 sm:border-b-0 sm:border-r dark:border-gray-600">
            <div class="flex items-center ps-2">
                <input :ref="`${language}_box`" v-bind:checked="languageSelected(language)" v-bind:id="language"
                    @input="$emit('update:languages', language, $event.target.checked)" type="checkbox"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                    :class="accent, color, ring">
                <label for="{{ language }}"
                    class="w-full py-3 ms-1 mr-2 text-sm font-medium text-gray-900 dark:text-gray-300 capitalize">{{
                    language }}</label>
            </div>
        </li>
    </ul>
</template>

<script>
export default {
    props: {
        exclude: Boolean,
        in_language: Array,
        out_language: Array,
        languages: Array,
    },
    computed: {
        accent() {
            return (this.exclude) ? 'accent-red-400' : 'accent-green-400'
        },
        color() {
            return (this.exclude) ? 'text-red-400' : 'text-green-400'
        },
        ring() {
            return (this.exclude) ? 'focus:ring-red-400' : 'focus:ring-green-400'
        },
    },
    methods: {
        languageSelected(language) {
            const set = (this.exclude) ? 'out_language' : 'in_language'
            if (this[set]) {
                return (0 <= this[set].indexOf(language)) ? true : false;
            } else {
                return false;
            }
        },
    },
    emits: ['update:languages', 'update:excludeLanguage'],
}
</script>
