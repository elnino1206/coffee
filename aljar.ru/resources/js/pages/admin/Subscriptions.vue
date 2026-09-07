<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatPrice } from '@/lib/money';

/**
 * Подписки. Разметка и классы перенесены из макета
 * versions/v2-anim/admin/subscriptions.html.
 *
 * Раздел только показывает. Паузу и отмену ставит покупатель из
 * кабинета, блокировку — неудачные списания: кнопка правки здесь
 * означала бы, что менеджер может остановить чужую подписку молча.
 */

type Row = {
    id: number;
    number: string;
    customer: string;
    email: string;
    product: string | null;
    variant: string | null;
    grind: string | null;
    frequency: string;
    next_delivery: string | null;
    charge: number | null;
    status: string;
    status_label: string;
    pill: string;
};

const props = defineProps<{
    subscriptions: {
        data: Row[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
}>();

const search = ref(props.filters.q);
const status = ref(props.filters.status);

function reload(): void {
    router.get(
        '/admin/subscriptions',
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
</script>

<template>
    <Head title="Подписки" />

    <AdminLayout title="Подписки" note="Регулярные отгрузки и их состояние">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <h2>Подписки</h2>
                <div class="admin-filters admin-panel__aside">
                    <input
                        class="field"
                        type="search"
                        placeholder="Номер, имя или почта"
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
                            <th>Подписчик</th>
                            <th>Позиция</th>
                            <th>Частота</th>
                            <th>Следующая отгрузка</th>
                            <th class="num">Списание</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in subscriptions.data" :key="item.id">
                            <td>
                                <strong>{{ item.number }}</strong>
                            </td>
                            <td>
                                <div>{{ item.customer }}</div>
                                <span class="tiny">{{ item.email }}</span>
                            </td>
                            <td>
                                <div class="admin-cell-product">
                                    <div>
                                        <strong>
                                            {{
                                                item.product ?? 'Снят с продажи'
                                            }}
                                        </strong>
                                        <span class="tiny">
                                            {{ item.variant ?? '—' }}
                                            <template v-if="item.grind">
                                                · {{ item.grind }}
                                            </template>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ item.frequency }}</td>
                            <td>{{ item.next_delivery ?? '—' }}</td>
                            <td class="num">
                                {{
                                    item.charge === null
                                        ? '—'
                                        : formatPrice(item.charge)
                                }}
                            </td>
                            <td>
                                <span
                                    class="status"
                                    :class="`status--${item.pill}`"
                                >
                                    {{ item.status_label }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!subscriptions.data.length">
                            <td colspan="7">
                                <div class="admin-empty">
                                    <strong>Подписок не нашлось</strong>
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
                v-if="subscriptions.last_page > 1"
                class="admin-panel__head"
                style="justify-content: space-between"
            >
                <span class="tiny">
                    Страница {{ subscriptions.current_page }} из
                    {{ subscriptions.last_page }} · всего
                    {{ subscriptions.total }}
                </span>
                <div class="admin-actions">
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!subscriptions.prev_page_url"
                        @click="router.get(subscriptions.prev_page_url!)"
                    >
                        Назад
                    </button>
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!subscriptions.next_page_url"
                        @click="router.get(subscriptions.next_page_url!)"
                    >
                        Вперёд
                    </button>
                </div>
            </div>
        </section>
    </AdminLayout>
</template>
