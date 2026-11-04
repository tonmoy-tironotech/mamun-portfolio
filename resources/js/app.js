import { createInertiaApp } from '@inertiajs/vue3';
import { url } from './url';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });

        return pages[`./pages/${name}.vue`];
    },
    withApp: (app) => {
        app.config.globalProperties.$url = url;
    },
});
