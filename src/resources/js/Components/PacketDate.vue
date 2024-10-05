<template>
  <span>{{ format(date) }}</span>
</template>
  
<script>
export default {
  props: {
    date: String,
  }, methods: {
    format: (dateStr) => {
      // Expect the packet date to be GMT
      const packet = new Date(`${dateStr} GMT`);
      const local = new Date()

      if (local.getDay() === packet.getDay()) {
        return packet.toLocaleTimeString('en-US')
      } else {
        return packet.toLocaleDateString('en-US')
      }
    }
  }
}
  </script>
