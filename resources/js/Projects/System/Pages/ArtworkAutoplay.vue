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
const portraitOrientations = ['left', 'right'];

const orientationFromDisplay = () => {
  const width = window.innerWidth;
  const height = window.innerHeight;

  if (!width || !height || width === height) return null;

  return height > width ? 'vertical' : 'horizontal';
};

const orientationFromScreenRecord = () => {
  return portraitOrientations.includes(props.appScreen.orientation) ? 'vertical' : 'horizontal';
};

const resolveOrientation = () => orientationFromDisplay() ?? orientationFromScreenRecord();

const screenOrientation = ref(resolveOrientation());
const carousel = ref(null);

const handleOrientationChange = () => {
  screenOrientation.value = resolveOrientation();
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
  window.addEventListener('resize', handleOrientationChange);
});

onBeforeUnmount(() => {
  window.removeEventListener('orientationchange', handleOrientationChange);
  window.removeEventListener('resize', handleOrientationChange);
  stopCarousel(carousel.value);
});

const shuffleRanks = new Map();

const shuffleRankOf = id => {
  if (!shuffleRanks.has(id)) shuffleRanks.set(id, Math.random());
  return shuffleRanks.get(id);
};

const artworksFilteredWithoutMissingOrientation = computed(() => {
  return state.artworks
    .filter(artwork => Boolean(artwork[screenOrientation.value]))
    .sort((a, b) => shuffleRankOf(a.id) - shuffleRankOf(b.id));
});
</script>

<template>
  <div class="h-screen w-full overflow-hidden">
    <Hooper
      v-if="artworksFilteredWithoutMissingOrientation.length > 0"
      ref="carousel"
      :mouse-drag="false"
      :hover-pause="false"
      :keys-control="false"
      :rtl="false"
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
