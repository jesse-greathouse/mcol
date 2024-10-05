<template>
  <span class="inline-grid w-4 place-content-center mr-2">
    <div class="block w-4 h-4">
      <svg data-accordion-icon class="w-4 h-4 shrink-0" :class="strokeWidth('desc'), stroke('desc'), fill('desc')" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5 5 1 1 5"/>
      </svg>
    </div>
    <div class="block w-4 h-4">
      <svg data-accordion-icon class="w-4 h-4 rotate-180 shrink-0" :class="strokeWidth('asc'), stroke('asc'), fill('asc')" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5 5 1 1 5"/>
      </svg>
    </div>
  </span>
</template>
  
<script>
const defaultStrokeWidth = 'stroke-1'
const defaultStroke = 'fill-slate-500'
const defaultFill = 'fill-slate-400'

const activeStrokeWidth = 'stroke-2'
const activeStroke = 'fill-cyan-500'
const activeFill = 'fill-cyan-400'

export default {
  props: {
    order: String,
    currentOrder: {
        type: String,
        default: 'created'
    },
    currentDirection: {
        type: String,
        default: 'asc'
    },
  },
  methods: {
    strokeWidth(direction) {
      if (direction === this.currentDirection) {
        return (this.order === this.currentOrder) ? activeStrokeWidth : defaultStrokeWidth
      } else {
        return defaultStrokeWidth;
      }
    },
    stroke(direction) {
      if (direction === this.currentDirection) {
        return (this.order === this.currentOrder) ? activeStroke : defaultStroke
      } else {
        return defaultStroke;
      }
    },
    fill(direction) {
      if (direction === this.currentDirection) {
        return (this.order === this.currentOrder) ? activeFill : defaultFill
      } else {
        return defaultFill;
      }
    }
  },
}
  </script>
