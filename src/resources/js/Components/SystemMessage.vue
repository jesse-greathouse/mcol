<template>
  <teleport to="body">
    <div class="fixed top-0 left-0 w-full h-full pointer-events-none">
      <div
        class="max-w-6xl z-50 m-2 absolute bottom-20 left-10 inline-block transition-opacity duration-2000"
        :class="{
          'opacity-0 pointer-events-none': !visible || faded,
          'opacity-100 pointer-events-auto': visible && !faded,
        }"
      >
        <component
          v-bind:is="card"
          :routingKey="routingKey"
          :msg="msg"
          :network="network"
          :target="target"
        />
      </div>
    </div>
  </teleport>
</template>

<script>
import { toRaw } from 'vue';
import { streamSystemMessage } from '@/Clients/stream';
import { parseSystemMessage } from '@/system-message';
import DefaultCard from '@/Components/Cards/SystemMessage.vue';
import NoticeCard from '@/Components/Cards/Notice.vue';
import MsgCard from '@/Components/Cards/Msg.vue';

const cardTypeMap = {
  notice: NoticeCard,
  msg: MsgCard,
  default: DefaultCard,
};

const refreshSystemMessagesInterval = 3000; // Check system messages every 3 seconds.
const displaySystemMessagesInterval = 1000; // Display a system message only at this interval.

export default {
  components: {
    DefaultCard,
    NoticeCard,
    MsgCard,
  },
  props: {
    queue: String,
  },
  data() {
    return {
      card: cardTypeMap.default,
      routingKey: '',
      msg: '',
      target: '',
      network: '',
      systemMessages: [],
      visible: false, // controls fade in
      faded: false, // triggering fade-out
      displaySystemMessagesId: null,
      refreshSystemMessagesId: null,
    };
  },
  mounted() {
    this.refreshSystemMessages();
    this.displaySystemMessages();
  },
  beforeUnmount() {
    clearTimeout(this.displaySystemMessagesId);
    clearTimeout(this.refreshSystemMessagesId);
  },
  methods: {
    async displaySystemMessages() {
      this.updateSystemMessage();
      clearTimeout(this.displaySystemMessagesId);
      this.displaySystemMessagesId = setTimeout(
        this.displaySystemMessages,
        displaySystemMessagesInterval
      );
    },
    async refreshSystemMessages() {
      await this.streamSystemMessage();
      clearTimeout(this.refreshSystemMessagesId);
      this.refreshSystemMessagesId = setTimeout(
        this.refreshSystemMessages,
        refreshSystemMessagesInterval
      );
    },
    updateSystemMessage() {
      if (this.systemMessages.length > 0) {
        let sm = toRaw(this.systemMessages.shift());
        const [network, channel, target] = sm.routingKey.split('.');

        //Set it to be invisible
        this.visible = false;
        this.faded = false;

        // Swap out the card
        if (Object.keys(cardTypeMap).indexOf(channel) > -1) {
          this.card = cardTypeMap[channel];
        } else {
          this.card = cardTypeMap.default;
        }

        this.msg = sm.msg;
        this.routingKey = sm.routingKey;
        this.network = network;
        this.target = target;

        //Force reflow to reset transition
        requestAnimationFrame(() => {
          this.visible = true;

          // Start fade after 2 seconds
          setTimeout(() => {
            this.faded = true;
          }, 2000);
        });
      }
    },
    async streamSystemMessage() {
      await streamSystemMessage(this.queue, async (chunk) => {
        let lines = chunk.split('\n');
        lines.forEach((line) => {
          if (line.trim() === '') return;

          const sysMsg = parseSystemMessage(line, this.queue);

          if (null === sysMsg.error) {
            this.systemMessages.push(sysMsg);
          } else {
            console.error(sysMsg.error);
          }
        });
      });
    },
  },
};
</script>
