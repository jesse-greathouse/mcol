<template>
    <div v-if="links.length > 3">
      <div class="flex flex-wrap -mb-1">
        <template v-for="(link, key) in links">
          <div
            class="mb-1 mr-1 px-4 py-3 text-gray-400 text-sm leading-4 border rounded"
            v-if="link.url === null"
            v-html="link.label" />
                <Link
                    class="mb-1 mr-1 px-4 py-3 focus:text-indigo-500 text-sm leading-4 hover:bg-white border focus:border-indigo-500 rounded"
                    :class="{ 'bg-white': link.active, 'text-slate-300': link.active }" :href="link.url"
                    v-else :key="`link-${key}`"
                    v-html="link.label"
                    @click="nav(link)" />
        </template>
      </div>
    </div>
</template>

<script>
  import { Link } from '@inertiajs/vue3'

  export default {
    components: {
      Link,
    },
    props: {
      links: Array,
    },
    methods: {
        nav(link) {
            let parts = link.url.split("page=")

            if (parts.length > 0) {
                let page = parts[1]
                const indexOfAmp = page.indexOf('&')

                // if there are other url parameters strip them out.
                if (indexOfAmp > -1) {
                    const substrLen = page - (indexOfAmp + 1)
                    page = page.substr(0, substrLen)
                }

                this.$emit('call:navigateToPage', page)
            }
        },
    },
    emits: [
        'call:navigateToPage',
    ],
  }
  </script>
