<script setup>
import { computed } from 'vue';
import { useScreenOrientation } from '@/screenOrientation.js';

const props = defineProps({
  appScreen: {
    type: Object,
    default: null,
  },
});

const { isPortrait } = useScreenOrientation(() => props.appScreen);

const orientationClass = computed(() => (isPortrait.value ? 'stage-portrait' : 'stage-landscape'));
</script>

<template>
  <div class="stage" :class="orientationClass">
    <div class="stage-back">
      <img class="stage-back-image" src="../Assets/images/background.png" alt="" />
    </div>

    <div class="stage-frame">
      <div class="stage-frame-drift">
        <img class="stage-frame-image" src="../Assets/images/foreground.png" alt="" />
      </div>
    </div>

    <div class="h-screen overflow-auto bg-transparent flex flex-col flex-grow">
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
.stage,
.stage.stage-landscape {
  --frame-width: 110vw;
  --frame-min-width: 196vh;
  --frame-rotate: none;
}

.stage.stage-portrait {
  --frame-width: 110vh;
  --frame-min-width: 196vw;
  --frame-rotate: rotate(90deg);
}

.stage-back,
.stage-frame {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
}

.stage-back {
  z-index: -2;
}

.stage-frame {
  z-index: -1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stage-back-image {
  position: absolute;
  left: -48px;
  top: -32px;
  width: calc(100% + 96px);
  max-width: none;
  height: calc(100% + 64px);
  object-fit: cover;
  will-change: transform;
  animation: back-drift 34s ease-in-out infinite alternate;
}

.stage-frame-drift {
  flex: none;
  will-change: transform;
  animation: frame-drift 24s ease-in-out infinite alternate;
}

.stage-frame-image {
  display: block;
  width: var(--frame-width);
  min-width: var(--frame-min-width);
  max-width: none;
  transform: var(--frame-rotate);
}

@keyframes back-drift {
  from {
    transform: translate3d(-14px, -8px, 0);
  }

  to {
    transform: translate3d(14px, 8px, 0);
  }
}

@keyframes frame-drift {
  from {
    transform: translate3d(-24px, -14px, 0);
  }

  to {
    transform: translate3d(24px, 14px, 0);
  }
}
</style>
