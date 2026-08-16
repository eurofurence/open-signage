<script setup>
const props = defineProps(['page']);
defineOptions({
  inheritAttrs: false,
});
</script>

<template>
  <div class="h-screen overflow-hidden bg-primary flex flex-col flex-grow">
    <slot></slot>
  </div>
</template>
