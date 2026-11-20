<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import { onMounted } from 'vue';

const login = useForm({
    turnstile: null,
    email: '',
    password: '',
});

onMounted(() => {
    const widget = window.turnstile.render('#cf-turnstile', {
        sitekey: '0x4AAAAAAF-1m3Jc0puuJQfZ',
        callback: (token) => {
            login.turnstile = token;
        },
    });
});

const submit = () => {
    if (login.processing) return;

    login.post('/login?redirect=' + usePage().props.url, {});
};
</script>
<template>
    <Head title="Profile">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="text-neutral-1000 relative flex min-h-screen w-full flex-col bg-neutral-100">
        <Header title="Profile" />
        <div class="flex grow flex-col items-center">
            <main class="flex flex-col items-center w-full max-w-6xl gap-8 p-8">
                <form
                    @submit.prevent="submit()"
                    class="w-2xl flex flex-col gap-2 rounded-2xl border-y border-b-neutral-200 border-t-white bg-white p-6 shadow-xl"
                >
                    <h1 class="text-center text-3xl font-bold">Log in</h1>
                    <p class="text-center text-lg">Get access to thousands of properties across the Chicagoland area</p>
                    <input
                        v-model="login.email"
                        type="text"
                        class="w-full rounded-lg bg-neutral-100 border border-neutral-300 px-4 py-3 text-lg transition-[border-radius]"
                        placeholder="Email"
                    />
                    <input
                        v-model="login.password"
                        type="password"
                        class="w-full rounded-lg bg-neutral-100 border border-neutral-300 px-4 py-3 text-lg transition-[border-radius]"
                        placeholder="Password"
                    />
                    <ul v-if="$page.props.errors">
                        <li class="text-red-500" v-for="error in $page.props.errors">{{ error }}</li>
                    </ul>
                    <div id="cf-turnstile" class="flex justify-center"></div>
                    <button
                        class="cursor-pointer border-black bg-neutral-800 rounded-lg border-b p-3 font-bold text-white shadow transition-colors"
                    >
                        Log In
                    </button>
                    <div class="flex items-center space-x-2">
                        <span class="grow border-b border-neutral-300"></span>
                        <span class="mb-1">or</span>
                        <span class="grow border-b border-neutral-300"></span>
                    </div>
                    <a
                        :href="`/oauth2/facebook/redirect?redirect=${$page.url}`"
                        class="flex items-center justify-center gap-2 rounded-lg border-b border-blue-700 bg-[#1877f2] p-2 font-bold text-white shadow"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                            <!-- Icon from Material Design Icons by Pictogrammers - https://github.com/Templarian/MaterialDesign/blob/master/LICENSE -->
                            <path
                                fill="currentColor"
                                d="M12 2.04c-5.5 0-10 4.49-10 10.02c0 5 3.66 9.15 8.44 9.9v-7H7.9v-2.9h2.54V9.85c0-2.51 1.49-3.89 3.78-3.89c1.09 0 2.23.19 2.23.19v2.47h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33v7a10 10 0 0 0 8.44-9.9c0-5.53-4.5-10.02-10-10.02"
                            />
                        </svg>
                        <span>Log in with Facebook</span>
                    </a>
                    <Link href="/register" class="text-center underline">I don't have an account</Link>
                </form>
            </main>
        </div>
        <Footer />
    </div>
</template>
