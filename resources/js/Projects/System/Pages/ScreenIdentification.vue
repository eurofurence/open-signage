<script setup>
import { useAppState } from '@/state.js';
import { computed } from 'vue';
import { synced } from '@/synced.js';

const state = useAppState();

function formatSince(ts) {
  const diff = state.currentTime - ts;
  if (diff < 0) return 'Now';

  if (diff >= 3600000) {
    const hours = Math.floor(diff / 3600000);
    return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  } else if (diff >= 60000) {
    const minutes = Math.floor(diff / 60000);
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  } else if (diff >= 1000) {
    const seconds = Math.floor(diff / 1000);
    return `${seconds} second${seconds === 1 ? '' : 's'} ago`;
  }

  return `${diff} millisecond${diff === 1 ? '' : 's'} ago`;
}

const systemInfoHeader = computed(() => ({
  'Screen ID': state.screen.id,
  'Screen Name': state.screen.name ?? '',
}));

const systemInfo = computed(() => ({
  'Current Time': state.currentTime,
  'Primary Room': state.screen.room?.name ?? '',
  'Conf Version': state.version,
  Connected: state.isConnected,
  'Last Error': state.connectionError,
  'Last Ping': formatSince(state.lastPing),
  '# Announcements': state.announcements.length,
  '# Artworks': state.artworks.length,
  '# Rooms': state.rooms.length,
}));

const code = [
  [false, false, false, false],
  [true, false, false, false],
  [true, true, false, false],
  [false, true, false, false],
  [false, true, true, false],
  [false, true, true, true],
  [false, true, false, true],
  [true, true, false, true],
  [true, false, false, true],
  [true, false, true, true],
  [true, true, true, true],
  [true, true, true, false],
  [true, false, true, false],
  [false, false, true, false],
  [false, false, true, true],
  [false, false, false, true],
];

const synchroscope = synced(code, 1000);
</script>

<template>
  <div class="h-full flex justify-between items-start m-10 gap-10">
    <table class="text-primary-100 text-5xl">
      <thead class="border border-transparent border-b-[32pt] text-6xl">
        <tr v-for="(value, key) in systemInfoHeader">
          <td class="font-semibold pr-6">{{ key }}</td>
          <td>{{ value }}</td>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(value, key) in systemInfo">
          <td class="font-semibold pr-6">{{ key }}</td>
          <td v-if="typeof value === 'boolean'" :class="{ 'text-red-500': !value }">
            {{ value ? 'Yes' : 'No' }}
          </td>
          <td v-else-if="typeof value === 'function'">{{ value() }}</td>
          <td v-else-if="value">{{ value }}</td>
          <td v-else class="italic text-primary-300">Empty</td>
        </tr>
      </tbody>
    </table>
    <div class="flex flex-col h-full">
      <div class="flex-1"></div>
      <div class="inline-grid grid-cols-2">
        <span v-for="row in synchroscope" class="size-36" :class="{ 'bg-primary-100': row }" />
      </div>
    </div>
  </div>
</template>
