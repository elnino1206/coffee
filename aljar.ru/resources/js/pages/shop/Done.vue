<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { formatPrice } from '@/lib/money';

defineProps<{
    order: {
        number: string;
        phone: string;
        ship_method: string;
        pay_method: string;
        delivery_price: number;
        goods_total: number;
        total: number;
        lines: {
            name: string;
            variant: string;
            grind: string | null;
            qty: number;
            total: number;
        }[];
    };
}>();
</script>

<template>
    <Head title="Заказ принят" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> · Заказ принят
            </nav>
        </div>

        <div class="panel success-box is-visible">
            <p class="eyebrow">Заказ {{ order.number }} принят</p>
            <h1 class="h3">Спасибо. Кофе уже готовим к обжарке.</h1>
            <p>
                Подтвердим заказ по телефону {{ order.phone }}. Дальше — обжарка
                под вашу доставку.
            </p>
        </div>

        <section class="panel" style="margin-top: 24px">
            <h2 class="h3">Состав</h2>
            <div class="stack" style="margin-top: 12px">
                <div
                    v-for="(line, index) in order.lines"
                    :key="index"
                    class="tiny"
                >
                    {{ line.name }} · {{ line.variant
                    }}<template v-if="line.grind"> · {{ line.grind }}</template>
                    × {{ line.qty }} — {{ formatPrice(line.total) }}
                </div>
            </div>
            <div class="totals">
                <div>
                    <span>Товары</span
                    ><span>{{ formatPrice(order.goods_total) }}</span>
                </div>
                <div>
                    <span>Доставка · {{ order.ship_method }}</span>
                    <span>{{
                        order.delivery_price
                            ? formatPrice(order.delivery_price)
                            : 'бесплатно'
                    }}</span>
                </div>
                <div class="grand">
                    <span>Итого</span
                    ><span>{{ formatPrice(order.total) }}</span>
                </div>
            </div>
            <p class="tiny">
                Способ оплаты: {{ order.pay_method }}. Списание подключается
                следующим шагом.
            </p>
            <Link class="btn btn--petrol btn--m" href="/catalog/coffee"
                >Вернуться в каталог</Link
            >
        </section>
    </div>
</template>
