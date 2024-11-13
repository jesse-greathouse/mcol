<template>
    <div class="relative flex flex-row content-end gap-4 grow">
        <!-- Start Chat Pane -->
        <div class="flex flex-col content-end overflow-y-auto w-full mr-3" :style="{ maxHeight: chatPaneHeight }" >
            <div class="p-3">
                <div class="flex">
                    <div class="relative w-12 h-12 shrink-0 undefined" >
                        <img
                            class="w-full h-full rounded-full overflow-hidden object-cover"
                            width="48"
                            height="48"
                            alt="Avatar"
                            src="https://plus.unsplash.com/premium_photo-1663076389306-44e78d5f2b82?ixlib=rb-4.0.3.&ix"
                        />
                    </div>
                    <div class="pl-3">
                        <div class="flex items-center mb-3">
                            <span class="font-bold">Putri Tanjak</span>
                            <span class="text-sm text-gray-400 shrink-0 ml-2">4:30 AM</span>
                        </div>
                        <div class="flex flex-col gap-3 items-start">
                            <div class="max-w-md p4 rounded-2xl overflow-hidden rounded-tl-none bg-message">
                                Look at this field! Beautiful!
                            </div>
                            <div class="max-w-md p0 rounded-2xl overflow-hidden false bg-message">
                                <img
                                    class="w-62 h-48"
                                    width="300"
                                    height="400"
                                    alt="photo"
                                    src="https://images.unsplash.com/photo-1566732500818-d76031ac0421?ixlib=rb-4.0.3&amp;"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative shrink-0 my-6 h-px bg-gray-200">
                <span class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 uppercase block">Today</span>
            </div>
            <div class="py-3">
                <div class="flex flex-row-reverse">
                    <div class="relative w-12 h-12 shrink-0 undefined">
                        <img
                            class="w-full h-full rounded-full overflow-hidden object-cover"
                            width="48"
                            height="48"
                            alt="Avatar"
                            src="https://plus.unsplash.com/premium_photo-1663076389306-44e78d5f2b82?ixlib=rb-4.0.3.&ix"
                        />
                    </div>
                    <div class="pr-3">
                        <div class="flex items-center mb-3 flex-row-reverse">
                            <span class="font-bold">Putri Tanjak</span>
                            <span class="text-sm text-gray-400 shrink-0 mr-2">4:30 AM</span>
                        </div>
                        <div class="flex flex-col gap-3 items-end">
                            <div class="max-w-md p-4 rounded-2xl overflow-hidden rounded-tr-none bg-nav text-white">
                                This is Content! This is Content! This is Content! This is Content! This is Content!
                            </div>
                            <div class="max-w-md p-0 rounded-2xl overflow-hidden false bg-nav text-white">
                                <img
                                    class="w-48 h-62"
                                    width="300"
                                    height="300"
                                    alt="photo"
                                    src="https://images.unsplash.com/photo-1563450151580-e391a277d92b?ixlib=rb-4.0.3&amp;"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="py-3">
                <div class="flex">
                    <div class="relative w-12 h-12 shrink-9 undefined">
                        <img
                            class="w-full h-full rounded-full overflow-hidden object-cover"
                            width="48"
                            height="48"
                            alt="Avatar"
                            src="https://plus.unsplash.com/premium_photo-1663076389306-44e78d5f2b82?ixlib=rb-4.0.3.&ix"
                        />
                    </div>
                    <div class="pl-3">
                        <div class="flex items-center mb-3">
                            <span class="font-bold">Putri Tanjak</span>
                            <span class="text-sm text-gray-400 shrink-0 ml-2">4:30 AM</span>
                        </div>
                        <div class="flex flex-col gap-3 items-start">
                            <div class="max-w-md p-4 rounded-2xl overflow-hidden rounded-tl-none bg-message">
                                This is Content! This is Content! This is Content! This is Content!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Console Pane -->
    </div>

    <!-- Start Console Input -->
    <div class="flex flex-row items-center p-4 grow-0">
        <button class="shrink-0 text-gray-400" type="button" aria-label="Add media to message">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>
        <div class="relative flex w-full max-h-24 overflow-hidden">
            <div
                class="w-full outline-0"
                contenteditable="true"
                tabindex="0"
                dir="ltr"
                spellcheck="false"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
            >
                Type your message here...
            </div>
        </div>
        <button
            class="flex items-center justify-center shrink-0 w-12 h-12 bg-nav rounded-full overflow-hidden"
            type="button"
            aria-label="Submit" >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
            </svg>
        </button>
    </div>
    <!-- End Console Input -->
</template>

<script>
import throttle from 'lodash/throttle'
import { scaleToViewportHeight } from '@/style'

const chatPaneScale = .76

export default {
  components: {
  },
  props: {
    user: String,
    network: String,
    isActive: Boolean,
  },
  data() {
    return {
        chatPaneHeight: this.scaleToViewportHeight(chatPaneScale)
    }
  },
  watch: {
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
    this.userList = this.makeUserList()
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize);
  },
  methods: {
    scaleToViewportHeight,
    handleResize() {
        this.chatPaneHeight = this.scaleToViewportHeight(chatPaneScale)
    },
  },
  emits: [],
}
</script>
