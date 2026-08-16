<script setup>
defineProps(['page', 'appScreen']);
defineOptions({
  inheritAttrs: false,
});

import Time from '@/Projects/EF30/Components/CurrentTime.vue';
import Stage from '@/Projects/EF30/Components/Stage.vue';
</script>

<template>
  <div>
    <Time :hourglass="true" />

    <Stage :appScreen="appScreen">
      <Transition mode="out-in">
        <slot></slot>
      </Transition>
    </Stage>
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
