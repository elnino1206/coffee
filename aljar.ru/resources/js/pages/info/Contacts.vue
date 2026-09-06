<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

/**
 * Контакты. Разметка и классы перенесены из прототипа
 * versions/v2-anim/contacts.html.
 *
 * Форма пока не отправляется — как и оптовая: по ТЗ 5.4 это два разных
 * потока с разными адресатами, и оба появятся вместе с приёмом обращений.
 * Телефон и почта рядом работают по-настоящему.
 */
</script>

<template>
    <Head title="Контакты" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> · Контакты
            </nav>
            <h1 class="h2">Контакты</h1>
            <p class="lead">
                Розничные обращения. Если вы кофейня, ресторан, отель или офис —
                напишите через форму
                <Link href="/wholesale">для бизнеса</Link>.
            </p>
        </div>

        <div class="heritage" style="padding-bottom: 72px">
            <div class="panel stack">
                <p>
                    <strong>Телефон</strong><br />
                    <a href="tel:+79851379235">8 (985) 137-92-35</a><br />
                    <a href="tel:+79067674725">8 (906) 767-47-25</a>
                </p>
                <p>
                    <strong>Почта</strong><br />
                    <a href="mailto:hello@aljar.ru">hello@aljar.ru</a>
                </p>
                <p class="tiny">
                    Телефон — основной способ связи: во всех формах сайта он
                    обязателен, почта по желанию.
                </p>

                <form class="stack" @submit.prevent>
                    <label class="label">
                        <span class="label__text">Имя</span>
                        <input class="field" name="name" required />
                    </label>
                    <label class="label">
                        <span class="label__text">
                            Телефон
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input class="field" name="phone" type="tel" required />
                    </label>
                    <label class="label">
                        <span class="label__text">Сообщение</span>
                        <textarea
                            class="textarea"
                            name="message"
                            required
                        ></textarea>
                    </label>
                    <label class="checkbox">
                        <input type="checkbox" required />
                        <span>
                            Согласен с
                            <Link href="/legal#privacy">политикой</Link>
                        </span>
                    </label>
                    <button
                        class="btn btn--petrol btn--m"
                        type="submit"
                        disabled
                    >
                        Отправить
                    </button>
                    <p class="tiny">
                        Приём обращений через форму подключим вместе с админкой:
                        обращение должно попадать к тому, кто на него ответит.
                        Пока напишите или позвоните — телефон и почта выше.
                    </p>
                </form>
            </div>

            <div
                class="panel"
                style="
                    min-height: 320px;
                    background: var(--sand);
                    display: grid;
                    place-items: center;
                "
            >
                <p class="tiny" style="text-align: center">
                    Карта шоурума и точки самовывоза появится, когда адрес будет
                    подтверждён.
                </p>
            </div>
        </div>
    </div>
</template>
