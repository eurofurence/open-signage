import './bootstrap.js';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import * as Sentry from '@sentry/vue';
import Main from './Main.vue';

import.meta.glob(['./Projects/**/Assets/**']);

const appName = 'Open Signage';
const appPath = import.meta.env.VITE_PROJECT_PATH;
import(`./Projects/${appPath}/app.css`);

createInertiaApp({
  title: () => appName,
  resolve: () => {
    return Main;
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
