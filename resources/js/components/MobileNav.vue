<script setup lang="ts">
import { routeIsURL } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <details class="md:hidden p-4 group">
        <summary class="relative z-50 group-open:text-black">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </summary>
        <div class="absolute top-0 left-0 text-black flex flex-col p-8 gap-8 pt-32 right-0 h-screen bg-white opacity-0 group-open:!opacity-100 transition-opacity z-30">
            <Link href="/" class="text-5xl font-bold w-fit" :class="{'border-b-4': routeIsURL('/')}">Home</Link>
            <Link href="/search" class="text-5xl font-bold w-fit" :class="{'border-b-4': routeIsURL('/search')}">Search</Link>
            <Link href="/agents" class="text-5xl font-bold w-fit" :class="{'border-b-4': routeIsURL('/agents')}">Agents</Link>
            <Link href="/contact" class="text-5xl font-bold w-fit" :class="{'border-b-4': routeIsURL('/contact')}">Contact</Link>
        </div>
    </details>
</template>
<style scoped>
details > summary::-webkit-details-marker {
    display:none;
}
details > summary {
    list-style: none;
}
</style>
