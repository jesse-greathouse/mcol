<template>
  <div class="py-12">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2.5" :class="contentClass">
        <Head title="Chat" />
        <div class="flex items-start justify-start mb-4">
            Box A
        </div>

        <div class="flex items-start justify-start mb-4">
            Box B
        </div>

        <div class="flex items-start justify-start mb-6">
            Box C
        </div>

        <div class="bg-white rounded-md shadow overflow-x-auto">
          Box D
        </div>
      </div>
    </div>
  </div>
  </template>

  <script>
  import axios from 'axios';
  import _ from 'lodash'
  import { Head, Link } from '@inertiajs/vue3'
  import { initFlowbite } from 'flowbite'
  import { mergeDataIntoQueryString, hrefToUrl } from '@inertiajs/core'
  import Multiselect from '@vueform/multiselect'
  import pickBy from 'lodash/pickBy'
  import throttle from 'lodash/throttle'
  import mapValues from 'lodash/mapValues'

  // local imports
  import { fetchNetworkClients } from '@/Clients/network'
  import { formatDate } from '@/format'
  import AppLayout from '@/Layouts/AppLayout.vue'

  const clientsInterval = 10000 // Check network connections every 10 seconds.
  let clientsTimeoutId
  const clearClientsInterval = function () {
    clearClientsInterval(clientsTimeoutId)
  }

  const clearAllIntervals = function() {
    clearClientsInterval()
  }

  export default {
    components: {
      Head,
      Link,
      Multiselect,
    },
    layout: AppLayout,
    props: {
      settings: Object,
      networks: Array,
      clients: Object,
    },
    mounted() {
      initFlowbite()
      this.resetIntervals()
    },
    methods: {
      resetIntervals() {
        clearAllIntervals()
        clientsTimeoutId = setTimeout(fetchNetworkClients, clientsInterval);
      },
    },
  }
  </script>

<style> @import '@vueform/multiselect/themes/tailwind.css' </style>
