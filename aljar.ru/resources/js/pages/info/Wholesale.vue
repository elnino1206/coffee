<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

/**
 * Для бизнеса. Разметка и классы перенесены из прототипа
 * versions/v2-anim/wholesale.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Форма пока не отправляется. По ТЗ 5.4 оптовая заявка идёт отдельным
 * потоком: своя таблица, свой адресат, уведомление менеджеру почтой и в
 * Telegram. Всё это появится вместе с приёмом заявок — до тех пор кнопка
 * выключена, а не делает вид, что письмо ушло.
 */
</script>

<template>
    <Head title="Оптовым покупателям" />

    <section class="hero">
        <div class="hero__grid container">
            <div class="hero__copy">
                <p class="eyebrow">Оптовым покупателям</p>
                <h1 class="display">Кофе для кофеен, ресторанов и офисов</h1>
                <p class="lead">
                    Стабильный профиль обжарки, поставки под объём, логистика
                    без сюрпризов. Это не розничная витрина — здесь говорим о
                    партиях, сроках и условиях.
                </p>
                <a class="btn btn--primary" href="#lead">Оставить заявку</a>
            </div>
            <div class="hero__visual">
                <span class="blob blob--sand"></span>
                <img
                    src="/img/beans.webp"
                    width="1152"
                    height="864"
                    fetchpriority="high"
                    decoding="async"
                    alt="Свежеобжаренные зёрна"
                />
            </div>
        </div>
    </section>

    <section class="section section--sand">
        <div class="container">
            <div class="steps">
                <article class="step">
                    <h3 class="h3">Кофейни</h3>
                    <p class="muted">
                        Подбор профиля под концепцию заведения. Стабильность от
                        партии к партии.
                    </p>
                </article>
                <article class="step">
                    <h3 class="h3">HoReCa</h3>
                    <p class="muted">
                        Рестораны и отели: сроки поставки, фасовка под кухню и
                        бар.
                    </p>
                </article>
                <article class="step">
                    <h3 class="h3">Офисы</h3>
                    <p class="muted">
                        Программы поставки под уровень потребления команды.
                    </p>
                </article>
            </div>
            <p class="tiny" style="margin-top: 20px">
                Минимальная партия и цены от объёма уточняются менеджером — на
                текущем сайте цифр нет, в новом они появятся, как только
                заказчик их закрепит. Ориентир: от 10 кг.
            </p>
        </div>
    </section>

    <section class="section" id="lead">
        <div class="container" style="max-width: 820px">
            <p class="eyebrow">Заявка для отдела продаж B2B</p>
            <h2 class="h2">Расскажите о задаче</h2>
            <p class="muted" style="margin-bottom: 20px">
                Это не общая форма «Напишите нам». Заявку получит менеджер по
                опту, не поддержка розницы.
            </p>

            <form class="panel stack" @submit.prevent>
                <div class="form-grid">
                    <label class="label">
                        <span class="label__text">
                            Имя
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input class="field" name="name" required />
                    </label>
                    <label class="label">
                        <span class="label__text">
                            Компания
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input class="field" name="company" required />
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
                        <input
                            class="field"
                            name="phone"
                            type="tel"
                            required
                            placeholder="+7 …"
                        />
                    </label>
                    <label class="label">
                        Email (необязательно)
                        <input class="field" name="email" type="email" />
                    </label>
                    <label class="label">
                        Тип бизнеса
                        <select class="select" name="type">
                            <option>Кофейня</option>
                            <option>Ресторан</option>
                            <option>Отель</option>
                            <option>Офис</option>
                            <option>Другое</option>
                        </select>
                    </label>
                    <label class="label">
                        Интересующий объём / позиции
                        <input
                            class="field"
                            name="volume"
                            placeholder="Например, от 10 кг в месяц, эспрессо-смесь"
                        />
                    </label>
                    <label class="label full">
                        Комментарий
                        <textarea
                            class="textarea"
                            name="comment"
                            maxlength="120"
                            placeholder="Как вы пьёте кофе?"
                        ></textarea>
                    </label>
                    <label class="label full">
                        Файл (спецификация, бриф) — необязательно
                        <input
                            class="field field--file"
                            type="file"
                            name="file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png"
                        />
                    </label>
                </div>
                <label class="checkbox">
                    <input type="checkbox" required />
                    <span>
                        Согласен на обработку персональных данных и с
                        <Link href="/delivery"
                            >политикой конфиденциальности</Link
                        >
                    </span>
                </label>
                <button class="btn btn--petrol" type="submit" disabled>
                    Отправить заявку
                </button>
                <p class="tiny">
                    Приём заявок подключим вместе с админкой: заявка идёт
                    отдельным потоком от розничных обращений и уходит менеджеру
                    по опту почтой и в Telegram. Пока форма показывает поля, но
                    ничего не отправляет — обещать отправку и потерять заявку
                    хуже, чем честно сказать.
                </p>
                <p class="tiny">
                    Срочно — звоните:
                    <a href="tel:+79851379235">8 (985) 137-92-35</a>.
                </p>
            </form>
        </div>
    </section>
</template>
