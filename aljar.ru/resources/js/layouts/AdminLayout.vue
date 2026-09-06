<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StorefrontToast from '@/components/StorefrontToast.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import '../../css/storefront.css';
import '../../css/admin.css';

/**
 * Оболочка админки: боковое меню и шапка страницы.
 *
 * Разметка и классы перенесены из прототипа versions/v2-anim/admin —
 * стили админки надстроены над витринными, поэтому подключаются оба
 * файла.
 *
 * Счётчик у пункта меню показывается, только когда есть что разбирать:
 * ноль рядом с разделом читается как поломка, а не как «дел нет».
 */

defineProps<{ title: string; note?: string }>();

const page = usePage();

const { isCurrentOrParentUrl } = useCurrentUrl();

const counts = computed(
    () => (page.props.adminCounts ?? {}) as Record<string, number>,
);

const groups = [
    {
        label: 'Обзор',
        items: [{ href: '/admin', title: 'Дашборд', key: 'dashboard' }],
    },
    {
        label: 'Продажи',
        items: [
            { href: '/admin/orders', title: 'Заказы', key: 'orders' },
            {
                href: '/admin/subscriptions',
                title: 'Подписки',
                key: 'subscriptions',
            },
            { href: '/admin/leads', title: 'Заявки опта', key: 'leads' },
        ],
    },
    {
        label: 'Контент',
        items: [
            { href: '/admin/products', title: 'Товары', key: 'products' },
            { href: '/admin/articles', title: 'Журнал', key: 'articles' },
        ],
    },
];

/** Дашборд отзывается только на свой адрес: иначе он подсвечен всегда. */
const active = (href: string): boolean =>
    href === '/admin' ? page.url === '/admin' : isCurrentOrParentUrl(href);
</script>

<template>
    <div class="admin">
        <aside class="admin-nav">
            <div class="admin-nav__brand">
                <img src="/img/logo.webp" alt="" width="187" height="138" />
                <span>
                    Al Jar<span class="admin-nav__tag">Панель управления</span>
                </span>
            </div>

            <div
                v-for="group in groups"
                :key="group.label"
                class="admin-nav__group"
            >
                <p class="admin-nav__label">{{ group.label }}</p>
                <Link
                    v-for="item in group.items"
                    :key="item.href"
                    :href="item.href"
                    :class="{ 'is-active': active(item.href) }"
                    :aria-current="active(item.href) ? 'page' : undefined"
                >
                    <span>{{ item.title }}</span>
                    <span v-if="counts[item.key]" class="admin-nav__count">
                        {{ counts[item.key] }}
                    </span>
                </Link>
            </div>

            <div class="admin-nav__foot">
                <a href="/">← Открыть витрину</a>
                <Form
                    action="/admin/logout"
                    method="post"
                    v-slot="{ processing }"
                >
                    <button
                        class="admin-btn admin-btn--ghost"
                        type="submit"
                        :disabled="processing"
                    >
                        Выйти
                    </button>
                </Form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar__title">
                    <h1>{{ title }}</h1>
                    <p v-if="note" class="tiny">{{ note }}</p>
                </div>
                <div class="admin-topbar__actions">
                    <slot name="actions" />
                </div>
            </header>

            <div class="admin-content">
                <slot />
            </div>
        </div>
    </div>
</template>
