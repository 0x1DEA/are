<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SocialIcons from '@/components/SocialIcons.vue';
import office_bg from '~/assets/office.jpg';
import chicago_bg from '~/assets/sawyer-bengtson-tnv84LOjes4-unsplash.jpg';
import AgentCard from '@/components/AgentCard.vue';
import MapSearchPage from '@/components/MapSearchPage.vue';

const props = defineProps({
    agents: Object,
});
</script>
<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="text-neutral-1000 relative flex min-h-screen w-full flex-col bg-neutral-100">
        <header class="absolute left-0 right-0 top-0 flex justify-between text-white">
            <div class="bg-linear-to-b absolute h-[150%] w-full from-black/75 to-transparent"></div>
            <div class="z-10">
                <img src="./../../assets/are_logo_full.svg" alt="" class="h-32 p-4"/>
            </div>
            <div class="z-10" style="text-shadow: 0 0 5px black, 0 0 3px black;">
                <div class="flex items-center gap-2 px-2 py-1">
                    <SocialIcons/>
                    <span class="font-bold text-xl">+1 (773) 735-4400</span>
                </div>
                <nav class="flex space-x-2 text-xl font-bold">
                    <span class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4">Home</span>
                    <span class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4">Listings</span>
                    <span class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4">Contact</span>
                </nav>
            </div>
        </header>
        <main class="grow">
            <div :style="`background: url('${chicago_bg}')`" class="flex flex-col justify-center bg-cover text-white">
                <div class="flex flex-col gap-4 items-center justify-center h-screen bg-black/50">
                    <h1 class="text-6xl font-bold">American Real Estate</h1>
                    <h2 class="text-2xl">Find your home sweet home</h2>
                    <label class="flex items-center bg-black/25 gap-4 px-4 py-4 border border-white/25 rounded-full backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-neutral-500">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                        <input type="text" placeholder="Search your city" class="focus-visible:outline-0"/>
                    </label>
                </div>
            </div>
            <div class="flex flex-col gap-8 items-center px-16 py-16">
                <h1 class="font-bold text-4xl">Our Agents</h1>
                <div class="grid grid-cols-3 w-full">
                    <AgentCard v-for="agent in agents" :key="agent.id" :agent="agent"/>
                </div>
            </div>
            <div class="flex flex-col gap-8 px-48 py-16">
                <h1 class="text-center text-6xl">Testimonials</h1>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p><span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span class="text-2xl font-bold">&rdquo;</span></p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p><span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span class="text-2xl font-bold">&rdquo;</span></p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p><span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span class="text-2xl font-bold">&rdquo;</span></p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p><span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span class="text-2xl font-bold">&rdquo;</span></p>
                        <span>John Smith</span>
                    </div>
                </div>
            </div>
            <div>

            </div>
            <div :style="`background-image: url('${office_bg}')`" class="flex flex-col justify-center bg-cover py-48 px-16 text-white">
                <div class="flex justify-between gap-8">
                    <div class="bg-black/50 p-4 rounded backdrop-blur w-1/2">
                        <h1 class="text-4xl font-bold">America Real Estate</h1>
                        <p>Your destination for real estate in the greater Chicago area</p>
                    </div>
                    <div class="flex flex-col gap-4 bg-black/50 p-4 rounded backdrop-blur w-1/2">
                        <h2 class="text-4xl font-bold">Contact Us</h2>
                        <input type="text" placeholder="Name" class="bg-white text-black placeholder:text-neutral-500 rounded px-2 py-1"/>
                        <input type="text" placeholder="Email" class="bg-white text-black placeholder:text-neutral-500 rounded px-2 py-1"/>
                        <textarea rows="4" class="bg-white text-black placeholder:text-neutral-500 rounded px-2 py-1" placeholder="Message"></textarea>
                        <button class="bg-white/25 px-4 py-1 rounded">Send</button>
                    </div>
                </div>
            </div>
        </main>
        <footer class="flex flex-col gap-4 px-16 py-4 text-white" style="background: #124039">
            <div class="flex items-center justify-between px-8">
                <div class="flex max-w-2xl items-center gap-4">
                    <img src="./../../assets/are_logo_full.svg" alt="" class="h-32 p-2" />
                    <p>America Real Estate has been providing excellent real estate services since 1998</p>
                </div>
                <div class="flex flex-col">
                    <span>+1 (773) 735-4400</span>
                    <span>info@americarealestateinc.com</span>
                    <span>5744 S Pulaski Rd, Chicago, IL 60629</span>
                    <SocialIcons class="mt-2"/>
                </div>
            </div>
            <p class="border-t border-[#feba69] pt-4 text-center">America Real Estate Inc. &copy; 2026</p>
        </footer>
    </div>
</template>
