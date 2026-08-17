import 'core-js/stable';
import './bootstrap.js';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import * as Sentry from '@sentry/vue';
import Main from './Main.vue';
import Timetable from './Timetable.vue';

import.meta.glob(['./Projects/**/Assets/**'], { eager: true, query: '?url', import: 'default' });

const projectStyles = import.meta.glob('./Projects/*/app.css');
projectStyles[`./Projects/${import.meta.env.VITE_PROJECT_PATH}/app.css`]?.();

const appName = 'Open Signage';

createInertiaApp({
  title: () => appName,
  resolve: name => {
    return name === 'Timetable' ? Timetable : Main;
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, Ziggy);
    Sentry.init({
      app,
      dsn: 'https://4353dd8344d1220a90805d9894143c8c@sentry.eurofurence.org/14',
    });
    app.mount(el);
    return app;
  },
});
