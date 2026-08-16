<script setup>
import { computed } from 'vue';

const icons = import.meta.glob('../../*/Components/Icons/*.svg', { eager: true });

defineOptions({
  inheritAttrs: false,
});

const props = defineProps({
  icon: {
    type: String,
    default: 'arrow',
  },
  rotation: {
    type: Number,
    default: 0,
  },
  mirror: {
    type: Boolean,
    default: false,
  },
  path: {
    type: String,
    required: true,
    default: 'System',
  },
});
const icon = computed(() => {
  return resolveComponent(props.icon);
});

function resolveComponent(name) {
  return icons[`../../${props.path}/Components/Icons/${name}.svg`]?.default;
}
</script>

<template>
  <component
    v-if="icon"
    v-bind="$attrs"
    :is="icon"
    :style="`transform: ${mirror ? 'scaleX(-1)' : ''} rotate(${rotation}deg)`"
  />
</template>
