<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatPrice } from '@/lib/money';

/**
 * Заказы. Разметка и классы перенесены из макета
 * versions/v2-anim/admin/orders.html.
 *
 * Правка идёт в выдвижной панели, а не в модальном окне: список остаётся
 * на виду, и понятно, какую строку правишь.
 *
 * Состав и сумма не редактируются — сумма считается по позициям, а чужой
 * выбор помола нельзя менять молча.
 */

type Line = {
    name: string;
    variant: string;
    grind: string | null;
    qty: number;
};

type Row = {
    number: string;
    placed_at: string;
    contact_name: string | null;
    phone: string;
    email: string | null;
    ship_method: string;
    ship_label: string;
    address: string | null;
    comment: string | null;
    total: number;
    status: string;
    status_label: string;
    lines: Line[];
};

const props = defineProps<{
    orders: {
        data: Row[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
    ships: { value: string; label: string }[];
}>();

const search = ref(props.filters.q);
const status = ref(props.filters.status);

function reload(): void {
    router.get(
        '/admin/orders',
        { q: search.value || undefined, status: status.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let typing: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(typing);
    typing = setTimeout(reload, 300);
});
watch(status, reload);

/* ── Выдвижная панель правки ────────────────────────────────────── */

const editing = ref<Row | null>(null);

const form = useForm({
    status: '',
    contact_name: '',
    phone: '',
    ship_method: '',
    address: '',
    comment: '',
});

function edit(order: Row): void {
    editing.value = order;

    form.defaults({
        status: order.status,
        contact_name: order.contact_name ?? '',
        phone: order.phone,
        ship_method: order.ship_method,
        address: order.address ?? '',
        comment: order.comment ?? '',
    });
    form.reset();
    form.clearErrors();
}

function save(): void {
    if (!editing.value) {
        return;
    }

    form.patch(`/admin/orders/${editing.value.number}`, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}
</script>

<template>
    <Head title="Заказы" />

    <AdminLayout title="Заказы" note="Розничные заказы с витрины">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <h2>Все заказы</h2>
                <div class="admin-filters admin-panel__aside">
                    <input
                        class="field"
                        type="search"
                        placeholder="Номер, имя или телефон"
                        v-model="search"
                    />
                    <select class="select" v-model="status">
                        <option value="">Все статусы</option>
                        <option
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Дата</th>
                            <th>Покупатель</th>
                            <th>Состав</th>
                            <th>Доставка</th>
                            <th class="num">Сумма</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders.data" :key="order.number">
                            <td>
                                <strong>{{ order.number }}</strong>
                            </td>
                            <td>{{ order.placed_at }}</td>
                            <td>
                                <div>{{ order.contact_name ?? '—' }}</div>
                                <span class="tiny">{{ order.phone }}</span>
                            </td>
                            <td>
                                <ul class="order-cell">
                                    <li
                                        v-for="(line, index) in order.lines"
                                        :key="index"
                                        class="order-cell__row"
                                    >
                                        <span class="order-cell__body">
                                            <strong>{{ line.name }}</strong>
                                            <span class="tiny">
                                                {{ line.variant
                                                }}<template v-if="line.grind">
                                                    · {{ line.grind }}</template
                                                >
                                            </span>
                                        </span>
                                        <span class="order-cell__qty">
                                            × {{ line.qty }}
                                        </span>
                                    </li>
                                </ul>
                            </td>
                            <td>{{ order.ship_label }}</td>
                            <td class="num">{{ formatPrice(order.total) }}</td>
                            <td>
                                <span
                                    class="status"
                                    :class="`status--${order.status}`"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <button
                                        class="admin-btn"
                                        type="button"
                                        @click="edit(order)"
                                    >
                                        Изменить
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!orders.data.length">
                            <td colspan="8">
                                <div class="admin-empty">
                                    <strong>Заказов не нашлось</strong>
                                    <span class="tiny">
                                        Проверьте фильтры — возможно, отбор
                                        слишком узкий.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="orders.last_page > 1"
                class="admin-panel__head"
                style="justify-content: space-between"
            >
                <span class="tiny">
                    Страница {{ orders.current_page }} из
                    {{ orders.last_page }} · всего {{ orders.total }}
                </span>
                <div class="admin-actions">
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!orders.prev_page_url"
                        @click="router.get(orders.prev_page_url!)"
                    >
                        Назад
                    </button>
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!orders.next_page_url"
                        @click="router.get(orders.next_page_url!)"
                    >
                        Вперёд
                    </button>
                </div>
            </div>
        </section>

        <div class="drawer" :hidden="!editing">
            <div class="drawer__scrim" @click="editing = null"></div>
            <form class="drawer__panel" @submit.prevent="save">
                <div class="drawer__head">
                    <div>
                        <h2>Заказ {{ editing?.number }}</h2>
                        <p class="tiny">{{ editing?.placed_at }}</p>
                    </div>
                    <button
                        class="drawer__close"
                        type="button"
                        aria-label="Закрыть"
                        @click="editing = null"
                    >
                        ✕
                    </button>
                </div>

                <div class="drawer__body">
                    <label class="label">
                        <span class="label__text">Статус</span>
                        <select class="select" v-model="form.status">
                            <option
                                v-for="item in statuses"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </option>
                        </select>
                    </label>

                    <label class="label">
                        <span class="label__text">Покупатель</span>
                        <input class="field" v-model="form.contact_name" />
                    </label>

                    <label class="label">
                        <span class="label__text">Телефон</span>
                        <input class="field" v-model="form.phone" />
                        <span v-if="form.errors.phone" class="help help--error">
                            {{ form.errors.phone }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Доставка</span>
                        <select class="select" v-model="form.ship_method">
                            <option
                                v-for="item in ships"
                                :key="item.value"
                                :value="item.value"
                            >
                                {{ item.label }}
                            </option>
                        </select>
                    </label>

                    <label class="label">
                        <span class="label__text">Адрес</span>
                        <input class="field" v-model="form.address" />
                    </label>

                    <label class="label">
                        <span class="label__text">Комментарий для склада</span>
                        <textarea
                            class="textarea"
                            v-model="form.comment"
                        ></textarea>
                    </label>

                    <div>
                        <p class="tiny">Состав заказа</p>
                        <ul class="order-cell">
                            <li
                                v-for="(line, index) in editing?.lines ?? []"
                                :key="index"
                                class="order-cell__row"
                            >
                                <span class="order-cell__body">
                                    <strong>{{ line.name }}</strong>
                                    <span class="tiny">
                                        {{ line.variant
                                        }}<template v-if="line.grind">
                                            · {{ line.grind }}</template
                                        >
                                    </span>
                                </span>
                                <span class="order-cell__qty">
                                    × {{ line.qty }}
                                </span>
                            </li>
                        </ul>
                        <p class="tiny">
                            Состав и сумма не редактируются: сумма считается по
                            позициям, а выбор помола сделал покупатель.
                        </p>
                    </div>
                </div>

                <div class="drawer__foot">
                    <button
                        class="btn btn--petrol btn--m"
                        type="submit"
                        :disabled="form.processing"
                    >
                        Сохранить
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="editing = null"
                    >
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
