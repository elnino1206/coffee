<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { formatPrice } from '@/lib/money';

/**
 * Каталог в админке. Разметка и классы перенесены из макета
 * versions/v2-anim/admin/products.html.
 *
 * Цена и остаток правятся по вариантам: одного поля «цена» у товара нет —
 * у килограмма и у 250 г они разные. В форме цена рублями, в базе копейки.
 *
 * Скрытая позиция остаётся в списке приглушённой: иначе про неё забывают
 * и потом ищут, почему товара нет в каталоге.
 */

type Variant = {
    id: number;
    title: string;
    price: number;
    stock: number;
    is_active: boolean;
};

type Row = {
    id: number;
    slug: string;
    name: string;
    image: string | null;
    type: string;
    type_label: string;
    category: string | null;
    origin: string | null;
    notes: string | null;
    roast: string | null;
    roast_label: string | null;
    brew_method: string | null;
    brew_label: string | null;
    is_featured: boolean;
    is_hidden: boolean;
    wholesale_available: boolean;
    rating: number;
    reviews: number;
    stock: number;
    price_from: number;
    variants: Variant[];
};

const props = defineProps<{
    products: {
        data: Row[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    filters: { q: string; roast: string; type: string };
    roasts: { value: string; label: string }[];
    methods: { value: string; label: string }[];
    types: { value: string; label: string }[];
}>();

const search = ref(props.filters.q);
const roast = ref(props.filters.roast);
const type = ref(props.filters.type);

function reload(): void {
    router.get(
        '/admin/products',
        {
            q: search.value || undefined,
            roast: roast.value || undefined,
            type: type.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

let typing: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(typing);
    typing = setTimeout(reload, 300);
});
watch([roast, type], reload);

/* ── Выдвижная панель правки ────────────────────────────────────── */

const editing = ref<Row | null>(null);

const form = useForm<{
    name: string;
    notes: string;
    roast: string;
    brew_method: string;
    is_featured: boolean;
    is_hidden: boolean;
    wholesale_available: boolean;
    variants: Record<
        number,
        { price: number; stock: number; is_active: boolean }
    >;
}>({
    name: '',
    notes: '',
    roast: '',
    brew_method: '',
    is_featured: false,
    is_hidden: false,
    wholesale_available: false,
    variants: {},
});

function edit(product: Row): void {
    editing.value = product;

    form.defaults({
        name: product.name,
        notes: product.notes ?? '',
        roast: product.roast ?? '',
        brew_method: product.brew_method ?? '',
        is_featured: product.is_featured,
        is_hidden: product.is_hidden,
        wholesale_available: product.wholesale_available,
        variants: Object.fromEntries(
            product.variants.map((variant) => [
                variant.id,
                {
                    price: variant.price,
                    stock: variant.stock,
                    is_active: variant.is_active,
                },
            ]),
        ),
    });
    form.reset();
    form.clearErrors();
}

function save(): void {
    if (!editing.value) {
        return;
    }

    form.patch(`/admin/products/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

/** Быстрое снятие с витрины и возврат — из строки, без открытия панели. */
function toggleHidden(product: Row): void {
    router.patch(
        `/admin/products/${product.id}`,
        {
            name: product.name,
            notes: product.notes ?? '',
            roast: product.roast ?? '',
            brew_method: product.brew_method ?? '',
            is_featured: product.is_featured,
            is_hidden: !product.is_hidden,
            wholesale_available: product.wholesale_available,
            variants: {},
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Товары" />

    <AdminLayout title="Товары" :note="`${products.total} позиций каталога`">
        <section class="admin-panel">
            <div class="admin-panel__head">
                <h2>Каталог</h2>
                <div class="admin-filters admin-panel__aside">
                    <input
                        class="field"
                        type="search"
                        placeholder="Название или регион"
                        v-model="search"
                    />
                    <select class="select" v-model="type">
                        <option value="">Все типы</option>
                        <option
                            v-for="item in types"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>
                    <select class="select" v-model="roast">
                        <option value="">Вся обжарка</option>
                        <option
                            v-for="item in roasts"
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
                            <th>Позиция</th>
                            <th>Обжарка</th>
                            <th>Способ</th>
                            <th class="num">Цена от</th>
                            <th class="num">Остаток</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            :class="{ 'is-hidden': product.is_hidden }"
                        >
                            <td>
                                <div class="admin-cell-product">
                                    <img
                                        v-if="product.image"
                                        :src="`/${product.image}`"
                                        alt=""
                                        loading="lazy"
                                        decoding="async"
                                    />
                                    <div>
                                        <strong>{{ product.name }}</strong>
                                        <span class="tiny">
                                            {{
                                                product.origin ??
                                                product.category ??
                                                product.type_label
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ product.roast_label ?? '—' }}</td>
                            <td>{{ product.brew_label ?? '—' }}</td>
                            <td class="num">
                                {{ formatPrice(product.price_from) }}
                            </td>
                            <td class="num">{{ product.stock }}</td>
                            <td>
                                <div class="admin-actions">
                                    <button
                                        class="admin-btn"
                                        type="button"
                                        @click="edit(product)"
                                    >
                                        Изменить
                                    </button>
                                    <button
                                        class="admin-btn admin-btn--ghost"
                                        type="button"
                                        @click="toggleHidden(product)"
                                    >
                                        {{
                                            product.is_hidden
                                                ? 'Вернуть'
                                                : 'Скрыть'
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!products.data.length">
                            <td colspan="6">
                                <div class="admin-empty">
                                    <strong>Позиций не нашлось</strong>
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
        </section>

        <div class="drawer" :hidden="!editing">
            <div class="drawer__scrim" @click="editing = null"></div>
            <form class="drawer__panel" @submit.prevent="save">
                <div class="drawer__head">
                    <div>
                        <h2>{{ editing?.name }}</h2>
                        <p class="tiny">
                            {{ editing?.type_label
                            }}<template v-if="editing?.category">
                                · {{ editing?.category }}</template
                            >
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
                        <span class="label__text">Название на витрине</span>
                        <input class="field" v-model="form.name" />
                        <span v-if="form.errors.name" class="help help--error">
                            {{ form.errors.name }}
                        </span>
                    </label>

                    <template v-if="editing?.type === 'coffee'">
                        <label class="label">
                            <span class="label__text">Вкусовые ноты</span>
                            <input class="field" v-model="form.notes" />
                        </label>

                        <label class="label">
                            <span class="label__text">Обжарка</span>
                            <select class="select" v-model="form.roast">
                                <option
                                    v-for="item in roasts"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>

                        <label class="label">
                            <span class="label__text"
                                >Способ приготовления</span
                            >
                            <select class="select" v-model="form.brew_method">
                                <option
                                    v-for="item in methods"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                        </label>
                    </template>

                    <div>
                        <p class="tiny">Варианты: цена в рублях и остаток</p>
                        <div
                            v-for="variant in editing?.variants ?? []"
                            :key="variant.id"
                            class="drawer__row"
                            style="
                                grid-template-columns: 1fr auto auto;
                                align-items: end;
                            "
                        >
                            <span class="tiny">{{ variant.title }}</span>
                            <label class="label">
                                <span class="label__text">Цена</span>
                                <input
                                    class="field"
                                    type="number"
                                    min="0"
                                    style="width: 110px"
                                    v-model.number="
                                        form.variants[variant.id].price
                                    "
                                />
                            </label>
                            <label class="label">
                                <span class="label__text">Остаток</span>
                                <input
                                    class="field"
                                    type="number"
                                    min="0"
                                    style="width: 90px"
                                    v-model.number="
                                        form.variants[variant.id].stock
                                    "
                                />
                            </label>
                        </div>
                    </div>

                    <label class="checkbox">
                        <input type="checkbox" v-model="form.is_featured" />
                        <span>Показывать в блоке «Свежая обжарка»</span>
                    </label>

                    <label class="checkbox">
                        <input type="checkbox" v-model="form.is_hidden" />
                        <span>Скрыть с витрины</span>
                    </label>

                    <label class="checkbox">
                        <input
                            type="checkbox"
                            v-model="form.wholesale_available"
                        />
                        <span>Доступно для опта</span>
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
    </AdminLayout>
</template>
