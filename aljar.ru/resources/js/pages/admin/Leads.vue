<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

/**
 * Заявки опта. Разметка и классы перенесены из макета
 * versions/v2-anim/admin/leads.html.
 *
 * Поля заявки показываются, но не правятся: это слова клиента, и менять
 * их задним числом нельзя. Менеджер двигает статус и пишет свой
 * комментарий — что обещано и о чём договорились.
 */

type Row = {
    id: number;
    number: string;
    received_at: string;
    name: string;
    company: string;
    phone: string;
    email: string | null;
    business_label: string;
    volume: string | null;
    comment: string | null;
    manager_comment: string | null;
    attachment_name: string | null;
    status: string;
    status_label: string;
};

const props = defineProps<{
    leads: {
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
        '/admin/leads',
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
    manager_comment: '',
});

function edit(lead: Row): void {
    editing.value = lead;

    form.defaults({
        status: lead.status,
        manager_comment: lead.manager_comment ?? '',
    });
    form.reset();
    form.clearErrors();
}

function save(): void {
    if (!editing.value) {
        return;
    }

    form.patch(`/admin/leads/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}
</script>

<template>
    <Head title="Заявки опта" />

    <AdminLayout title="Заявки опта" note="Обращения со страницы «Для бизнеса»">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <h2>Заявки оптовых покупателей</h2>
                <div class="admin-filters admin-panel__aside">
                    <input
                        class="field"
                        type="search"
                        placeholder="Номер, имя, компания или телефон"
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
                            <th>Заявка</th>
                            <th>Контактное лицо</th>
                            <th>Связь</th>
                            <th>Тип бизнеса</th>
                            <th>Объём и комментарий</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in leads.data" :key="lead.id">
                            <td>
                                <strong>{{ lead.number }}</strong>
                                <br />
                                <span class="tiny">{{ lead.received_at }}</span>
                            </td>
                            <td>
                                <div>{{ lead.name }}</div>
                                <span class="tiny">{{ lead.company }}</span>
                            </td>
                            <td>
                                <div>{{ lead.phone }}</div>
                                <span class="tiny">{{
                                    lead.email ?? '—'
                                }}</span>
                            </td>
                            <td>{{ lead.business_label }}</td>
                            <td>
                                <div>{{ lead.volume ?? '—' }}</div>
                                <span v-if="lead.comment" class="tiny">
                                    {{ lead.comment }}
                                </span>
                                <a
                                    v-if="lead.attachment_name"
                                    class="tiny"
                                    :href="`/admin/leads/${lead.id}/file`"
                                >
                                    📎 {{ lead.attachment_name }}
                                </a>
                            </td>
                            <td>
                                <span
                                    class="status"
                                    :class="`status--${lead.status}`"
                                >
                                    {{ lead.status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <button
                                        class="admin-btn"
                                        type="button"
                                        @click="edit(lead)"
                                    >
                                        Изменить
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!leads.data.length">
                            <td colspan="7">
                                <div class="admin-empty">
                                    <strong>Заявок не нашлось</strong>
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
                v-if="leads.last_page > 1"
                class="admin-panel__head"
                style="justify-content: space-between"
            >
                <span class="tiny">
                    Страница {{ leads.current_page }} из {{ leads.last_page }} ·
                    всего {{ leads.total }}
                </span>
                <div class="admin-actions">
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!leads.prev_page_url"
                        @click="router.get(leads.prev_page_url!)"
                    >
                        Назад
                    </button>
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!leads.next_page_url"
                        @click="router.get(leads.next_page_url!)"
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
                        <h2>Заявка {{ editing?.number }}</h2>
                        <p class="tiny">{{ editing?.received_at }}</p>
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
                        <span class="label__text">Комментарий менеджера</span>
                        <textarea
                            class="textarea"
                            v-model="form.manager_comment"
                            placeholder="Что обещано, о чём договорились"
                        ></textarea>
                        <span
                            v-if="form.errors.manager_comment"
                            class="help help--error"
                        >
                            {{ form.errors.manager_comment }}
                        </span>
                    </label>

                    <div>
                        <p class="tiny">Что написал клиент</p>
                        <ul class="order-cell">
                            <li class="order-cell__row">
                                <span class="order-cell__body">
                                    <strong>{{ editing?.company }}</strong>
                                    <span class="tiny">
                                        {{ editing?.name }} ·
                                        {{ editing?.phone }}
                                        <template v-if="editing?.email">
                                            · {{ editing?.email }}
                                        </template>
                                    </span>
                                </span>
                            </li>
                            <li class="order-cell__row">
                                <span class="order-cell__body">
                                    <strong>
                                        {{ editing?.business_label }}
                                    </strong>
                                    <span class="tiny">
                                        {{
                                            editing?.volume ?? 'объём не указан'
                                        }}
                                    </span>
                                </span>
                            </li>
                            <li v-if="editing?.comment" class="order-cell__row">
                                <span class="order-cell__body">
                                    <span class="tiny">
                                        {{ editing?.comment }}
                                    </span>
                                </span>
                            </li>
                        </ul>
                        <p v-if="editing?.attachment_name" class="tiny">
                            <a :href="`/admin/leads/${editing?.id}/file`">
                                📎 {{ editing?.attachment_name }}
                            </a>
                        </p>
                        <p class="tiny">
                            Слова клиента не редактируются: заявка — документ
                            обращения, правки к ней пишутся комментарием.
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
