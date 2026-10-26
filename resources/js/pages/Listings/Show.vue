<script setup>
import { $$, fmtPrice, fmtPriceLng, listingAddress, listingOverview, listingRooms, listingThumb } from '@/lib/utils.ts';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Leaflet from '@/components/Leaflet.vue';
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    LightGallery,
    LgItem,
} from '@lightgallery/vue';
import Thumbnail from '@lightgallery/vue/plugins/thumbnail';
import Zoom from '@lightgallery/vue/plugins/zoom';
import 'lightgallery/css/lightgallery.css';
import 'lightgallery/css/lg-thumbnail.css';
import 'lightgallery/css/lg-zoom.css';

const props = defineProps({
    listing: Object,
    agent: Object,
});

const plugins = [Thumbnail, Zoom];

const back = () => window.history.back();

const term = ref(30);
const interest = ref(7);
const downPercent = ref(20);
const downMoney = ref((props.listing?.sale_price ?? 0) * (downPercent.value / 100));
const salePrice = ref(props.listing?.sale_price ?? 0);
const paymentPrincipal = ref(null);

const monthly = computed(() => {
    const _interest = interest.value / 100 / 12;
    const _payments = term.value * 12;
    const principal = salePrice.value - downMoney.value;

    let payment;

    paymentPrincipal.value = principal / _payments;

    if (_interest === 0) {
        payment = paymentPrincipal.value;
    } else {
        payment =
            (principal * _interest * Math.pow(1 + _interest, _payments)) / (Math.pow(1 + _interest, _payments) - 1);
    }

    return payment.toFixed(2);
});

const mediaLength = props.listing.media.length;

const mediaPreviewLength = computed(() => {
    if (mediaLength >= 5) return 5;
    if (mediaLength >= 2) return 2;
    return 1;
});

const galleryItems = computed(() => {
    let out = [];

    for (let i = 0; i < mediaLength; i++) {
        let m = props.listing.media[i];
        out.push({
            src: '/storage/' + m.url,
            thumb: '/storage/' + m.url,
        })
    }

    return out;
});

const gridClass = computed(() => {
    if (mediaPreviewLength.value >= 5) return 'grid-cols-4';
    if (mediaPreviewLength.value >= 2) return 'grid-cols-2';
});

const gridItemClass = (i) => {
    return {
        'hidden': i >= mediaPreviewLength.value,
        'col-span-2 row-span-2': mediaLength >= 5 && i === 0,
        'max-h-96': mediaLength === 1,
    };
};
</script>
<template>
    <div class="flex flex-col items-center">
        <Header class="w-full" title="Listing" />
        <div class="flex w-full max-w-6xl flex-col gap-4 py-8">
            <div v-if="0" class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                    <path
                        fill-rule="evenodd"
                        d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
                <Link href="/search" class="cursor-pointer">Back to Search</Link>
            </div>
            <div class="grid overflow-hidden rounded-xl gap-1" :class="gridClass">
                <LightGallery :plugins="plugins">
                    <LgItem v-for="(media, i) in galleryItems" :key="media.key" :item="media" :class="gridItemClass(i)">
                        <img loading="lazy" class="w-full h-full object-center object-cover" :src="media.src" alt=""/>
                    </LgItem>
                </LightGallery>
            </div>
            <div class="grid grid-cols-2 gap-8 p-4 md:grid-cols-3 md:p-0">
                <div class="col-span-2 flex flex-col gap-2">
                    <span class="text-4xl font-bold">{{ fmtPrice(listing) }}</span>
                    <span>{{ listingRooms(listing) }}</span>
                    <h1 class="text-2xl font-bold">{{ listingAddress(listing) }}</h1>

                    <h2 class="mt-4 border-b-2 font-bold">Overview</h2>
                    <span class="font-bold">{{ listingOverview(listing) }}</span>
                    <p>{{ listing.public_remarks }}</p>

                    <h2 v-if="listing.coordinates" class="mt-4 border-b-2 font-bold">Location</h2>
                    <span v-if="listing.coordinates" class="text-2xl font-bold">{{ listingAddress(listing) }}</span>
                    <Leaflet
                        v-if="listing.coordinates"
                        :lat="listing.coordinates.coordinates[0]"
                        :lng="listing.coordinates.coordinates[1]"
                        :zoom="16"
                        class="aspect-square w-full rounded-lg"
                    />

                    <h2 v-if="listing.sale_price" class="mt-4 border-b-2 font-bold">Mortgage Calculator</h2>
                    <div v-if="listing.sale_price" class="grid gap-4 rounded-lg bg-neutral-100 p-4 md:grid-cols-2">
                        <div class="flex flex-col gap-2">
                            <div>
                                <div class="flex justify-between items-end gap-2">
                                    <span>Sale Price</span>
                                    <button @click="salePrice = listing.sale_price" class="underline text-sm cursor-pointer">Reset</button>
                                </div>
                                <label class="flex items-center rounded-lg bg-white pr-2 w-full">
                                    <span class="px-2 text-neutral-500">$</span>
                                    <input
                                        v-model="salePrice"
                                        @input="downPercent = (downMoney / salePrice) * 100"
                                        type="number"
                                        class="ronuded-r-lg w-full min-w-0 py-1 focus-visible:outline-0 mr-2"
                                    />
                                    <span v-if="salePrice - listing.sale_price" class="flex items-center">
                                        <span class="mr-1" :class="salePrice > listing.sale_price ? 'text-red-500' : 'text-green-500'">{{ fmtPriceLng(salePrice - listing.sale_price) }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5" :class="salePrice > listing.sale_price ? 'text-red-500 rotate-180' : 'text-green-500'">
                                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.499 8.248h15m-15 7.501h15" />
                                    </svg>

                                </label>
                            </div>
                            <div>
                                <span>Down Payment</span>
                                <div class="flex gap-1">
                                    <label class="flex w-2/3 items-center rounded-lg bg-white pr-2">
                                        <span class="px-2 text-neutral-500">$</span>
                                        <input
                                            v-model="downMoney"
                                            @input="downPercent = (downMoney / listing.sale_price) * 100"
                                            type="number"
                                            class="ronuded-r-lg w-full min-w-0 py-1 focus-visible:outline-0"
                                        />
                                    </label>
                                    <label class="flex w-1/3 items-center rounded-lg bg-white pl-2">
                                        <input
                                            v-model="downPercent"
                                            @input="downMoney = (downPercent / 100) * listing.sale_price"
                                            type="number"
                                            class="ronuded-l-lg w-full min-w-0 py-1 focus-visible:outline-0"
                                        />
                                        <span class="px-2 text-neutral-500">%</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <span>Term</span>
                                <label class="flex items-center rounded-lg bg-white pl-2">
                                    <input
                                        v-model="term"
                                        type="number"
                                        class="ronuded-l-lg w-full min-w-0 py-1 focus-visible:outline-0"
                                    />
                                    <span class="px-2 text-neutral-500">yrs</span>
                                </label>
                            </div>
                            <div>
                                <span>Interest</span>
                                <label class="flex items-center rounded-lg bg-white pl-2">
                                    <input
                                        v-model="interest"
                                        type="number"
                                        class="ronuded-l-lg w-full min-w-0 py-1 focus-visible:outline-0"
                                    />
                                    <span class="px-2 text-neutral-500">%</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 text-center">
                            <p>Your Monthly Payment</p>
                            <div class="flex grow items-center justify-center py-4">
                                <span class="text-6xl font-bold">{{ fmtPriceLng(monthly) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Principle</span>
                                <span>{{ fmtPriceLng(paymentPrincipal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Interest</span>
                                <span>{{ fmtPriceLng(monthly - paymentPrincipal) }}</span>
                            </div>
                        </div>
                    </div>

                    <span v-if="0">Table of MLS data here</span>
                </div>
                <div class="relative col-span-2 md:col-span-1">
                    <div
                        v-if="agent"
                        class="sticky top-2 flex flex-col gap-1 rounded-lg bg-neutral-100 p-4"
                    >
                        <h2 class="font-bold text-2xl text-center">Interested?</h2>
                        <p class="text-center pb-2">We can help!</p>
                        <img class="aspect-square object-cover rounded-lg" loading="lazy" alt="" :src="`/storage/${agent.headshot_url}`"/>
                        <h1 class="text-2xl font-bold">{{ agent.name_first }} {{ agent.name_last }}</h1>
                        <a :href="`tel:${$$.tel}`" class="underline">{{ $$.tel_s }}</a>
                        <a :href="`mailto:${$$.email}`" class="underline">{{ $$.email }}</a>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-4 p-4 md:p-0">
                <span>This property listed by {{listing.mls_data.ListAgentFullName}} at {{listing.mls_data.ListOfficeName}} {{listing.mls_data.ListAgentMobilePhone}}</span>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-8">
                    <img alt="" class="h-20" src="../../../assets/equal_housing.png" />
                    <img alt="" class="h-20 invert" src="../../../assets/realtor_logo.png" />
                    <img alt="" src="../../../assets/mred_listing.png" />
                </div>
                <p>Listings courtesy of MRED as distributed by MLS GRID</p>
                <p class="text-neutral-500">
                    Based on information submitted to the MLS GRID as of
                    {{ new Date(listing.updated_at).toLocaleString() }}. All data is obtained from various sources and
                    may not have been verified by broker or MLS GRID. Supplied Open House Information is subject to
                    change without notice. All information should be independently reviewed and verified for accuracy.
                    Properties may or may not be listed by the office/agent presenting the information
                </p>
                <p class="text-neutral-500">
                    The Digital Millennium Copyright Act of 1998, 17 U.S.C. § 512 (the “DMCA”) provides recourse for
                    copyright owners who believe that material appearing on the Internet infringes their rights under
                    U.S. copyright law. If you believe in good faith that any content or material made available in
                    connection with our website or services infringes your copyright, you (or your agent) may send us a
                    notice requesting that the content or material be removed, or access to it blocked. Notices must be
                    sent in writing by email to: info@americarealestateinc.com
                </p>
                <p class="text-neutral-500">Website managed by America Real Estate Inc. IDFPR 478009186</p>
            </div>
        </div>
        <Footer class="w-full" />
    </div>
</template>
<style scoped>
input {
    background-color: white;
}
</style>
