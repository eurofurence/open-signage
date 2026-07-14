<script setup>
import {useAppState} from "@/state.js";
import {synced} from "@/synced.js";

const props = defineProps({
    playSpeed: {
        type: Number,
        required: false,
        default: 1600 + 5000
    },
    transition: {
        type: Number,
        required: false,
        default: 1600
    },
});

const state = useAppState();
const announcement = synced(state.announcements, props.playSpeed);
</script>

<template>
    <Transition mode="out-in" :duration="transition">
        <div v-if="announcement" :key="announcement.id"
             class="h-full flex flex-col justify-center items-center text-primary-200">
            <h1 class="themeFont text-[128pt] mb-12 text-center">
                {{ announcement.title }}
            </h1>
            <p class="font-semibold themeFontSecondary leading-normal mx-auto whitespace-pre-wrap text-[88pt] text-center">
                {{ announcement.content }}
            </p>
        </div>
    </Transition>
</template>
