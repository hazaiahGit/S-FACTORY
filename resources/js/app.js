import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import FormattedNumberInput from '@/Components/FormattedNumberInput.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('FormattedNumberInput', FormattedNumberInput);
            
        app.config.globalProperties.$can = function(permission) {
            const roles = this.$page.props.auth.roles || [];
            if (roles.includes('Super Admin')) return true;
            const permissions = this.$page.props.auth.permissions || [];
            return permissions.includes(permission);
        };

        app.config.globalProperties.$hasRole = function(role) {
            const roles = this.$page.props.auth.roles || [];
            if (roles.includes('Super Admin')) return true;
            return roles.includes(role);
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
