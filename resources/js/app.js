import './bootstrap';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { passwordNotice, toast } from './lib/ui';
import { createApp, h } from 'vue';

// Flash data from the server (one-time, never stored in history) becomes a toast.
// Registered before the app starts so the very first message is not missed.
router.on('flash', (event) => {
    const { toast: t, password } = event.detail.flash ?? {};
    if (t) toast(t.message, t.type);
    if (password) passwordNotice.value = password;
});

// Server errors (403 / 404 / 500) become a toast instead of a broken page.
router.on('httpException', (event) => {
    const status = event.detail.response?.status;
    if (!status || status < 400) return;
    event.preventDefault();
    toast(status === 403 ? 'You do not have access to that.' : status === 404 ? 'That item no longer exists.' : 'Something went wrong. Please try again.', 'error');
});

createInertiaApp({
    title: (title) => (title ? `${title} · CCS Capstone Repository` : 'CCS Capstone Repository'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    // Thin bar at the top of the window while a page loads (colored in polish.css).
    progress: { delay: 120, color: '#6b0808', showSpinner: false },
});
