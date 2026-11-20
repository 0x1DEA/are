<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import Header from '@/components/Header.vue';
import { computed, ref } from 'vue';

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

const nextWizard = () => {
    wizard.value++;

    if (wizard.value === 1) {
        if (!window.turnstile) return;

        const widget = window.turnstile.render('#cf-turnstile', {
            sitekey: '0x4AAAAAAF-1m3Jc0puuJQfZ',
            callback: (token) => {
                register.turnstile = token;
            },
        });
    } else if (wizard.value > 1) {
        console.log('hi');
        register.post('/register?redirect=' + usePage().props.url, {});
    }
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
                <div
                    class="w-2xl flex flex-col gap-2 rounded-2xl border-y border-b-neutral-200 border-t-white bg-white p-6 shadow-xl"
                >
                    <h1 class="text-center text-3xl font-bold">Register</h1>
                    <p class="text-center text-lg">
                        Get personalized help from our team, view new listings, and perform automatic searches for new
                        properties
                    </p>
                    <div>
                        <div class="flex w-full rounded-lg bg-neutral-100 shadow">
                            <div
                                class="grid grid-cols-[0fr] transition-[grid-template-columns]"
                            >
                                <div class="overflow-hidden">
                                    <select class="mr-4 h-full pl-4 pr-2">
                                        <option>+1</option>
                                    </select>
                                </div>
                            </div>
                            <input
                                v-model="register.email"
                                type="text"
                                class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-lg transition-[border-radius]"
                                placeholder="Email"
                            />
                        </div>
                    </div>
                    <template v-if="wizard > 0">
                        <input
                            v-model="register.name"
                            type="text"
                            class="w-full rounded-lg border-l border-neutral-100 bg-white px-4 py-3 text-lg shadow transition-[border-radius]"
                            placeholder="Name"
                        />
                        <input
                            v-model="register.password"
                            type="password"
                            class="w-full rounded-lg border-l border-neutral-100 bg-white px-4 py-3 text-lg shadow transition-[border-radius]"
                            placeholder="Password"
                        />
                        <input
                            v-model="register.password_confirmation"
                            type="password"
                            class="w-full rounded-lg border-l border-neutral-100 bg-white px-4 py-3 text-lg shadow transition-[border-radius]"
                            placeholder="Confirm Password"
                        />
                        <label class="mt-2 flex items-center gap-3 px-4">
                            <input type="checkbox" class="mt-0.5 scale-150" v-model="register.terms" />
                            <span class="select-none">I agree to the Terms and Conditions and Privacy Policy</span>
                        </label>
                    </template>
                    <ul v-if="$page.props.errors">
                        <li class="text-red-500" v-for="error in $page.props.errors">{{ error }}</li>
                    </ul>
                    <div v-show="wizard > 0" id="cf-turnstile" class="flex justify-center"></div>
                    <button
                        @click="nextWizard"
                        :disabled="wizard === 1 && !canRegister"
                        :class="
                            wizard === 0 || canRegister
                                ? 'cursor-pointer border-black bg-neutral-800'
                                : 'border-neutral-300 bg-neutral-200'
                        "
                        class="rounded-lg border-b p-3 font-bold text-white shadow transition-colors"
                    >
                        Next
                    </button>
                    <div v-if="wizard === 0" class="flex items-center space-x-2">
                        <span class="grow border-b border-neutral-300"></span>
                        <span class="mb-1">or</span>
                        <span class="grow border-b border-neutral-300"></span>
                    </div>
                    <Link
                        v-if="wizard === 0"
                        :href="`/login`"
                        class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-b border-black bg-neutral-800 p-3 font-bold text-white shadow"
                    >
                        <span>Log in with Email</span>
                    </Link>
                    <a
                        v-if="wizard === 0"
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
                </div>
            </main>
        </div>
        <Footer />
    </div>
</template>
