import '../css/app.css';
import 'vue-sonner/style.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initializeTheme } from './theme';

const appName = window.document.title;
const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
const buildInfo = typeof __APP_BUILD_INFO__ === 'object' ? __APP_BUILD_INFO__ : null;

window.__APP_BUILD_INFO__ = buildInfo;

initializeTheme();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => pages[`./pages/${name}.vue`],
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#09090b',
    },
});
