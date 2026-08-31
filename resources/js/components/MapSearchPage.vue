<script setup>
import L from 'leaflet';
import { onMounted, ref } from 'vue';
import 'leaflet/dist/leaflet.css';
import { Link } from '@inertiajs/vue3';
import { fmtPrice, fmtPriceLng, listingAddress } from '@/lib/utils.ts';

const map = ref(null);
const markers = ref(L.layerGroup());
const popup = ref(L.popup());

const gridListings = ref([]);
const markerListings = ref([]);

onMounted(() => {
    map.value = L.map('map').setView([41.85, -87.99], 11);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        minZoom: 10,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map.value);

    markers.value.addTo(map.value);

    map.value.on('moveend', searchMap);

    searchMap();
});

const searchMap = async () => {
    let bounds = map.value.getBounds();

    let coords = [
        bounds.getNorthEast(),
        bounds.getNorthWest(),
        bounds.getSouthWest(),
        bounds.getSouthEast(),
        bounds.getNorthEast(),
        bounds.getCenter(),
    ];

    for (let coord in coords) coords[coord] = [coords[coord].lat.toFixed(5), coords[coord].lng.toFixed(5)].join(',');

    let center = coords.pop();

    let res = await (await fetch('/api/search/' + coords.join(';') + '/' + center)).json();

    markers.value.clearLayers();

    gridListings.value = res;

    for (const listing in res) {
        let el = document.createElement('div');
        el.className = 'leaflet-custom-marker-popup';
        el.innerText = fmtPrice(res[listing], true);

        let markdiv = L.divIcon({
            className: 'leaflet-custom-marker group',
            html: el,
        });

        L.marker(res[listing].coordinates.coordinates, {
            icon: markdiv,
        }).addTo(markers.value);
    }
};

const properties = ['ad', 'asda', 'gasdda', 'fdsa'];
</script>
<template>
    <div class="flex h-screen flex-col bg-white">
        <div class="flex items-center gap-2 bg-neutral-100 p-2">
            <label class="flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2 py-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="size-5 text-neutral-500"
                >
                    <path
                        fill-rule="evenodd"
                        d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                        clip-rule="evenodd"
                    />
                </svg>
                <input type="text" placeholder="Search" class="focus-visible:outline-0" />
            </label>
            <button class="rounded-lg border border-neutral-200 bg-white px-2 py-1">For Sale</button>
            <button class="rounded-lg border border-neutral-200 bg-white px-2 py-1">Price</button>
            <button class="rounded-lg border border-neutral-200 bg-white px-2 py-1">Baths</button>
            <button class="rounded-lg border border-neutral-200 bg-white px-2 py-1">Beds</button>
            <button class="rounded-lg border border-neutral-200 bg-white px-2 py-1">Type</button>
            <button @click="searchMap" class="rounded-lg border border-neutral-200 bg-white px-2 py-1">Search</button>
        </div>
        <div class="flex grow">
            <div class="flex w-1/3 flex-col">
                <div class="flex items-end justify-between border-b border-neutral-200 px-4 pb-3 pt-2">
                    <div>
                        <h1 class="text-lg font-bold">Properties For Sale</h1>
                        <span class="text-sm text-neutral-500">{{ gridListings.length }} Results</span>
                    </div>
                    <span>Sorting</span>
                </div>
                <div class="relative grow">
                    <div class="absolute inset-0 grid grid-cols-2 gap-4 overflow-y-auto p-4">
                        <Link
                            v-for="p in gridListings"
                            :key="p.mls_id"
                            :href="`/listing/${p.mls_id}`"
                            class="flex flex-col"
                        >
                            <div
                                class="relative flex aspect-video rounded-lg bg-neutral-500 bg-cover bg-center text-white"
                            >
                                <div class="absolute inset-0 overflow-hidden rounded-lg">
                                    <img
                                        alt=""
                                        class="absolute inset-0 object-cover"
                                        loading="lazy"
                                        :src="
                                            (p.data.Media?.[0]?.MediaURL ?? null)
                                                ? '/api/proxy/' + p.data?.Media?.[0]?.MediaURL
                                                : 'https://picsum.photos/300/200'
                                        "
                                    />
                                </div>
                                <div v-if="0" class="absolute top-0 flex w-full items-center justify-between p-2">
                                    <div
                                        class="rounded-lg border-x border-white/50 bg-black/50 px-2 py-1 text-sm backdrop-blur"
                                    >
                                        Active
                                    </div>
                                    <div class="rounded-full border-x border-white/50 bg-black/50 p-1.5 backdrop-blur">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                            class="size-5"
                                        >
                                            <path
                                                d="m9.653 16.915-.005-.003-.019-.01a20.759 20.759 0 0 1-1.162-.682 22.045 22.045 0 0 1-2.582-1.9C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 0 1 8-2.828A4.5 4.5 0 0 1 18 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 0 1-3.744 2.582l-.019.01-.005.003h-.002a.739.739 0 0 1-.69.001l-.002-.001Z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <span class="pb-1 pt-2 font-bold">{{
                                p.sales_price ? fmtPriceLng(p.sales_price) : 'No Price Data'
                            }}</span>
                            <div>
                                <span v-if="p.bedrooms">{{ p.bedrooms }} bed &middot; </span>
                                <span v-if="p.total_bathrooms">{{ p.total_bathrooms }} bath </span>
                                <span v-if="p.living_area_sq_ft">&middot; {{ p.living_area_sq_ft }} sqft.</span>
                            </div>
                            <p class="grow">{{ listingAddress(p) }}</p>
                            <span class="text-neutral-400">MRED: #{{ p.mls_id.replace('MRD', '') }}</span>
                        </Link>
                    </div>
                </div>
            </div>
            <div class="grow bg-neutral-300" id="map"></div>
        </div>
    </div>
</template>
<style>
.leaflet-custom-marker {
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 100%;
    --popup-offset: 0.25rem;
}

.leaflet-custom-marker:hover {
    cursor: pointer;
    --popup-offset: 0.15rem;
}

.leaflet-custom-marker::after {
    content: '';
    inset: 0.25rem;
    background: rgba(255, 255, 255, 1);
    border-radius: 100%;
    display: block;
    position: absolute;
}

.leaflet-custom-marker-popup {
    position: absolute;
    padding: 0.1rem 0.5rem;
    background: white;
    font-weight: bold;
    border-radius: calc(infinity * 1px);
    top: calc(100% + var(--popup-offset));
    left: 50%;
    transform: translateX(-50%);
    transition: top 100ms;
}

/* use padding to fill interaction gap */

.leaflet-custom-marker-popup:hover {
    @apply group-hover:shadow-xl;
}
</style>
