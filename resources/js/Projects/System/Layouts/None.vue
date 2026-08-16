<script setup>
defineProps(['page']);
defineOptions({
  inheritAttrs: false,
});
</script>

<template>
  <div class="h-screen overflow-hidden bg-[#005953] flex flex-col flex-grow">
    <Transition mode="out-in">
      <slot></slot>
    </Transition>
  </div>
</template>
