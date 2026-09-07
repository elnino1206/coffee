<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

/**
 * Нижняя панель разделов для мобильных.
 *
 * Перенесена из прототипа (versions/v2-anim, функция tabBar в main.js):
 * основная навигация вынесена из гамбургера вниз, под большой палец.
 * На экранах шире 767px скрыта — там работает горизонтальное меню.
 *
 * На оформлении заказа панели нет: там человек доводит покупку до конца,
 * и уводить его отсюда пятью ссылками незачем. В прототипе это было
 * условие `PAGE !== "checkout"`.
 */

const page = usePage();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

/** Кабинет ведёт туда, где человек окажется: внутрь или на вход. */
const account = computed(() =>
    page.props.auth.user ? '/dashboard' : '/login',
);

const hidden = computed(() => page.url.startsWith('/checkout'));

const tabs = computed(() => [
    { href: '/', label: 'Главная', icon: 'home', exact: true },
    { href: '/catalog/coffee', label: 'Кофе', icon: 'cup', exact: false },
    { href: '/subscription', label: 'Подписка', icon: 'repeat', exact: true },
    { href: '/cart', label: 'Корзина', icon: 'bag', exact: true },
    { href: account.value, label: 'Кабинет', icon: 'user', exact: true },
]);

function active(tab: { href: string; exact: boolean }): boolean {
    return tab.exact ? isCurrentUrl(tab.href) : isCurrentOrParentUrl(tab.href);
}
</script>

<template>
    <nav v-if="!hidden" class="tabbar" aria-label="Разделы сайта">
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="tab.href"
            class="tabbar__item"
            :class="{ 'is-active': active(tab) }"
            :aria-current="active(tab) ? 'page' : undefined"
        >
            <span class="tabbar__icon">
                <svg
                    v-if="tab.icon === 'home'"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linejoin="round"
                >
                    <path d="M3.5 10.5 12 3.5l8.5 7" />
                    <path d="M5.5 9.6V20h13V9.6" />
                    <path d="M9.8 20v-5.4h4.4V20" />
                </svg>
                <svg
                    v-else-if="tab.icon === 'cup'"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linejoin="round"
                >
                    <path d="M4.5 7.5h12v6a6 6 0 0 1-12 0v-6z" />
                    <path d="M16.5 9.2h1.6a2.6 2.6 0 0 1 0 5.2h-1.6" />
                    <path d="M3 20.5h15" />
                </svg>
                <svg
                    v-else-if="tab.icon === 'repeat'"
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M4 12a8 8 0 0 1 13.7-5.6" />
                    <path d="M20 12a8 8 0 0 1-13.7 5.6" />
                    <path d="M17.8 3v3.6h-3.6" />
                    <path d="M6.2 21v-3.6h3.6" />
                </svg>
                <svg
                    v-else-if="tab.icon === 'bag'"
                    width="20"
                    height="20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                >
                    <path d="M5 7h10l-1 11H6L5 7z" />
                    <path d="M8 7V5.5A2 2 0 0 1 10 3.5 2 2 0 0 1 12 5.5V7" />
                </svg>
                <svg
                    v-else
                    width="20"
                    height="20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                >
                    <circle cx="10" cy="7" r="3.2" />
                    <path d="M4 17c1.2-3 3.2-4.5 6-4.5S14.8 14 16 17" />
                </svg>

                <!-- Пустая корзина бейджа не показывает: ноль в
                     кружке читается как непрочитанное сообщение.
                     Прячет его CSS по data-count. -->
                <span
                    v-if="tab.icon === 'bag'"
                    class="cart-count"
                    :data-count="page.props.cart.count"
                    >{{ page.props.cart.count }}</span
                >
            </span>
            <span class="tabbar__label">{{ tab.label }}</span>
        </Link>
    </nav>
</template>
