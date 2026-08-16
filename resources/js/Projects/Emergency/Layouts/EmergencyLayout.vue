<script setup>
import { computed, onMounted, reactive, useAttrs } from 'vue';

const props = defineProps(['page']);
defineOptions({
  inheritAttrs: false,
});

const attrs = reactive(useAttrs());

onMounted(() => {
  const audio = new Audio('/audio/attention-tone.mp3');
  audio.play();
});

const usableAttributes = computed(() => {
  return {
    ...attrs,
    ...props.page,
    ...props.page.props,
  };
});
</script>

<template>
  <div class="h-screen overflow-hidden bg-red-600 flex flex-col flex-grow">
    <component class="pt-8 px-8" :is="page.resolvedComponent" v-bind="usableAttributes"></component>
  </div>
</template>
