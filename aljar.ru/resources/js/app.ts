import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { enableMotion } from '@/lib/motion';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('admin/'):
                return null;
            case name === 'Home':
            case name.startsWith('account/'):
            case name.startsWith('catalog/'):
            case name.startsWith('shop/'):
            case name.startsWith('info/'):
                return StorefrontLayout;
            case [
                'auth/Login',
                'auth/Register',
                'auth/ForgotPassword',
            ].includes(name):
                return StorefrontLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

/* Решение о движении принимается здесь, а не в раскладке витрины: дочерний
   компонент монтируется раньше родителя, и страница со сценарием успевала
   спросить про is-enhanced до того, как класс выдан, — сцена не собиралась. */
enableMotion();

// This will listen for flash toast data from the server...
initializeFlashToast();
