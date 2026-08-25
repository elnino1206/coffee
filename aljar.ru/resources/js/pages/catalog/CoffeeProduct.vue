<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatPrice } from '@/lib/money';

type Variant = {
    id: number;
    title: string;
    price: number;
    weight_g: number | null;
    in_stock: boolean;
};

const props = defineProps<{
    coffee: {
        slug: string;
        name: string;
        full_name: string | null;
        notes: string | null;
        image: string | null;
        species: string | null;
        roast: string | null;
        origin: string | null;
        region: string | null;
        process: string | null;
        method: string | null;
        rating: number;
        reviews: number;
        price_from: number;
        freshness: { index: number; label: string; note: string; roasted_at: string | null } | null;
        profile: Record<string, number>;
        variants: Variant[];
    };
    related: { slug: string; name: string; image: string | null; price_from: number }[];
}>();

/** Подписи шкал — те же, что в прототипе: порядок держит смысл. */
const profileLabels: Record<string, string> = {
    fruity: 'Фруктовость',
    chocolate: 'Шоколад',
    spice: 'Специи',
    body: 'Тело',
    acidity: 'Кислотность',
};

const grinds = [
    { id: 'whole', tile: 'В зёрнах, без помола', note: 'Смелете сами перед завариванием', image: '/img/method-beans.svg' },
    { id: 'espresso', tile: 'Рекомендуется для эспрессо', note: 'Тонкий помол под рожок', image: '/img/method-espresso.svg' },
    { id: 'filter', tile: 'Рекомендуется для фильтр-кофе', note: 'Средний помол под воронку и кемекс', image: '/img/method-filter.svg' },
    { id: 'cezve', tile: 'Рекомендуется для турки', note: 'Самый мелкий помол, почти пудра', image: '/img/method-cezve.svg' },
];

const variantId = ref(props.coffee.variants[0]?.id ?? null);
const grind = ref('whole');

const variant = computed(() => props.coffee.variants.find((item) => item.id === variantId.value));
const price = computed(() => variant.value?.price ?? props.coffee.price_from);
</script>

<template>
    <Head :title="coffee.name" />

    <div class="container" data-pdp>
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> ·
                <Link href="/catalog/coffee">Каталог</Link> ·
                <span>{{ coffee.name }}</span>
            </nav>
        </div>

        <div class="pdp">
            <div class="pdp__photo">
                <img
                    :src="`/${coffee.image}`"
                    :alt="coffee.name"
                    width="864"
                    height="1152"
                    fetchpriority="high"
                    decoding="async"
                />
            </div>

            <div class="stack">
                <span v-if="coffee.freshness">
                    <span
                        class="badge badge--roast"
                        :class="`badge--fresh-${coffee.freshness.index}`"
                        :title="coffee.freshness.note"
                    >
                        Обжарено: {{ coffee.freshness.roasted_at }}
                    </span>
                </span>

                <h1 class="h2">{{ coffee.name }}</h1>
                <p class="muted">{{ coffee.full_name }}</p>
                <p class="tiny">{{ coffee.region }}, {{ coffee.origin }}</p>
                <p class="tiny">{{ coffee.species }} · {{ coffee.process }}</p>
                <p class="tiny">
                    Рекомендуем: <strong>{{ coffee.method }}</strong> · {{ coffee.roast }}
                </p>

                <div class="price">{{ formatPrice(price) }}</div>

                <div class="taste">
                    <strong>Профиль вкуса</strong>
                    <div class="taste-profile">
                        <div
                            v-for="(label, key, index) in profileLabels"
                            :key="key"
                            class="bar"
                            :style="{ '--i': index }"
                        >
                            <span>{{ label }}</span>
                            <div class="bar__track">
                                <div class="bar__fill" :style="{ '--v': coffee.profile[key] / 100 }"></div>
                            </div>
                            <span class="bar__value">{{ coffee.profile[key] }}%</span>
                        </div>
                    </div>
                    <p class="tiny">{{ coffee.notes }}</p>
                </div>

                <label class="label">
                    Вес
                    <select class="select" v-model="variantId">
                        <option v-for="item in coffee.variants" :key="item.id" :value="item.id">
                            {{ item.title }} — {{ formatPrice(item.price) }}
                        </option>
                    </select>
                </label>

                <fieldset class="grind-picker">
                    <legend class="label">Помол</legend>
                    <div class="grind-grid">
                        <button
                            v-for="item in grinds"
                            :key="item.id"
                            type="button"
                            class="grind-tile"
                            :class="{ 'is-active': grind === item.id }"
                            :aria-pressed="grind === item.id"
                            @click="grind = item.id"
                        >
                            <img :src="item.image" alt="" width="32" height="32" />
                            <span>{{ item.tile }}</span>
                            <span class="tiny">{{ item.note }}</span>
                        </button>
                    </div>
                </fieldset>

                <button class="btn btn--petrol btn--block" type="button" disabled>
                    Добавить в корзину
                </button>
                <p class="tiny">
                    Корзина подключается следующим шагом — сейчас страница показывает
                    товар, но ничего не запоминает.
                </p>
            </div>
        </div>

        <div class="section" style="padding-top: 0" v-if="related.length">
            <h2 class="h3" style="margin-bottom: 16px">Сопутствующие товары</h2>
            <div class="product-grid">
                <article v-for="item in related" :key="item.slug" class="card">
                    <div class="card__media">
                        <img :src="`/${item.image}`" :alt="item.name" loading="lazy" />
                    </div>
                    <div class="card__body">
                        <span class="card__title">{{ item.name }}</span>
                        <div class="card__row">
                            <span class="price">{{ formatPrice(item.price_from) }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>
