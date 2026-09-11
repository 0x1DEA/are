<script setup>
import L from 'leaflet';
import { computed, onBeforeMount, onMounted, ref } from 'vue';
import 'leaflet/dist/leaflet.css';
import { Link } from '@inertiajs/vue3';
import {
    fmtPrice,
    fmtPriceCmp,
    fmtPriceLng,
    listingAddress,
    listingMLSID,
    listingRooms,
    listingThumb,
} from '@/lib/utils.ts';
import cities_csv from '~/assets/cities.csv?raw';
import MinMax from '@/components/MinMax.vue';

const map = ref(null);
const markers = ref(L.layerGroup());

const gridListings = ref([]);
const markerListings = ref([]);

const props = defineProps({
    lat: {
        type: Number,
        default: 41.85,
    },
    lng: {
        type: Number,
        default: -87.99,
    },
    zoom: {
        type: Number,
        default: 10,
    },
});

const popupLat = ref(props.lat ?? 41.85);
const popupLng = ref(props.lng ?? -87.99);

const popupTransformX = ref(0);
const popupTransformY = ref(0);

onMounted(() => {
    map.value = L.map('map').setView([props.lat, props.lng], props.zoom);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        minZoom: 10,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map.value);

    markers.value.addTo(map.value);

    map.value.on('moveend', searchMap);
    map.value.on('move', updatePosition);
    map.value.on('zoom', updatePosition);
    map.value.on('viewreset', updatePosition);

    map.value.on('click', () => {
        popupListing.value = null;
        popupLat.value = 0;
        popupLng.value = 0;
    });

    updatePosition();
    searchMap();
});

const updatePosition = () => {
    if (!map.value) return;

    const layerPoint = map.value.latLngToLayerPoint(L.latLng(popupLat.value, popupLng.value));

    const containerPoint = map.value.layerPointToContainerPoint(layerPoint);

    popupTransformX.value = containerPoint.x;
    popupTransformY.value = containerPoint.y;
};

const searchMap = async () => {
    // TODO: race condition check
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

    let url = new URL(`${location.protocol}//${location.host}/api/search/${coords.join(';')}`);

    url.searchParams.append('center', center);

    url.searchParams.append('sort[field]', sortingField.value);
    url.searchParams.append('sort[dir]', sortingDirection.value);

    if (priceMin.value !== null) url.searchParams.append('filter[price_min]', priceCurve[priceMin.value]);
    if (priceMax.value !== null) url.searchParams.append('filter[price_max]', priceCurve[priceMax.value]);

    if (bedsMin.value !== null) url.searchParams.append('filter[beds_min]', bedsMin.value);
    if (bedsMax.value !== null) url.searchParams.append('filter[beds_max]', bedsMax.value);

    if (bathsMin.value !== null) url.searchParams.append('filter[baths_min]', bathsMin.value);
    if (bathsMax.value !== null) url.searchParams.append('filter[baths_max]', bathsMax.value);

    let res = await fetch(url);

    if (!res.ok) return; // API error?

    res = await res.json();

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
            listing_index: listing,
        })
            .on('click', markerCard)
            .addTo(markers.value);
    }
};

const popupListing = ref(null);

const markerCard = (e) => {
    if (e?.type === 'click') {
        popupListing.value = gridListings.value[e.target.options.listing_index];
        popupLat.value = e.latlng.lat;
        popupLng.value = e.latlng.lng;
    } else {
        popupListing.value = e;
        popupLat.value = e.coordinates.coordinates[0];
        popupLng.value = e.coordinates.coordinates[1];
    }
    updatePosition();
};

const cities = ref([]);

onBeforeMount(() => {
    let rows = cities_csv.split('\n');

    for (const i in rows) {
        let frags = rows[i].split(',');

        cities.value[i] = {
            name: frags[1],
            lat: frags[3],
            lng: frags[4],
        };
    }
});

const citySearch = ref('');

const cityResults = computed(() => {
    let out = [];

    for (let i = 0; i < cities.value.length; i++) {
        let check = (cities.value?.[i]?.name ?? '').toLowerCase();
        let search = citySearch.value.toLowerCase();
        if (check.includes(search)) out.push(i);
    }

    return out;
});

const goTo = ({ lat, lng }) => {
    goToLL(lat, lng);
};

const goToLL = (lat, lng, zoom = 10) => {
    map.value.setView(L.latLng(lat, lng), zoom);
    citySearch.value = '';
    popupListing.value = null;
};

const goToProperty = (p) => {
    goToLL(...p.coordinates.coordinates, 14);
    markerCard(p);
};

const sortingField = ref('created_at');
const sortingDirection = ref('desc');

const priceMin = ref(1);
const priceMax = ref(18);

const bedsMin = ref(1);
const bedsMax = ref(5);

const bathsMin = ref(1);
const bathsMax = ref(5);

const priceCurve = [
    0, 10_000, 50_000, 75_000, 100_000, 125_000, 150_000, 175_000, 200_000, 225_000, 250_000, 275_000, 300_000, 325_000,
    350_000, 375_000, 400_000, 425_000, 450_000, 475_000, 500_000, 550_000, 600_000, 650_000, 700_000, 750_000, 800_000,
    850_000, 900_000, 950_000, 1_000_000, 1_100_000, 1_200_000, 1_300_000, 1_400_000, 1_500_000, 1_600_000, 1_700_000,
    1_800_000, 1_900_000, 2_000_000, 2_250_000, 2_500_000, 2_750_000, 3_000_000, 3_250_000, 3_500_000, 3_750_000,
    4_000_000, 4_250_000, 4_500_000, 4_750_000, 5_000_000, 5_500_000, 6_000_000, 7_000_000, 8_000_000, 9_000_000,
    10_000_000, 999_000_000,
];

const setSorting = (field, dir) => {
    sortingField.value = field;
    sortingDirection.value = dir;
    searchMap();
};

// TODO: highlight card and dot when either one is hovered to quickly identify each
</script>
<template>
    <div class="flex flex-col bg-white">
        <div class="flex items-center gap-2 bg-neutral-100 p-2">
            <div>
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
                    <input
                        v-model="citySearch"
                        type="text"
                        placeholder="Search Cities"
                        class="focus-visible:outline-0"
                    />
                </label>
                <div class="relative z-10 w-64 px-4">
                    <div
                        v-show="citySearch !== ''"
                        class="absolute left-0 right-0 top-1 max-h-64 overflow-hidden overflow-y-auto rounded-lg border border-black/25 bg-white/50 backdrop-blur-lg"
                    >
                        <button
                            v-for="city in cityResults"
                            :key="city"
                            class="block w-full cursor-pointer px-4 py-2 text-left hover:bg-black/25"
                            @click="goTo(cities[city])"
                        >
                            {{ cities[city].name }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="group relative">
                <div class="flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2 py-1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path
                            d="M10 3.75a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM17.25 4.5a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM5 3.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75ZM4.25 17a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM17.25 17a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM9 10a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1 0-1.5h5.5A.75.75 0 0 1 9 10ZM17.25 10.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM14 10a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM10 16.25a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z"
                        />
                    </svg>
                    <span>Filters</span>
                </div>
                <div class="absolute left-0 top-full z-10 hidden pt-1 group-hover:block">
                    <div class="flex w-72 flex-col gap-2 rounded-lg border border-black/25 bg-white shadow-lg">
                        <div class="px-4 py-2">
                            <span>List Price</span>
                            <div class="flex justify-between mb-2">
                                <span>{{ fmtPriceCmp(priceCurve[priceMin]) }}</span>
                                <span>{{ fmtPriceCmp(priceCurve[priceMax]) }}</span>
                            </div>
                            <MinMax
                                @commit="searchMap"
                                :curve="priceCurve"
                                :min="0"
                                :max="priceCurve.length - 1"
                                :step="1"
                                v-model:low="priceMin"
                                v-model:high="priceMax"
                            />
                        </div>
                        <div class="px-4 py-2">
                            <span>Bedrooms</span>
                            <MinMax
                                @commit="searchMap"
                                :min="0"
                                :max="5"
                                :step="1"
                                v-model:low="bedsMin"
                                v-model:high="bedsMax"
                            />
                        </div>
                        <div class="px-4 py-2">
                            <span>Bathrooms</span>
                            <MinMax
                                @commit="searchMap"
                                :min="0"
                                :max="5"
                                :step="1"
                                v-model:low="bathsMin"
                                v-model:high="bathsMax"
                            />
                        </div>
                        <div class="px-4 py-2">
                            <span>Type</span>
                            <MinMax
                                @commit="searchMap"
                                :min="0"
                                :max="5_000_000"
                                :step="10_000"
                                v-model:low="priceMin"
                                v-model:high="priceMax"
                            />
                        </div>
                        <div class="p-2">
                            <button @click="searchMap" class="cursor-pointer w-full bg-neutral-200 rounded-lg px-2 py-1">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex grow">
            <div class="flex grow md:grow-0 md:w-1/3 flex-col">
                <div class="flex items-end justify-between border-b border-neutral-200 px-4 pb-3 pt-2">
                    <div>
                        <h1 class="text-lg font-bold">Properties For Sale</h1>
                        <span class="text-sm text-neutral-500">{{ gridListings.length }} Results</span>
                    </div>
                    <div class="group relative">
                        <div class="flex items-center gap-1 rounded-lg border border-neutral-200 bg-white px-2 py-1">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                class="size-4"
                            >
                                <path
                                    d="M10 3.75a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM17.25 4.5a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM5 3.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75ZM4.25 17a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM17.25 17a.75.75 0 0 0 0-1.5h-5.5a.75.75 0 0 0 0 1.5h5.5ZM9 10a.75.75 0 0 1-.75.75h-5.5a.75.75 0 0 1 0-1.5h5.5A.75.75 0 0 1 9 10ZM17.25 10.75a.75.75 0 0 0 0-1.5h-1.5a.75.75 0 0 0 0 1.5h1.5ZM14 10a2 2 0 1 0-4 0 2 2 0 0 0 4 0ZM10 16.25a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z"
                                />
                            </svg>
                            <span>Sorting</span>
                        </div>
                        <div class="absolute left-0 top-full z-10 hidden w-48 pt-1 group-hover:block">
                            <div
                                class="flex flex-col gap-2 rounded-lg border border-black/25 bg-white/50 backdrop-blur"
                            >
                                <span
                                    @click="setSorting('listed_at', 'desc')"
                                    class="cursor-pointer px-2 py-1 first:rounded-t-lg hover:bg-black/25"
                                    >Newest</span
                                >
                                <span
                                    @click="setSorting('price', 'asc')"
                                    class="cursor-pointer px-2 py-1 hover:bg-black/25"
                                    >Price: Low to High</span
                                >
                                <span
                                    @click="setSorting('price', 'desc')"
                                    class="cursor-pointer px-2 py-1 hover:bg-black/25"
                                    >Price: High to Low</span
                                >
                                <span
                                    @click="setSorting('listed_at', 'asc')"
                                    class="cursor-pointer px-2 py-1 last:rounded-b-lg hover:bg-black/25"
                                    >Oldest</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
                <div v-show="gridListings.length === 0" class="p-8 text-center">
                    <h1 class="text-xl font-bold">No Results</h1>
                    <p>Try searching a different area, zooming out, or adjusting your filters</p>
                </div>
                <div class="relative grow">
                    <div class="absolute inset-0 grid grid-cols-2 gap-4 overflow-y-auto p-4">
                        <div v-for="p in gridListings" :key="p.mls_id" class="flex h-fit flex-col">
                            <div
                                class="relative flex aspect-video rounded-lg bg-neutral-500 bg-cover bg-center text-white"
                            >
                                <Link
                                    :href="`/listing/${p.mls_id}`"
                                    class="absolute inset-0 overflow-hidden rounded-lg"
                                >
                                    <img
                                        v-if="listingThumb(p)"
                                        alt=""
                                        class="absolute inset-0 object-cover"
                                        loading="lazy"
                                        :src="listingThumb(p)"
                                    />
                                </Link>
                                <div class="absolute left-2 right-2 top-2 flex items-center justify-between">
                                    <span
                                        class="rounded-lg border-x border-white/50 bg-black/50 px-2 py-1 text-sm backdrop-blur"
                                        >{{ p.status }}</span
                                    >
                                    <div class="flex gap-1">
                                        <div
                                            @click="goToProperty(p)"
                                            class="cursor-pointer rounded-full border-x border-white/50 bg-black/50 p-1.5 backdrop-blur"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                                class="size-5"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div
                                            v-if="0"
                                            class="rounded-full border-x border-white/50 bg-black/50 p-1.5 backdrop-blur"
                                        >
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
                            </div>
                            <Link :href="`/listing/${p.mls_id}`" class="flex grow flex-col">
                                <span class="pb-1 pt-2 font-bold">{{ fmtPrice(p) }}</span>
                                <span>{{ listingRooms(p) }}</span>
                                <p class="grow">{{ listingAddress(p) }}</p>
                                <span class="text-neutral-400">{{ listingMLSID(p) }}</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hidden md:block relative grow overflow-hidden bg-neutral-300">
                <div class="absolute inset-0 z-0" id="map"></div>
                <div
                    v-if="popupListing"
                    class="z-10 flex w-72 flex-col rounded-lg border border-black/25 bg-white/25 backdrop-blur"
                    :style="`transform: translate3d(calc(${popupTransformX}px - 50%), calc(${popupTransformY}px - 100% - 1rem), 0);`"
                >
                    <img
                        class="h-32 w-full rounded-t-lg bg-black/25 object-cover object-center"
                        loading="lazy"
                        alt=""
                        :src="listingThumb(popupListing)"
                    />
                    <div class="flex flex-col border-t border-black/25 p-2 text-sm">
                        <span>{{ listingAddress(popupListing) }}</span>
                        <span class="py-1 text-base font-bold">{{ fmtPrice(popupListing) }}</span>
                        <span>{{ listingRooms(popupListing) }}</span>
                        <span class="text-neutral-500">{{ listingMLSID(popupListing) }}</span>
                    </div>
                    <Link :href="`/listing/${popupListing.mls_id}`" class="absolute inset-0"></Link>
                </div>
            </div>
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
