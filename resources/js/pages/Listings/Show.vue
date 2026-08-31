<script setup>
import { fmtPrice, listingAddress } from '@/lib/utils.ts';

const props = defineProps({
    listing: Object,
});
</script>
<template>
    <div class="flex justify-center">
        <div class="flex flex-col gap-4 py-8 w-full max-w-6xl">
            <span>&lt; back to search</span>
            <div class="rounded-lg border">
                <div class="h-64 w-1/2"></div>
                <div class="w-1/2"></div>
            </div>
            <div class="flex gap-8">
                <div class="flex flex-col gap-2 grow">
                    <span>Listed by brokerage by listing agent</span>
                    <span class="text-4xl font-bold">{{ fmtPrice(listing) }}</span>
                    <span>2 bed - 2 bath - 123 sqft</span>
                    <h1 class="text-2xl font-bold">{{ listingAddress(listing) }}</h1>
                    <span>Overview</span>
                    <span>Active | Listed Aug 20, 2023 | Updated ??hrs ago</span>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque aliquam fermentum nunc ut egestas. Morbi eget orci vestibulum, vulputate est sit amet, mollis odio. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur sed nulla ut magna dapibus hendrerit. Praesent dapibus elementum purus volutpat cursus. Aliquam aliquam efficitur dapibus. Integer pharetra varius urna ut pharetra. Vestibulum posuere quam arcu, id fermentum ex suscipit id. Ut vestibulum tortor neque, ac mattis tortor congue quis.</p>

                    <span>Location</span>
                    <span>Address</span>
                    <div class="border aspect-video w-full">slippy map here</div>
                    <span>Add other info like schools and providers and shit</span>

                    <span>Cost calculature here mortgage + expenses</span>

                    <span>Table of MLS data here</span>
                </div>
                <div class="relative h-full w-1/3 shrink-0">
                    <div class="sticky top-2">addafs</div>
                </div>
            </div>
            <p>LONG ASS MLS DISCLAIMER ABOUT DATA ACCURACY</p>
            <span>MRED logo or something w copyright notice</span>
            <span>Equal fair housing thingy</span>
            <span>realtor logo</span>
            <span>broker reciprocireciprocrocity</span>
            <p>Based on information submitted to the MLS GRID as of {{ listing.updated_at }}. All data is obtained from various sources and may not have been verified by broker or
MLS GRID. Supplied Open House Information is subject to change without notice. All information
should be independently reviewed and verified for accuracy. Properties may or may not be listed by
the office/agent presenting the information</p>
            <p>Listings courtesy of MRED as distributed by MLS GRID</p>
            <p>The Digital Millennium Copyright Act of 1998, 17 U.S.C. § 512 (the “DMCA”) provides recourse for
                copyright owners who believe that material appearing on the Internet infringes their rights under
                U.S. copyright law. If you believe in good faith that any content or material made available in
                connection with our website or services infringes your copyright, you (or your agent) may send us a
                notice requesting that the content or material be removed, or access to it blocked. Notices must be
                sent in writing by email to: [Insert e-mail address for Member Participant’s DMCA designated agent
                (e.g. copyright@ABCrealestatefirm.com)].</p>
        </div>
    </div>
</template>
