<script setup>
defineProps(['page', 'appScreen']);

defineOptions({
  inheritAttrs: false,
});

import Stage from '@/Projects/EF30/Components/Stage.vue';
</script>

<template>
  <div>
    <Stage :appScreen="appScreen">
      <Transition mode="out-in">
        <slot></slot>
      </Transition>
    </Stage>
  </div>
</template>
