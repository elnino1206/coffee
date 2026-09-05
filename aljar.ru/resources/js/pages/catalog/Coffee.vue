<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import AddToCartModal from '@/components/AddToCartModal.vue';
import { formatPrice } from '@/lib/money';
import { animateNumber, motionOn, observeReveals } from '@/lib/motion';

/**
 * Каталог кофе. Разметка и классы перенесены из прототипа
 * versions/v2-anim/catalog.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа: фильтрация уезжает на сервер — он владеет
 * каталогом и только он может честно сказать, сколько позиций нашлось.
 */

type Facet = { value: string; label: string; count: number };

type Variant = { id: number; title: string; price: number; in_stock: boolean };

type Card = {
    slug: string;
    name: string;
    notes: string | null;
    image: string | null;
    species: string | null;
    origin: string | null;
    region: string | null;
    roast: string | null;
    roast_value: string | null;
    rating: number;
    price_from: number;
    price_from_title: string | null;
    variants: Variant[];
};

const props = defineProps<{
    products: Card[];
    total: number;
    filters: {
        roast: string[];
        method: string[];
        origin: string[];
        price_min: number | null;
        price_max: number | null;
        q: string | null;
        sort: string;
    };
    facets: {
        roasts: Facet[];
        methods: Facet[];
        origins: Facet[];
        price: { min: number; max: number };
    };
}>();

const methodIcons: Record<string, string> = {
    espresso: '/img/method-espresso.svg',
    filter: '/img/method-filter.svg',
    cezve: '/img/method-cezve.svg',
};

/**
 * Шкала обжарки: пять делений, из них закрашено столько, на сколько
 * тянет степень. В боковой панели степень показывает шкала, а не число
 * позиций, — как в прототипе.
 */
const ROAST_SCALE = 5;

const roastLevels: Record<string, number> = { light: 1, medium: 3, dark: 5 };

/** В панели фильтров подписи короткие: «Светлая», а не «Светлая обжарка». */
const roastShort: Record<string, string> = {
    light: 'Светлая',
    medium: 'Средняя',
    dark: 'Тёмная',
};

const form = ref({ ...props.filters });
const search = ref(props.filters.q ?? '');
const view = ref<'grid' | 'list'>('grid');

/**
 * Счётчик результатов. Число не подменяется, а прокручивается: глаз
 * успевает заметить, что оно изменилось, и в какую сторону.
 */
const resultsEl = ref<HTMLElement | null>(null);

/** Показанное значение — чтобы прокрутить от него, а не от нуля. */
let shownCount = 0;

/**
 * Слово склоняется на каждом кадре прокрутки — иначе на «3» стояло бы
 * «позиций», пока число уже подъехало к десяти.
 */
function resultsLabel(value: number): string {
    const k = Math.round(value);

    const word =
        k % 10 === 1 && k % 100 !== 11
            ? 'позиция'
            : [2, 3, 4].includes(k % 10) && ![12, 13, 14].includes(k % 100)
              ? 'позиции'
              : 'позиций';

    return `${k} ${word}`;
}

onMounted(() => {
    /* Первая отрисовка идёт без прокрутки: катить число от нуля при
       заходе на страницу нечего — оно ещё не менялось. */
    if (resultsEl.value) {
        resultsEl.value.textContent = resultsLabel(props.total);
    }

    shownCount = props.total;
});

watch(
    () => props.total,
    (next) => {
        animateNumber(resultsEl.value, shownCount, next, resultsLabel, 450);
        shownCount = next;
    },
);

/** Сетка выдачи — её гасим на время подмены набора. */
const grid = ref<HTMLElement | null>(null);
const swapping = ref(false);

/** Свёрнутые группы фильтров и открытая панель на узком экране. */
const collapsed = ref<Record<string, boolean>>({});
const filtersOpen = ref(false);

const priceLo = computed(() => form.value.price_min ?? props.facets.price.min);
const priceHi = computed(() => form.value.price_max ?? props.facets.price.max);

/** Ширина заливки ползунка считается от границ каталога, а не от нуля. */
const fill = computed(() => {
    const span = props.facets.price.max - props.facets.price.min || 1;
    const from = ((priceLo.value - props.facets.price.min) / span) * 100;
    const to = ((priceHi.value - props.facets.price.min) / span) * 100;

    return { left: `${from}%`, width: `${Math.max(to - from, 0)}%` };
});

/**
 * Фильтры уезжают на сервер. Перезагружается при этом не вся страница,
 * а выдача.
 */
function apply() {
    /* Старый набор гасим до подмены: иначе карточки меняются рывком
       прямо под курсором. Новые проявляются сами, через [data-reveal].
       Первая отрисовка и режим без движения идут напрямую. */
    if (motionOn() && props.products.length) {
        swapping.value = true;
    }

    router.get(
        '/catalog/coffee',
        { ...form.value, q: search.value || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['products', 'total', 'filters'],
            onFinish: () => (swapping.value = false),
        },
    );
}

function toggle(list: 'roast' | 'method' | 'origin', value: string) {
    const current = form.value[list];

    form.value[list] = current.includes(value)
        ? current.filter((item) => item !== value)
        : [...current, value];

    apply();
}

function reset() {
    form.value = {
        roast: [],
        method: [],
        origin: [],
        price_min: null,
        price_max: null,
        q: null,
        sort: 'rating',
    };
    search.value = '';
    apply();
}

const activeChips = computed(() => [
    ...form.value.roast.map((value) => ({
        list: 'roast' as const,
        value,
        label: roastShort[value] ?? value,
    })),
    ...form.value.method.map((value) => ({
        list: 'method' as const,
        value,
        label:
            props.facets.methods.find((f) => f.value === value)?.label ?? value,
    })),
    ...form.value.origin.map((value) => ({
        list: 'origin' as const,
        value,
        label: value,
    })),
]);

let typing: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(typing);
    typing = setTimeout(apply, 300);
});

/**
 * Выдача перерисовывается без перезагрузки страницы, поэтому новые
 * карточки нужно отдать наблюдателю появления заново — иначе они
 * останутся спрятанными ради анимации, которая для них не запустится.
 */
watch(
    () => props.products,
    () => nextTick(() => observeReveals(grid.value ?? document)),
);

/** Товар, для которого открыт выбор веса и помола. */
const picked = ref<Card | null>(null);
</script>

<template>
    <Head title="Каталог кофе" />

    <div class="catalog-page">
        <div class="catalog-shell">
            <aside
                id="filters-panel"
                class="filters"
                :class="{ 'is-open': filtersOpen }"
            >
                <div class="filters__head">
                    <nav class="breadcrumbs">
                        <Link href="/">Главная</Link> · Каталог
                    </nav>
                    <h1 class="h2 filters__title">Весь кофе</h1>
                    <p class="tiny">
                        Моносорта и смеси — обжариваем небольшими партиями ради
                        чистоты, баланса и характера.
                    </p>
                </div>

                <div class="filter-group">
                    <button
                        class="filter-group__head"
                        type="button"
                        :aria-expanded="!collapsed.roast"
                        @click="collapsed.roast = !collapsed.roast"
                    >
                        <span>Обжарка</span>
                        <svg
                            class="chev"
                            width="14"
                            height="14"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 10l4-4 4 4" />
                        </svg>
                    </button>
                    <div class="filter-group__body">
                        <label
                            v-for="roast in facets.roasts"
                            :key="roast.value"
                            class="facet"
                        >
                            <input
                                type="checkbox"
                                :checked="form.roast.includes(roast.value)"
                                @change="toggle('roast', roast.value)"
                            />
                            <span class="facet__label">{{
                                roastShort[roast.value] ?? roast.label
                            }}</span>
                            <span class="roast-scale" aria-hidden="true">
                                <i
                                    v-for="n in ROAST_SCALE"
                                    :key="n"
                                    :class="{
                                        on:
                                            n <=
                                            (roastLevels[roast.value] ?? 0),
                                    }"
                                ></i>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <button
                        class="filter-group__head"
                        type="button"
                        :aria-expanded="!collapsed.origin"
                        @click="collapsed.origin = !collapsed.origin"
                    >
                        <span>Происхождение</span>
                        <svg
                            class="chev"
                            width="14"
                            height="14"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 10l4-4 4 4" />
                        </svg>
                    </button>
                    <div class="filter-group__body">
                        <label
                            v-for="origin in facets.origins"
                            :key="origin.value"
                            class="facet"
                        >
                            <input
                                type="checkbox"
                                :checked="form.origin.includes(origin.value)"
                                @change="toggle('origin', origin.value)"
                            />
                            <span class="facet__label">{{ origin.label }}</span>
                            <span class="facet__count">{{ origin.count }}</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <button
                        class="filter-group__head"
                        type="button"
                        :aria-expanded="!collapsed.price"
                        @click="collapsed.price = !collapsed.price"
                    >
                        <span>Цена</span>
                        <svg
                            class="chev"
                            width="14"
                            height="14"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 10l4-4 4 4" />
                        </svg>
                    </button>
                    <div class="filter-group__body">
                        <div class="price-range">
                            <div class="price-range__track">
                                <div
                                    class="price-range__fill"
                                    :style="fill"
                                ></div>
                            </div>
                            <input
                                type="range"
                                :min="facets.price.min"
                                :max="facets.price.max"
                                :value="priceLo"
                                aria-label="Цена от"
                                @change="
                                    form.price_min = Number(
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    );
                                    apply();
                                "
                            />
                            <input
                                type="range"
                                :min="facets.price.min"
                                :max="facets.price.max"
                                :value="priceHi"
                                aria-label="Цена до"
                                @change="
                                    form.price_max = Number(
                                        ($event.target as HTMLInputElement)
                                            .value,
                                    );
                                    apply();
                                "
                            />
                        </div>
                        <div class="price-range__labels">
                            <span>{{ priceLo }} ₽</span>
                            <span>{{ priceHi }} ₽</span>
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <button
                        class="filter-group__head"
                        type="button"
                        :aria-expanded="!collapsed.method"
                        @click="collapsed.method = !collapsed.method"
                    >
                        <span>Способ приготовления</span>
                        <svg
                            class="chev"
                            width="14"
                            height="14"
                            viewBox="0 0 16 16"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M4 10l4-4 4 4" />
                        </svg>
                    </button>
                    <div class="filter-group__body method-grid">
                        <label
                            v-for="method in facets.methods"
                            :key="method.value"
                            class="facet"
                        >
                            <input
                                type="checkbox"
                                :checked="form.method.includes(method.value)"
                                @change="toggle('method', method.value)"
                            />
                            <img
                                :src="methodIcons[method.value]"
                                alt=""
                                loading="lazy"
                                decoding="async"
                            />
                            <span class="facet__label">{{ method.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="filters__actions">
                    <button
                        class="btn btn--petrol btn--m"
                        type="button"
                        @click="filtersOpen = false"
                    >
                        Показать
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="reset"
                    >
                        Сбросить
                    </button>
                </div>
            </aside>

            <section class="catalog-main">
                <header class="results-head">
                    <div>
                        <p class="eyebrow">Результаты</p>
                        <h2 ref="resultsEl" class="h2"></h2>
                    </div>
                    <div class="results-tools">
                        <label class="sort-label">
                            Сортировка
                            <select
                                v-model="form.sort"
                                class="select"
                                @change="apply"
                            >
                                <option value="rating">
                                    Сначала популярные
                                </option>
                                <option value="price-asc">
                                    Цена по возрастанию
                                </option>
                                <option value="price-desc">
                                    Цена по убыванию
                                </option>
                            </select>
                        </label>
                        <div
                            class="view-toggle"
                            role="group"
                            aria-label="Вид списка"
                        >
                            <button
                                type="button"
                                :class="{ 'is-active': view === 'grid' }"
                                :aria-pressed="view === 'grid'"
                                aria-label="Сеткой"
                                @click="view = 'grid'"
                            >
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 16 16"
                                    fill="currentColor"
                                >
                                    <rect
                                        x="1"
                                        y="1"
                                        width="6"
                                        height="6"
                                        rx="1.5"
                                    />
                                    <rect
                                        x="9"
                                        y="1"
                                        width="6"
                                        height="6"
                                        rx="1.5"
                                    />
                                    <rect
                                        x="1"
                                        y="9"
                                        width="6"
                                        height="6"
                                        rx="1.5"
                                    />
                                    <rect
                                        x="9"
                                        y="9"
                                        width="6"
                                        height="6"
                                        rx="1.5"
                                    />
                                </svg>
                            </button>
                            <button
                                type="button"
                                :class="{ 'is-active': view === 'list' }"
                                :aria-pressed="view === 'list'"
                                aria-label="Списком"
                                @click="view = 'list'"
                            >
                                <svg
                                    width="16"
                                    height="16"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                >
                                    <path d="M2 4h12M2 8h12M2 12h12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </header>

                <div class="toolbar-row">
                    <input
                        v-model="search"
                        class="field"
                        type="search"
                        placeholder="Поиск по названию, региону, вкусу…"
                    />
                    <button
                        id="filters-toggle"
                        class="btn btn--ghost btn--m filters-toggle"
                        type="button"
                        @click="filtersOpen = !filtersOpen"
                    >
                        Фильтры
                    </button>
                </div>

                <div class="chips">
                    <span
                        v-for="chip in activeChips"
                        :key="`${chip.list}-${chip.value}`"
                        class="chip"
                    >
                        {{ chip.label }}
                        <button
                            type="button"
                            aria-label="Сбросить"
                            @click="toggle(chip.list, chip.value)"
                        >
                            ×
                        </button>
                    </span>
                </div>

                <div
                    ref="grid"
                    class="product-grid"
                    :class="{
                        'is-list': view === 'list',
                        'is-swapping': swapping,
                    }"
                >
                    <article
                        v-for="product in products"
                        :key="product.slug"
                        class="catalog-card"
                        data-reveal
                    >
                        <div class="catalog-card__media">
                            <Link :href="`/catalog/coffee/${product.slug}`">
                                <img
                                    :src="`/${product.image}`"
                                    :alt="product.name"
                                    loading="lazy"
                                    decoding="async"
                                />
                            </Link>
                        </div>
                        <div class="catalog-card__body">
                            <Link
                                class="catalog-card__title"
                                :href="`/catalog/coffee/${product.slug}`"
                            >
                                {{ product.name }}
                            </Link>
                            <p class="catalog-card__origin">
                                {{ product.origin
                                }}<template
                                    v-if="
                                        product.region &&
                                        product.region !== product.origin
                                    "
                                >
                                    · {{ product.region }}
                                </template>
                            </p>
                            <p class="catalog-card__notes">
                                {{ product.notes?.split(', ').join(' · ') }}
                            </p>
                            <p class="catalog-card__price">
                                <svg
                                    width="15"
                                    height="15"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                >
                                    <path
                                        d="M3 8.5V4a1 1 0 0 1 1-1h4.5L17 11.5 11.5 17 3 8.5z"
                                    />
                                    <circle cx="6.6" cy="6.6" r="1.1" />
                                </svg>
                                {{ formatPrice(product.price_from) }}
                                <small v-if="product.price_from_title"
                                    >/ {{ product.price_from_title }}</small
                                >
                            </p>
                            <div class="catalog-card__cta">
                                <button
                                    class="add-quick add-quick--wide"
                                    type="button"
                                    :aria-label="`Выбрать вес и помол: ${product.name}`"
                                    @click="picked = product"
                                >
                                    <svg
                                        width="15"
                                        height="15"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                    >
                                        <path d="M4.5 6.5h11l-1 10h-9l-1-10z" />
                                        <path
                                            d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5"
                                        />
                                    </svg>
                                    В корзину
                                </button>
                            </div>
                        </div>
                    </article>
                </div>

                <p v-if="!products.length" class="muted">
                    Ничего не нашлось. Попробуйте снять часть фильтров.
                </p>
            </section>
        </div>
    </div>

    <AddToCartModal :product="picked" @close="picked = null" />
</template>
