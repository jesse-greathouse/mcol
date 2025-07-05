<template>
  <div class="flex flex-row items-start">
    <div class="flex flex-row items-center">
      <span class="flex w-3 h-3 m-2 bg-yellow-500 rounded-full"></span>
      <span class="text-yellow-700 text-medium font-bold m-2 py-2 dark:text-yellow-300">
        {{ network }}
      </span>
      <span class="text-yellow-700 text-medium font-bold m-2 py-2 dark:text-yellow-300"
        >Queued</span
      >
    </div>
    <div class="flex flex-row items-start max-w-3/4">
      <span class="text-slate-800 dark:text-slate-200 text-medium font-normal m-2 py-2">
        <a class="text-yellow-700 dark:text-yellow-300 font-extrabold">{{ fileName }}</a>
        <template v-if="position !== '?' && total !== '?'">
          &nbsp;position
          <a class="text-yellow-700 dark:text-yellow-300 font-extrabold">{{ position }}</a>
          &#x2f;
          <a class="text-yellow-700 dark:text-yellow-300 font-extrabold">{{ total }}</a>
        </template>
      </span>
      <span
        class="text-slate-800 dark:text-slate-200 text-medium font-normal m-2 py-2 max-w-1/3"
        v-if="timeQueued !== '' || timeRemaining !== ''"
      >
        <template v-if="timeQueued !== ''">
          <div class="flex justify-between">
            <span>for: </span>
            <span class="text-yellow-700 dark:text-yellow-300 font-extrabold"
              >{{ timeQueued }}
            </span>
          </div>
        </template>
        <template v-if="timeRemaining !== ''">
          <div class="flex justify-between">
            <span>remaining: </span>
            <span class="text-yellow-700 dark:text-yellow-300 font-extrabold"
              >{{ timeRemaining }}
            </span>
          </div>
        </template>
      </span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    network: String,
    routingKey: String,
    msg: String,
  },
  data() {
    return {
      fileName: '',
      timeQueued: '',
      position: '?',
      total: '?',
      timeRemaining: '',
      time: '',
    };
  },
  mounted() {
    this.extract();
  },
  updated() {
    this.extract();
  },
  methods: {
    extract() {
      const [timeQueued, fileName, position, total, timeRemaining, time] = this.extractData();
      this.timeQueued = timeQueued;
      this.fileName = fileName;
      this.position = position;
      this.total = total;
      this.timeRemaining = timeRemaining;
      this.time = time;
    },
    extractData() {
      let [timeQueued, fileName, position, total, timeRemaining, time] = ['', '', '?', '?', '', ''];

      if (this.msg.indexOf('Queued') > -1) {
        let match = this.matchQueuedUpdate();

        if (match) {
          return match;
        }

        match = this.matchQueuedNotice();

        if (match) {
          fileName = match[2];
        } else {
          match = this.matchFallback();
          fileName = match && match.length > 2 ? match[2] : '';
        }
      }

      return [timeQueued, fileName, position, total, timeRemaining, time];
    },
    intToIp(intVal) {
      const part1 = intVal & 255;
      const part2 = (intVal >> 8) & 255;
      const part3 = (intVal >> 16) & 255;
      const part4 = (intVal >> 24) & 255;

      return `${part4}.${part3}.${part2}.${part1}`;
    },
    matchQueuedUpdate() {
      // Queued 0h59m for "SOME MEDIA TITLE 2015 1080p 264.mkv", in position 1 of 1. 111h54m or more remaining. (at 19:11)
      // regexr.com/8de39
      const re =
        /Queued\s+(\d{1,}h\d{1,}m)\s+for\s"(.*)",\s+in\s+position\s+(\w+)\s+of\s+(\w+)\.\s+(\d{1,}h\d{1,2}m)\s+or\s+(?:more|less)\s+remaining\.\s+\(at\s+(\d+:\d+)\)/g;

      const match = re.exec(this.msg);

      if (match && match.length > 0) {
        match.shift();
      }

      return match;
    },
    matchQueuedNotice() {
      // Queued for #3690 Some.Media.Title.1080p.H264.mkv - 1.3G
      // regexr.com/8de2q
      const re = /Queued\s+for\s+#(\d{1,})\s+([\w\-._]+)\s+?.+?$/g;

      return re.exec(this.msg);
    },
    matchFallback() {
      // Queued for #82 The Brutalist (2024) Bluray-1080p.mkv -  31G
      // regexr.com/8de3f
      const re = /Queued\s+for\s+#(\d{1,})\s+(.*)$/g;

      return re.exec(this.msg);
    },
  },
};
</script>
