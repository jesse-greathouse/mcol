<template>
    <p>{{ content }}</p>
</template>

<script>
export default {
    inheritAttrs: false,
    props: {
        content: String,
    },
}
</script>
