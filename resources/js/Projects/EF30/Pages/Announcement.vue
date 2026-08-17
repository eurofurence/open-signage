<script setup>
import { useScreenOrientation } from '@/screenOrientation.js';

const props = defineProps({
  appScreen: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: 'Announcement',
  },
  text: {
    type: String,
    default: "Welp!\n\nLooks like there's no announcement right now.",
  },
  centerContent: {
    type: Boolean,
    default: false,
  },
  headerSize: {
    type: String,
    default: 'text-12xl',
  },
  textSize: {
    type: String,
    default: 'text-9xl',
  },
  useContainer: {
    type: Boolean,
    default: false,
  },
});

const { isPortrait } = useScreenOrientation(() => props.appScreen);
</script>

<template>
  <div
    class="h-full flex flex-col justify-center items-center z-50 bgImage bg-no-repeat bg-cover bg-center text-primary-200"
    :class="{ 'text-center': centerContent }"
  >
    <div
      :class="[
        isPortrait ? 'p-8 w-full' : 'p-16',
        { 'bg-white bg-opacity-80 mx-auto min-h-full': useContainer },
        { 'max-w-7xl': useContainer && !isPortrait },
      ]"
    >
      <h1
        class="theme-font text-center break-words"
        :class="isPortrait ? 'text-[8vw] mb-8' : ['text-[128pt] mb-12', headerSize]"
      >
        {{ title }}
      </h1>
      <div
        class="font-semibold theme-font-secondary leading-normal mx-auto whitespace-pre-wrap text-center break-words"
        :class="isPortrait ? 'text-[5vw]' : ['text-[88pt]', textSize]"
      >
        <div class="textscreen" v-html="text"></div>
      </div>
    </div>
  </div>
</template>

<style>
figcaption {
  display: none;
}

.textscreen img {
  padding: 60px;
  margin: auto;
}
</style>
