<script setup>
import { computed, onMounted, reactive, useAttrs } from 'vue';

const props = defineProps(['page']);
defineOptions({
  inheritAttrs: false,
});

const attrs = reactive(useAttrs());

const usableAttributes = computed(() => {
  return {
    ...attrs,
    page: props.page,
    ...props.page.props,
  };
});
</script>

<template>
  <div class="h-screen overflow-hidden bg-[#005953] flex flex-col flex-grow">
    <Transition mode="out-in">
      <slot></slot>
    </Transition>
  </div>
</template>
