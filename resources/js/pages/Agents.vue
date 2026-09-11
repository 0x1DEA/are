<script setup>
import { Head, Link } from '@inertiajs/vue3';
import chicago_bg from '~/assets/sawyer-bengtson-tnv84LOjes4-unsplash.jpg';
import AgentCard from '@/components/AgentCard.vue';
import { useTemplateRef } from 'vue';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';

const props = defineProps({
    agents: Object,
});

const agentsSection = useTemplateRef('agents');

const scrollDown = () => {
    agentsSection.value.scrollIntoView({
        behavior: 'smooth'
    });
}
</script>
<template>
    <Head title="Agents">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="text-neutral-1000 relative flex min-h-screen w-full flex-col bg-neutral-100">
        <Header title="Agents"/>
        <main class="grow">
            <div :style="`background: url('${chicago_bg}')`" class="flex flex-col justify-center bg-cover text-white">
                <div class="flex h-72 relative flex-col items-center text-center justify-center gap-4 bg-black/50">
                    <h1 class="text-6xl font-bold">Our Agents</h1>
                    <h2 class="text-2xl">Meet our team</h2>
                    <button @click="scrollDown" class="absolute bottom-6 cursor-pointer bg-black/25 rounded-full p-3 border-x border-white/50 backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5" />
                        </svg>
                    </button>
                </div>
            </div>
            <div ref="agents" class="flex flex-col items-center px-16 gap-8 py-8">
                <h1 class="text-6xl font-bold text-center">Our Agents</h1>
                <div class="flex flex-wrap justify-center gap-8">
                    <AgentCard v-for="agent in agents" :key="agent.id" :agent="agent" class="md:w-1/4"/>
                </div>
            </div>
        </main>
        <Footer/>
    </div>
</template>
<style>

</style>
