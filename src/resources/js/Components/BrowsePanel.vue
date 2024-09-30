<script setup>
import BrowseForm from '@/Components/BrowseForm.vue';
import BrowseTable from '@/Components/BrowseTable.vue';

const props = defineProps({ 
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

});

const emit = defineEmits(['change'])

console.log(props);

</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <BrowseForm />
        </div>

        <div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
            <div>
                <BrowseTable />
            </div>
        </div>
    </div>
</template>
