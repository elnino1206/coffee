<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatPrice } from '@/lib/money';

/**
 * Личный кабинет. Разметка и классы перенесены из прототипа
 * versions/v2-anim/account.html: дизайн утверждён, переписывать его на
 * другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа: заказы и подписки настоящие, из базы.
 * Реферальная программа в прототипе нарисована, но в приложении её ещё
 * нет — панель говорит об этом прямо, а не показывает чужой промокод.
 */

type Subscription = {
    id: number;
    number: string;
    product: string | null;
    variant: string | null;
    grind: string | null;
    frequency: string;
    next_delivery: string | null;
    charge: number | null;
    discount_percent: number;
    status: string;
    status_label: string;
    pill: string;
    pausable: boolean;
    resumable: boolean;
    cancelable: boolean;
};

defineProps<{
    customer: { name: string; email: string };
    subscriptions: Subscription[];
    orders: {
        number: string;
        placed_at: string;
        status: string;
        total: number;
        lines: {
            name: string;
            variant: string;
            grind: string | null;
            qty: number;
        }[];
        repeatable: boolean;
    }[];
}>();

function pause(id: number): void {
    router.post(
        `/account/subscriptions/${id}/pause`,
        {},
        { preserveScroll: true },
    );
}

function resume(id: number): void {
    router.post(
        `/account/subscriptions/${id}/resume`,
        {},
        { preserveScroll: true },
    );
}

/* ── Отмена ──────────────────────────────────────────────────────────
   По ТЗ 5.2 на отмене показывается удерживающее предложение. Это пауза,
   а не скидка вдогонку: человек чаще уезжает или не успевает допить, и
   остановка решает это честнее уговоров. */

const canceling = ref<Subscription | null>(null);
const reason = ref('');

function askCancel(subscription: Subscription): void {
    canceling.value = subscription;
    reason.value = '';
}

function confirmCancel(): void {
    if (!canceling.value) {
        return;
    }

    router.post(
        `/account/subscriptions/${canceling.value.id}/cancel`,
        { reason: reason.value || null },
        {
            preserveScroll: true,
            onSuccess: () => {
                canceling.value = null;
                reason.value = '';
            },
        },
    );
}

function pauseInstead(): void {
    if (!canceling.value) {
        return;
    }

    const id = canceling.value.id;
    canceling.value = null;
    pause(id);
}

function repeat(number: string): void {
    router.post(
        `/account/orders/${number}/repeat`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Личный кабинет" />

    <div class="container">
        <nav class="account-nav" aria-label="Кабинет">
            <a class="is-active" href="#overview">Обзор</a>
            <a href="#sub">Подписка</a>
            <a href="#orders">История заказов</a>
            <a href="#ref">Реферальная ссылка</a>
            <Link href="/contacts">Поддержка</Link>
        </nav>

        <div class="stack-l">
            <div id="overview">
                <p class="eyebrow">Личный кабинет</p>
                <h1 class="h2">Здравствуйте, {{ customer.name }}</h1>
                <p class="muted">
                    Управляйте подпиской и заказами в 1–2 клика.
                </p>
            </div>

            <section class="panel" id="sub">
                <div class="cluster" style="justify-content: space-between">
                    <h2 class="h3">Подписка</h2>
                    <Link class="tiny" href="/subscription">Условия</Link>
                </div>

                <p
                    v-if="!subscriptions.length"
                    class="muted"
                    style="margin-top: 12px"
                >
                    Подписок пока нет. Оформите на странице подписки — кофе
                    будет приезжать сам, свежей обжарки и со скидкой.
                </p>

                <div
                    v-for="item in subscriptions"
                    :key="item.id"
                    class="order-row"
                >
                    <div>
                        <strong>
                            {{ item.product ?? 'Позиция снята с продажи' }}
                        </strong>
                        <div class="tiny">
                            № {{ item.number }}
                            <template v-if="item.variant">
                                · {{ item.variant }}
                            </template>
                            <template v-if="item.grind">
                                · {{ item.grind }}
                            </template>
                            · {{ item.frequency }}
                        </div>

                        <div v-if="item.status === 'active'" class="sub-status">
                            {{ item.status_label }}
                        </div>
                        <div v-else>
                            <span class="badge badge--ice">
                                {{ item.status_label }}
                            </span>
                        </div>

                        <div class="tiny">
                            <template v-if="item.next_delivery">
                                Следующая отгрузка
                                {{ item.next_delivery }} ·
                            </template>
                            <template v-if="item.charge !== null">
                                {{ formatPrice(item.charge) }} за отгрузку,
                                скидка {{ item.discount_percent }}%
                            </template>
                            <template v-else>
                                Стоимость уточняется: позиции больше нет в
                                каталоге
                            </template>
                        </div>

                        <div v-if="item.status === 'blocked'" class="tiny">
                            Списание не прошло три раза подряд. Подписка
                            возобновится сама, когда оплата пройдёт.
                        </div>
                    </div>

                    <div class="cluster">
                        <button
                            v-if="item.pausable"
                            class="btn btn--ghost btn--s"
                            type="button"
                            @click="pause(item.id)"
                        >
                            Пауза
                        </button>
                        <button
                            v-if="item.resumable"
                            class="btn btn--ghost btn--s"
                            type="button"
                            @click="resume(item.id)"
                        >
                            Возобновить
                        </button>
                        <button
                            v-if="item.cancelable"
                            class="btn btn--ghost btn--s"
                            type="button"
                            @click="askCancel(item)"
                        >
                            Отменить
                        </button>
                    </div>
                </div>

                <div class="cluster" style="margin-top: 12px">
                    <Link class="btn btn--primary btn--m" href="/subscription">
                        {{
                            subscriptions.length
                                ? 'Оформить ещё'
                                : 'Посмотреть условия'
                        }}
                    </Link>
                </div>
            </section>

            <section class="panel" id="orders">
                <div class="cluster" style="justify-content: space-between">
                    <h2 class="h3">История заказов</h2>
                    <Link class="tiny" href="/catalog/coffee">В каталог</Link>
                </div>

                <p v-if="!orders.length" class="muted" style="margin-top: 12px">
                    Заказов пока нет. Первый появится здесь сразу после
                    оформления.
                </p>

                <div
                    v-for="order in orders"
                    :key="order.number"
                    class="order-row"
                >
                    <div>
                        <strong>{{ order.placed_at }}</strong>
                        <div class="tiny">
                            № {{ order.number }} ·
                            <template
                                v-for="(line, index) in order.lines"
                                :key="index"
                            >
                                <template v-if="index"> · </template>
                                {{ line.name }} · {{ line.variant
                                }}<template v-if="line.grind">
                                    · {{ line.grind }}</template
                                ><template v-if="line.qty > 1">
                                    × {{ line.qty }}</template
                                >
                            </template>
                        </div>
                        <div class="sub-status">{{ order.status }}</div>
                        <div class="tiny">{{ formatPrice(order.total) }}</div>
                    </div>
                    <button
                        class="btn btn--ghost btn--s"
                        type="button"
                        :disabled="!order.repeatable"
                        @click="repeat(order.number)"
                    >
                        Повторить
                    </button>
                </div>
            </section>

            <section class="panel" id="ref">
                <p class="eyebrow">Реферальная программа</p>
                <h2 class="h3">Пригласите друзей</h2>
                <p class="muted">
                    Программа готовится: ссылка и начисления появятся вместе с
                    бонусным счётом. Показывать код, который ещё ничего не даёт,
                    было бы обманом.
                </p>
            </section>

            <section class="panel">
                <h2 class="h3">Учётная запись</h2>
                <p class="muted" style="margin-top: 8px">
                    {{ customer.email }}
                </p>
                <div class="cluster" style="margin-top: 12px">
                    <a class="btn btn--ghost btn--m" href="/settings/profile">
                        Профиль
                    </a>
                    <a class="btn btn--ghost btn--m" href="/settings/security">
                        Безопасность
                    </a>
                    <Link
                        class="btn btn--ghost btn--m"
                        href="/logout"
                        method="post"
                        as="button"
                    >
                        Выйти
                    </Link>
                </div>
            </section>
        </div>
    </div>

    <div
        class="modal"
        :class="{ 'is-open': canceling !== null }"
        role="dialog"
        aria-modal="true"
        aria-label="Отмена подписки"
        @click.self="canceling = null"
    >
        <div class="modal__box">
            <div class="stack">
                <h3 class="h3">Отменить подписку {{ canceling?.number }}?</h3>
                <p class="muted">
                    Если дело в паузе — поставьте её вместо отмены. Подписка
                    остановится, условия и цена сохранятся, а вернуть её можно
                    одной кнопкой.
                </p>

                <label class="label">
                    <span class="label__text">
                        Почему уходите — по желанию
                    </span>
                    <textarea
                        class="textarea"
                        v-model="reason"
                        placeholder="Так мы поймём, что починить"
                    ></textarea>
                </label>

                <div class="cluster">
                    <button
                        v-if="canceling?.pausable"
                        class="btn btn--primary btn--m"
                        type="button"
                        @click="pauseInstead"
                    >
                        Лучше на паузу
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="confirmCancel"
                    >
                        Всё равно отменить
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="canceling = null"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
