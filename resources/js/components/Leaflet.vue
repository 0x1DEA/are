<script setup>
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    lat: Number,
    lng: Number,
    zoom: Number,
});

const map = ref(null);

onMounted(() => {
    map.value = L.map('map').setView([props.lat, props.lng], props.zoom);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map.value);

    L.marker([props.lat, props.lng]).addTo(map.value)
});
</script>
<template>
    <div id="map"></div>
</template>
