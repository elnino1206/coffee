<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatPrice } from '@/lib/money';

type Item = {
    id: number;
    name: string | null;
    slug: string | null;
    image: string | null;
    variant: string | null;
    grind: string | null;
    qty: number;
    unit_price: number;
    total: number;
};

const props = defineProps<{
    items: Item[];
    goods_total: number;
    free_delivery_from: number;
}>();

const left = computed(() => Math.max(props.free_delivery_from - props.goods_total, 0));

function setQty(item: Item, qty: number) {
    if (qty < 1) {
        return;
    }

    router.patch(`/cart/${item.id}`, { qty }, { preserveScroll: true });
}

function remove(item: Item) {
    router.delete(`/cart/${item.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Корзина" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs"><Link href="/">Главная</Link> · Корзина</nav>
            <h1 class="h2">Корзина</h1>
        </div>

        <div v-if="!items.length" class="panel">
            <p>Пока пусто. Начните с каталога — там десять сортов.</p>
            <Link class="btn btn--petrol btn--m" href="/catalog/coffee">Смотреть кофе</Link>
        </div>

        <div v-else class="checkout">
            <div>
                <div class="cart-list">
                    <article v-for="item in items" :key="item.id" class="cart-row panel">
                        <img
                            v-if="item.image"
                            :src="`/${item.image}`"
                            :alt="item.name ?? ''"
                            width="80"
                            height="107"
                            loading="lazy"
                        />
                        <div class="stack">
                            <Link class="card__title" :href="`/catalog/coffee/${item.slug}`">
                                {{ item.name }}
                            </Link>
                            <p class="tiny">
                                {{ item.variant }}<template v-if="item.grind"> · {{ item.grind }}</template>
                            </p>
                            <p class="tiny">{{ formatPrice(item.unit_price) }} за штуку</p>
                        </div>
                        <div class="qty">
                            <button type="button" aria-label="Меньше" @click="setQty(item, item.qty - 1)">−</button>
                            <span>{{ item.qty }}</span>
                            <button type="button" aria-label="Больше" @click="setQty(item, item.qty + 1)">+</button>
                        </div>
                        <div class="stack">
                            <span class="price">{{ formatPrice(item.total) }}</span>
                            <button class="btn btn--ghost btn--s" type="button" @click="remove(item)">
                                Удалить
                            </button>
                        </div>
                    </article>
                </div>
            </div>

            <aside class="summary">
                <h2 class="h3">Итого</h2>
                <div class="totals">
                    <div><span>Товары</span><span>{{ formatPrice(goods_total) }}</span></div>
                    <div><span>Доставка</span><span>рассчитаем при оформлении</span></div>
                    <div class="grand"><span>К оплате</span><span>{{ formatPrice(goods_total) }}</span></div>
                </div>
                <p class="tiny" v-if="left > 0">
                    До бесплатной доставки курьером — {{ formatPrice(left) }}.
                </p>
                <p class="tiny" v-else>Доставка курьером бесплатно.</p>
                <Link class="btn btn--petrol btn--block" href="/checkout">Оформить заказ</Link>
            </aside>
        </div>
    </div>
</template>
