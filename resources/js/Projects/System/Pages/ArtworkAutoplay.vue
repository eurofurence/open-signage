<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Hooper, Slide } from 'hooper-vue3';
import 'hooper-vue3/dist/hooper.css';
import { useAppState } from '@/state.js';

const props = defineProps({
  appScreen: {
    type: Object,
    required: true,
  },
  playSpeed: {
    type: Number,
    required: false,
    default: 1600 + 5000,
  },
  transition: {
    type: Number,
    required: false,
    default: 1600,
  },
});

const state = useAppState();
const isScreenHorizontal = props.appScreen.orientation === 'normal' || props.appScreen.orientation === 'inverted';
const screenOrientation = ref(isScreenHorizontal ? 'horizontal' : 'vertical');
const carousel = ref(null);

const handleOrientationChange = () => {
  if (screen.orientation.angle === 90) {
    screenOrientation.value = 'vertical';
  } else {
    screenOrientation.value = 'horizontal';
  }
};

const stopCarousel = instance => {
  if (!instance) return;
  if (instance.timer) instance.timer.stop();
  window.removeEventListener('resize', instance.update);
};

watch(carousel, (current, previous) => {
  if (previous && previous !== current) stopCarousel(previous);
});

onMounted(() => {
  window.addEventListener('orientationchange', handleOrientationChange);
});

onBeforeUnmount(() => {
  window.removeEventListener('orientationchange', handleOrientationChange);
  stopCarousel(carousel.value);
});

const artworksFilteredWithoutMissingOrientation = computed(() => {
  const filteredArt = state.artworks.filter(artwork => {
    return artwork[screenOrientation.value] !== null;
  });
  // Randomize the order of the artworks
  return filteredArt.sort(() => Math.random() - 0.5);
});
</script>

<template>
  <div class="h-screen">
    <Hooper
      v-if="artworksFilteredWithoutMissingOrientation.length > 0"
      ref="carousel"
      :mouse-drag="false"
      :hover-pause="false"
      :keys-control="false"
      class="h-screen w-full"
      :transition="transition"
      :wheel-control="false"
      :center-mode="false"
      :auto-play="true"
      :itemsToShow="1"
      :pagination="false"
    >
      <Slide
        v-for="slide in artworksFilteredWithoutMissingOrientation"
        :duration="playSpeed"
        :index="slide.id"
        :key="slide.id"
      >
        <div>
          <img :src="slide[screenOrientation] + '.webp'" :alt="slide.name" class="object-cover h-full w-full" />
        </div>
      </Slide>
    </Hooper>
  </div>
</template>

<style scoped>
.hooper {
  height: 100% !important;
}
</style>
