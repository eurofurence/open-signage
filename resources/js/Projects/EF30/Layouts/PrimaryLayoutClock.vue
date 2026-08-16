<script setup>
const props = defineProps(['page']);
defineOptions({
  inheritAttrs: false,
});

import Time from '@/Projects/EF30/Components/CurrentTime.vue';
</script>

<template>
  <div>
    <!-- background -->
    <div style="position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; z-index: -1">
      <img src="../Assets/images/background.png" class="background_foreground" style="z-index: -1" />
    </div>

    <Time :hourglass="false" />

    <div class="h-screen overflow-auto bg-transparent flex flex-col flex-grow">
      <!-- Main Content -->
      <Transition mode="out-in">
        <slot></slot>
      </Transition>
    </div>

    <!-- foreground, drawn over the page content (logo-less: the clock owns the top corner) -->
    <img src="../Assets/images/foreground.png" class="background_foreground" style="z-index: 9999" />
  </div>
</template>

<style>
.bounce-enter-active {
  animation: bounce-in 0.5s;
}

.bounce-leave-active {
  animation: bounce-in 0.5s reverse;
}

@keyframes bounce-in {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.25);
  }
  100% {
    transform: scale(1);
  }
}
</style>
