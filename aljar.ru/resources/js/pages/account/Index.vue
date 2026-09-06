<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { formatPrice } from '@/lib/money';

/**
 * Личный кабинет. Разметка и классы перенесены из прототипа
 * versions/v2-anim/account.html: дизайн утверждён, переписывать его на
 * другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа: заказы настоящие, из базы. Подписка и
 * реферальная программа в прототипе нарисованы, но в приложении их ещё
 * нет — панели говорят об этом прямо, а не показывают выдуманную
 * доставку «29 августа» и чужой промокод.
 */

defineProps<{
    customer: { name: string; email: string };
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
                </div>
                <p class="muted" style="margin-top: 12px">
                    Подписок пока нет: оформление подключается вместе со
                    списаниями по карте. Здесь появятся дата следующей доставки,
                    состав и кнопки паузы и отмены.
                </p>
                <div class="cluster" style="margin-top: 12px">
                    <Link class="btn btn--primary btn--m" href="/subscription">
                        Посмотреть условия
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
</template>
