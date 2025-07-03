<template>
  <button
    :id="triggerId"
    ref="commandTrigger"
    :class="$attrs.class"
    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
    type="button"
  >
    {{ selectedMask }}
    <svg
      class="w-2.5 h-2.5 ms-3"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 10 6"
    >
      <path
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="m1 1 4 4 4-4"
      />
    </svg>
  </button>

  <!-- Dropdown menu -->
  <div
    :id="targetId"
    ref="commandTarget"
    class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700"
  >
    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" :aria-labelledby="targetId">
      <li v-for="(value, name, index) in commands" :key="value">
        <a
          href="#"
          @click="$emit('update:selected', value)"
          class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          {{ name }}
        </a>
      </li>
    </ul>
  </div>
</template>

<script>
import { Dropdown } from 'flowbite';
import { COMMAND_MASK, getCmdMask } from '@/chat';

export default {
  components: {},
  props: {
    target: String,
    selected: String,
  },
  data() {
    return {
      commands: COMMAND_MASK,
      dropdown: null,
    };
  },
  mounted() {
    this.dropdown = this.makeDropDown();
  },
  methods: {
    makeDropDown() {
      // options with default values
      const options = {
        placement: 'bottom',
        triggerType: 'hover',
        offsetSkidding: 0,
        offsetDistance: 10,
        delay: 100,
        ignoreClickOutsideClass: false,
      };

      /*
       * target: required
       * trigger: required
       * options: optional
       * instanceOptions: optional
       */
      return new Dropdown(this.$refs.commandTarget, this.$refs.commandTrigger, options, {
        id: `commandTarget-${this.target}`,
        override: true,
      });
    },
    emits: ['update:selected'],
  },
  computed: {
    triggerId() {
      return `commandTrigger-${this.target}`;
    },
    targetId() {
      return `commandTarget-${this.target}`;
    },
    selectedMask() {
      return getCmdMask(this.selected);
    },
  },
};
</script>
