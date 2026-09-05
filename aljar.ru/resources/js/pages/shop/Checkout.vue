<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { formatPrice } from '@/lib/money';

type ShipMethod = {
    value: string;
    label: string;
    note: string;
    price: number;
    requires_address: boolean;
};

const props = defineProps<{
    items: {
        name: string | null;
        image: string | null;
        variant: string | null;
        grind: string | null;
        roast: string | null;
        subscribe: boolean;
        qty: number;
        total: number;
    }[];
    goods_total: number;
    ship_methods: ShipMethod[];
    pay_methods: { value: string; label: string; note: string }[];
}>();

const ship = ref(props.ship_methods[0]?.value ?? 'courier');
const pay = ref(props.pay_methods[0]?.value ?? 'card');

const selectedShip = computed(() =>
    props.ship_methods.find((m) => m.value === ship.value),
);
const delivery = computed(() => selectedShip.value?.price ?? 0);
const total = computed(() => props.goods_total + delivery.value);
</script>

<template>
    <Head title="Оформление заказа" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> ·
                <Link href="/cart">Корзина</Link> · Оформление
            </nav>
            <h1 class="h2">Оформление заказа</h1>
        </div>

        <!-- Порядковый номер шага берём из --i: анимация появления
             читает его как задержку, и точки проступают по очереди. -->
        <div class="progress">
            <span class="dot is-done" style="--i: 0">1</span>
            <span class="is-done">Корзина</span>
            →
            <span class="dot is-current" style="--i: 1">2</span>
            <span class="is-current">Доставка</span>
            →
            <span class="dot" style="--i: 2">3</span> Оплата
        </div>

        <Form
            action="/checkout"
            method="post"
            class="checkout"
            v-slot="{ errors, processing }"
        >
            <div class="stack">
                <section class="panel">
                    <h2 class="h3">Способ доставки</h2>
                    <div class="pay-options" style="margin-top: 12px">
                        <label
                            v-for="method in ship_methods"
                            :key="method.value"
                            class="pay-option"
                            :class="{ 'is-selected': ship === method.value }"
                        >
                            <input
                                type="radio"
                                name="ship_method"
                                :value="method.value"
                                v-model="ship"
                            />
                            <span>
                                <strong>{{ method.label }}</strong
                                ><br />
                                <span class="tiny">
                                    {{ method.note }} ·
                                    {{
                                        method.price
                                            ? formatPrice(method.price)
                                            : 'бесплатно'
                                    }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.ship_method" />
                </section>

                <section class="panel">
                    <h2 class="h3">Контакты</h2>
                    <div class="form-grid" style="margin-top: 12px">
                        <label class="label">
                            <span class="label__text"
                                >Телефон
                                <span class="req" aria-hidden="true"
                                    >*</span
                                ></span
                            >
                            <input
                                class="field"
                                name="phone"
                                type="tel"
                                autocomplete="tel"
                                placeholder="+7 9XX XXX-XX-XX"
                                required
                            />
                            <InputError :message="errors.phone" />
                        </label>
                        <label class="label">
                            Имя
                            <input
                                class="field"
                                name="contact_name"
                                autocomplete="name"
                            />
                            <InputError :message="errors.contact_name" />
                        </label>
                        <label class="label">
                            Email (необязательно)
                            <input
                                class="field"
                                name="email"
                                type="email"
                                placeholder="you@email.ru"
                            />
                            <InputError :message="errors.email" />
                        </label>
                        <label
                            class="label full"
                            v-if="selectedShip?.requires_address"
                        >
                            Адрес доставки
                            <input
                                class="field"
                                name="address"
                                placeholder="Город, улица, дом, квартира"
                            />
                            <InputError :message="errors.address" />
                        </label>
                        <label class="label full">
                            Комментарий
                            <textarea
                                class="textarea"
                                name="comment"
                                placeholder="Домофон, пожелания к доставке"
                            ></textarea>
                        </label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="h3">Способ оплаты</h2>
                    <div class="pay-options" style="margin-top: 12px">
                        <label
                            v-for="method in pay_methods"
                            :key="method.value"
                            class="pay-option"
                            :class="{ 'is-selected': pay === method.value }"
                        >
                            <input
                                type="radio"
                                name="pay_method"
                                :value="method.value"
                                v-model="pay"
                            />
                            <span>
                                <strong>{{ method.label }}</strong
                                ><br />
                                <span class="tiny">{{ method.note }}</span>
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.pay_method" />
                    <p class="tiny" style="margin-top: 8px">
                        Списание подключается следующим шагом: сейчас заказ
                        принимается без оплаты.
                    </p>
                </section>
            </div>

            <aside class="summary">
                <h2 class="h3">Ваш заказ</h2>
                <div>
                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="line-item"
                    >
                        <img
                            :src="`/${item.image}`"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                        <div>
                            <strong
                                >{{ item.name
                                }}<template v-if="item.qty > 1">
                                    × {{ item.qty }}</template
                                ></strong
                            >
                            <div class="tiny">
                                {{ item.variant
                                }}<template v-if="item.roast">
                                    · {{ item.roast }}</template
                                ><template v-if="item.grind">
                                    · {{ item.grind }}</template
                                ><template v-if="item.subscribe">
                                    · Подписка</template
                                >
                            </div>
                        </div>
                        <div class="price">{{ formatPrice(item.total) }}</div>
                    </div>
                </div>
                <div class="totals">
                    <div>
                        <span>Товары</span
                        ><span>{{ formatPrice(goods_total) }}</span>
                    </div>
                    <div>
                        <span>Доставка</span>
                        <span>{{
                            delivery ? formatPrice(delivery) : 'бесплатно'
                        }}</span>
                    </div>
                    <div class="grand">
                        <span>Итого</span><span>{{ formatPrice(total) }}</span>
                    </div>
                </div>
                <label class="checkbox" style="margin: 12px 0">
                    <input
                        type="checkbox"
                        name="agreement"
                        value="1"
                        required
                    />
                    <span
                        >Согласен с офертой и политикой конфиденциальности</span
                    >
                </label>
                <InputError :message="errors.agreement" />
                <button
                    class="btn btn--petrol btn--block"
                    type="submit"
                    :disabled="processing"
                >
                    Оформить заказ
                </button>
                <p class="tiny">
                    Все данные защищены и используются только для оформления
                    заказа.
                </p>
            </aside>
        </Form>
    </div>
</template>
