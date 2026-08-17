<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { DateTime } from 'luxon';
import HourTime from '@/Components/HourTime.vue';
import _ from 'lodash';
import truncate from '@/truncate.js';
import { useScreenOrientation } from '@/screenOrientation.js';

const props = defineProps({
  appScreen: {
    type: Object,
    required: true,
  },
  schedule: {
    type: Array,
    required: true,
  },
  showRoomName: {
    type: Boolean,
    default: true,
  },
});

const currentTime = ref(DateTime.now());

const { isPortrait } = useScreenOrientation(() => props.appScreen);

onMounted(() => {
  const interval = setInterval(() => {
    currentTime.value = DateTime.now();
  }, 5000);
  onUnmounted(() => {
    clearInterval(interval);
  });
});

function getDayDescription(startsAt) {
  // Parse the starts_at date string into a Luxon DateTime object
  const eventDate = DateTime.fromISO(startsAt).startOf('day');

  // Get the current date and set it to the start of the day (00:00:00)
  const currentDate = DateTime.local().startOf('day');

  // Calculate the difference in days between the event date and the current date
  const diffInDays = eventDate.diff(currentDate, 'days').days;

  // Check the difference and return the appropriate string
  if (diffInDays === 0) {
    return null; // Return nothing if the date is today
  } else if (diffInDays === 1) {
    return 'Tomorrow'; // Return "Tomorrow" if the date is tomorrow
  }
  return eventDate.toFormat('EEEE'); // Return the weekday name for any other date
}

const nextEvent = computed(() => {
  return _.cloneDeep(props.schedule)
    .filter(event => {
      return event.room_id === props.appScreen.room_id;
    })
    .filter(event => {
      return currentTime.value <= DateTime.fromISO(event.ends_at).plus({ minutes: event.delay });
    })[0];
});
</script>

<template>
  <div class="text-white z-50 overflow-hidden h-full" v-if="nextEvent">
    <div
      v-if="nextEvent.title"
      class="flex flex-col items-center justify-around p-4 overflow-hidden h-[75%] z-50 theme-font leading-none mt-8"
    >
      <div
        v-if="nextEvent.room.name !== nextEvent.title && showRoomName"
        class="leading-none font-bold text-center neonTubeColor heading-font"
        :class="isPortrait ? 'text-[6vh]' : 'text-[10vw]'"
      >
        {{ nextEvent.room.name }}
      </div>
      <div class="leading-[1.2] font-bold text-center" :class="isPortrait ? 'text-[4vh]' : 'text-[6vw]'">
        {{ truncate(nextEvent.title, 90) }}
      </div>
      <div class="mb-2 whitespace-nowrap text-center leading-none" :class="isPortrait ? 'text-[5vh]' : 'text-[9vw]'">
        <div
          v-if="
            getDayDescription(
              DateTime.fromISO(nextEvent.starts_at).plus({
                minutes: nextEvent.delay,
              }),
            )
          "
          class="leading-none"
          :class="isPortrait ? 'text-[4.5vh]' : 'text-[6vw]'"
        >
          {{
            getDayDescription(
              DateTime.fromISO(nextEvent.starts_at).plus({
                minutes: nextEvent.delay,
              }),
            )
          }}
        </div>
        <div class="leading-none" :class="isPortrait ? 'text-[4.5vh]' : 'text-[6vw]'">
          <HourTime :time="DateTime.fromISO(nextEvent.starts_at)" />
          -
          <HourTime :time="DateTime.fromISO(nextEvent.ends_at)" />
        </div>
        <div
          v-if="nextEvent.delay > 0"
          class="leading-none text-center text-wrap"
          :class="isPortrait ? 'text-[4.5vh]' : 'text-[6vw]'"
        >
          Delayed by
          <span class="text-red-300">{{ nextEvent.delay }}</span>
          minutes
        </div>
      </div>
    </div>
    <div v-else>Test</div>
  </div>
</template>
