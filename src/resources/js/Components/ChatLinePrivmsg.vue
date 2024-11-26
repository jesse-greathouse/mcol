<template>
    <div class="flex items-start w-full mr-4">
        <div class="inline-flex items-start mr-1">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded border" :class="nickClass"  >
                {{ nick }}
            </span>&colon;
        </div>
        <div class="inline-flex items-start">{{ message }}</div>
    </div>
</template>

<script>

export default {
  props: {
    settings: Object,
    downloads: Object,
    nick: String,
    message: String,
    color: {
        type: String,
        default: 'gray',
    },
  },
  computed: {
    nickClass() {
        return [
            `bg-${this.color}-100`,
            `text-${this.color}-800`,
            `border-${this.color}-800`,
            `dark:bg-${this.color}-700`,
            `dark:text-${this.color}-400`,
            `dark:border-${this.color}-400`,
        ]
    },
    },
}
</script>
