<script setup>
import SocialIcons from '@/components/SocialIcons.vue';
import { $$ } from '@/lib/utils.ts';
</script>
<template>
    <footer class="flex flex-col gap-4 px-16 py-4 text-white" style="background: #124039">
        <div class="flex items-center justify-between px-8">
            <div class="flex max-w-2xl items-center gap-4">
                <img src="./../../assets/are_logo_full.svg" alt="" class="h-32 p-2" />
                <!--                    <p>America Real Estate has been providing excellent real estate services since 1998</p>-->
            </div>
            <div class="flex flex-col">
                <span>{{ $$.tel_s }}</span>
                <span>{{ $$.email }}</span>
                <span>{{ $$.addr }}</span>
                <SocialIcons class="mt-2" />
            </div>
        </div>
        <p class="border-t border-[#feba69] pt-4 text-center">America Real Estate Inc. &copy; {{ new Date().getFullYear() }}</p>
    </footer>
</template>
