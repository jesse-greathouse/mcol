<template>
  <svg
    class="size-full"
    :class="svgClass"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
    <g id="SVGRepo_iconCarrier">
      <path
        d="M4 12C4 11.0572 4 10.5858 4.29289 10.2929C4.58579 10 5.05719 10 6 10H18C18.9428 10 19.4142 10 19.7071 10.2929C20 10.5858 20 11.0572 20 12V18.0375C20 18.4185 20 18.6091 19.9711 18.7772C19.8575 19.4381 19.4203 19.9981 18.8067 20.2685C18.6506 20.3374 18.4658 20.3836 18.0961 20.476C16.8589 20.7853 16.2403 20.9399 15.6215 21.0539C13.2273 21.4947 10.7727 21.4947 8.37847 21.0539C7.75968 20.9399 7.14108 20.7853 5.90388 20.476C5.53423 20.3836 5.34941 20.3374 5.19329 20.2685C4.5797 19.9981 4.14248 19.4381 4.0289 18.7772C4 18.6091 4 18.4185 4 18.0375V12Z"
        fill="#2A4157"
        fill-opacity="0.24"
      ></path>
      <path
        d="M16.5 10V9C16.5 6.51472 14.4853 4.5 12 4.5V4.5C9.51472 4.5 7.5 6.51472 7.5 9V10"
        stroke="#5d6269"
      ></path>
      <circle cx="12" cy="15" r="2" fill="#5d6269"></circle>
      <path d="M12 16V18.5" stroke="#5d6269" stroke-linecap="round"></path>
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
      default: 'gray',
    },
  },
  computed: {
    svgClass() {
      return [`h-${this.height}`, `fill-${this.fill}-600`];
    },
  },
};
</script>
