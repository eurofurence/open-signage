<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { DateTime } from 'luxon';
import _ from 'lodash';
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
  pageSwitchingTimer: {
    type: [Number, String],
    default: 15000,
  },
  lookaheadHours: {
    type: [Number, String],
    default: 12,
  },
  isThemeFont: {
    type: Boolean,
    default: true,
  },
});

const currentTime = ref(DateTime.now());
const currentPageIndex = ref(0);

const lookaheadHours = computed(() => Number(props.lookaheadHours) || 12);
const pageSwitchingTimer = computed(() => Number(props.pageSwitchingTimer) || 15000);

const filteredEvents = computed(() => {
  return _.cloneDeep(props.schedule)
    .filter(event => {
      return (
        currentTime.value > DateTime.fromISO(event.starts_at).minus({ hours: lookaheadHours.value }) &&
        currentTime.value <= DateTime.fromISO(event.ends_at).plus({ minutes: event.delay }).plus({ minutes: 10 }) &&
        !event.title.toLowerCase().includes('seating')
      );
    })
    .map(event => {
      if (event.room.name.includes(event.title) || event.title.includes(event.room.name)) {
        if (event.room.venue_name && event.room.name !== event.room.venue_name) {
          event.room.name = event.room.venue_name;
        }
      }

      if (event.title.length > 30) {
        const parts = event.title.split(' – ');
        if (parts.length > 1) {
          if (event.room.name.includes(parts[0]) || parts[0].includes(event.room.name)) {
            parts.shift();
            event.title = parts
              .join(' – ')
              .replace(/^[\W]+/g, '')
              .replace(/[\W]+$/g, '');
          } else {
            parts.pop();
            event.title = parts
              .join(' – ')
              .replace(/^[\W]+/g, '')
              .replace(/[\W]+$/g, '');
          }
        }
      }

      event.title = truncate(event.title, 32, true);

      return event;
    });
});

const schedulePages = computed(() => {
  return chunkArray(filteredEvents.value, 3);
});

const currentSlide = computed(() => {
  return schedulePages.value[currentPageIndex.value] ?? [];
});

onMounted(() => {
  const interval = setInterval(() => {
    currentTime.value = DateTime.now();
  }, 5000);
  const pageSwitcher = setInterval(() => {
    const pageCount = schedulePages.value.length;
    currentPageIndex.value = pageCount === 0 ? 0 : (currentPageIndex.value + 1) % pageCount;
  }, pageSwitchingTimer.value);
  onUnmounted(() => {
    clearInterval(interval);
    clearInterval(pageSwitcher);
  });
});

const TITLE_MIN_FONT_SIZE = 32;
const titleMaxSizes = new WeakMap();

function fitTitle(el) {
  const text = el.firstElementChild;
  if (!text) return;

  let max = titleMaxSizes.get(el);
  if (!max) {
    max = parseFloat(window.getComputedStyle(el).fontSize) || TITLE_MIN_FONT_SIZE;
    titleMaxSizes.set(el, max);
  }

  const setSize = size => el.style.setProperty('font-size', `${size}px`, 'important');

  setSize(max);

  const available = el.clientWidth;
  if (!available) return;

  const natural = text.getBoundingClientRect().width;
  if (natural <= available) return;

  let size = Math.max(TITLE_MIN_FONT_SIZE, Math.floor((max * available) / natural));
  setSize(size);

  while (size > TITLE_MIN_FONT_SIZE && text.getBoundingClientRect().width > available) {
    size -= 1;
    setSize(size);
  }
}

const vFitTitle = {
  mounted(el) {
    fitTitle(el);
    if (document.fonts) {
      document.fonts.ready.then(() => fitTitle(el));
    }
  },
  updated(el) {
    fitTitle(el);
  },
};

function onBeforeEnter() {
  //spanify(el);
}

function onEnter(node, done) {
  // call the done callback to indicate transition end
  // optional if used in combination with CSS
  setTimeout(() => {
    for (let i = 0; i < 3; i++) {
      if (node.children[i]) node.children[i].classList.add('animation-done');
    }
  }, 1000);

  setTimeout(() => {
    done();
  }, 4000);
}

function onBeforeLeave() {
  //spanify(el);
}

function onLeave(node, done) {
  // call the done callback to indicate transition end
  // optional if used in combination with CSS
  for (let i = 0; i < 3; i++) {
    if (node.children[i]) node.children[i].classList.remove('animation-done');
  }

  setTimeout(() => {
    done();
  }, 2000);
}
</script>

<template>
  <div class="flex absolute z-30 h-[100vh] w-[100vw] justify-items-center overflow-hidden">
    <Transition
      appear
      @before-enter="onBeforeEnter"
      @enter="onEnter"
      @before-leave="onBeforeLeave"
      @leave="onLeave"
      :css="false"
    >
      <div
        :key="currentPageIndex"
        class="animation flex absolute z-30 mt-28 h-[100vh] w-[100vw] p-12 px-38 space-y-8 justify-center overflow-hidden"
      >
        <!--                <TransitionGroup name="list">-->
        <div
          v-for="item in currentSlide"
          :key="item.id"
          class="flex flex-row space_text items-start items-baseline"
          :class="[isThemeFont ? 'theme-font' : 'theme-font-secondary']"
        >
          <div class="relative flex flex-col flex-auto schedule_entry justify-center pl-10 pr-2">
            <div v-fit-title class="relative flex flex-row flex-nowrap heading-font schedule_title">
              <span class="schedule_title_text">{{ item.title }}</span>
            </div>
            <div class="relative flex flex-row flex-nowrap text-justify text-5xl subtext">
              {{ item.room.name }}
            </div>
          </div>
          <div class="relative flex flex-col flex-auto text-center items-center schedule_entry_back pt-7">
            <div class="relative flex flex-row text-justify items-start">
              <div class="relative flex flex-row flex-shrink-0 flex-nowrap items-baseline text-justify text-6xl">
                <div class="flex flex-row flex-nowrap text-justify align-top">
                  {{ DateTime.fromISO(item.starts_at).toFormat('HH:mm') }}
                  –
                </div>
                <div class="flex flex-row flex-nowrap text-justify align-top">
                  {{ DateTime.fromISO(item.ends_at).toFormat('HH:mm') }}
                </div>
              </div>
            </div>
            <div class="relative flex flex-row flex-nowrap">
              <div v-if="item.delay" class="flex flex-row items-baseline text-4xl">
                <div v-if="item.delay < 15" class="flex text-left" style="color: #f8d032 !important">
                  Slightly Delayed
                </div>
                <div v-else class="flex text-left" style="color: #e73d3d !important">Delayed: {{ item.delay }}min</div>
              </div>
              <div v-else class="relative flex flex-row flex-nowrap text-justify align-top text-[2vw] subtext">
                On Time
              </div>
            </div>
          </div>
        </div>
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

.w-digit-120 {
  width: 12ch;
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
