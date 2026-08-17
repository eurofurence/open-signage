<script setup>
import { computed } from 'vue';

const props = defineProps({
  video: {
    type: String,
    required: true,
  },
});

const mimeType = computed(() => {
  const extension = (props.video ?? '').split('?')[0].split('.').pop().toLowerCase();

  return extension === 'webm' ? 'video/webm' : 'video/mp4';
});
</script>

<template>
  <div class="fullscreen-image h-screen w-screen">
    <video autoplay muted loop class="object-cover h-full w-full">
      <source :src="video" :type="mimeType" />
    </video>
  </div>
</template>
