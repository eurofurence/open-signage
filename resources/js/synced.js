import {computed, onMounted, onUnmounted, ref, toRaw} from "vue";

export const synced = (arrayOrLength, interval, transform, opts) => {
    let intervalHandle = null;
    const selected = ref(null);
    const length = computed(() => Array.isArray(arrayOrLength) ? arrayOrLength.length : arrayOrLength);

    const callback = () => {
        const index = Math.round(Date.now() / interval) % length.value;
        const value = Array.isArray(arrayOrLength) ? structuredClone(toRaw(arrayOrLength[index])) : index;
        selected.value = transform ? transform(value, index) : value;

        const rest = interval - (Date.now() % interval);
        intervalHandle = setTimeout(callback, rest === 0 ? interval : rest);
    };

    onMounted(() => {
        callback();
        onUnmounted(() => clearTimeout(intervalHandle));
    });

    return selected;
};
