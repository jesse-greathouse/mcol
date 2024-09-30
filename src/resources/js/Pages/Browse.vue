<template>
    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5">
                <Head title="Browse" />
                <div class="flex items-start justify-start mb-6">
                  <div class="relative m-0 w-1/4 mr-4">
                    <search-filter :model="form.search_string" class="w-full max-w-md" @update:searchString="updateSearchString" @reset="reset" />
                    <div class="absolute inset-y-0 start-16 flex items-center ps-3 pointer-events-none">
                      <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                      </svg>
                    </div>
                    <button v-if="searchStringActive" type="button" @click="resetSearchString" class="text-white absolute end-2 bottom-2 bg-slate-200 hover:bg-slate-300 focus:ring-1 focus:outline-none focus:ring-slate-50 font-medium rounded-lg text-sm px-4 py-2 dark:bg-slate-400 dark:hover:bg-slate-500 dark:focus:ring-slate-300">Clear</button>
                  </div>
                  <span class="m-0 w-48">
                    <Multiselect placeholder="Media" mode='multiple' @change="updateMediaType" class="p-1 hover:text-gray-700 focus:text-indigo-500 text-sm"
                      :multipleLabel="mediaLabel"
                      v-model="form.in_media_type"
                      :options="media_types"
                      ref="media"
                    />
                  </span>
                </div>

                <div class="flex items-start justify-start mb-6">
                  <language-filter :exclude="exclude_languages" :selected="selected_language" :languages="languages" class="w-full max-w-md" @update:languages="updateLanguages"  @update:excludelanguage="updateExcludeLanguages"/>
                </div>

                <div class="bg-white rounded-md shadow overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                    <thead>
                        <tr class="text-left font-bold">
                        <th class="pb-4 pt-6 px-6">Media</th>
                        <th class="pb-4 pt-6 px-6">Gets</th>
                        <th class="pb-4 pt-6 px-6">File Name</th>
                        <th class="pb-4 pt-6 px-6">Size</th>
                        <th class="pb-4 pt-6 px-6">Network</th>
                        <th class="pb-4 pt-6 px-6">Bot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="packet in packets" :key="packet.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4  hover:cursor-pointer" @click="requestDownload(packet.id)">
                                  {{ packet.media_type }}
                              </span>
                          </td>
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4  hover:cursor-pointer" @click="requestDownload(packet.id)">
                                  {{ packet.gets }}
                              </span>
                          </td>
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="requestDownload(packet.id)" tabindex="-1">
                              {{ packet.file_name }}
                              </span>
                          </td>
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="requestDownload(packet.id)" tabindex="-1">
                              {{ packet.size }}
                              </span>
                          </td>
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="requestDownload(packet.id)" tabindex="-1">
                              {{ packet.network }}
                              </span>
                          </td>
                          <td class="border-t">
                              <span class="flex items-center px-6 py-4 hover:cursor-pointer" @click="requestDownload(packet.id)" tabindex="-1">
                              {{ packet.nick }}
                              </span>
                          </td>
                        </tr>
                        <tr v-if="packets.length === 0">
                        <td class="px-6 py-4 border-t" colspan="4">No Packets Found.</td>
                        </tr>
                    </tbody>
                    </table>
                </div>
                <pagination class="mt-6" :links="pagination_nav" />
            </div>
        </div>
    </div>
  </template>
  
  <script>
  import { ref } from 'vue'
  import { Head, Link } from '@inertiajs/vue3'
  import Multiselect from '@vueform/multiselect'
  import _ from 'lodash';
  import pickBy from 'lodash/pickBy'
  import throttle from 'lodash/throttle'
  import mapValues from 'lodash/mapValues'
  import AppLayout from '@/Layouts/AppLayout.vue';
  import Icon from '@/Components/ApplicationMark.vue'
  import Pagination from '@/Components/Pagination.vue'
  import LanguageFilter from '@/Components/LanguageFilter.vue'
  import SearchFilter from '@/Components/SearchFilter.vue'
  
  
  export default {
    components: {
      Head,
      Icon,
      Link,
      Pagination,
      LanguageFilter,
      SearchFilter,
      Multiselect,
    },
    layout: AppLayout,
    props: {
      filters: Object,
      packets: Object,
      path: String,
      current_page: Number,
      from_record: Number,
      to_record: Number,
      per_page: Number,
      last_page: Number,
      total_packets: Number,
      first_page_url: String,
      last_page_url: String,
      prev_page_url: String,
      next_page_url: String,
      pagination_nav: Object,
      media_types: Array,
      languages: Array,
    },
    mounted() {
      //
    },
    data() {
      const in_language = (_.isSet(this.filters.in_language)) ? this.filters.in_language : [];
      const out_language = (_.isSet(this.filters.out_language)) ? this.filters.out_language : [];

      let exclude_languages = false;
      let selected_language = [];
      if (0 <= in_language.length) {
        selected_language = in_language;
      } else if (0 <= out_language.length) {
        exclude_languages = true;
        selected_language = out_language;
      }

      return {
        form: {
          search_string: this.filters.search_string,
          in_media_type: this.filters.in_media_type,
          out_media_type: this.filters.out_media_type,
          in_language: in_language,
          out_language: out_language,
        },
        media_types: this.media_types,
        languages: this.languages,
        selected_language: selected_language,
      }
    },
    computed: {
      searchStringActive() {
        return this.form.search_string && this.form.search_string.length > 0;
      }
    },
    watch: {
      form: {
        deep: true,
        handler: throttle(function () {
          this.$inertia.get('/browse', pickBy(this.form), { preserveState: true })
        }, 150),
      },
    },
    methods: {
      reset() {
        this.$refs.media.clear() // reset the media dropdownlist
        this.form = mapValues(this.form, () => null)
      },
      resetSearchString() {
        this.form.search_string = null;
      },
      updateSearchString(searchString) {
        if (searchString.length > 0) {
          this.search_string_active = true;
          if (searchString.length > 3) {
            this.form.search_string = searchString;
          }
        } else {
          this.search_string_active = false;
        }
      },
      updateMediaType(mediaTypes) {
        this.form.in_media_type = mediaTypes
      },
      mediaLabel(value, select) {
        const tvStr = 'tv '
        const values = []
        const abbr = []
        const maxLen = 6;
        let val = ''
        let sub = ''

        value.forEach((type) => {
          if (0 <= type.value.indexOf(tvStr)) {
            val = type.value.split(tvStr)[1]
          } else {
            val = type.value
          }

          values.push(val)
        });

        // Dynamically shrink the truncation of each option.
        let trunc = maxLen - values.length;
        if (1 > trunc) trunc = 1
        values.forEach((o) => {
          sub = o.substring(0, trunc)
          if (0 > abbr.indexOf(sub)) abbr.push(sub)
        });

        const label = abbr.sort().join(', ').substring(0, 16);
        return `${label} ...`
      },
      updateLanguages(language, checked) {
        const set = (this.exclude_languages) ? 'out_language' : 'in_language';
        const i = this.form[set].indexOf(language)
        if (checked && (0 > i)) {
          this.form[set].push(language)
        } else if (!checked && (0 <= i)) {
          delete this.form[set][i]
        }
      },
      updateExcludeLanguages(checked) {
        if (checked === this.exclude_languages) return;
        this.form.out_language = [];
        this.form.in_language = [];
        this.exclude_languages = checked;
      },
      requestDownload(packetId) {
        const url = `/api/rpc/download`
        const rpcMethod = 'download@request';

        const requestOptions = {
          method: "POST",
          headers: { 
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'Origin': 'http://hera:8080'
          },
          body: JSON.stringify({
              jsonrpc: '2.0',
              method: rpcMethod,
              params: {
                  packet: packetId
              },
              id: 1
          })
        };
        
        fetch(url, requestOptions)
          .then(response => response.json())
          .then(data => {
            console.log(data);
          })
      }
    },
  }
  </script>

<style>
  @import '@vueform/multiselect/themes/tailwind.css';
</style>
