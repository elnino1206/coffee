<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';
import AddToCartModal from '@/components/AddToCartModal.vue';
import type {ModalProduct} from '@/components/AddToCartModal.vue';
import { formatPrice } from '@/lib/money';
import { motionOn, observeReveals } from '@/lib/motion';

/**
 * Корзина. Разметка и классы перенесены из прототипа
 * versions/v2-anim/cart.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 */

type Item = {
    id: number;
    name: string | null;
    slug: string | null;
    image: string | null;
    variant: string | null;
    grind: string | null;
    roast: string | null;
    subscribe: boolean;
    qty: number;
    unit_price: number;
    base_price: number;
    total: number;
};

const props = defineProps<{
    items: Item[];
    goods_total: number;
    delivery: number;
    free_delivery_from: number;
    upsell: ModalProduct[];
}>();

const list = ref<HTMLElement | null>(null);

/** Товар, для которого открыт выбор веса и помола. */
const picked = ref<ModalProduct | null>(null);

function setQty(item: Item, qty: number) {
    if (qty < 1) {
return;
}

    router.patch(`/cart/${item.id}`, { qty }, { preserveScroll: true });
}

/**
 * Строка не исчезает мгновенно, а схлопывается: так видно, что она ушла,
 * а не пропала, и соседние строки не прыгают вверх рывком.
 *
 * Высоту фиксируем замером — `auto` не анимируется.
 */
function remove(item: Item, event: MouseEvent) {
    const drop = () => router.delete(`/cart/${item.id}`, { preserveScroll: true });
    const row = (event.currentTarget as HTMLElement)?.closest<HTMLElement>('.line-item');

    if (!motionOn() || !row) {
        drop();

        return;
    }

    row.style.height = `${row.offsetHeight}px`;
    void row.offsetWidth;
    row.classList.add('is-removing');
    setTimeout(drop, 240);
}

/* Блок допродажи меняется вместе с составом корзины — новые карточки
   нужно отдать наблюдателю появления заново. */
watch(
    () => props.upsell,
    () => nextTick(() => observeReveals(list.value ?? document)),
);
</script>

<template>
    <Head title="Корзина" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs"><Link href="/">Главная</Link> · Корзина</nav>
            <h1 class="h2">Корзина</h1>
        </div>

        <div class="checkout">
            <div ref="list">
                <div v-if="!items.length" class="empty panel">
                    <p>Корзина пуста.</p>
                    <p class="tiny">Свежая обжарка уже ждёт в каталоге.</p>
                    <p><Link class="btn btn--primary" href="/catalog/coffee">Смотреть кофе</Link></p>
                </div>

                <div v-else>
                    <div v-for="item in items" :key="item.id" class="line-item">
                        <img
                            :src="`/${item.image}`"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                        <div>
                            <strong>{{ item.name }}</strong>
                            <div class="tiny">
                                {{ item.variant
                                }}<template v-if="item.roast"> · {{ item.roast }}</template
                                ><template v-if="item.grind"> · {{ item.grind }}</template
                                ><template v-if="item.subscribe"> · Подписка −10%</template>
                            </div>
                            <div class="qty" style="margin-top: 8px">
                                <button type="button" aria-label="Меньше" @click="setQty(item, item.qty - 1)">−</button>
                                <span>{{ item.qty }}</span>
                                <button type="button" aria-label="Больше" @click="setQty(item, item.qty + 1)">+</button>
                            </div>
                        </div>
                        <div>
                            <div class="price">{{ formatPrice(item.total) }}</div>
                            <button class="tiny" type="button" @click="remove(item, $event)">Удалить</button>
                        </div>
                    </div>
                </div>

                <div v-if="upsell.length" style="margin-top: 32px">
                    <h2 class="h3" style="margin-bottom: 12px">Добавить к заказу</h2>
                    <div class="product-grid product-grid--upsell">
                        <article v-for="product in upsell" :key="product.slug" class="card" data-reveal>
                            <div class="card__media">
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
                                    <span class="price">{{ formatPrice(product.variants[0]?.price ?? 0) }}</span>
                                    <button
                                        class="add-quick"
                                        type="button"
                                        :aria-label="`Выбрать вес и помол: ${product.name}`"
                                        @click="picked = product"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                            <path d="M4.5 6.5h11l-1 10h-9l-1-10z" />
                                            <path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <aside v-if="items.length" class="summary">
                <h2 class="h3">Итого</h2>
                <div class="totals">
                    <div><span>Товары</span><span>{{ formatPrice(goods_total) }}</span></div>
                    <div>
                        <span>Доставка</span>
                        <span>{{ delivery ? formatPrice(delivery) : 'Бесплатно' }}</span>
                    </div>
                    <div class="grand">
                        <span>К оплате</span><span>{{ formatPrice(goods_total + delivery) }}</span>
                    </div>
                </div>
                <p class="tiny">Бесплатная доставка от {{ formatPrice(free_delivery_from) }}</p>
                <Link class="btn btn--petrol btn--block" href="/checkout">Оформить заказ</Link>
            </aside>
        </div>
    </div>

    <AddToCartModal :product="picked" @close="picked = null" />
</template>
