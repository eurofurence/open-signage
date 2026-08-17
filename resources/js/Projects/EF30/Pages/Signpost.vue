<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { DateTime } from 'luxon';
import IconRouter from '@/Projects/System/Components/IconRouter.vue';
import chunkArray from '@/chunkArray.js';
import truncate from '@/truncate.js';

const props = defineProps({
  title: {
    type: String,
    default: 'Event Rooms',
  },
  schedule: {
    type: Array,
    default: [],
  },
  appScreen: {
    type: Array,
    default: [],
  },
  rooms: {
    type: Array,
    default: [],
  },
  pageSwitchingTimer: {
    type: Number,
    default: 15000,
  },
});

const currentTime = ref(DateTime.now());
const currentPageIndex = ref(0);

const nextEventByRoom = computed(() => {
  const byRoom = {};

  for (const event of props.schedule) {
    if (byRoom[event.room_id]) continue;

    if (
      currentTime.value <=
      DateTime.fromISO(event.ends_at).plus({
        minutes: event.delay,
      })
    ) {
      byRoom[event.room_id] = event;
    }
  }

  return byRoom;
});

function containsOnly(title) {
  return title.includes('Only') || title.includes('only') || title.includes('ONLY');
}

const signPostPages = computed(() => {
  return chunkArray(props.rooms, 3);
});

const currentSignPostPage = computed(() => {
  return signPostPages.value[currentPageIndex.value] ?? signPostPages.value[0];
});

onMounted(() => {
  const interval = setInterval(() => {
    currentTime.value = DateTime.now();
  }, 5000);
  const pageSwitcher = setInterval(() => {
    const pageCount = signPostPages.value.length;
    currentPageIndex.value = pageCount === 0 ? 0 : (currentPageIndex.value + 1) % pageCount;
  }, props.pageSwitchingTimer);
  onUnmounted(() => {
    clearInterval(interval);
    clearInterval(pageSwitcher);
  });
});
</script>

<template>
  <Transition mode="out-in">
    <div :key="currentPageIndex" class="h-screen overflow-hidden flex flex-col justify-between w-screen">
      <div
        v-for="(item, _index) in currentSignPostPage"
        class="flex flex-col relative z-30 text-white magic-text theme-font w-[100vw]"
      >
        <div class="mx-16 my-16 flex flex-row flex-nowrap items-center">
          <div v-if="item.pivot.icon" class="min-w-[200px] mr-6">
            <IconRouter
              path="EF30"
              class="fill-white w-[200px] svgIconGlow"
              :icon="item.pivot.icon"
              :mirror="item.pivot.mirror"
              :rotation="item.pivot.rotation"
            ></IconRouter>
          </div>
          <div class="flex flex-1 flex-col w-[70vw]">
            <div class="flex flex-row items-baseline">
              <div class="flex text-[7vw] text-left items-center leading-none">
                {{ item.name }}
              </div>

              <div
                v-if="item.name !== item.venue_name && item.venue_name"
                class="flex text-[2.5vw] text-left items-center leading-none ml-8"
              >
                ( {{ item.venue_name }} )
              </div>
            </div>

            <div v-if="nextEventByRoom[item.id]" class="flex text-[5vw] leading-none">
              <div class="mr-3">
                <div
                  v-if="DateTime.fromISO(nextEventByRoom[item.id].starts_at) < DateTime.local()"
                  class="text-left leading-none"
                >
                  Now:
                </div>
                <div v-else class="text-left leading-none items-center">Next:</div>
              </div>
              <div>
                <div>
                  <div class="leading-none" v-if="nextEventByRoom[item.id].title.split(' – ')[0]">
                    {{ truncate(nextEventByRoom[item.id].title.split(' – ')[0], 30) }}
                  </div>
                  <div
                    :class="{
                      'text-green-300': containsOnly(nextEventByRoom[item.id].title),
                    }"
                    class="text-[3vw] leading-none"
                    v-if="nextEventByRoom[item.id].title.split(' – ')[1]"
                  >
                    {{ truncate(nextEventByRoom[item.id].title.split(' – ')[1], 45) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-0 flex-row text-white w-[20vw] space-x-8">
            <IconRouter
              v-if="item.pivot.flags ? item.pivot.flags.includes('wheelchair') : false"
              path="EF30"
              class="flex fill-white w-[5vw] svgIconGlow"
              icon="Wheelchair"
            ></IconRouter>

            <IconRouter
              v-if="item.pivot.flags ? item.pivot.flags.includes('first_aid') : false"
              path="EF30"
              class="flex w-[5vw] svgIconGlow"
              icon="FirstAid"
            ></IconRouter>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* we will explain what these classes do next! */
.v-enter-active,
.v-leave-active {
  transition: opacity 1s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>

<style>
body {
  overflow: hidden;
  @apply bg-primary;
}

.magic-text {
  position: relative;
  user-select: none;
  /*
    font-family: 'primaryThemeFont', sans-serif;
    white-space: pre;
    */
}

.magic-text span {
  position: relative;
  white-space: pre;
  display: inline-block;
  cursor: pointer;
  opacity: 1;
}

.w-digit-15 {
  width: 1.5ch;
}

.w-digit-15 span {
  width: 1ch;
}

.w-digit-2 {
  width: 2ch;
}

.w-digit-2 span {
  width: 1ch;
}

.w-digit-45 {
  width: 4.5ch;
}

.w-digit-45 span {
  width: 1ch;
}

.w-digit-5 {
  width: 5ch;
}

.w-digit-5 span {
  width: 1ch;
}
</style>
