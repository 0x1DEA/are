<script setup>
import MapSearchPage from '@/components/MapSearchPage.vue';
import Header from '@/components/Header.vue';

const props = defineProps({
    lat: Number,
    lng: Number,
    zoom: Number,
});
</script>
<template>
    <Header/>
    <MapSearchPage class="h-[calc(100dvh-7.5rem)]" v-bind="props"/>
    <div class="flex gap-2 justify-between border-t border-neutral-300 bg-neutral-100 text-sm">
        <span class="text-neutral-400 px-2 py-0.5 border-r border-neutral-300">America Real Estate Inc. &copy; 2026 &mdash; Listing data courtesy of MRED via MLS GRID</span>
        <div class="flex divide-x divide-neutral-300 border-l border-neutral-300">
            <a href="tel:+1773735440" class="px-2 py-0.5 text-blue-500 hover:bg-neutral-200">+1 (773) 735-4400</a>
            <a href="mailto:info@americarealestate.com" class="px-2 py-0.5 text-blue-500 hover:bg-neutral-200">info@americarealestateinc.com</a>
            <span class="px-2 py-0.5">5744 S Pulaski Rd, Chicago, IL 60629</span>
        </div>
    </div>
</template>
