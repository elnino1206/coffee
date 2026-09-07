<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

/**
 * Журнал. Разметка и классы те же, что в остальных разделах админки.
 *
 * В отличие от заказов и заявок статью можно завести и удалить: это
 * собственный материал магазина, а не слова клиента.
 *
 * Адрес статьи при правке не показывается как поле — он задаётся один
 * раз при создании и дальше не меняется: по нему уже могли поставить
 * ссылку.
 */

type Row = {
    id: number;
    slug: string;
    title: string;
    tag: string;
    excerpt: string;
    body: string | null;
    cover_path: string | null;
    status: string;
    status_label: string;
    published_at: string | null;
    published_label: string | null;
    is_scheduled: boolean;
    has_body: boolean;
    author: string | null;
};

const props = defineProps<{
    articles: {
        data: Row[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
    tags: string[];
}>();

const search = ref(props.filters.q);
const status = ref(props.filters.status);

function reload(): void {
    router.get(
        '/admin/articles',
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

/** Открытая статья; `null` — панель закрыта, `'new'` — заведение новой. */
const editing = ref<Row | 'new' | null>(null);

const form = useForm({
    title: '',
    tag: '',
    excerpt: '',
    body: '',
    cover_path: '',
    status: 'draft',
    published_at: '',
});

function edit(article: Row): void {
    editing.value = article;

    form.defaults({
        title: article.title,
        tag: article.tag,
        excerpt: article.excerpt,
        body: article.body ?? '',
        cover_path: article.cover_path ?? '',
        status: article.status,
        published_at: article.published_at ?? '',
    });
    form.reset();
    form.clearErrors();
}

function create(): void {
    editing.value = 'new';

    form.defaults({
        title: '',
        tag: props.tags[0] ?? '',
        excerpt: '',
        body: '',
        cover_path: '',
        status: 'draft',
        published_at: '',
    });
    form.reset();
    form.clearErrors();
}

function save(): void {
    if (editing.value === null) {
        return;
    }

    const done = { preserveScroll: true, onSuccess: () => (editing.value = null) };

    if (editing.value === 'new') {
        form.post('/admin/articles', done);

        return;
    }

    form.patch(`/admin/articles/${editing.value.id}`, done);
}

/** Удаление подтверждается: восстановить статью неоткуда. */
const removing = ref<Row | null>(null);

function remove(): void {
    if (!removing.value) {
        return;
    }

    router.delete(`/admin/articles/${removing.value.id}`, {
        preserveScroll: true,
        onFinish: () => (removing.value = null),
    });
}
</script>

<template>
    <Head title="Журнал" />

    <AdminLayout title="Журнал" note="Статьи на витрине">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <h2>Статьи</h2>
                <div class="admin-filters admin-panel__aside">
                    <input
                        v-model="search"
                        class="field"
                        type="search"
                        placeholder="Заголовок, рубрика или анонс"
                    />
                    <select v-model="status" class="select">
                        <option value="">Все статусы</option>
                        <option
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                    <button class="btn btn--petrol btn--m" type="button" @click="create">
                        Новая статья
                    </button>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Статья</th>
                            <th>Рубрика</th>
                            <th>Текст</th>
                            <th>Публикация</th>
                            <th>Статус</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="article in articles.data" :key="article.id">
                            <td>
                                <strong>{{ article.title }}</strong>
                                <br />
                                <span class="tiny">/blog/{{ article.slug }}</span>
                            </td>
                            <td>{{ article.tag }}</td>
                            <td>
                                <span v-if="article.has_body" class="tiny">Написан</span>
                                <span v-else class="tiny">
                                    Нет текста — на витрине стоит пометка
                                </span>
                            </td>
                            <td>
                                <div>{{ article.published_label ?? '—' }}</div>
                                <span v-if="article.is_scheduled" class="tiny">
                                    отложена
                                </span>
                                <span v-else-if="article.author" class="tiny">
                                    {{ article.author }}
                                </span>
                            </td>
                            <td>
                                <span class="status" :class="`status--${article.status}`">
                                    {{ article.status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <button
                                        class="admin-btn"
                                        type="button"
                                        @click="edit(article)"
                                    >
                                        Изменить
                                    </button>
                                    <button
                                        class="admin-btn"
                                        type="button"
                                        @click="removing = article"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!articles.data.length">
                            <td colspan="6">
                                <div class="admin-empty">
                                    <strong>Статей не нашлось</strong>
                                    <span class="tiny">
                                        Проверьте фильтры — возможно, отбор слишком узкий.
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="articles.last_page > 1"
                class="admin-panel__head"
                style="justify-content: space-between"
            >
                <span class="tiny">
                    Страница {{ articles.current_page }} из {{ articles.last_page }} ·
                    всего {{ articles.total }}
                </span>
                <div class="admin-actions">
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!articles.prev_page_url"
                        @click="router.get(articles.prev_page_url!)"
                    >
                        Назад
                    </button>
                    <button
                        class="admin-btn"
                        type="button"
                        :disabled="!articles.next_page_url"
                        @click="router.get(articles.next_page_url!)"
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
                        <h2>
                            {{ editing === 'new' ? 'Новая статья' : 'Правка статьи' }}
                        </h2>
                        <p v-if="editing && editing !== 'new'" class="tiny">
                            /blog/{{ editing.slug }}
                        </p>
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
                        <span class="label__text">Заголовок</span>
                        <input v-model="form.title" class="field" type="text" />
                        <span v-if="form.errors.title" class="help help--error">
                            {{ form.errors.title }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Рубрика</span>
                        <!-- Список подсказывает уже использованные рубрики,
                             но не запрещает завести новую: по доменной
                             модели вопрос ещё открыт. -->
                        <input
                            v-model="form.tag"
                            class="field"
                            type="text"
                            list="article-tags"
                        />
                        <datalist id="article-tags">
                            <option v-for="tag in tags" :key="tag" :value="tag" />
                        </datalist>
                        <span v-if="form.errors.tag" class="help help--error">
                            {{ form.errors.tag }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Анонс</span>
                        <textarea
                            v-model="form.excerpt"
                            class="textarea"
                            placeholder="Две-три строки: о чём статья"
                        ></textarea>
                        <span v-if="form.errors.excerpt" class="help help--error">
                            {{ form.errors.excerpt }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Текст</span>
                        <textarea
                            v-model="form.body"
                            class="textarea"
                            rows="12"
                            placeholder="Абзацы разделяйте пустой строкой"
                        ></textarea>
                        <span class="help">
                            Пустая строка разделяет абзацы. Без текста на витрине
                            останется анонс и пометка, что материал в работе.
                        </span>
                        <span v-if="form.errors.body" class="help help--error">
                            {{ form.errors.body }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Обложка</span>
                        <input
                            v-model="form.cover_path"
                            class="field"
                            type="text"
                            placeholder="img/roaster.webp"
                        />
                        <span class="help">
                            Путь к файлу в папке снимков. Загрузка появится
                            вместе с файловым хранилищем.
                        </span>
                        <span v-if="form.errors.cover_path" class="help help--error">
                            {{ form.errors.cover_path }}
                        </span>
                    </label>

                    <label class="label">
                        <span class="label__text">Статус</span>
                        <select v-model="form.status" class="select">
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
                        <span class="label__text">Дата публикации</span>
                        <input
                            v-model="form.published_at"
                            class="field"
                            type="datetime-local"
                        />
                        <span class="help">
                            Пусто при публикации — встанет текущее время. Дата
                            в будущем откладывает выход: до неё статьи в
                            журнале не будет.
                        </span>
                        <span v-if="form.errors.published_at" class="help help--error">
                            {{ form.errors.published_at }}
                        </span>
                    </label>
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

        <div class="modal" :class="{ 'is-open': removing }" @click.self="removing = null">
            <div class="modal__box" role="dialog" aria-modal="true">
                <h3 class="h3">Удалить статью?</h3>
                <p class="tiny">
                    «{{ removing?.title }}» исчезнет из журнала. Восстановить
                    её будет неоткуда — если нужно просто убрать с витрины,
                    переведите в черновик.
                </p>
                <div class="cluster" style="margin-top: 16px">
                    <button class="btn btn--petrol btn--m" type="button" @click="remove">
                        Удалить
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="removing = null"
                    >
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
