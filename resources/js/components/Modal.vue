<script setup>
defineProps({
    containerClass: String,
    heading: String,
});

const open = defineModel('open', { default: false });
</script>
<template>
    <div @click="open = true">
        <slot name="default"/>
        <teleport to="body">
            <div v-if="open" @click="open = false" class="flex items-center justify-center fixed inset-0 bg-black/25 z-100 p-8">
                <div @click.stop :class="containerClass" class="flex flex-col gap-2 bg-white p-6 rounded-2xl w-2xl">
                    <div class="flex justify-between">
                        <div></div>
                        <h1 class="text-3xl text-center font-bold">Share This Listing</h1>
                        <svg @click="open = false" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 cursor-pointer">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <slot name="content"/>
                </div>
            </div>
        </teleport>
    </div>
</template>
