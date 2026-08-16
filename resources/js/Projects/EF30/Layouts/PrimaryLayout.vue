<script setup>
const props = defineProps(['page']);

defineOptions({
  inheritAttrs: false,
});
</script>

<template>
  <div>
    <!-- background -->
    <div style="position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; z-index: -1">
      <img src="../Assets/images/background.png" class="background_foreground" style="z-index: -1" />
    </div>

    <div class="h-screen overflow-auto bg-transparent flex flex-col flex-grow">
      <!-- Main Content -->
      <Transition mode="out-in">
        <slot></slot>
      </Transition>
    </div>

    <!-- foreground, drawn over the page content -->
    <img src="../Assets/images/foreground_with_logo.png" class="background_foreground" style="z-index: 9999" />
  </div>
</template>
