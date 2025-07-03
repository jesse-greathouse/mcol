<template>
  <a :href="lnk">
    <div :ref="`${network}-${routingKey}-download-card`" v-html="card"></div>
  </a>
</template>

<script>
import { fetchDownloadCard } from '@/Clients/download-card';

export default {
  props: {
    network: String,
    routingKey: String,
    msg: String,
  },
  data() {
    return {
      lnk: '/download',
      card: '',
    };
  },
  mounted() {
    this.fetchCard();
  },
  watch: {
    msg: {
      deep: false,
      handler() {
        this.fetchCard();
      },
    },
  },
  methods: {
    async fetchCard() {
      const parts = this.msg.split('fileName=');
      if (parts.length < 2) return;

      const valStr = parts[1];
      const valParts = valStr.split('&');
      const fileName = valParts[0];
      const refKey = `${this.network}-${this.routingKey}-download-card`;
      const ref = this.$refs[refKey];

      if (ref) {
        try {
          const svg = await fetchDownloadCard(fileName, 'Connecting...');
          ref.innerHTML = svg;
          this.lnk = `/download#${fileName}`;
        } catch (error) {
          console.error(`Attempt ${attempt} failed for ${fileName}:`, error);
        }
      }
    },
  },
};
</script>
