<script setup>
import { computed } from 'vue';

const props = defineProps({
  appScreen: {
    type: Object,
    default: null,
  },
});

const portraitOrientations = ['left', 'right'];

const orientationClass = computed(() => {
  const orientation = props.appScreen?.orientation;

  if (!orientation || orientation === 'normal') {
    return null;
  }

  return portraitOrientations.includes(orientation) ? 'stage-portrait' : 'stage-landscape';
});
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

    <div class="stage-content h-screen overflow-auto bg-transparent flex flex-col flex-grow">
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
.stage {
  --frame-width: 108vw;
  --frame-min-width: 178vh;
  --frame-rotate: none;
}

@media (orientation: portrait) {
  .stage {
    --frame-width: 108vh;
    --frame-min-width: 178vw;
    --frame-rotate: rotate(90deg);
  }
}

.stage.stage-landscape {
  --frame-width: 108vw;
  --frame-min-width: 178vh;
  --frame-rotate: none;
}

.stage.stage-portrait {
  --frame-width: 108vh;
  --frame-min-width: 178vw;
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
  z-index: 0;
}

.stage-frame {
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stage-content {
  position: relative;
  z-index: 2;
}

.stage-back-image {
  position: absolute;
  left: -32px;
  top: -20px;
  width: calc(100% + 64px);
  height: calc(100% + 40px);
  object-fit: cover;
  will-change: transform;
  animation: back-drift 41s ease-in-out infinite alternate;
}

.stage-frame-drift {
  flex: none;
  will-change: transform;
  animation: frame-drift 29s ease-in-out infinite alternate;
}

.stage-frame-image {
  display: block;
  width: var(--frame-width);
  min-width: var(--frame-min-width);
  transform: var(--frame-rotate);
}

@keyframes back-drift {
  from {
    transform: translate3d(-8px, -5px, 0);
  }

  to {
    transform: translate3d(8px, 5px, 0);
  }
}

@keyframes frame-drift {
  from {
    transform: translate3d(-16px, -8px, 0);
  }

  to {
    transform: translate3d(16px, 8px, 0);
  }
}
</style>
