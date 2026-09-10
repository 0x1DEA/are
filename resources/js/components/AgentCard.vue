<script setup>
const props = defineProps({
    agent: Object
});
</script>
<template>
    <div class="flex flex-col relative z-0 group bg-neutral-200 rounded-lg">
        <div class="absolute inset-0 overflow-hidden rounded-lg">
            <img :src="'storage/' + agent.headshot_url" alt="" loading="lazy" class="size-full group-hover:scale-110 transition-transform object-cover object-center"/>
        </div>
        <div class="flex flex-col justify-end text-white z-10 p-4 pt-48 bg-linear-to-b from-transparent to-black/50 aspect-3/4 rounded-lg">
            <span class="text-lg uppercase">{{ agent.name_first }}</span>
            <span class="text-2xl uppercase">{{ agent.name_last }}</span>
            <div class="flex gap-1 items-center rounded-full pl-2 pr-4 mt-4 w-fit py-1 bg-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span class="text-lg uppercase">{{ agent.type }}</span>
            </div>
        </div>
    </div>
</template>
