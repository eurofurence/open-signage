import { computed, onBeforeUnmount, onMounted, ref, toValue } from 'vue';

const PORTRAIT_ORIENTATIONS = ['left', 'right'];

function readViewport() {
  const width = window.innerWidth;
  const height = window.innerHeight;

  if (!width || !height) return false;

  return height > width;
}

const viewportIsPortrait = ref(readViewport());

let subscribers = 0;

function syncViewport() {
  viewportIsPortrait.value = readViewport();
}

export function useScreenOrientation(screen) {
  onMounted(() => {
    if (subscribers === 0) {
      window.addEventListener('resize', syncViewport);
      window.addEventListener('orientationchange', syncViewport);
    }

    subscribers += 1;
    syncViewport();
  });

  onBeforeUnmount(() => {
    subscribers -= 1;

    if (subscribers === 0) {
      window.removeEventListener('resize', syncViewport);
      window.removeEventListener('orientationchange', syncViewport);
    }
  });

  const isPortrait = computed(() => {
    const orientation = toValue(screen)?.orientation;

    if (orientation && orientation !== 'normal') {
      return PORTRAIT_ORIENTATIONS.includes(orientation);
    }

    return viewportIsPortrait.value;
  });

  const isLandscape = computed(() => !isPortrait.value);

  return { isPortrait, isLandscape };
}
