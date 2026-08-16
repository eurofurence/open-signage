<script setup>
import { ref, computed, onMounted, onUnmounted, toRaw } from 'vue';
import None from '@/Projects/System/Layouts/None.vue';
import Error from '@/Projects/System/Pages/Error.vue';
import { useAppState } from '@/state.js';
import { synced } from '@/synced.js';

const props = defineProps({
  initialPages: {
    type: Array,
    default: () => [],
  },
  initialSchedule: {
    type: Array,
    required: true,
  },
  initialAnnouncements: {
    type: Array,
    required: true,
    default: () => [],
  },
  initialScreen: {
    type: Object,
    required: true,
  },
  initialArtworks: {
    type: Array,
    required: true,
  },
});

const state = useAppState();
const appScreen = ref(props.initialScreen);

const ping = () => {
  window.axios
    .post(
      route('screens.ping', {
        screen: props.initialScreen.id,
        shared_secret: new URLSearchParams(window.location.search).get('shared_secret'),
        version: state.version,
      }),
    )
    .then(() => {
      state.lastPing = Date.now();
      state.isConnected = true;
    })
    .catch(() => {
      state.isConnected = false;
      state.connectionError = 'Ping failed';
    });
};

synced(0, 5000, () => {
  state.currentTime = Date.now();
});

onMounted(() => {
  ping();
  const pingInterval = setInterval(ping, 60000);
  onUnmounted(() => clearInterval(pingInterval));
});

window.Echo.channel('ScreenAll')
  .listen('.announcement.create', announcement => {
    state.announcements.push(announcement);
    state.version++;
  })
  .listen('.announcement.update', announcement => {
    state.announcements = state.announcements.map(old => (old.id === announcement.id ? announcement : old));
    state.version++;
  })
  .listen('.announcement.delete', announcement => {
    state.announcements = state.announcements.filter(old => old.id !== announcement.id);
    state.version++;
  })
  .listen('.artwork.create', artwork => {
    state.artworks.push(artwork);
    state.version++;
  })
  .listen('.artwork.update', artwork => {
    state.artworks = state.artworks.map(old => (old.id === artwork.id ? artwork : old));
    state.version++;
  })
  .listen('.artwork.delete', artwork => {
    state.artworks = state.artworks.filter(old => old.id !== artwork.id);
    state.version++;
  })
  .listen('.schedule.create', scheduleEntry => {
    state.schedule.push(scheduleEntry);

    state.schedule.sort((a, b) => {
      const timeCompare = a.starts_at.localeCompare(b.starts_at);

      if (timeCompare !== 0) {
        return timeCompare;
      }

      return a.title.localeCompare(b.title);
    });

    state.version++;
  })
  .listen('.schedule.update', scheduleEntry => {
    state.schedule = state.schedule.map(old => (old.id === scheduleEntry.id ? scheduleEntry : old));

    state.schedule.sort((a, b) => {
      const timeCompare = a.starts_at.localeCompare(b.starts_at);

      if (timeCompare !== 0) {
        return timeCompare;
      }

      return a.title.localeCompare(b.title);
    });

    state.version++;
  })
  .listen('.schedule.delete', scheduleEntry => {
    state.schedule = state.schedule.filter(old => old.id !== scheduleEntry.id);
    state.version++;
  });

window.Echo.channel(`Screen.${props.initialScreen.id}`)
  .listen('.screen.refresh', () => {
    window.location.reload();
  })
  .listen('.playlist.switch', playlist => {
    window.axios
      .get(
        route('api.playlist.get', {
          playlistId: playlist.id,
        }),
      )
      .then(response => {
        state.playlist = response.data;
        state.version++;
        updatePlaylistItem();
      })
      .catch(() => window.location.reload());
  })
  .listen('.playlistItem.create', playlistItem => {
    state.playlist.playlist_items.push(playlistItem);
    state.version++;
  })
  .listen('.playlistItem.update', playlistItem => {
    state.playlist.playlist_items = state.playlist.playlist_items.map(item =>
      item.id === playlistItem.id ? playlistItem : item,
    );
    // const old = state.version;
    state.version++;
    // console.log(".playlistItem.update", old, state.version);
  })
  .listen('.playlistItem.delete', playlistItem => {
    state.playlist.playlist_items = state.playlist.playlist_items.filter(item => item.id !== playlistItem.id);
    state.version++;
  });

window.Echo.connector.pusher.connection.bind('connecting', () => {
  state.isConnected = false;
  state.connectionError = 'Socket reconnecting';
});

window.Echo.connector.pusher.connection.bind('connected', () => {
  state.isConnected = true;
});

window.Echo.connector.pusher.connection.bind('unavailable', () => {
  state.isConnected = false;
  state.connectionError = 'Socket failed';
});

const activePlaylistItems = computed(() => {
  return state.playlist.playlist_items
    .map((item, index) => ({ index, ...item }))
    .filter(item => item.is_active)
    .filter(item => !item.starts_at || state.currentTime >= new Date(item.starts_at).getTime())
    .filter(item => !item.ends_at || state.currentTime <= new Date(item.ends_at).getTime())
    .map(toRaw);
});

const rooms = computed(() => {
  return appScreen.value.rooms.filter(room => {
    return (
      // If room.pivot.starts_at exists, check if the current time is greater than or equal to it
      (!room.pivot.starts_at || state.currentTime >= new Date(room.pivot.starts_at).getTime()) &&
      // If room.pivot.ends_at exists, check if the current time is less than or equal to it
      (!room.pivot.ends_at || state.currentTime <= new Date(room.pivot.ends_at).getTime())
    );
  });
});

const cycleLength = computed(() =>
  activePlaylistItems.value.reduce((acc, item) => acc + parseInt(item.duration, 10) * 1000, 0),
);

let updatePlaylistItemTimeout = null;
const currentPlaylistItem = ref(null);

function updatePlaylistItem() {
  const now = Date.now();
  let period = Math.round(now) % cycleLength.value;
  let index = -1;

  while (period >= 0) {
    index++;
    period -= parseInt(activePlaylistItems.value[index].duration, 10) * 1000;
  }

  if (index < 0) index = 0;
  currentPlaylistItem.value = activePlaylistItems.value[index];
  const rest = now - Date.now() + parseInt(activePlaylistItems.value[index].duration, 10) * 1000;
  updatePlaylistItemTimeout = setTimeout(updatePlaylistItem, rest < 1000 ? 1000 : rest);
}

onMounted(() => {
  updatePlaylistItem();
  onUnmounted(() => clearTimeout(updatePlaylistItemTimeout));
});

const projectComponents = {
  ...import.meta.glob('./Projects/*/Layouts/*.vue', { eager: true }),
  ...import.meta.glob('./Projects/*/Pages/*.vue', { eager: true }),
};

const layoutComponents = computed(() =>
  state.playlist.playlist_items.reduce((acc, curr) => {
    const id = curr.layout_id;
    const path = `./Projects/${curr.layout.project.path}/Layouts/${curr.layout.component}.vue`;
    if (acc[id]?.path === path) return acc;

    return {
      ...acc,
      [id]: {
        id,
        path,
        component: projectComponents[path]?.default ?? None,
      },
    };
  }, layoutComponents.value ?? {}),
);

const pageComponents = computed(() =>
  state.playlist.playlist_items.reduce((acc, curr) => {
    const id = curr.page_id;
    const path = `./Projects/${curr.page.project.path}/Pages/${curr.page.component}.vue`;
    if (acc[id]?.path === path) return acc;

    return {
      ...acc,
      [id]: {
        id,
        path,
        component: projectComponents[path]?.default ?? Error,
      },
    };
  }, pageComponents.value ?? {}),
);

const activeLayout = computed(
  () => layoutComponents.value[currentPlaylistItem.value?.layout_id] ?? { component: None },
);
const activePageComponent = computed(
  () => pageComponents.value[currentPlaylistItem.value?.page_id] ?? { component: Error },
);
</script>

<template>
  <div
    v-if="state.isConnected === false"
    class="bg-black z-50 absolute top-0 left p-1 px-4 font-bold text-white rounded-br"
  >
    Reconnecting... ({{ state.connectionError }})
  </div>
  <Transition>
    <component
      :connected="state.isConnected"
      v-show="activePageComponent.component"
      :appScreen="appScreen"
      :page="currentPlaylistItem ?? { component: Error }"
      :is="activeLayout.component"
      :key="activeLayout.id"
    >
      <component
        :key="currentPlaylistItem?.id"
        :is="activePageComponent.component"
        v-bind="currentPlaylistItem?.content"
        :appScreen="appScreen"
        :rooms="rooms"
        :schedule="state.schedule"
        :artworks="state.artworks"
      />
    </component>
  </Transition>
</template>

<style>
@reference "tailwindcss";

body {
  overflow: hidden;
  @apply bg-stone-800;
}

/* Used by Vue3 for transitions */

/*noinspection CssUnusedSymbol*/
.v-enter-active {
  transition: opacity 1s ease-in;
}

/*noinspection CssUnusedSymbol*/
.v-leave-active {
  transition: opacity 0.5s ease-out;
}

/*noinspection CssUnusedSymbol*/
.v-enter-from,
.v-leave-to {
  opacity: 0;
}
</style>
