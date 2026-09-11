<script setup>
import SocialIcons from '@/components/SocialIcons.vue';
import { Link } from '@inertiajs/vue3';
import { $$ } from '@/lib/utils.ts';
import MobileNav from '@/components/MobileNav.vue';
import { onMounted, ref } from 'vue';

const title = ref('');

onMounted(() => {
    title.value = document.title.replace(/- ARE.*/g, '');
});
</script>
<template>
    <header>
        <div class="flex flex-col gap-4 text-white" style="background: #124039">
            <div class="flex items-center justify-between px-4">
                <Link href="/">
                    <img src="./../../assets/are_logo_full.svg" alt="" class="h-24 p-3" />
                </Link>
                <div class="flex flex-col items-end gap-2">
                    <nav class="hidden gap-2 md:flex">
                        <Link href="/" class="px-4 py-2 text-xl font-bold">Home</Link>
                        <Link href="/search" class="px-4 py-2 text-xl font-bold">Search</Link>
                        <Link href="/agents" class="px-4 py-2 text-xl font-bold">Agents</Link>
                        <Link href="/contact" class="px-4 py-2 text-xl font-bold">Contact</Link>
                    </nav>
                    <MobileNav />
                </div>
            </div>
        </div>
        <div class="flex items-center justify-between bg-[rgb(126,37,58)] px-4 py-1.5 font-bold text-[rgb(255,178,94)]">
            <div class="flex flex-wrap items-center gap-1">
                <span>Home</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                    <path
                        fill-rule="evenodd"
                        d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>{{ title }}</span>
            </div>
            <div class="hidden gap-4 md:flex">
                <SocialIcons />
                <a :href="`tel:${$$.tel}`" class="font-bold">{{ $$.tel_s }}</a>
            </div>
        </div>
    </header>
</template>
<style>
* {
    --thing: rgb(255, 178, 94);
    --thing2: rgb(126, 37, 58);
}
</style>
