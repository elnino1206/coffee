<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { GRINDS } from '@/lib/grinds';
import { formatPrice } from '@/lib/money';
import { animateNumber } from '@/lib/motion';

/**
 * Выбор варианта перед добавлением в корзину. Перенесено из прототипа
 * versions/v2-anim/js/main.js — разметка и классы те же.
 *
 * Быстрая кнопка на карточке иначе молча кладёт 250 г в зёрнах, и
 * покупатель узнаёт об этом уже в корзине.
 *
 * Обжарка вынесена в выбор наравне с помолом — бренд обжаривает под
 * заказ, а степень из карточки товара берётся как значение по умолчанию.
 *
 * Цена считается от варианта, а не от множителя к базовой: здесь каждый
 * вес — отдельный ProductVariant со своей ценой.
 */

const SUBSCRIBE_DISCOUNT = 0.1;

const ROASTS = [
    { id: 'light', label: 'Светлая' },
    { id: 'medium', label: 'Средняя' },
    { id: 'dark', label: 'Тёмная' },
];

export type ModalVariant = {
    id: number;
    title: string;
    price: number;
    in_stock: boolean;
};

export type ModalProduct = {
    slug: string;
    name: string;
    notes: string | null;
    image: string | null;
    species: string | null;
    roast: string | null;
    roast_value: string | null;
    variants: ModalVariant[];
};

const props = defineProps<{ product: ModalProduct | null }>();
const emit = defineEmits<{ close: [] }>();

const variantId = ref<number | null>(null);
const grind = ref('whole');
const roast = ref('medium');
const subscribe = ref(false);
const adding = ref(false);
const priceEl = ref<HTMLElement | null>(null);
const dialog = ref<HTMLElement | null>(null);

/** Показанная сумма — чтобы прокрутить число от неё, а не от нуля. */
let shown: number | null = null;

const variant = computed(() => props.product?.variants.find((v) => v.id === variantId.value) ?? null);
const base = computed(() => variant.value?.price ?? props.product?.variants[0]?.price ?? 0);
const price = computed(() =>
    subscribe.value ? Math.round(base.value * (1 - SUBSCRIBE_DISCOUNT)) : base.value,
);

watch(
    () => props.product,
    (product) => {
        if (!product) {
return;
}

        /* Предвыбран первый вариант в наличии: предлагать то, чего нет
           на складе, — тупик на ровном месте. */
        variantId.value = (product.variants.find((v) => v.in_stock) ?? product.variants[0])?.id ?? null;
        grind.value = 'whole';
        /* Обжарка из карточки — значение по умолчанию, а не запрет:
           покупатель может попросить другую. */
        roast.value = product.roast_value ?? 'medium';
        subscribe.value = false;
        shown = null;

        nextTick(() => {
            if (priceEl.value) {
priceEl.value.textContent = formatPrice(price.value);
}

            shown = price.value;
            dialog.value?.querySelector<HTMLInputElement>('input:checked')?.focus();
        });
    },
);

watch(price, (next) => {
    if (!priceEl.value) {
return;
}

    if (shown === null) {
priceEl.value.textContent = formatPrice(next);
} else {
animateNumber(priceEl.value, shown, next, formatPrice, 400);
}

    shown = next;
});

function submit(): void {
    if (variantId.value === null) {
return;
}

    router.post(
        '/cart',
        {
            product_variant_id: variantId.value,
            grind: grind.value,
            roast: roast.value,
            subscribe: subscribe.value,
            qty: 1,
        },
        {
            preserveScroll: true,
            onStart: () => (adding.value = true),
            onFinish: () => {
                adding.value = false;
                emit('close');
            },
        },
    );
}
</script>

<template>
    <!-- Клик по затемнению и Escape закрывают — окно не удерживает
         человека, который передумал. -->
    <div
        v-if="product"
        class="modal is-open"
        data-add-modal
        @click.self="emit('close')"
        @keydown.esc="emit('close')"
    >
        <div
            ref="dialog"
            class="modal__box modal__box--add"
            role="dialog"
            aria-modal="true"
            aria-labelledby="add-modal-title"
        >
            <div class="add-modal__head">
                <img :src="`/${product.image}`" :alt="product.name" />
                <div>
                    <h3 id="add-modal-title" class="h3">{{ product.name }}</h3>
                    <p class="tiny">{{ product.notes }}</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="stack">
                    <fieldset class="opt-group">
                        <legend class="opt-group__label">Вес</legend>
                        <div class="opt-row">
                            <label v-for="item in product.variants" :key="item.id" class="opt">
                                <input v-model="variantId" type="radio" name="weight" :value="item.id" :disabled="!item.in_stock" />
                                <span>{{ item.title }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="opt-group">
                        <legend class="opt-group__label">Обжарка</legend>
                        <div class="opt-row">
                            <label v-for="item in ROASTS" :key="item.id" class="opt">
                                <input v-model="roast" type="radio" name="roast" :value="item.id" />
                                <span>{{ item.label }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="opt-group">
                        <legend class="opt-group__label">Помол</legend>
                        <div class="opt-row">
                            <label v-for="item in GRINDS" :key="item.id" class="opt">
                                <input v-model="grind" type="radio" name="grind" :value="item.id" />
                                <span>{{ item.label }}</span>
                            </label>
                        </div>
                    </fieldset>

                    <label class="checkbox">
                        <input v-model="subscribe" type="checkbox" name="subscribe" />
                        <span>Оформить подпиской — −10% и свежая обжарка к дате доставки</span>
                    </label>
                </div>

                <div class="add-modal__foot">
                    <div class="add-modal__price">
                        <span ref="priceEl" class="price"></span>
                        <span v-if="subscribe" class="tiny">
                            Вместо {{ formatPrice(base) }} — экономия {{ formatPrice(base - price) }}
                        </span>
                        <span v-else class="tiny">{{ product.roast }} · {{ product.species }}</span>
                    </div>
                    <button class="btn btn--primary" type="submit" :disabled="adding || variantId === null">
                        В корзину
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
