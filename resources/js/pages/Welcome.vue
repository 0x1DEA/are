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

const cities = ref([]);

onBeforeMount(() => {
    let rows = cities_csv.split('\n');

    for (const i in rows) {
        let frags = rows[i].split(',');

        cities.value[i] = {
            name: frags[1],
            lat: frags[3],
            lng: frags[4],
        };
    }
});

const citySearch = ref('');

const cityResults = computed(() => {
    let out = [];

    for (let i = 0; i < cities.value.length; i++) {
        let check = (cities.value?.[i]?.name ?? '').toLowerCase();
        let search = citySearch.value.toLowerCase();
        if (check.includes(search)) out.push(i);
    }

    return out;
});

const props = defineProps({
    agents: Object,
});

const agentsSection = useTemplateRef('agents');

const scrollDown = () => {
    agentsSection.value.scrollIntoView({
        behavior: 'smooth',
    });
};

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
};

const contacted = ref(false);
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
                <img src="./../../assets/are_logo_full.svg" alt="" class="h-32 p-4" />
            </div>
            <div class="z-10 flex flex-col">
                <div
                    class="hidden items-center justify-end gap-2 px-2 py-1 md:flex"
                    style="
                        text-shadow:
                            0 0 5px black,
                            0 0 3px black;
                    "
                >
                    <SocialIcons />
                    <a :href="`tel:${$$.tel}`" class="text-xl font-bold">{{ $$.tel_s }}</a>
                </div>
                <nav
                    class="hidden justify-end space-x-2 text-xl font-bold md:flex"
                    style="
                        text-shadow:
                            0 0 5px black,
                            0 0 3px black;
                    "
                >
                    <Link href="/search" class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4"
                        >Search</Link
                    >
                    <Link href="/agents" class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4"
                        >Agents</Link
                    >
                    <Link href="/contact" class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4"
                        >Contact</Link
                    >
                    <Link v-if="$page.props.auth.user" href="/profile" class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4"
                        >Profile</Link
                    >
                    <Link v-else href="/profile" class="hover:bg-linear-to-b from-white/50 to-transparent px-6 py-4"
                    >Log In</Link
                    >
                </nav>
                <MobileNav class="p-8" />
            </div>
        </header>
        <main class="grow">
            <div :style="`background: url('${chicago_bg}')`" class="flex flex-col justify-center bg-cover text-white">
                <div class="relative flex h-screen flex-col items-center justify-center gap-4 bg-black/50 text-center">
                    <h1 class="text-6xl font-bold">America Real&nbsp;Estate</h1>
                    <h2 class="text-2xl">Find your home sweet home</h2>
                    <label
                        class="flex items-center gap-4 rounded-full border border-white/25 bg-black/25 px-4 py-4 backdrop-blur"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="size-5 text-neutral-500"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <input
                            v-model="citySearch"
                            type="text"
                            autocomplete="off"
                            placeholder="Search your city"
                            class="focus-visible:outline-0"
                        />
                    </label>
                    <div v-if="citySearch !== ''" class="relative w-64 px-4">
                        <div
                            class="absolute -top-2 left-0 right-0 max-h-64 overflow-hidden overflow-y-auto rounded-lg border border-white/25 bg-black/25 backdrop-blur"
                        >
                            <Link
                                v-for="city in cityResults"
                                :key="city"
                                class="block px-4 py-2 hover:bg-black/50"
                                :href="`/search?lat=${cities[city].lat}&lng=${cities[city].lng}`"
                                >{{ cities[city].name }}</Link
                            >
                        </div>
                    </div>
                    <button
                        @click="scrollDown"
                        class="absolute bottom-6 cursor-pointer rounded-full border-x border-white/50 bg-black/25 p-3 backdrop-blur"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="size-6"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 5.25 7.5 7.5 7.5-7.5m-15 6 7.5 7.5 7.5-7.5"
                            />
                        </svg>
                    </button>
                    <span
                        class="absolute bottom-2 right-2 rounded-lg bg-black/25 px-2 py-1 text-sm text-white/50 backdrop-blur"
                        >Photo by
                        <a
                            class="underline"
                            href="https://unsplash.com/@sawyerbengtson?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText"
                            >Sawyer Bengtson</a
                        ></span
                    >
                </div>
            </div>
            <div ref="agents" class="flex flex-col items-center gap-8 px-16 py-8">
                <h1 class="text-center text-6xl font-bold">Our Agents</h1>
                <div class="flex w-full flex-wrap justify-center gap-8">
                    <AgentCard v-for="agent in agents" :key="agent.id" :agent="agent" class="md:w-1/4" />
                </div>
            </div>
            <div v-if="0" class="flex flex-col gap-8 px-48 py-16">
                <h1 class="text-center text-6xl">Testimonials</h1>
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <p>
                            <span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span
                                class="text-2xl font-bold"
                                >&rdquo;</span
                            >
                        </p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p>
                            <span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span
                                class="text-2xl font-bold"
                                >&rdquo;</span
                            >
                        </p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p>
                            <span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span
                                class="text-2xl font-bold"
                                >&rdquo;</span
                            >
                        </p>
                        <span>John Smith</span>
                    </div>
                    <div>
                        <p>
                            <span class="text-2xl font-bold">&ldquo;</span>Lorem ipsum dolor sit amet<span
                                class="text-2xl font-bold"
                                >&rdquo;</span
                            >
                        </p>
                        <span>John Smith</span>
                    </div>
                </div>
            </div>
            <div
                id="contact"
                :style="`background-image: url('${office_bg}')`"
                class="flex flex-col justify-center bg-cover px-4 py-4 text-white md:px-16 md:py-48"
            >
                <div class="flex flex-col justify-between gap-4 md:flex-row md:gap-8">
                    <div class="rounded-lg bg-black/50 p-4 backdrop-blur md:w-1/2">
                        <h1 class="text-4xl font-bold">America Real Estate</h1>
                        <p>Your destination for real estate in the greater Chicago area</p>
                        <h1 class="text-2xl font-bold">Hours</h1>
                        <p>9-5 Mon-Sat</p>
                        <h1 class="text-2xl font-bold">Phone</h1>
                        <a :href="`tel:${$$.tel}`">{{ $$.tel_s }}</a>
                    </div>
                    <div class="flex flex-col gap-4 rounded-lg bg-black/50 p-4 backdrop-blur md:w-1/2">
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
                            <button @click="contactSubmit()" class="cursor-pointer rounded-lg bg-white/25 px-4 py-1">
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <Footer />
    </div>
</template>
<style></style>
