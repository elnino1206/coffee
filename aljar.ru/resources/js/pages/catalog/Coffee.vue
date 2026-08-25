<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatPrice } from '@/lib/money';

type Facet = { value: string; label: string; count: number };

type Card = {
    slug: string;
    name: string;
    notes: string | null;
    image: string | null;
    species: string | null;
    roast: string | null;
    rating: number;
    price_from: number;
    freshness: {
        index: number;
        label: string;
        note: string;
        roasted_at: string | null;
    } | null;
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

const form = ref({ ...props.filters });
const search = ref(props.filters.q ?? '');
const view = ref<'grid' | 'list'>('grid');

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
 * Фильтры уезжают на сервер: он владеет каталогом и только он может
 * честно сказать, сколько позиций нашлось. Перезагружается при этом
 * не вся страница, а выдача.
 */
function apply() {
    router.get('/catalog/coffee', { ...form.value, q: search.value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'total', 'filters'],
    });
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
        sort: 'fresh',
    };
    search.value = '';
    apply();
}

const activeChips = computed(() => [
    ...form.value.roast.map((value) => ({ list: 'roast' as const, value, label: props.facets.roasts.find((f) => f.value === value)?.label ?? value })),
    ...form.value.method.map((value) => ({ list: 'method' as const, value, label: props.facets.methods.find((f) => f.value === value)?.label ?? value })),
    ...form.value.origin.map((value) => ({ list: 'origin' as const, value, label: value })),
]);

let typing: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(typing);
    typing = setTimeout(apply, 300);
});
</script>

<template>
    <Head title="Каталог кофе" />

    <div class="catalog-page">
        <div class="catalog-shell">
            <aside class="filters" id="filters-panel">
                <div class="filters__head">
                    <nav class="breadcrumbs"><Link href="/">Главная</Link> · Каталог</nav>
                    <h1 class="h2 filters__title">Весь кофе</h1>
                    <p class="tiny">
                        Моносорта и смеси — обжариваем небольшими партиями ради
                        чистоты, баланса и характера.
                    </p>
                </div>

                <div class="filter-group">
                    <div class="filter-group__head"><span>Обжарка</span></div>
                    <div class="filter-group__body">
                        <label v-for="roast in facets.roasts" :key="roast.value" class="check">
                            <input
                                type="checkbox"
                                :checked="form.roast.includes(roast.value)"
                                @change="toggle('roast', roast.value)"
                            />
                            <span>{{ roast.label }}</span>
                            <span class="check__count">{{ roast.count }}</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-group__head"><span>Происхождение</span></div>
                    <div class="filter-group__body">
                        <label v-for="origin in facets.origins" :key="origin.value" class="check">
                            <input
                                type="checkbox"
                                :checked="form.origin.includes(origin.value)"
                                @change="toggle('origin', origin.value)"
                            />
                            <span>{{ origin.label }}</span>
                            <span class="check__count">{{ origin.count }}</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-group__head"><span>Цена</span></div>
                    <div class="filter-group__body">
                        <div class="price-range">
                            <div class="price-range__track">
                                <div class="price-range__fill" :style="fill"></div>
                            </div>
                            <input
                                type="range"
                                :min="facets.price.min"
                                :max="facets.price.max"
                                :value="priceLo"
                                aria-label="Цена от"
                                @change="form.price_min = Number(($event.target as HTMLInputElement).value); apply()"
                            />
                            <input
                                type="range"
                                :min="facets.price.min"
                                :max="facets.price.max"
                                :value="priceHi"
                                aria-label="Цена до"
                                @change="form.price_max = Number(($event.target as HTMLInputElement).value); apply()"
                            />
                        </div>
                        <div class="price-range__labels">
                            <span>{{ priceLo }} ₽</span>
                            <span>{{ priceHi }} ₽</span>
                        </div>
                    </div>
                </div>

                <div class="filter-group">
                    <div class="filter-group__head"><span>Способ приготовления</span></div>
                    <div class="filter-group__body method-grid">
                        <button
                            v-for="method in facets.methods"
                            :key="method.value"
                            type="button"
                            class="method-tile"
                            :class="{ 'is-active': form.method.includes(method.value) }"
                            :aria-pressed="form.method.includes(method.value)"
                            @click="toggle('method', method.value)"
                        >
                            <img :src="methodIcons[method.value]" :alt="''" width="28" height="28" />
                            <span>{{ method.label }}</span>
                        </button>
                    </div>
                </div>

                <div class="filters__actions">
                    <button class="btn btn--ghost btn--m" type="button" @click="reset">
                        Сбросить
                    </button>
                </div>
            </aside>

            <section class="catalog-main">
                <header class="results-head">
                    <div>
                        <p class="eyebrow">Результаты</p>
                        <h2 class="h2">{{ total }} — кофе</h2>
                    </div>
                    <div class="results-tools">
                        <label class="sort-label">
                            Сортировка
                            <select class="select" v-model="form.sort" @change="apply">
                                <option value="fresh">Сначала свежие</option>
                                <option value="rating">По рейтингу</option>
                                <option value="price-asc">Цена по возрастанию</option>
                                <option value="price-desc">Цена по убыванию</option>
                            </select>
                        </label>
                        <div class="view-toggle" role="group" aria-label="Вид списка">
                            <button
                                type="button"
                                :class="{ 'is-active': view === 'grid' }"
                                :aria-pressed="view === 'grid'"
                                aria-label="Сеткой"
                                @click="view = 'grid'"
                            >
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <rect x="1" y="1" width="6" height="6" rx="1.5" />
                                    <rect x="9" y="1" width="6" height="6" rx="1.5" />
                                    <rect x="1" y="9" width="6" height="6" rx="1.5" />
                                    <rect x="9" y="9" width="6" height="6" rx="1.5" />
                                </svg>
                            </button>
                            <button
                                type="button"
                                :class="{ 'is-active': view === 'list' }"
                                :aria-pressed="view === 'list'"
                                aria-label="Списком"
                                @click="view = 'list'"
                            >
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                    <path d="M2 4h12M2 8h12M2 12h12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </header>

                <div class="toolbar-row">
                    <input
                        class="field"
                        type="search"
                        placeholder="Поиск по названию, региону, вкусу…"
                        v-model="search"
                    />
                </div>

                <div class="chips" v-if="activeChips.length">
                    <button
                        v-for="chip in activeChips"
                        :key="`${chip.list}-${chip.value}`"
                        class="chip"
                        type="button"
                        @click="toggle(chip.list, chip.value)"
                    >
                        {{ chip.label }} ×
                    </button>
                </div>

                <div class="product-grid" :class="{ 'product-grid--list': view === 'list' }">
                    <article v-for="product in products" :key="product.slug" class="card">
                        <div class="card__media">
                            <span
                                v-if="product.freshness"
                                class="badge badge--roast"
                                :class="`badge--fresh-${product.freshness.index}`"
                                :title="product.freshness.note"
                            >
                                <svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <rect x="3" y="4.5" width="14" height="13" rx="2.5" />
                                    <path d="M3 8.5h14M7 2.5v3M13 2.5v3" />
                                </svg>
                                Обжарено: {{ product.freshness.roasted_at }}
                            </span>
                            <Link :href="`/catalog/coffee/${product.slug}`">
                                <img :src="`/${product.image}`" :alt="product.name" loading="lazy" decoding="async" />
                            </Link>
                        </div>
                        <div class="card__body">
                            <Link class="card__title" :href="`/catalog/coffee/${product.slug}`">
                                {{ product.name }}
                            </Link>
                            <p class="card__notes">{{ product.notes }}</p>
                            <div class="card__meta">
                                <span>{{ product.roast }}</span>
                                <span>{{ product.species }}</span>
                            </div>
                            <div class="card__row">
                                <span class="price">{{ formatPrice(product.price_from) }}</span>
                                <Link
                                    class="add-quick"
                                    :href="`/catalog/coffee/${product.slug}`"
                                    :aria-label="`Выбрать вес и помол: ${product.name}`"
                                >
                                    <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path d="M4.5 6.5h11l-1 10h-9l-1-10z" />
                                        <path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>

                <p v-if="!products.length" class="muted">
                    По этим фильтрам ничего не нашлось. Сбросьте часть условий.
                </p>
            </section>
        </div>
    </div>
</template>
