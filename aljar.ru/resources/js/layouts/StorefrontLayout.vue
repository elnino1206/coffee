<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import StorefrontFooter from '@/components/StorefrontFooter.vue';
import StorefrontToast from '@/components/StorefrontToast.vue';
import TabBar from '@/components/TabBar.vue';
import { formatPrice } from '@/lib/money';
import {
    guardReveals,
    initIsland,
    observeReveals,
    replayAnimation,
    watchHeaderOffset,
} from '@/lib/motion';
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
    { label: 'Подписка', href: '/subscription', page: 'subscription' },
    { label: 'О нас', href: '/about', page: 'about' },
    { label: 'Для бизнеса', href: '/wholesale', page: 'wholesale' },
    { label: 'Журнал', href: '/blog', page: 'blog' },
];

defineProps<{ page?: string }>();

const inertia = usePage();

/**
 * Страницы с горизонтальным рельсом рисуют подвал сами — последней
 * панелью. Здесь его выводить нельзя: он добавил бы странице вторую ось
 * прокрутки, и рельс уезжал бы вверх вместе с ней.
 */
const railPages = ['Home', 'info/About', 'info/Subscription', 'info/Wholesale'];

const ownsFooter = computed(() => railPages.includes(inertia.component));

/** Кабинет ведёт туда, где человек окажется: внутрь или на вход. */
const account = computed(() =>
    inertia.props.auth.user ? '/dashboard' : '/login',
);

/**
 * Панель поиска в шапке. В прототипе она фильтрует локальный каталог,
 * здесь подсказки приходят с сервера — каталогом владеет он.
 */
const searchOpen = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
const searchPanel = ref<HTMLElement | null>(null);
const searchToggle = ref<HTMLElement | null>(null);
const query = ref('');
const hits = ref<{ slug: string; name: string; price_from: number }[]>([]);
const searched = ref(false);

function setSearch(open: boolean): void {
    searchOpen.value = open;

    if (open) {
        nextTick(() => searchInput.value?.focus());

        return;
    }

    /* Фокус возвращаем на кнопку, только если он остался внутри панели:
       иначе отберём его у того, куда пользователь уже ушёл. */
    if (searchPanel.value?.contains(document.activeElement)) {
        searchToggle.value?.focus();
    }
}

let searchTimer: ReturnType<typeof setTimeout>;

watch(query, (value) => {
    clearTimeout(searchTimer);

    const term = value.trim();

    if (!term) {
        hits.value = [];
        searched.value = false;

        return;
    }

    /* Задержка, чтобы не слать запрос на каждое нажатие клавиши. */
    searchTimer = setTimeout(async () => {
        const response = await fetch(
            `/catalog/search?q=${encodeURIComponent(term)}`,
            {
                headers: { Accept: 'application/json' },
            },
        );
        const data = await response.json();

        hits.value = data.results ?? [];
        searched.value = true;
    }, 250);
});

/* Панель не перекрывает страницу целиком, поэтому закрываться должна и
   по клику мимо, и по Escape: иначе останется висеть над содержимым. */
function onPointerDown(event: PointerEvent): void {
    if (!searchOpen.value) {
        return;
    }

    const target = event.target as Node;

    // Клик по самой кнопке обрабатывает её слушатель — иначе панель
    // закрылась бы здесь и тут же открылась снова.
    if (
        searchPanel.value?.contains(target) ||
        searchToggle.value?.contains(target)
    ) {
        return;
    }

    setSearch(false);
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && searchOpen.value) {
        setSearch(false);
    }
}

/** Бейдж корзины — чтобы подскочить при пополнении. */
const cartBadge = ref<HTMLElement | null>(null);

const cartCount = computed(() =>
    Number((inertia.props as { cart?: { count?: number } }).cart?.count ?? 0),
);

/* Подскок только на пополнении: удаление позиции подпрыгивать не должно,
   и на первой отрисовке страницы — тоже. */
watch(cartCount, (next, prev) => {
    if (next > prev && next > 0) {
        replayAnimation(cartBadge.value, 'is-bump');
    }
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
    document.addEventListener('pointerdown', onPointerDown);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', sizeTornMasks);
    document.removeEventListener('pointerdown', onPointerDown);
    document.removeEventListener('keydown', onKeydown);
});

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
    <svg
        class="svg-defs"
        data-torn-defs
        aria-hidden="true"
        focusable="false"
    ></svg>

    <header class="site-header" id="header">
        <div class="site-header__inner container">
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
                <button
                    ref="searchToggle"
                    class="icon-btn"
                    type="button"
                    aria-label="Поиск"
                    aria-controls="search"
                    :aria-expanded="searchOpen"
                    @click="setSearch(!searchOpen)"
                >
                    <svg
                        width="20"
                        height="20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <circle cx="9" cy="9" r="6" />
                        <path d="M14 14l4 4" />
                    </svg>
                </button>
                <Link
                    class="icon-btn"
                    :href="account"
                    aria-label="Личный кабинет"
                >
                    <svg
                        width="20"
                        height="20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <circle cx="10" cy="7" r="3.2" />
                        <path d="M4 17c1.2-3 3.2-4.5 6-4.5S14.8 14 16 17" />
                    </svg>
                </Link>
                <Link class="icon-btn" href="/cart" aria-label="Корзина">
                    <svg
                        width="20"
                        height="20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <path d="M5 7h10l-1 11H6L5 7z" />
                        <path
                            d="M8 7V5.5A2 2 0 0 1 10 3.5 2 2 0 0 1 12 5.5V7"
                        />
                    </svg>
                    <span ref="cartBadge" class="cart-count">{{
                        $page.props.cart.count
                    }}</span>
                </Link>
            </div>
        </div>

        <!-- Панель поиска живёт внутри шапки: так она разворачивается
             ровно под её нижней границей, какой бы высоты шапка ни была.
             При прокрутке шапка сжимается — привязка к фиксированному
             отступу разъехалась бы. -->
        <div
            id="search"
            ref="searchPanel"
            class="search-panel"
            :class="{ 'is-open': searchOpen }"
        >
            <div class="search-panel__inner">
                <div class="search-panel__body container">
                    <div class="search-panel__field">
                        <svg
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <circle cx="9" cy="9" r="6" />
                            <path d="M14 14l4 4" />
                        </svg>
                        <input
                            ref="searchInput"
                            v-model="query"
                            class="field"
                            type="search"
                            placeholder="Найти кофе по названию, региону, вкусу…"
                            aria-label="Поиск по каталогу"
                        />
                        <button
                            class="icon-btn"
                            type="button"
                            aria-label="Закрыть поиск"
                            @click="setSearch(false)"
                        >
                            <svg
                                width="22"
                                height="22"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                            >
                                <path d="M5 5l12 12M17 5L5 17" />
                            </svg>
                        </button>
                    </div>
                    <div class="search-results">
                        <Link
                            v-for="hit in hits"
                            :key="hit.slug"
                            class="chip"
                            :href="`/catalog/coffee/${hit.slug}`"
                        >
                            {{ hit.name }} · {{ formatPrice(hit.price_from) }}
                        </Link>
                        <p v-if="searched && !hits.length" class="empty">
                            Ничего не нашли. Попробуйте «эспрессо» или
                            «Эфиопия».
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main id="main">
        <slot />
    </main>

    <StorefrontFooter v-if="!ownsFooter" />

    <TabBar />

    <StorefrontToast />
</template>
