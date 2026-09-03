<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { guardReveals, initIsland, observeReveals, replayAnimation, watchHeaderOffset } from '@/lib/motion';
import { setupTornEdges, sizeTornMasks } from '@/lib/torn';
import '../../css/storefront.css';

/**
 * Оболочка витрины: шапка, подвал и нижняя навигация из прототипа
 * versions/v2-anim. Разметка и классы перенесены как есть — дизайн
 * утверждён, и переписывать его на другие классы значит расходиться с
 * эталоном ради ничего.
 *
 * Разделы, которых ещё нет, ведут на «#»: они загорятся по мере того,
 * как страницы будут появляться.
 */
const nav = [
    { label: 'Кофе', href: '/catalog/coffee', page: 'catalog' },
    { label: 'Подписка', href: '#', page: 'subscription' },
    { label: 'О нас', href: '/about', page: 'about' },
    { label: 'Для бизнеса', href: '#', page: 'wholesale' },
    { label: 'Журнал', href: '#', page: 'blog' },
];

defineProps<{ page?: string }>();

const inertia = usePage();

/** Бейдж корзины — чтобы подскочить при пополнении. */
const cartBadge = ref<HTMLElement | null>(null);

const cartCount = computed(() => Number((inertia.props as { cart?: { count?: number } }).cart?.count ?? 0));

/* Подскок только на пополнении: удаление позиции подпрыгивать не должно,
   и на первой отрисовке страницы — тоже. */
watch(cartCount, (next, prev) => {
    if (next > prev && next > 0) replayAnimation(cartBadge.value, 'is-bump');
});

/* Решение о движении принимает app.ts, до монтирования. Здесь остаются
   наблюдатели за появлением — их нужно переустанавливать на каждую
   страницу: Inertia меняет разметку без перезагрузки, и новые блоки
   иначе остались бы ненаблюдаемыми. */
onMounted(() => {
    /* Шапка живёт вместе с раскладкой, поэтому метка ставится один раз. */
    watchHeaderOffset();
    initIsland();
    observeReveals();
    guardReveals();
    setupTornEdges();

    /* Маски рваного края зависят от фактических размеров снимков,
       а те меняются вместе с шириной окна. */
    window.addEventListener('resize', sizeTornMasks);
});

onBeforeUnmount(() => window.removeEventListener('resize', sizeTornMasks));

/* Набор снимков меняется вместе со страницей, а маски нумеруются по
   порядку — после перехода их нужно пересобрать, иначе на новой странице
   останутся ссылки на маски прежней. */
router.on('navigate', () =>
    nextTick(() => {
        observeReveals();
        setupTornEdges();
        initIsland();
    }),
);
</script>

<template>
    <a class="skip-link" href="#main">К содержанию</a>

    <!-- Сюда собираются маски рваного края: по одной на снимок. Общей
         маской не обойтись — размер её прямоугольника задаётся в пикселях
         под конкретную фотографию. -->
    <svg class="svg-defs" data-torn-defs aria-hidden="true" focusable="false"></svg>

    <header class="site-header" id="header">
        <div class="container site-header__inner">
            <Link class="logo" href="/">
                <img
                    src="/img/logo.webp"
                    alt="Al Jar Coffee"
                    width="187"
                    height="138"
                    decoding="async"
                />
            </Link>

            <nav class="nav-desktop" aria-label="Основное меню">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="item.href"
                    :class="{ 'is-active': page === item.page }"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="header-actions">
                <a class="icon-btn" href="#" aria-label="Личный кабинет">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="10" cy="7" r="3.2" />
                        <path d="M4 17c1.2-3 3.2-4.5 6-4.5S14.8 14 16 17" />
                    </svg>
                </a>
                <Link class="icon-btn" href="/cart" aria-label="Корзина">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 7h10l-1 11H6L5 7z" />
                        <path d="M8 7V5.5A2 2 0 0 1 10 3.5 2 2 0 0 1 12 5.5V7" />
                    </svg>
                    <span ref="cartBadge" class="cart-count">{{ $page.props.cart.count }}</span>
                </Link>
            </div>
        </div>
    </header>

    <main id="main">
        <slot />
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <Link class="logo logo--lg" href="/">
                    <img
                        src="/img/logo.webp"
                        alt="Al Jar Coffee"
                        width="187"
                        height="138"
                        loading="lazy"
                        decoding="async"
                    />
                </Link>
                <p class="tiny">
                    Семейный бренд с ливанскими корнями и современной обжаркой
                    полного цикла в России. От зерна к чашке.
                </p>
            </div>
            <div class="footer-col">
                <h4>Магазин</h4>
                <Link href="/catalog/coffee">Каталог</Link>
                <a href="/catalog/coffee?method[]=espresso">Для эспрессо</a>
                <a href="/catalog/coffee?method[]=filter">Для фильтра</a>
            </div>
            <div class="footer-col">
                <h4>Контакты</h4>
                <a href="tel:+79851379235">8 (985) 137-92-35</a>
                <a href="mailto:hello@aljar.ru">hello@aljar.ru</a>
                <p class="tiny" style="margin-top: 8px">
                    Телефон — основной способ связи. Email по желанию.
                </p>
            </div>
        </div>
        <div class="container footer-bottom">
            <span>© 2026 Al Jar Coffee. Все права защищены.</span>
            <span>Обжариваем в России · Корни в Ливане</span>
        </div>
    </footer>
</template>
