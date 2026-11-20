<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import { listingAddress, listingMLSID, listingOverview, listingRooms, listingThumb } from '@/lib/utils.ts';

defineProps({
    reactions: Object,
});
</script>
<template>
    <Head title="Profile">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="text-neutral-1000 relative flex min-h-screen w-full flex-col bg-neutral-100">
        <Header title="Profile" />
        <div class="flex grow flex-col items-center">
            <main class="flex w-full max-w-6xl gap-8 p-8">
                <div v-if="0" class="flex min-w-44 flex-col gap-2 font-bold">
                    <span>Profile</span>
                    <span>Saved Listings</span>
                    <span>Sent Listings</span>
                    <span>Messages</span>
                    <span>Settings</span>
                </div>
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-bold">Welcome, {{ $page.props.auth.user.name }}!</h1>
                    <h1 class="text-lg font-bold">Saved Listings</h1>
                    <p v-if="reactions.length === 0" class="px-4 py-1 bg-neutral-200 rounded-lg">You don't have any saved listings</p>
                    <Link class="flex flex-col md:flex-row gap-2 bg-white shadow p-2 rounded-lg" v-for="r in reactions" :href="`/listing/${r.listing.mls_id}`">
                        <img
                            loading="lazy"
                            class="aspect-video h-32 rounded-lg bg-neutral-200 object-cover"
                            :src="listingThumb(r.listing)"
                            alt=""
                        />
                        <div class="flex flex-col grow">
                            <span class="font-bold text-lg">{{ listingAddress(r.listing) }}</span>
                            <span>{{ listingRooms(r.listing) }}</span>
                            <span class="bg-neutral-200 text-sm font-bold px-1.5 py-0.5 rounded w-fit">{{ r.listing.status }}</span>
                            <div class="flex justify-between gap-2 items-end grow">
                                <span class="text-right text-sm text-neutral-400">{{ new Date(r.listing.listed_at).toLocaleDateString() }}</span>
                                <span class="text-right text-sm text-neutral-400">{{ listingMLSID(r.listing) }}</span>
                            </div>
                        </div>
                    </Link>
                    <h1 class="text-lg font-bold">Account</h1>
                    <a v-if="$page.props.auth.user.group_id === 1" href="/admin" class="rounded-lg text-center bg-black px-4 py-1 font-bold text-white">Go to Administrator Panel</a>
                    <button
                        @click="router.post('logout')"
                        class="cursor-pointer rounded-lg bg-black px-2 py-1 font-bold text-white"
                    >
                        Log Out
                    </button>
                    <!--                    <button class="rounded-lg font-bold text-white bg-black px-2 py-1">Delete Account</button>-->
                </div>
            </main>
        </div>
        <Footer />
    </div>
</template>
