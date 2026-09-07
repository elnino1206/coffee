<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import RailBar from '@/components/RailBar.vue';
import StorefrontFooter from '@/components/StorefrontFooter.vue';
import { useRail } from '@/composables/useRail';
import { formatPrice } from '@/lib/money';

/**
 * Подписка. Разметка и классы перенесены из прототипа
 * versions/v2-anim/subscription.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа: конструктор собран на сортах и ценах из базы, а
 * не на списке в разметке — иначе он показывал бы цену, которой в
 * каталоге уже нет. Оформление создаёт саму подписку; списания по
 * карте подключатся отдельно — до тех пор кофе не уезжает автоматически,
 * и страница говорит об этом прямо.
 */

interface Variant {
    id: number;
    title: string;
    price: number;
}

const props = defineProps<{
    coffee: { slug: string; name: string; variants: Variant[] }[];
    grinds: { value: string; label: string }[];
    frequencies: { value: number; label: string }[];
    discount: number;
    signedIn: boolean;
}>();

const slug = ref(props.coffee[0]?.slug ?? '');
const variantId = ref(props.coffee[0]?.variants.at(-1)?.id ?? null);
const grind = ref(props.grinds[0]?.value ?? 'whole');
const frequency = ref(props.frequencies.at(-1)?.value ?? 4);

const selected = computed(() =>
    props.coffee.find((item) => item.slug === slug.value),
);

/* Смена сорта сбрасывает вес: у другого сорта свои варианты, и прежний
   идентификатор указывал бы в пустоту. */
function pickCoffee(value: string): void {
    slug.value = value;
    variantId.value = selected.value?.variants.at(-1)?.id ?? null;
}

const variant = computed(() =>
    selected.value?.variants.find((item) => item.id === variantId.value),
);

const base = computed(() => variant.value?.price ?? 0);
const price = computed(() =>
    Math.round((base.value * (100 - props.discount)) / 100),
);
const saved = computed(() => base.value - price.value);

/* Оформление. Гостю подписку класть некуда — она принадлежит покупателю,
   поэтому его сначала отправляем на вход и возвращаем обратно. */
const placing = ref(false);

function subscribe(): void {
    if (!variantId.value) {
        return;
    }

    placing.value = true;

    router.post(
        '/subscription',
        {
            product_variant_id: variantId.value,
            grind: grind.value,
            frequency_weeks: frequency.value,
        },
        { onFinish: () => (placing.value = false) },
    );
}

const rail = ref<HTMLElement | null>(null);

const { current, go, progress } = useRail(rail);

/** Подписи остановок на шкале — по одной на панель, в порядке рельса. */
const stops = [
    'Подписка',
    'Как это работает',
    'Выгода',
    'Конструктор',
    'Вопросы',
];
</script>

<template>
    <Head title="Кофе по подписке" />

    <div ref="rail" class="rail">
        <div class="rail__panel" :class="{ 'is-current': current === 0 }">
            <section class="hero">
                <div class="hero__grid container">
                    <div class="hero__copy">
                        <p class="eyebrow">Кофе всегда свежий. Без забот.</p>
                        <h1 class="display">
                            Кофе по подписке — всегда свежий
                        </h1>
                        <p class="lead">
                            Свежеобжаренный кофе из specialty зерна доставляем
                            регулярно. Выбираете вы — мы заботимся о свежести.
                        </p>
                        <div class="cluster">
                            <button
                                class="btn btn--primary"
                                type="button"
                                @click="go(3)"
                            >
                                Настроить подписку →
                            </button>
                            <Link class="btn btn--ghost" href="/catalog/coffee">
                                Смотреть кофе
                            </Link>
                        </div>
                        <div class="trust-row">
                            <span class="trust-item"
                                >Пауза или отмена в любой момент</span
                            >
                            <span class="trust-item"
                                >Обжарка небольшими партиями</span
                            >
                            <span class="trust-item"
                                >−{{ discount }}% с каждого заказа</span
                            >
                        </div>
                    </div>
                    <div class="hero__visual">
                        <span class="blob blob--baby"></span>
                        <img
                            src="/img/subscription-hero.webp"
                            width="1152"
                            height="864"
                            fetchpriority="high"
                            decoding="async"
                            alt="Чашка кофе и гейзерная кофеварка на грифельной доске"
                        />
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 1 }">
            <section class="section">
                <div class="container">
                    <p class="eyebrow">Как это работает</p>
                    <h2 class="h2" style="margin-bottom: 24px">
                        Три шага до ритуала
                    </h2>
                    <div class="steps">
                        <article class="step">
                            <span class="step__n">1</span>
                            <h3 class="h3">Выбираете кофе</h3>
                            <p class="muted">
                                Объём, помол и частоту доставки — под ваш вкус и
                                ритм.
                            </p>
                        </article>
                        <article class="step">
                            <span class="step__n">2</span>
                            <h3 class="h3">Доставляем свежий</h3>
                            <p class="muted">
                                Обжариваем небольшими партиями и привозим к
                                нужной дате.
                            </p>
                        </article>
                        <article class="step">
                            <span class="step__n">3</span>
                            <h3 class="h3">Наслаждаетесь</h3>
                            <p class="muted">
                                Всегда свежий кофе без забот. Пауза или отмена —
                                в любой момент.
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 2 }">
            <section class="section section--ice">
                <div class="container">
                    <h2 class="h2" style="margin-bottom: 24px">
                        Разовая покупка vs подписка
                    </h2>
                    <div class="compare">
                        <div class="compare-card">
                            <p class="eyebrow">Разовая покупка</p>
                            <p class="h3">
                                {{ formatPrice(base) }}
                                <span class="tiny">{{ variant?.title }}</span>
                            </p>
                            <p class="muted">
                                Одна покупка · без обжарки под вас · без
                                регулярной доставки
                            </p>
                        </div>
                        <div class="compare-mid">Экономия {{ discount }}%</div>
                        <div class="compare-card compare-card--best">
                            <p class="eyebrow" style="color: var(--teal-ink)">
                                Подписка Al Jar
                            </p>
                            <p class="h3" style="color: #fff">
                                {{ formatPrice(price) }}
                                <span class="tiny">{{ variant?.title }}</span>
                            </p>
                            <p>
                                Свежая обжарка под вас · регулярная доставка ·
                                управление в 1 клик
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 3 }">
            <section class="section" id="constructor">
                <div class="constructor container">
                    <div>
                        <p class="eyebrow">Конструктор</p>
                        <h2 class="h3">Настройте свою подписку</h2>
                    </div>
                    <form @submit.prevent="subscribe">
                        <div class="constructor-grid">
                            <label class="label">
                                Кофе
                                <select
                                    class="select"
                                    :value="slug"
                                    @change="
                                        pickCoffee(
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        )
                                    "
                                >
                                    <option
                                        v-for="item in coffee"
                                        :key="item.slug"
                                        :value="item.slug"
                                    >
                                        {{ item.name }}
                                    </option>
                                </select>
                            </label>
                            <label class="label">
                                Объём
                                <select class="select" v-model="variantId">
                                    <option
                                        v-for="item in selected?.variants ?? []"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.title }}
                                    </option>
                                </select>
                            </label>
                            <label class="label">
                                Помол
                                <select class="select" v-model="grind">
                                    <option
                                        v-for="item in grinds"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="label full">
                                Частота
                                <select class="select" v-model="frequency">
                                    <option
                                        v-for="item in frequencies"
                                        :key="item.value"
                                        :value="item.value"
                                    >
                                        {{ item.label }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <p class="price">
                            {{ formatPrice(price) }}
                            <span class="tiny">
                                вместо {{ formatPrice(base) }} — экономия
                                {{ formatPrice(saved) }} с каждой доставки
                            </span>
                        </p>

                        <button
                            v-if="signedIn"
                            class="btn btn--primary"
                            type="submit"
                            :disabled="placing || !variantId"
                        >
                            Оформить подписку
                        </button>
                        <Link v-else class="btn btn--primary" href="/login">
                            Войти и оформить
                        </Link>
                        <p class="tiny">
                            Подписка появится в личном кабинете: там её можно
                            поставить на паузу или отменить в один клик.
                            Списания по карте подключаются отдельно — пока
                            отгрузку подтверждает менеджер, и деньги сами никуда
                            не уходят.
                        </p>
                    </form>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 4 }">
            <section class="section">
                <div class="container">
                    <h2 class="h2" style="margin-bottom: 16px">
                        Часто задаваемые вопросы
                    </h2>
                    <div class="accordion">
                        <details>
                            <summary>
                                Как отменить или приостановить подписку?
                            </summary>
                            <p>
                                В личном кабинете: «Пауза» или «Отменить». Перед
                                отменой покажем удерживающее предложение — без
                                тёмных паттернов, решение за вами.
                            </p>
                        </details>
                        <details>
                            <summary>
                                Можно ли изменить заказ или помол?
                            </summary>
                            <p>
                                Да. Сорт, объём, помол и частоту меняете до
                                следующей обжарки — обычно за несколько дней до
                                доставки.
                            </p>
                        </details>
                        <details>
                            <summary>Из какого кофе вы обжариваете?</summary>
                            <p>
                                Арабика под эспрессо и ливанский кофе, а также
                                лоты под фильтр. Актуальный список — в каталоге,
                                с профилем вкуса на каждой карточке.
                            </p>
                        </details>
                    </div>
                </div>
            </section>

            <StorefrontFooter />
        </div>
    </div>

    <RailBar :stops="stops" :current="current" :progress="progress" @go="go" />
</template>
