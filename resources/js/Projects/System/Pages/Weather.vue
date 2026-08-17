<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useAppState } from '@/state.js';
import { useScreenOrientation } from '@/screenOrientation.js';

const props = defineProps({
  appScreen: {
    type: Object,
    default: null,
  },
  hoursAhead: {
    type: [Number, String],
    default: null,
  },
  pastHours: {
    type: [Number, String],
    default: null,
  },
  refreshSeconds: {
    type: [Number, String],
    default: null,
  },
  showChart: {
    type: [Boolean, String],
    default: null,
  },
  showBreakdown: {
    type: [Boolean, String],
    default: null,
  },
});

defineOptions({
  inheritAttrs: false,
});

const HOUR_MS = 3600000;
const MAX_GAP_MS = 48 * HOUR_MS;
const REQUEST_TIMEOUT_MS = 15000;
const STALE_CHECK_MS = 30000;
const DARK_INK = '#10161d';
const LIGHT_INK = '#ffffff';
const FALLBACK_COLOR = '#4f6374';

const payload = ref(null);
const loadError = ref('');
let loadedAt = 0;
let inFlight = null;

function loadWeather() {
  if (inFlight) return inFlight;

  inFlight = window.axios
    .get(route('api.weather.get'), { timeout: REQUEST_TIMEOUT_MS })
    .then(response => {
      payload.value = response.data;
      loadedAt = Date.now();
      loadError.value = '';
    })
    .catch(error => {
      loadError.value = error.message || 'Weather request failed';
    })
    .finally(() => {
      inFlight = null;
    });

  return inFlight;
}

function normaliseHex(color) {
  if (typeof color !== 'string') return null;

  const hex = color.trim().replace(/^#/, '');

  if (/^[0-9a-f]{3}$/i.test(hex)) return hex.replace(/./g, part => part + part);

  return /^[0-9a-f]{6}$/i.test(hex) ? hex : null;
}

function luminance(color) {
  const hex = normaliseHex(color);

  if (!hex) return NaN;

  const linear = [0, 2, 4]
    .map(index => parseInt(hex.slice(index, index + 2), 16) / 255)
    .map(channel => (channel <= 0.03928 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4));

  return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2];
}

function contrastText(color) {
  const value = luminance(color);

  if (!Number.isFinite(value)) return DARK_INK;

  return (value + 0.05) / 0.05 >= 1.05 / (value + 0.05) ? DARK_INK : LIGHT_INK;
}

function positiveNumber(value, fallback) {
  const parsed = parseInt(value, 10);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function enabled(value) {
  if (value === null || value === undefined) return true;
  if (typeof value === 'string') return !['false', '0', ''].includes(value.trim().toLowerCase());

  return Boolean(value);
}

function measure(value, digits, unit) {
  if (value === null || value === undefined) return '–';

  return `${Number(value).toFixed(digits)}${unit}`;
}

function capitalise(text) {
  if (!text) return '';

  return text.charAt(0).toUpperCase() + text.slice(1);
}

function hourStart(iso) {
  return new Date(iso).setMinutes(0, 0, 0);
}

function contiguous(series) {
  const filled = [];

  series.forEach(entry => {
    const at = hourStart(entry.time);
    const previous = filled.length ? hourStart(filled[filled.length - 1].time) : at;

    if (at - previous <= MAX_GAP_MS) {
      for (let slot = previous + HOUR_MS; slot < at; slot += HOUR_MS) {
        filled.push({ time: new Date(slot).toISOString(), score: null });
      }
    }

    filled.push(entry);
  });

  return filled;
}

function steps(count, portrait) {
  const crowding = portrait ? count * 2 : count;
  const room = { dense: crowding > 40, values: crowding <= 64 };

  if (crowding <= 14) return { ...room, icons: 1, labels: 1 };
  if (crowding <= 20) return { ...room, icons: 2, labels: 1 };
  if (crowding <= 28) return { ...room, icons: 2, labels: 2 };
  if (crowding <= 40) return { ...room, icons: 3, labels: 2 };
  if (crowding <= 64) return { ...room, icons: 4, labels: 4 };

  return { ...room, icons: 6, labels: 6 };
}

const state = useAppState();
const { isPortrait } = useScreenOrientation(() => props.appScreen);

const hoursAheadValue = computed(() => positiveNumber(props.hoursAhead, 24));
const pastHoursValue = computed(() => positiveNumber(props.pastHours, 4));
const refreshMs = computed(() => positiveNumber(props.refreshSeconds, 300) * 1000);
const chartVisible = computed(() => enabled(props.showChart));
const breakdownVisible = computed(() => enabled(props.showBreakdown));

const summary = computed(() => payload.value?.summary ?? null);
const timezone = computed(() => summary.value?.location?.timezone);
const fsi = computed(() => summary.value?.fsi ?? null);
const bands = computed(() => summary.value?.bands ?? []);

const hourFormatter = computed(
  () =>
    new Intl.DateTimeFormat('en-GB', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: timezone.value,
    }),
);

const clockFormatter = computed(
  () =>
    new Intl.DateTimeFormat('en-GB', {
      weekday: 'short',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
      timeZone: timezone.value,
    }),
);

function clockOf(value) {
  const date = new Date(value);

  return Number.isNaN(date.getTime()) ? '' : hourFormatter.value.format(date);
}

const clock = computed(() => clockFormatter.value.format(new Date(state.currentTime)));

const eventName = computed(() => summary.value?.event?.name ?? '');
const locationName = computed(() => summary.value?.location?.name ?? '');
const scoreColor = computed(() => fsi.value?.color ?? FALLBACK_COLOR);
const scoreInk = computed(() => contrastText(scoreColor.value));
const scoreText = computed(() => {
  const score = Number(fsi.value?.score);

  return Number.isFinite(score) ? score.toFixed(1) : '–';
});

const breakdown = computed(() => {
  const subscores = fsi.value?.subscores;

  if (!subscores) return [];

  return Object.keys(subscores)
    .filter(key => Number.isFinite(Number(subscores[key]?.score)))
    .map(key => {
      const score = Number(subscores[key].score);

      return {
        key,
        label: subscores[key].label ?? summary.value?.subscore_labels?.[key] ?? capitalise(key),
        text: score.toFixed(1),
        width: `${Math.max(2, score * 10)}%`,
      };
    });
});

const hours = computed(() => {
  const series = summary.value?.fsi_series;

  if (!series || !series.length) return [];

  const padded = contiguous(series);
  const currentHour = new Date(state.currentTime).setMinutes(0, 0, 0);
  const first = padded.findIndex(entry => hourStart(entry.time) >= currentHour);

  if (first === -1) return padded.slice(-hoursAheadValue.value);

  return padded.slice(Math.max(0, first - pastHoursValue.value), first + hoursAheadValue.value);
});

const chart = computed(() => steps(hours.value.length, isPortrait.value));

const columns = computed(() => {
  const { icons, labels, values } = chart.value;

  return hours.value.map((entry, index) => {
    const start = hourStart(entry.time);
    const missing = entry.score === null || entry.score === undefined;
    const label = clockOf(start);
    const hour = parseInt(label.slice(0, 2), 10);
    const color = entry.color || FALLBACK_COLOR;

    return {
      key: entry.time,
      missing,
      past: start + HOUR_MS <= state.currentTime,
      height: `${missing ? 100 : Math.max(4, entry.score * 10)}%`,
      color,
      ink: contrastText(color),
      inside: !missing && entry.score >= 2.5,
      value: missing || !values ? '' : entry.score.toFixed(1),
      icon: index % icons === 0 ? entry.icon || entry.weather?.icon || '' : '',
      label: hour % labels === 0 ? label : '',
    };
  });
});

const nowMarker = computed(() => {
  const list = hours.value;

  if (!list.length) return null;

  const index = list.findIndex(entry => {
    const start = hourStart(entry.time);

    return state.currentTime >= start && state.currentTime < start + HOUR_MS;
  });

  if (index === -1) return null;

  const progress = (state.currentTime - hourStart(list[index].time)) / HOUR_MS;

  return `${((index + progress) / list.length) * 100}%`;
});

function spanOf(start, end) {
  const list = hours.value;

  if (!list.length) return null;

  const from = new Date(start).getTime();

  if (!Number.isFinite(from)) return null;

  const to = end ? new Date(end).getTime() : Infinity;
  let first = -1;
  let last = -1;

  list.forEach((entry, index) => {
    const at = new Date(entry.time).getTime();

    if (at < from || at >= to) return;
    if (first === -1) first = index;

    last = index;
  });

  if (first === -1) return null;

  const covered = last + 1 - first;

  return {
    covered,
    left: `${(first / list.length) * 100}%`,
    width: `${(covered / list.length) * 100}%`,
  };
}

function windowText(mark, word, window, covered) {
  const start = window?.start ? clockOf(window.start) : '';
  const end = window?.end ? clockOf(window.end) : '';

  if (covered >= 6 && start && end) return `${mark} ${word} ${start}\u00a0–\u00a0${end}`;
  if (covered >= 3) return `${mark} ${word}`;

  return mark;
}

const ranges = computed(() => {
  const data = summary.value;

  if (!data || !hours.value.length) return [];

  const wanted = [
    { kind: 'is-good', mark: '✅', word: 'BEST', window: data.best_window },
    { kind: 'is-bad', mark: '⛔', word: 'AVOID', window: data.worst_window },
  ];

  return wanted
    .filter(item => Boolean(item.window))
    .map(item => ({ item, span: spanOf(item.window.start, item.window.end) }))
    .filter(entry => Boolean(entry.span))
    .map(entry => ({
      key: entry.item.kind,
      kind: entry.item.kind,
      left: entry.span.left,
      width: entry.span.width,
      text: windowText(entry.item.mark, entry.item.word, entry.item.window, entry.span.covered),
    }));
});

const warningBands = computed(() => {
  const warnings = summary.value?.warnings ?? [];

  if (!hours.value.length) return [];

  return warnings
    .filter(warning => Boolean(warning.start))
    .map(warning => ({ warning, span: spanOf(warning.start, warning.end) }))
    .filter(entry => Boolean(entry.span))
    .slice(0, 2)
    .map((entry, index) => {
      const color = entry.warning.advance ? '#e53935' : entry.warning.color || '#ffd633';
      const name = entry.warning.event_en || entry.warning.event;

      return {
        key: `${name}-${index}`,
        advance: Boolean(entry.warning.advance),
        color,
        ink: entry.warning.advance ? LIGHT_INK : contrastText(color),
        left: entry.span.left,
        width: entry.span.width,
        text: entry.span.covered >= (isPortrait.value ? 6 : 3) ? `⚠ ${name}` : '⚠',
      };
    });
});

const alerts = computed(() => {
  const data = summary.value;

  if (!data) return [];

  const chips = [];

  (data.warnings ?? []).forEach((warning, index) => {
    const color = warning.color || '#ffd633';
    const name = warning.event_en || warning.event;
    const endText = warning.end ? clockOf(warning.end) : '';
    const until = endText ? `until ${endText}` : '';

    chips.push({
      key: `warning-${index}`,
      advance: Boolean(warning.advance),
      mark: warning.advance ? '👁️' : '⚠️',
      title: warning.advance ? `Advance notice: ${name}` : name,
      detail: [warning.region, until].filter(Boolean).join(' · '),
      color,
      ink: contrastText(color),
    });
  });

  (data.pollen ?? []).forEach(reading => {
    if (!reading.warn) return;

    const color = reading.color || '#ffd633';

    chips.push({
      key: `pollen-${reading.key}`,
      advance: false,
      mark: '🤧',
      title: `Pollen: ${capitalise(reading.key)} ${reading.level}`,
      detail: `${measure(reading.value, 0, ' grains/m³')} · forecast, not a measurement`,
      color,
      ink: contrastText(color),
    });
  });

  return chips;
});

const conditions = computed(() => {
  const current = summary.value?.current;

  if (!current) return [];

  return [
    { key: 'Conditions', value: `${current.weather?.icon ?? ''} ${current.weather?.text ?? '–'}`.trim() },
    { key: 'Temperature', value: measure(current.temperature, 1, ' °C') },
    { key: 'Wet-bulb', value: measure(fsi.value?.wetbulb, 1, ' °C') },
    { key: 'Dew point', value: measure(fsi.value?.dewpoint, 1, ' °C') },
    { key: 'Humidity', value: measure(current.humidity, 0, ' %') },
    { key: 'Wind', value: measure(current.wind_speed_kmh, 0, ' km/h') },
    { key: 'Gusts', value: measure(current.wind_gust_kmh, 0, ' km/h') },
    { key: 'Rain (1 h)', value: measure(current.precipitation, 1, ' mm') },
  ];
});

const degraded = computed(() => summary.value?.degraded ?? []);

const isStale = computed(() => Boolean(payload.value?.stale) || Boolean(loadError.value) || degraded.value.length > 0);

const observedAt = computed(() => {
  const parts = [];
  const current = summary.value?.current;

  if (current?.time_local) parts.push(`Observed ${clockOf(current.time_local)}`);
  if (payload.value?.fetched_at) parts.push(`updated ${clockOf(payload.value.fetched_at)}`);
  if (degraded.value.length) parts.push(`⚠️ ${degraded.value.join(', ')}`);
  if (loadError.value) parts.push('⚠️ showing last known data');

  return parts.join(' · ');
});

const attribution = computed(() => summary.value?.attribution ?? '');

function refreshIfStale() {
  if (Date.now() - loadedAt < refreshMs.value) return;

  loadWeather();
}

onMounted(() => {
  refreshIfStale();

  const timer = setInterval(refreshIfStale, STALE_CHECK_MS);

  onUnmounted(() => clearInterval(timer));
});
</script>

<template>
  <div class="wx-board" :class="{ 'is-portrait': isPortrait, 'is-dense': chart.dense }">
    <p v-if="!summary" class="wx-boot">
      <span v-if="loadError">Could not load weather data: {{ loadError }}. Retrying…</span>
      <span v-else>Loading weather data…</span>
    </p>

    <template v-else>
      <header class="wx-strip">
        <div class="wx-place">
          <span class="wx-event">{{ eventName }}</span>
          <span class="wx-what">Weather</span>
          <span class="wx-where">{{ locationName }}</span>
        </div>
        <div class="wx-clock">{{ clock }}</div>
      </header>

      <section v-if="alerts.length" class="wx-alerts">
        <div
          v-for="alert in alerts"
          :key="alert.key"
          class="wx-alert"
          :class="{ 'is-advance': alert.advance }"
          :style="{ backgroundColor: alert.color, color: alert.ink }"
        >
          <span class="wx-alert-head">{{ alert.mark }} {{ alert.title }}</span>
          <span v-if="alert.detail" class="wx-alert-detail">{{ alert.detail }}</span>
        </div>
      </section>

      <section class="wx-tile wx-score-tile" :style="{ backgroundColor: scoreColor, color: scoreInk }">
        <h2 class="wx-head">Fursuiting Index</h2>
        <div class="wx-score-row">
          <div class="wx-score">
            <span class="wx-score-number">{{ scoreText }}</span>
            <span class="wx-score-of">/10</span>
          </div>
          <div class="wx-score-text">
            <div class="wx-band">{{ fsi?.label }}</div>
            <p class="wx-advice">{{ fsi?.advice }}</p>
          </div>
          <div v-if="breakdownVisible && breakdown.length" class="wx-breakdown">
            <div v-for="part in breakdown" :key="part.key" class="wx-part">
              <span class="wx-part-key">{{ part.label }}</span>
              <span class="wx-meter"><span class="wx-meter-fill" :style="{ width: part.width }"></span></span>
              <span class="wx-part-value">{{ part.text }}</span>
            </div>
          </div>
        </div>
      </section>

      <section v-if="chartVisible" class="wx-tile wx-chart-tile">
        <h2 class="wx-head">Next {{ hoursAheadValue }} hours</h2>
        <div class="wx-timeline">
          <div v-if="warningBands.length" class="wx-warn-track">
            <div v-for="band in warningBands" :key="band.key" class="wx-track-row">
              <span
                class="wx-warn-band"
                :class="{ 'is-advance': band.advance }"
                :style="{ left: band.left, width: band.width, backgroundColor: band.color, color: band.ink }"
                >{{ band.text }}</span
              >
            </div>
          </div>

          <div class="wx-hours">
            <div
              v-for="column in columns"
              :key="column.key"
              class="wx-hour"
              :class="{ 'is-past': column.past, 'is-empty': column.missing }"
            >
              <span class="wx-icon">{{ column.icon }}</span>
              <span class="wx-bar-area">
                <b v-if="column.value && !column.inside" class="wx-value is-outside">{{ column.value }}</b>
                <span class="wx-bar" :style="{ height: column.height, backgroundColor: column.color }">
                  <b v-if="column.value && column.inside" class="wx-value" :style="{ color: column.ink }">{{
                    column.value
                  }}</b>
                </span>
              </span>
              <span class="wx-hour-label">{{ column.label }}</span>
            </div>
            <span v-if="nowMarker" class="wx-now" :style="{ left: nowMarker }"></span>
          </div>

          <div v-if="ranges.length" class="wx-ranges">
            <div v-for="range in ranges" :key="range.key" class="wx-track-row">
              <span class="wx-range" :class="range.kind" :style="{ left: range.left, width: range.width }">{{
                range.text
              }}</span>
            </div>
          </div>
        </div>
        <p class="wx-legend">
          <span v-for="band in bands" :key="band.key" class="wx-legend-item">
            <span class="wx-chip" :style="{ backgroundColor: band.color }"></span>{{ band.label }}
          </span>
        </p>
      </section>

      <section v-if="conditions.length" class="wx-tile wx-conditions-tile">
        <h2 class="wx-head">Right now</h2>
        <div class="wx-conditions">
          <div v-for="item in conditions" :key="item.key" class="wx-item">
            <span class="wx-item-key">{{ item.key }}</span>
            <span class="wx-item-value">{{ item.value }}</span>
          </div>
        </div>
      </section>

      <footer class="wx-strip wx-foot">
        <span :class="{ 'is-stale': isStale }">{{ observedAt }}</span>
        <span>{{ attribution }}</span>
      </footer>
    </template>
  </div>
</template>

<style scoped>
.wx-board {
  --wx-bg: #1a2039;
  --wx-tile: #222b54;
  --wx-inset: #171d33;
  --wx-border: rgba(79, 115, 140, 0.45);
  --wx-text: #e7e9f2;
  --wx-muted: #9dabc2;
  --wx-pink: #f13ca3;
  --wx-cyan: #b3ffff;
  --wx-amber: #ffd633;
  --wx-good: #40ad3e;
  --wx-now: #ff2f2f;
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100vh;
  padding: 1.2vh 1.2vw;
  overflow: hidden;
  background: var(--wx-bg);
  color: var(--wx-text);
  font-family: -apple-system, 'Segoe UI', 'Roboto', 'Noto Sans', 'Noto Color Emoji', sans-serif;
  line-height: 1.4;
}

.wx-board * {
  box-sizing: border-box;
}

.wx-boot {
  margin: 0;
  padding: 4vh 2vw;
  color: var(--wx-muted);
  font-size: 2.4vh;
}

.wx-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.wx-place {
  display: flex;
  align-items: baseline;
  min-width: 0;
}

.wx-event {
  margin-right: 0.7vw;
  color: var(--wx-pink);
  font-size: 1.5vh;
  font-weight: 800;
  letter-spacing: 0.18em;
  text-transform: uppercase;
}

.wx-what {
  margin-right: 0.7vw;
  font-size: 2.4vh;
  font-weight: bold;
}

.wx-where {
  color: var(--wx-muted);
  font-size: 1.8vh;
}

.wx-clock {
  flex: 0 0 auto;
  margin-left: 1vw;
  color: var(--wx-cyan);
  font-size: 3.4vh;
  font-weight: bold;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.wx-foot {
  margin-top: 0.8vh;
  color: var(--wx-muted);
  font-size: 1.6vh;
}

.wx-foot > span + span {
  margin-left: 2vw;
}

.wx-foot .is-stale {
  color: var(--wx-amber);
}

.wx-alerts {
  display: flex;
  flex-wrap: wrap;
  margin-top: 1vh;
  margin-right: -0.8vw;
}

.wx-alert {
  display: flex;
  flex: 1 1 22ch;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  margin-right: 0.8vw;
  margin-bottom: 0.8vh;
  padding: 0.9vh 1vw;
  border-radius: 12px;
}

.wx-alert.is-advance {
  background-image: repeating-linear-gradient(
    -45deg,
    rgba(0, 0, 0, 0) 0,
    rgba(0, 0, 0, 0) 0.7vw,
    rgba(0, 0, 0, 0.25) 0.7vw,
    rgba(0, 0, 0, 0.25) 1.4vw
  );
}

.wx-alert-head {
  overflow: hidden;
  font-size: 2.2vh;
  font-weight: bold;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.wx-alert-detail {
  overflow: hidden;
  opacity: 0.8;
  font-size: 1.5vh;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.wx-tile {
  display: flex;
  flex-direction: column;
  min-height: 0;
  margin-top: 1vh;
  padding: 1.4vh 1.1vw;
  overflow: hidden;
  border: 1px solid var(--wx-border);
  border-radius: 12px;
  background: var(--wx-tile);
}

.wx-head {
  margin: 0 0 0.6vh;
  color: var(--wx-muted);
  font-size: 1.7vh;
  font-weight: 600;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}

.wx-score-tile .wx-head {
  opacity: 0.75;
  color: inherit;
}

.wx-score-row {
  display: flex;
  flex: 1;
  align-items: center;
  min-height: 0;
}

.wx-score {
  display: flex;
  align-items: baseline;
  margin-right: 1.4vw;
  line-height: 1;
}

.wx-score-number {
  font-size: 12vh;
  font-weight: 800;
}

.wx-score-of {
  margin-left: 0.2rem;
  opacity: 0.7;
  font-size: 3vh;
}

.wx-score-text {
  flex: 1;
  min-width: 0;
}

.wx-band {
  font-size: 4.6vh;
  font-weight: 800;
  line-height: 1.05;
}

.wx-advice {
  margin: 0.6vh 0 0;
  opacity: 0.92;
  font-size: 2.1vh;
}

.wx-breakdown {
  flex: 0 0 24vw;
  margin-left: 1.4vw;
}

.wx-part {
  display: flex;
  align-items: center;
  font-size: 1.6vh;
}

.wx-part-key {
  flex: 0 0 8vw;
  overflow: hidden;
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.wx-meter {
  flex: 1;
  height: 1.1vh;
  margin: 0 0.6vw;
  overflow: hidden;
  border-radius: 6px;
  background: rgba(0, 0, 0, 0.25);
}

.wx-meter-fill {
  display: block;
  height: 100%;
  border-radius: 6px;
  background: currentcolor;
}

.wx-part-value {
  flex: 0 0 3ch;
  text-align: right;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.wx-chart-tile {
  flex: 1;
}

.wx-timeline {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-height: 0;
  padding: 0.8vh 0.6vw;
  border-radius: 10px;
  background: var(--wx-inset);
}

.wx-warn-track {
  margin-bottom: 0.6vh;
}

.wx-track-row {
  position: relative;
  height: 3.2vh;
}

.wx-track-row + .wx-track-row {
  margin-top: 0.4vh;
}

.wx-warn-band,
.wx-range {
  position: absolute;
  top: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 0 0.4vw;
  overflow: hidden;
  border-radius: 6px;
  color: var(--wx-inset);
  font-size: 1.5vh;
  font-weight: bold;
  line-height: 1.15;
  text-align: center;
}

.wx-warn-band.is-advance {
  border: 1px solid currentcolor;
  background-image: repeating-linear-gradient(
    45deg,
    rgba(0, 0, 0, 0) 0,
    rgba(0, 0, 0, 0) 6px,
    rgba(0, 0, 0, 0.35) 6px,
    rgba(0, 0, 0, 0.35) 13px
  );
}

.wx-range.is-good {
  background: var(--wx-good);
}

.wx-range.is-bad {
  background: var(--wx-pink);
}

.wx-hours {
  position: relative;
  display: flex;
  flex: 1;
  align-items: stretch;
  min-height: 0;
}

.wx-hour {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  padding: 0 0.12vw;
}

.wx-icon {
  flex: 0 0 auto;
  height: 2.8vh;
  overflow: hidden;
  font-size: 2vh;
  line-height: 2.8vh;
  text-align: center;
}

.wx-bar-area {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: flex-end;
  min-height: 0;
}

.wx-bar {
  display: block;
  min-height: 4px;
  border-radius: 5px 5px 0 0;
}

.wx-hour.is-past .wx-bar,
.wx-hour.is-past .wx-icon {
  opacity: 0.4;
  filter: grayscale(1);
}

.wx-hour.is-empty .wx-bar {
  border: 1px dashed var(--wx-border);
  background: transparent !important;
}

.wx-value {
  display: block;
  padding-top: 0.4vh;
  font-size: 1.7vh;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1;
  text-align: center;
}

.wx-value.is-outside {
  padding: 0 0 0.3vh;
  color: var(--wx-text);
}

.wx-hour-label {
  flex: 0 0 auto;
  height: 2.6vh;
  margin: 0 -1.4em;
  color: var(--wx-muted);
  font-size: 1.7vh;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 2.6vh;
  white-space: nowrap;
  text-align: center;
}

.wx-hour:first-of-type .wx-hour-label {
  margin-left: 0;
  text-align: left;
}

.wx-hour:last-of-type .wx-hour-label {
  margin-right: 0;
  text-align: right;
}

.wx-now {
  position: absolute;
  top: 0;
  bottom: 2.6vh;
  width: 3px;
  margin-left: -1.5px;
  border-radius: 2px;
  background: var(--wx-now);
}

.wx-legend {
  display: flex;
  flex-wrap: wrap;
  margin: 0.6vh 0 0;
  color: var(--wx-muted);
  font-size: 1.6vh;
}

.wx-legend-item {
  display: inline-flex;
  align-items: center;
  margin-right: 1vw;
}

.wx-chip {
  display: inline-block;
  width: 0.9em;
  height: 0.9em;
  margin-right: 0.3em;
  border-radius: 3px;
}

.wx-conditions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
}

.wx-item {
  display: flex;
  flex-direction: column;
  min-width: 5.5rem;
  padding-right: 1vw;
}

.wx-item-key {
  color: var(--wx-muted);
  font-size: 1.5vh;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.wx-item-value {
  font-size: 2.7vh;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.is-portrait .wx-score-row {
  flex-wrap: wrap;
}

.is-portrait .wx-score-number {
  font-size: 8vh;
}

.is-portrait .wx-band {
  font-size: 3.4vh;
}

.is-portrait .wx-breakdown {
  flex: 1 0 100%;
  margin-top: 1vh;
  margin-left: 0;
}

.is-portrait .wx-part-key {
  flex: 0 0 22vw;
}

.is-portrait .wx-item {
  flex: 0 0 33%;
  padding-bottom: 0.8vh;
}

.is-portrait .wx-track-row {
  height: 4.4vh;
}

.is-portrait.is-dense .wx-value {
  padding-top: 0.3vh;
  font-size: 1.15vh;
}

.is-portrait .wx-foot {
  flex-direction: column;
  align-items: flex-start;
}

.is-portrait .wx-foot > span + span {
  margin-top: 0.2vh;
  margin-left: 0;
}
</style>
