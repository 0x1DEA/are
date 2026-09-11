<script setup>
import { fmtPrice, fmtPriceLng, listingAddress, listingOverview, listingRooms } from '@/lib/utils.ts';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Leaflet from '@/components/Leaflet.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    listing: Object,
});

const back = () => window.history.back();

const term = ref(30);
const interest = ref(7);
const downPercent = ref(20);
const downMoney = ref(props.listing?.sale_price * (downPercent.value / 100));
const paymentPrincipal = ref(null);

const monthly = computed(() => {
    const _interest = interest.value / 100 / 12;
    const _payments = term.value * 12;
    const principal = props.listing?.sale_price - downMoney.value;

    let payment;

    paymentPrincipal.value = principal / _payments;

    if (_interest === 0) {
        payment = paymentPrincipal.value
    } else {
        payment = (principal * _interest * Math.pow(1 + _interest, _payments)) / (Math.pow(1 + _interest, _payments) - 1);
    }

    return payment.toFixed(2);
});
</script>
<template>
    <div class="flex flex-col items-center">
        <Header class="w-full"/>
        <div class="flex flex-col gap-4 py-8 w-full max-w-6xl">
            <div class="flex gap-1 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                    <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
                </svg>
                <span @click="back()" class="cursor-pointer">Back to Search</span>
            </div>
            <div class="rounded-lg border">
                <div class="h-64 w-1/2"></div>
                <div class="w-1/2"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-8 p-4 md:p-0">
                <div class="flex flex-col gap-2 col-span-2">
                    <span>Listed by brokerage by listing agent</span>
                    <span class="text-4xl font-bold">{{ fmtPrice(listing) }}</span>
                    <span>{{ listingRooms(listing) }}</span>
                    <h1 class="text-2xl font-bold">{{ listingAddress(listing) }}</h1>

                    <h2 class="font-bold border-b-2 mt-4">Overview</h2>
                    <span class="font-bold">{{ listingOverview(listing) }}</span>
                    <p>{{ listing.public_remarks }}</p>

                    <h2 class="font-bold border-b-2 mt-4">Location</h2>
                    <span class="text-2xl font-bold">{{ listingAddress(listing) }}</span>

                    <Leaflet :lat="listing.coordinates.coordinates[0]" :lng="listing.coordinates.coordinates[1]" :zoom="16" class="w-full aspect-square rounded-lg"/>
                    <span>Add other info like schools and providers and shit</span>

                    <h2 v-if="listing.sale_price" class="font-bold border-b-2 mt-4">Mortgage</h2>
                    <div v-if="listing.sale_price" class="grid md:grid-cols-2 rounded-lg gap-4 p-4 bg-neutral-100">
                        <div class="flex flex-col gap-2">
                            <div>
                                <span>Home price</span>
                                <label class="flex items-center bg-white rounded-lg pr-2">
                                    <span class="px-2 text-neutral-500">$</span>
                                    <span class="py-1 grow">{{ fmtPrice(listing).replace('$', '') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-neutral-300">
                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                            </div>
                            <div>
                                <span>Down Payment</span>
                                <div class="flex gap-1">
                                    <label class="flex items-center w-2/3 bg-white rounded-lg pr-2">
                                        <span class="px-2 text-neutral-500">$</span>
                                        <input v-model="downMoney" @input="downPercent = downMoney / listing.sale_price * 100" type="number" class="w-full min-w-0 ronuded-r-lg py-1 focus-visible:outline-0"/>
                                    </label>
                                    <label class="flex items-center w-1/3 bg-white rounded-lg pl-2">
                                        <input v-model="downPercent" @input="downMoney = downPercent / 100 * listing.sale_price" type="number" class="w-full min-w-0 ronuded-l-lg py-1 focus-visible:outline-0"/>
                                        <span class="px-2 text-neutral-500">%</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <span>Term</span>
                                <label class="flex items-center bg-white rounded-lg pl-2">
                                    <input v-model="term" type="number" class="w-full min-w-0 ronuded-l-lg py-1 focus-visible:outline-0"/>
                                    <span class="px-2 text-neutral-500">yrs</span>
                                </label>
                            </div>
                            <div>
                                <span>Interest</span>
                                <label class="flex items-center bg-white rounded-lg pl-2">
                                    <input v-model="interest" type="number" class="w-full min-w-0 ronuded-l-lg py-1 focus-visible:outline-0"/>
                                    <span class="px-2 text-neutral-500">%</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 text-center">
                            <p>Your Monthly Payment</p>
                            <div class="flex justify-center items-center grow py-4">
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

                    <span>Table of MLS data here</span>
                </div>
                <div class="relative col-span-2 md:col-span-1">
                    <div class="flex flex-col gap-1 sticky top-2 rounded-lg bg-neutral-100 shadow-lg border border-neutral-200 p-4">
                        <div class="aspect-square bg-neutral-300"></div>
                        <h1 class="text-2xl font-bold">Sonia Anaya</h1>
                        <span>+1 (773) 965-4618</span>
                        <span>sonia@americarealestate.com</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-4 p-4 md:p-0">
                <div class="flex justify-center flex-wrap gap-8 items-center">
                    <img alt="" class="h-20" src="../../../assets/equal_housing.png"/>
                    <img alt="" class="h-20 invert" src="../../../assets/realtor_logo.png"/>
                    <img alt="" src="../../../assets/mred_listing.png"/>
                </div>
                <p>Listings courtesy of MRED as distributed by MLS GRID</p>
                <p  class="text-neutral-500">Based on information submitted to the MLS GRID as of {{ new Date(listing.updated_at).toLocaleString() }}. All data is obtained from various sources and may not have been verified by broker or
                    MLS GRID. Supplied Open House Information is subject to change without notice. All information
                    should be independently reviewed and verified for accuracy. Properties may or may not be listed by
                    the office/agent presenting the information</p>
                <p class="text-neutral-500">The Digital Millennium Copyright Act of 1998, 17 U.S.C. § 512 (the “DMCA”) provides recourse for
                    copyright owners who believe that material appearing on the Internet infringes their rights under
                    U.S. copyright law. If you believe in good faith that any content or material made available in
                    connection with our website or services infringes your copyright, you (or your agent) may send us a
                    notice requesting that the content or material be removed, or access to it blocked. Notices must be
                    sent in writing by email to: info@americarealestateinc.com</p>
                <p class="text-neutral-500">Website managed by America Real Estate Inc. IDFPR 478009186</p>
            </div>
        </div>
        <Footer class="w-full"/>
    </div>
</template>
<style scoped>
input {
    background-color: white;
}
</style>
