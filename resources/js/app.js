import "./bootstrap.js";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import * as Sentry from "@sentry/vue";
import Main from "./Main.vue";

import.meta.glob(["./Projects/**/Assets/**"]);

const appName = "Open Signage";
const appPath = import.meta.env.VITE_PROJECT_PATH;
import(`./Projects/${appPath}/app.css`);

createInertiaApp({
    title: (title) => `${appName}`,
    resolve: (name) => {
        return Main;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy);
        Sentry.init({
            app,
            dsn: "https://4353dd8344d1220a90805d9894143c8c@sentry.eurofurence.org/14",
        });
        app.mount(el);
        return app;
    },
});

String.prototype.truncate =
    String.prototype.truncate ||
    function (n, useWordBoundary) {
        if (this.length <= n) {
            return this;
        }
        const subString = this.slice(0, n - 1); // the original check
        return (
            (useWordBoundary
                ? subString.slice(0, subString.lastIndexOf(" "))
                : subString) + " …"
        );
    };
