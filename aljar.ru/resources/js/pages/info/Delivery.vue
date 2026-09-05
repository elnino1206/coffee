<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

/**
 * Доставка и оплата. Разметка и классы перенесены из прототипа
 * versions/v2-anim/delivery.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Страница статическая — данных с сервера не требует.
 */
</script>

<template>
    <Head title="Доставка и оплата" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> · Доставка и оплата
            </nav>
            <h1 class="h2">Доставка и оплата</h1>
            <p class="lead">
                Прозрачные условия. Финальные службы и тарифы уточняются с
                разработчиком — структура экрана уже такая, какой будет на
                сайте.
            </p>
        </div>

        <div class="steps" style="margin-bottom: 48px">
            <article class="step">
                <h3 class="h3">Курьер</h3>
                <p class="muted">
                    1–2 дня. 350 ₽, бесплатно от 3 000 ₽. Позвоним перед
                    выездом.
                </p>
            </article>
            <article class="step">
                <h3 class="h3">Пункт выдачи</h3>
                <p class="muted">
                    1–3 дня. Бесплатно. Хранение — по правилам ПВЗ.
                </p>
            </article>
            <article class="step">
                <h3 class="h3">Почта</h3>
                <p class="muted">
                    3–7 дней. По тарифу перевозчика. Для отдалённых регионов.
                </p>
            </article>
        </div>

        <section class="panel" style="margin-bottom: 64px">
            <h2 class="h3">Оплата</h2>
            <p class="muted" style="margin: 8px 0 16px">
                Карта онлайн (Visa, Mastercard, Мир) — способ по умолчанию. СБП.
                Для подписки — рекуррентные списания: конкретный провайдер
                подключается на этапе Laravel.
            </p>
            <p>
                Телефон обязателен на чекауте: подтверждение заказа и статус
                доставки. Email — по желанию.
            </p>
        </section>
    </div>
</template>
