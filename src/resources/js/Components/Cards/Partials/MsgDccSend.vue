<template>
  <div class="flex flex-row items-start gap-3">
    <div class="flex flex-row items-center gap-3">
      <span class="flex w-3 h-3 m-2 bg-blue-500 rounded-full"></span>
      <span class="text-blue-800 dark:text-blue-300 text-medium font-bold py-2">
        {{ network }}
      </span>
      <span class="text-blue-800 dark:text-blue-300 text-medium font-bold py-2 break-keep">{{
        target
      }}</span>
    </div>
    <div class="m-2 font-normal">
      <span
        class="text-slate-800 dark:text-slate-200 break-words overflow-hidden tracking-tight font-medium"
      >
        <a class="font-extrabold underline decoration-sky-500">{{ fileName }}</a> &raquo;
        <a class="underline decoration-pink-500">{{ ip }}</a
        >:<a class="underline decoration-indigo-500">{{ port }}</a>
      </span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    network: String,
    target: String,
    routingKey: String,
    msg: String,
  },
  data() {
    return {
      fileName: '',
      ip: '',
      port: '',
    };
  },
  mounted() {
    const [fileName, ip, port] = this.extractData();
    this.fileName = fileName;
    this.ip = ip;
    this.port = port;
  },
  methods: {
    extractData() {
      let [fileName, intIp, ip, port] = ['', null, '', ''];

      if (this.msg.indexOf('DCC SEND') > -1) {
        const re = /DCC\s+SEND\s+(.*)$/;
        const match = re.exec(this.msg);

        if (match) {
          const meta = match[1];
          [fileName, intIp, port] = meta.split(' ');
          ip = this.intToIp(intIp);
        }
      }

      return [fileName, ip, port];
    },
    intToIp(intVal) {
      const part1 = intVal & 255;
      const part2 = (intVal >> 8) & 255;
      const part3 = (intVal >> 16) & 255;
      const part4 = (intVal >> 24) & 255;

      return `${part4}.${part3}.${part2}.${part1}`;
    },
  },
};
</script>
