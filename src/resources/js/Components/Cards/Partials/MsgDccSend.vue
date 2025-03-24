<template>
    <div class="flex flex-row">
        <div class="flex flex-row items-center">
            <span class="flex w-3 h-3 m-2 bg-blue-500 rounded-full"></span>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-gray-700 dark:text-blue-400 border border-blue-400">{{ network }}</span>
            <span class="text-blue-800 text-medium font-bold me-2 px-2.5 py-0.5 dark:bg-blue-900 dark:text-blue-300">{{ target }}</span>
        </div>
        <div class="max-w-5xl m-2 overflow-x-hidden">
            <p class="max-w-full bg-zinc-100 border border-zinc-400 rounded-lg justify-start text-Neutral-800 text-base p-2">
                <a class="font-extrabold underline decoration-sky-500">{{ fileName }}</a> transfer opened at: <a class="underline decoration-pink-500">{{ ip }}</a>:<a class="underline decoration-indigo-500">{{ port }}</a>
            </p>
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
        }
    },
    mounted() {
        const [fileName, ip, port] = this.extractData()
        this.fileName = fileName
        this.ip = ip
        this.port = port
    },
    methods: {
        extractData() {
            let [fileName, intIp, ip, port] = ['', null, '', ''];

            if (this.msg.indexOf('DCC SEND') > -1) {
                const re = /DCC\s+SEND\s+(.*)$/;
                const match = re.exec(this.msg);

                if (match) {
                    const meta = match[1];
                    ([fileName, intIp, port] = meta.split(" "));
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
        }
    }
}
</script>
