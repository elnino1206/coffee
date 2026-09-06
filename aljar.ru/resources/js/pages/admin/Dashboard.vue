<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatPrice } from '@/lib/money';

/**
 * Дашборд админки. Разметка и классы перенесены из прототипа
 * versions/v2-anim/admin/index.html.
 *
 * Показатели приходят с сервера посчитанными из тех же заказов, что
 * показаны в таблице: отдельные числа разошлись бы с ней после первой
 * правки статуса.
 */

defineProps<{
    stats: { label: string; value: string; note: string }[];
    orders: {
        number: string;
        placed_at: string;
        customer: string;
        positions: number;
        ship: string;
        total: number;
        status: string;
        status_label: string;
        lines: {
            name: string;
            variant: string;
            grind: string | null;
            qty: number;
        }[];
    }[];
}>();
</script>

<template>
    <Head title="Дашборд" />

    <AdminLayout title="Дашборд" note="Сводка по продажам и заказам">
        <div class="admin-stats">
            <div v-for="stat in stats" :key="stat.label" class="admin-stat">
                <span class="admin-stat__label">{{ stat.label }}</span>
                <span class="admin-stat__value">{{ stat.value }}</span>
                <span class="admin-stat__delta">{{ stat.note }}</span>
            </div>
        </div>

        <div class="admin-cols">
            <section class="admin-panel">
                <div class="admin-panel__head">
                    <h2>Последние заказы</h2>
                    <Link
                        class="admin-btn admin-panel__aside"
                        href="/admin/orders"
                    >
                        Все заказы
                    </Link>
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Номер</th>
                                <th>Дата</th>
                                <th>Покупатель</th>
                                <th class="num">Позиций</th>
                                <th>Доставка</th>
                                <th class="num">Сумма</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.number">
                                <td>{{ order.number }}</td>
                                <td>{{ order.placed_at }}</td>
                                <td>{{ order.customer }}</td>
                                <td class="num">{{ order.positions }}</td>
                                <td>{{ order.ship }}</td>
                                <td class="num">
                                    {{ formatPrice(order.total) }}
                                </td>
                                <td>
                                    <span
                                        class="status"
                                        :class="`status--${order.status}`"
                                    >
                                        {{ order.status_label }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!orders.length">
                                <td colspan="7">Заказов пока нет.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
