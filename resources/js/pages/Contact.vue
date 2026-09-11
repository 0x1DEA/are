<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import SocialIcons from '@/components/SocialIcons.vue';
import office_bg from '~/assets/office.jpg';
import chicago_bg from '~/assets/sawyer-bengtson-tnv84LOjes4-unsplash.jpg';
import AgentCard from '@/components/AgentCard.vue';
import cities_csv from '~/assets/cities.csv?raw';
import { computed, onBeforeMount, ref, useTemplateRef } from 'vue';
import Footer from '@/components/Footer.vue';
import { $$, routeIsURL } from '@/lib/utils.ts';
import MobileNav from '@/components/MobileNav.vue';
import Header from '@/components/Header.vue';

const contactSection = useTemplateRef('contact');

const scrollDown = () => {
    contactSection.value.scrollIntoView({
        behavior: 'smooth'
    });
}

const contactForm = useForm({
    subject: '',
    email: '',
    content: '',
});

const contactSubmit = () => {
    contactForm.post('/contact', {
        onSuccess: () => {
            contacted.value = true;
        },
        preserveScroll: true,
    });
}

const contacted = ref(false);
</script>
<template>
    <Head title="Contact">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="text-neutral-1000 relative flex min-h-screen w-full flex-col bg-neutral-100">
        <Header/>
        <main class="grow">
            <div :style="`background: url('${chicago_bg}')`" class="flex flex-col justify-center bg-cover text-white">
                <div class="flex h-72 relative flex-col items-center text-center justify-center gap-4 bg-black/50">
                    <h1 class="text-6xl font-bold">Contact Us</h1>
                    <h2 class="text-2xl">Get in touch</h2>
                    <button @click="scrollDown" class="absolute bottom-6 cursor-pointer bg-black/25 rounded-full p-3 border-x border-white/50 backdrop-blur">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5" />
                        </svg>
                    </button>
                </div>
            </div>
            <div
                ref="contact"
                :style="`background-image: url('${office_bg}')`"
                class="flex flex-col justify-center bg-cover px-4 md:px-16 py-4 md:py-48 text-white"
            >
                <div class="flex flex-col md:flex-row justify-between gap-4 md:gap-8">
                    <div class="md:w-1/2 rounded-lg bg-black/50 p-4 backdrop-blur">
                        <h1 class="text-4xl font-bold">America Real Estate</h1>
                        <p>Your destination for real estate in the greater Chicago area</p>
                        <h1 class="text-2xl font-bold">Hours</h1>
                        <p>9-5 Mon-Sat</p>
                        <h1 class="text-2xl font-bold">Phone</h1>
                        <a :href="`tel:${$$.tel}`">{{$$.tel_s}}</a>
                    </div>
                    <div class="flex md:w-1/2 flex-col gap-4 rounded-lg bg-black/50 p-4 backdrop-blur">
                        <h2 class="text-4xl font-bold">Contact Us</h2>
                        <p v-if="contacted">Thank you for reaching out! We'll get back to you shortly!</p>
                        <div v-else class="flex flex-col gap-4">
                            <input
                                v-model="contactForm.subject"
                                type="text"
                                placeholder="Name"
                                class="rounded bg-white px-2 py-1 text-black placeholder:text-neutral-500"
                            />
                            <input
                                v-model="contactForm.email"
                                type="text"
                                placeholder="Email"
                                class="rounded bg-white px-2 py-1 text-black placeholder:text-neutral-500"
                            />
                            <textarea
                                v-model="contactForm.content"
                                rows="4"
                                class="rounded bg-white px-2 py-1 text-black placeholder:text-neutral-500"
                                placeholder="Message"
                            ></textarea>
                            <button @click="contactSubmit()" class="rounded-lg bg-white/25 px-4 py-1 cursor-pointer">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <Footer/>
    </div>
</template>
<style>

</style>
