import {reactive} from "vue";
import {usePage} from "@inertiajs/vue3";

let globalStateInitialized = false;

const globalState = reactive({
    currentTime: Date.now(),
    lastPing: -1,
    pages: [],
    schedule: [],
    announcements: [],
    screen: {},
    playlist: {},
    artworks: [],
    rooms: [],
    isConnected: true,
    connectionError: "",
    version: -1,
});

export const initAppState = () => {
    if (globalStateInitialized) return;
    const page = usePage();
    globalState.pages = page.props.initialPages;
    globalState.schedule = page.props.initialSchedule;
    globalState.announcements = page.props.initialAnnouncements;
    globalState.screen = page.props.initialScreen;
    globalState.playlist = page.props.initialPlaylist;
    globalState.artworks = page.props.initialArtworks;
    globalState.version = page.props.initialScreen.version;
    globalStateInitialized = true;
};

export const useAppState = () => {
    if (!globalStateInitialized) initAppState();
    return globalState;
}

window.appState = globalState;
