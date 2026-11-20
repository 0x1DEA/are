<script setup>
import { $$, fmtPrice, fmtPriceLng, listingAddress, listingOverview, listingRooms } from '@/lib/utils.ts';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import Leaflet from '@/components/Leaflet.vue';
import Modal from '@/components/Modal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    LightGallery,
    LgItem,
} from '@lightgallery/vue';
import Thumbnail from '@lightgallery/vue/plugins/thumbnail';
import Zoom from '@lightgallery/vue/plugins/zoom';
import 'lightgallery/css/lightgallery.css';
import 'lightgallery/css/lg-thumbnail.css';
import 'lightgallery/css/lg-zoom.css';
import { useStorage, useTimestamp } from '@vueuse/core';

const props = defineProps({
    listing: Object,
    agent: Object,
    reactions: Object,
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

const inputType = ref(null);
const contact = ref('');
watch(contact, (value, oldValue) => {
    const COMPLETE_PHONE = /^(\+\d{1,2}\s)?\(?\d{3}\)?[\s.-]\d{3}[\s.-]\d{4}$/;
    const PARTIAL_PHONE = /^[0-9()+-\s]+$/;

    if (value === '') {
        inputType.value = null;
    } else if (PARTIAL_PHONE.test(value)) {
        // inputType.value = 'tel';
        inputType.value = 'email';
    } else {
        inputType.value = 'email';
    }
});

const register = useForm({
    turnstile: null,
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const canRegister = computed(() => {
    if (!register.turnstile) return false;
    if (!register.email) return false;
    if (!register.password) return false;
    if (!register.password_confirmation) return false;
    if (!register.terms) return false;

    return true;
});

const wizard = ref(0);
const share = ref(false);

const nextWizard = () => {
    wizard.value++;

    if (wizard.value === 1) {
        if (!window.turnstile) return;

        const widget = window.turnstile.render("#cf-turnstile", {
            sitekey: '0x4AAAAAAF-1m3Jc0puuJQfZ',
            callback: (token) => {
                register.turnstile = token;
            },
        });
    } else if (wizard.value > 1) {
        register.post('/register?redirect=' + usePage().props.url, {

        });
    }
}

const reactionForm = useForm({});

const saveListing = () => {
    if (usePage().props.auth.user) {
        if (props.reactions) {
            reactionForm.delete(`/reaction/${props.reactions.id}`);
        } else {
            reactionForm.post(`/listing/${props.listing.mls_id}/save`);
        }
    } else {
        wizard.value = 0;
        lastSignupBugDismissal.value = timestamp.value - signupBugInterval.value;
    }
}

watch(share, (v) => {
    if (v) {
        nextTick(() => {
            const widget = window.turnstile.render("#cf-turnstile-share", {
                sitekey: '0x4AAAAAAF-1m3Jc0puuJQfZ',
                callback: (token) => {
                    shareForm.turnstile = token;
                },
            });
        });
    }
})

const shareForm = useForm({
    sender_name: usePage().props.auth?.user?.name,
    sender_email: usePage().props.auth?.user?.email,
    sender_phone: '',
    recipient_email: '',
    message: '',
    turnstile: null,
});

const submitShare = () => {
    shareForm.post(`/listing/${props.listing.mls_id}/share`, {
        onSuccess: () => {
            lastSignupBugDismissal.value = timestamp.value - signupBugInterval.value;
            share.value = false;
            shareForm.reset();
        }
    });
}

const signupBugInterval = ref(1000 * 60 * 15); // every 15 min

const timestamp = useTimestamp();

const lastSignupBugDismissal = useStorage('are::last-signup-bug-dismissal', Math.floor(Date.now() / 1000) - signupBugInterval);

const showNag = computed(() => {
    if (parseInt(lastSignupBugDismissal.value) + signupBugInterval.value < timestamp.value) {
        return true
    }

    return false;
});
</script>
<template>
    <div class="flex flex-col items-center">
        <Header class="w-full" title="Listing" />
        <div v-if="!$page.props.auth.user && showNag && !share" class="flex items-center justify-center fixed inset-0 bg-black/25 z-100 p-8">
            <div class="flex flex-col gap-2 bg-neutral-100 p-6 rounded-2xl w-2xl shadow-xl border-y border-t-white border-b-neutral-200">
                <div class="flex justify-between">
                    <div></div>
                    <h1 class="text-3xl text-center font-bold">Let's Make it Official</h1>
                    <svg @click="wizard = 0; lastSignupBugDismissal = timestamp;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 cursor-pointer">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
                <p class="text-lg text-center">Get personalized help from our team, view new listings, <b>save listings</b>, and perform automatic searches for new properties</p>
                <div>
                    <div class="flex bg-white shadow rounded-lg w-full">
                        <div class="grid grid-cols-[0fr] transition-[grid-template-columns]" :class="{'grid-cols-[1fr]': inputType === 'tel'}">
                            <div class="overflow-hidden">
                                <select class="pl-4 pr-2 mr-4 h-full">
                                    <option>+1</option>
                                </select>
                            </div>
                        </div>
                        <input v-model="register.email" type="text" class="border-l border-neutral-100 rounded-r-lg text-lg transition-[border-radius] px-4 py-3 w-full" :class="{'rounded-l-lg': inputType !== 'tel'}" placeholder="Email"/>
                    </div>
                    <div class="grid grid-rows-[0fr] transition-[grid-template-rows]" :class="{'grid-rows-[1fr]': inputType === 'email'}">
                        <div class="overflow-hidden">
                            <div class="flex flex-wrap gap-2 w-full mt-2 pb-2 text-neutral-600">
                                <button @click="contact = contact + '@gmail.com'" class="px-2 py-0.5 bg-white shadow border-b border-neutral-300 rounded-lg">@gmail.com</button>
                                <button @click="contact = contact + '@hotmail.com'" class="px-2 py-0.5 bg-white shadow border-b border-neutral-300 rounded-lg">@hotmail.com</button>
                                <button @click="contact = contact + '@yahoo.com'" class="px-2 py-0.5 bg-white shadow border-b border-neutral-300 rounded-lg">@yahoo.com</button>
                                <button @click="contact = contact + '@msn.com'" class="px-2 py-0.5 bg-white shadow border-b border-neutral-300 rounded-lg">@msn.com</button>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-rows-[0fr] transition-[grid-template-rows]" :class="{'grid-rows-[1fr]': inputType === null}">
                        <div class="overflow-hidden">
<!--                            <p class="mt-1 text-center">We'll send you a confirmation code</p>-->
                        </div>
                    </div>
                    <div class="grid grid-rows-[0fr] transition-[grid-template-rows]" :class="{'grid-rows-[1fr]': inputType === 'tel'}">
                        <div class="overflow-hidden">
                            <p class="text-sm w-full text-neutral-500 px-2">Message and data rates apply</p>
                        </div>
                    </div>
                </div>
                <template v-if="wizard > 0">
                    <input v-model="register.name" type="text" class="bg-white shadow rounded-lg border-l border-neutral-100 text-lg transition-[border-radius] px-4 py-3 w-full" placeholder="Name"/>
                    <input v-model="register.password" type="password" class="bg-white shadow rounded-lg border-l border-neutral-100 text-lg transition-[border-radius] px-4 py-3 w-full" placeholder="Password"/>
                    <input v-model="register.password_confirmation" type="password" class="bg-white shadow rounded-lg border-l border-neutral-100 text-lg transition-[border-radius] px-4 py-3 w-full" placeholder="Confirm Password"/>
                    <label class="flex gap-3 items-center px-4 mt-2">
                        <input type="checkbox" class="mt-0.5 scale-150" v-model="register.terms"/>
                        <span class="select-none">I agree to the Terms and Conditions and Privacy Policy</span>
                    </label>
                </template>
                <ul v-if="$page.props.errors">
                    <li class="text-red-500" v-for="error in $page.props.errors">{{ error }}</li>
                </ul>
                <div v-show="wizard > 0" id="cf-turnstile" class="flex justify-center"></div>
                <button @click="nextWizard" :disabled="wizard === 1 && !canRegister" :class="wizard === 0 || canRegister ? 'bg-neutral-800 border-black cursor-pointer' : 'bg-neutral-200 border-neutral-300'" class="text-white font-bold shadow transition-colors border-b p-3 rounded-lg">Next</button>
                <div v-if="wizard === 0" class="flex items-center space-x-2">
                    <span class="grow border-b border-neutral-300"></span>
                    <span class="mb-1">or</span>
                    <span class="grow border-b border-neutral-300"></span>
                </div>
                <Link  v-if="wizard === 0" :href="`/login`" class="flex items-center justify-center gap-2 text-white font-bold bg-neutral-800 border-black cursor-pointer shadow border-b p-3 rounded-lg">
                    <span>Log in with Email</span>
                </Link>
                <a  v-if="wizard === 0" :href="`/oauth2/facebook/redirect?redirect=${$page.url}`" class="flex items-center justify-center gap-2 text-white font-bold bg-[#1877f2] shadow border-b border-blue-700 p-2 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><!-- Icon from Material Design Icons by Pictogrammers - https://github.com/Templarian/MaterialDesign/blob/master/LICENSE --><path fill="currentColor" d="M12 2.04c-5.5 0-10 4.49-10 10.02c0 5 3.66 9.15 8.44 9.9v-7H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.89 3.78-3.89c1.09 0 2.23.19 2.23.19v2.47h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33v7a10 10 0 0 0 8.44-9.9c0-5.53-4.5-10.02-10-10.02"/></svg>
                    <span>Log in with Facebook</span>
                </a>
            </div>
        </div>
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
                        class="aspect-square w-full rounded-lg z-0"
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
                <div class="relative col-span-2 md:col-span-1 space-y-2">
                    <Modal v-model:open="share" heading="Share This Listing">
                        <button class="bg-neutral-800 text-white font-bold p-2 rounded-lg w-full cursor-pointer">Share This Listing</button>
                        <template #content>
                            <span>Your Info</span>
                            <input v-model="shareForm.sender_name" type="text" class="!bg-neutral-100 rounded-lg p-4" placeholder="Full Name"/>
                            <input v-model="shareForm.sender_email" type="email" class="!bg-neutral-100 rounded-lg p-4" placeholder="Email"/>
                            <input v-model="shareForm.sender_phone" type="tel" class="!bg-neutral-100 rounded-lg p-4" placeholder="Phone Number"/>
                            <span>Who should we send this to?</span>
                            <input v-model="shareForm.recipient_email" type="email" class="!bg-neutral-100 rounded-lg p-4" placeholder="Recipient Email"/>
                            <textarea v-model="shareForm.message" rows="4" class="bg-neutral-100 rounded-lg px-4 py-2" placeholder="Message"></textarea>
                            <ul v-if="$page.props.errors">
                                <li class="text-red-500" v-for="error in $page.props.errors">{{ error }}</li>
                            </ul>
                            <div id="cf-turnstile-share" class="flex justify-center"></div>
                            <button @click="submitShare" class="text-white font-bold bg-neutral-800 p-3 rounded-lg cursor-pointer">Send</button>
                        </template>
                    </Modal>
                    <button :disabled="reactionForm.processing" @click="saveListing" class="flex items-center    justify-center gap-1 bg-neutral-800 text-white font-bold p-2 rounded-lg w-full cursor-pointer">
                        <svg v-if="reactions" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
                            <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                        <span v-if="reactions">Listing Saved</span>
                        <span v-else>Save Listing</span>

                        <span v-show="reactionForm.processing" class="ml-2 animate-spin border-2 border-y-transparent border-white size-4 rounded-full"></span>
                    </button>
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
