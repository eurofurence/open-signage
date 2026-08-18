<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { DateTime } from 'luxon';
import IconRouter from '@/Projects/System/Components/IconRouter.vue';
import chunkArray from '@/chunkArray.js';
import truncate from '@/truncate.js';
import { useScreenOrientation } from '@/screenOrientation.js';

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
    type: Object,
    default: null,
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

const { isPortrait } = useScreenOrientation(() => props.appScreen);

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

function roomNameParts(room) {
  return room.name.split(/\s[–-]\s/);
}

function roomName(room) {
  return roomNameParts(room)[0].trim();
}

function roomVenue(room) {
  if (room.venue_name) {
    return room.venue_name;
  }

  const parts = roomNameParts(room);

  return parts.length > 1 ? parts.slice(1).join(' – ').trim() : null;
}

const roomsPerPage = computed(() => (isPortrait.value ? 4 : 3));

const signPostPages = computed(() => {
  return chunkArray(props.rooms, roomsPerPage.value);
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
  }, props.pageSwitchingTimer || 15000);
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
        v-for="item in currentSignPostPage"
        :key="item.id"
        class="flex flex-col relative z-30 text-white magic-text theme-font w-[100vw]"
      >
        <div class="flex flex-row flex-nowrap items-center" :class="isPortrait ? 'mx-8 my-10' : 'mx-16 my-10'">
          <div v-if="item.pivot.icon" :class="isPortrait ? 'min-w-[11vw] mr-4' : 'min-w-[150px] mr-6'">
            <IconRouter
              path="EF30"
              class="fill-white svgIconGlow"
              :class="isPortrait ? 'w-[11vw]' : 'w-[150px]'"
              :icon="item.pivot.icon"
              :mirror="item.pivot.mirror"
              :rotation="item.pivot.rotation"
            ></IconRouter>
          </div>
          <div class="flex flex-1 flex-col min-w-0">
            <div
              class="text-left"
              :class="isPortrait ? 'text-[4.6vw] leading-tight break-words' : 'text-[5.5vw] leading-none truncate'"
            >
              {{ roomName(item) }}
            </div>

            <div
              v-if="roomVenue(item)"
              class="text-left leading-none text-[2vw]"
              :class="isPortrait ? 'break-words' : 'truncate'"
            >
              ( {{ roomVenue(item) }} )
            </div>

            <div
              v-if="nextEventByRoom[item.id]"
              class="flex leading-none"
              :class="isPortrait ? 'text-[2.8vw]' : 'text-[4vw]'"
            >
              <div class="mr-3 flex-shrink-0 whitespace-nowrap">
                <div
                  v-if="DateTime.fromISO(nextEventByRoom[item.id].starts_at) < DateTime.local()"
                  class="text-left leading-none"
                >
                  Now:
                </div>
                <div v-else class="text-left leading-none items-center">Next:</div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="leading-none truncate" v-if="nextEventByRoom[item.id].title.split(' – ')[0]">
                  {{ truncate(nextEventByRoom[item.id].title.split(' – ')[0], 30) }}
                </div>
                <div
                  :class="[
                    { 'text-green-300': containsOnly(nextEventByRoom[item.id].title) },
                    isPortrait ? 'text-[2vw]' : 'text-[2.5vw]',
                  ]"
                  class="leading-none truncate"
                  v-if="nextEventByRoom[item.id].title.split(' – ')[1]"
                >
                  {{ truncate(nextEventByRoom[item.id].title.split(' – ')[1], 45) }}
                </div>
              </div>
            </div>
          </div>

          <div
            class="flex flex-0 flex-row text-white"
            :class="isPortrait ? 'w-[13vw] flex-shrink-0 space-x-3' : 'w-[12vw] flex-shrink-0 space-x-6'"
          >
            <IconRouter
              v-if="item.pivot.flags ? item.pivot.flags.includes('wheelchair') : false"
              path="EF30"
              class="flex fill-white svgIconGlow"
              :class="isPortrait ? 'w-[6vw]' : 'w-[4vw]'"
              icon="Wheelchair"
            ></IconRouter>

            <IconRouter
              v-if="item.pivot.flags ? item.pivot.flags.includes('first_aid') : false"
              path="EF30"
              class="flex svgIconGlow"
              :class="isPortrait ? 'w-[6vw]' : 'w-[4vw]'"
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
