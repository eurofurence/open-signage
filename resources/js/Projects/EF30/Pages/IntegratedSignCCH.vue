<script setup>
import { computed, onMounted, onUnmounted, ref, toRaw } from 'vue';
import { DateTime } from 'luxon';
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
  rooms: {
    type: Array,
    default: [],
  },
  screen: {
    type: Object,
    default: [],
  },
  pageSwitchingTimer: {
    type: Number,
    default: 15000,
  },
  isThemeFont: {
    type: Boolean,
    default: true,
  },
});

const currentTime = ref(DateTime.now());
const currentPageIndex = ref(0);

function getNextEventForRoom(room, timeObject) {
  const event = props.schedule.find(entry => {
    return (
      entry.room_id === room.id &&
      timeObject <=
        DateTime.fromISO(entry.ends_at).plus({
          minutes: entry.delay,
        }) &&
      !(entry.title ?? '').toLowerCase().includes('seating')
    );
  });

  if (!event) return undefined;

  const eventCopy = { ...toRaw(event) };
  eventCopy.title = eventCopy.title
    ? eventCopy.title
        .replace("Dealers' Den & Art Show", '')
        .replace("Dealers' Den", '')
        .replace('Art Show', '')
        .replace('Fursuit Badge', '')
        .replace('Registration', '')
        .replace('Constore', '')
        .replace('Fursuit Badge', '')
        .replace('Fursuit Lounge', '')
        .replace("Artists' Lounge", '')
        .replace('Locker Service', '')
        .replace('The Electric Lounge Sessions', '')
        .replace(/^[\W]+/g, '')
    : eventCopy.title;
  eventCopy.title = truncate((eventCopy.title ?? '').split(' – ')[0], 30, true);
  return eventCopy;
}

const populatedRooms = computed(() => {
  return props.rooms.map(room => {
    return { ...toRaw(room), nextEvent: getNextEventForRoom(room, currentTime.value) };
  });
});

function chunkArray(array, chunkSize) {
  const result = [];
  for (let i = 0; i < array.length; i += chunkSize) {
    result.push(array.slice(i, i + chunkSize));
  }
  return result;
}

const roomPages = computed(() => {
  return chunkArray(populatedRooms.value, 3);
});

const currentSlide = computed(() => {
  return roomPages.value[currentPageIndex.value] ?? roomPages.value[0];
});

onMounted(() => {
  const interval = setInterval(() => {
    currentTime.value = DateTime.now();
  }, 5000);
  const pageSwitcher = setInterval(() => {
    const pageCount = roomPages.value.length;
    currentPageIndex.value = pageCount === 0 ? 0 : (currentPageIndex.value + 1) % pageCount;
  }, props.pageSwitchingTimer);
  onUnmounted(() => {
    clearInterval(interval);
    clearInterval(pageSwitcher);
  });
});

function onEnter(node, done) {
  // call the done callback to indicate transition end
  // optional if used in combination with CSS

  done();
}

function onLeave(node, done) {
  // call the done callback to indicate transition end
  // optional if used in combination with CSS

  done();
}
</script>

<template>
  <div class="flex absolute z-30 h-[100vh] w-[100vw] justify-items-center overflow-hidden">
    <Transition appear @enter="onEnter" @leave="onLeave" :css="false">
      <div
        :key="currentPageIndex"
        class="flex flex-col absolute z-30 h-[100vh] w-[100vw] p-16 space-y-8 justify-items-center overflow-hidden"
      >
        <!--                <TransitionGroup name="list">-->
        <div
          v-for="item in currentSlide"
          :key="item.id"
          class="flex flex-col text-white magic-text anim"
          :class="[isThemeFont ? 'theme-font' : 'theme-font-secondary']"
        >
          <div class="flex text-[9vw] text-justify">
            {{ item.name }}
          </div>

          <div class="flex flex-row text-[4vw] items-baseline">
            <div v-if="item.nextEvent" class="flex flex-row items-baseline">
              <div
                v-if="item.nextEvent && DateTime.fromISO(item.nextEvent.starts_at) < DateTime.local()"
                class="flex text-left text-green-300"
              >
                OPEN
              </div>
              <div v-else class="flex text-left text-red-300">CLOSED</div>
            </div>

            <div
              v-if="
                item.nextEvent && DateTime.fromISO(item.nextEvent.starts_at) < DateTime.local() && item.nextEvent.title
              "
              class="flex text-left pl-16"
            >
              {{ item.nextEvent.title }}
            </div>
            <div v-else-if="item.nextEvent && item.nextEvent.title" class="flex text-left pl-16">
              Next: {{ item.nextEvent.title }}
            </div>
          </div>
        </div>
        <!--                </TransitionGroup>-->
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 5s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

.list-enter-active,
.list-leave-active {
  transition: all 5s ease;
}

.list-enter-from,
.list-leave-to {
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
