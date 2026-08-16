<script setup>
defineProps(['page']);

defineOptions({
  inheritAttrs: false,
});
</script>

<template>
  <div>
    <!-- background -->
    <div style="position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; z-index: -1">
      <img
        src="../Assets/images/background.png"
        class="background_foreground"
        style="position: absolute; left: 0; top: 0; width: 100vw; height: 100vh; z-index: -1"
      />
    </div>

    <!--    <Time hourglass="true"/>-->

    <div class="h-screen overflow-auto bg-transparent flex flex-col flex-grow">
      <!-- Main Content -->
      <Transition mode="out-in">
        <slot></slot>
      </Transition>
    </div>
  </div>
</template>
