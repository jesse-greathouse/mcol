<template>
  <svg
    class="size-full"
    :class="svgClass"
    viewBox="0 -1.5 35 35"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    <g id="SVGRepo_iconCarrier">
      <path
        d="M29.426 15.535c0 0 0.648-8.743-7.361-9.74-6.866-0.701-8.955 5.679-8.955 5.679s-2.067-1.988-4.873-0.364c-2.51 1.55-2.067 4.388-2.067 4.388s-5.576 1.083-5.576 6.768c0.125 5.677 6.055 5.734 6.055 5.734h11.351l-5-5h3v-6h4v6h3l-5 5h10.467c0 0 5.52 0.006 6.295-5.395 0.369-5.906-5.336-7.070-5.336-7.070z"
      ></path>
    </g>
  </svg>
</template>

<script>
export default {
  props: {
    height: {
      type: String,
      default: '8',
    },
    fill: {
      type: String,
      default: 'blue',
    },
  },
  computed: {
    svgClass() {
      return [`h-${this.height}`, `fill-${this.fill}-600`];
    },
  },
};
</script>
