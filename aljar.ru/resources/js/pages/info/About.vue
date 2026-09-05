<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import RailBar from '@/components/RailBar.vue';
import StorefrontFooter from '@/components/StorefrontFooter.vue';
import { useRail } from '@/composables/useRail';

/**
 * О бренде. Страница собрана как главная — горизонтальным рельсом:
 * секции стоят в ряд, колесо листает их по одной, снизу идёт шкала.
 *
 * Сценарий «От зерна к чашке» раньше жил здесь запиненной вертикальной
 * сценой. Две механики на одной странице не уживаются: рельс забирает
 * колесо себе, и пиннинг внутри панели остаётся без прокрутки, которая
 * его двигала. Поэтому пять кадров сценария стали пятью панелями
 * рельса — история осталась той же и той же длины, сменился способ её
 * листать.
 *
 * Тексты, снимки и классы блоков перенесены без изменений.
 */

const rail = ref<HTMLElement | null>(null);

const { current, go, progress } = useRail(rail);

/** Подписи остановок на шкале — по одной на панель, в порядке рельса. */
const stops = [
    'История',
    'Ценности',
    'Производство',
    'Плантация',
    'Отбор',
    'Обжарка',
    'Фасовка',
    'Ритуал',
];
</script>

<template>
    <Head title="О бренде" />

    <div ref="rail" class="rail">
        <div class="rail__panel" :class="{ 'is-current': current === 0 }">
            <section class="hero">
                <div class="hero__grid container">
                    <div class="hero__copy">
                        <p class="eyebrow">О бренде</p>
                        <h1 class="display">История семьи и ритуала</h1>
                        <p class="lead">
                            С XVI века кофе в Ливане — часть культуры, знак
                            уважения и живая традиция. Al Jar продолжает её в
                            современном формате: семейный бренд, опыт, внимание
                            к деталям.
                        </p>
                    </div>
                    <div class="hero__visual">
                        <span class="blob blob--sand"></span>
                        <img
                            src="/img/dallah.webp"
                            width="1152"
                            height="864"
                            fetchpriority="high"
                            decoding="async"
                            alt="Медная турка — ритуал восточного кофе"
                        />
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 1 }">
            <section class="section section--sand">
                <div class="container">
                    <p class="eyebrow">Ценности</p>
                    <h2 class="h2" style="margin-bottom: 24px">
                        Как мы работаем
                    </h2>
                    <div class="steps">
                        <article class="step">
                            <h3 class="h3">Честность</h3>
                            <p class="muted">
                                Происхождение партии, степень обжарки, вкусовой
                                профиль — на виду, не в подвале карточки.
                            </p>
                        </article>
                        <article class="step">
                            <h3 class="h3">Ритуал</h3>
                            <p class="muted">
                                Кофе — способ собраться вместе. Мы уважаем гостя
                                так же, как хороший хозяин.
                            </p>
                        </article>
                        <article class="step">
                            <h3 class="h3">Ремесло</h3>
                            <p class="muted">
                                Небольшие партии, стабильный профиль, никакой
                                агрессии в продаже.
                            </p>
                        </article>
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 2 }">
            <section class="section">
                <div class="heritage container">
                    <div class="stack" data-reveal>
                        <p class="eyebrow">Производство</p>
                        <h2 class="h2">Полный цикл в России</h2>
                        <p class="lead">
                            Закупка сырого зерна, обжарка до 20 тонн в месяц,
                            фасовка. Не прячем цех: показываем, откуда зерно и
                            как идёт обжарка.
                        </p>
                        <p class="muted">
                            Специализация: эспрессо и ливанский кофе. Также —
                            гейзер, френч-пресс, фильтр, турка (арабская и
                            турецкая). На российский рынок бренд пришёл с
                            многолетним опытом работы на Ближнем Востоке.
                        </p>
                    </div>
                    <div class="torn" data-reveal>
                        <img
                            src="/img/roaster.webp"
                            width="1152"
                            height="864"
                            loading="lazy"
                            decoding="async"
                            alt="Обжарочный барабан"
                            style="
                                height: 380px;
                                width: 100%;
                                object-fit: cover;
                            "
                        />
                    </div>
                </div>
            </section>
        </div>

        <!-- Сценарий «От зерна к чашке»: пять кадров — пять панелей.
             Фон чередуется, чтобы соседние остановки не сливались. -->
        <div class="rail__panel" :class="{ 'is-current': current === 3 }">
            <section class="section section--sand">
                <article class="journey__slide container">
                    <figure class="journey__media torn">
                        <img
                            src="/img/plantation.webp"
                            width="1280"
                            height="720"
                            loading="lazy"
                            decoding="async"
                            alt="Кофейные деревья на плантации"
                        />
                    </figure>
                    <div class="journey__copy">
                        <p class="eyebrow">Кадр 1 из 5</p>
                        <h2 class="h2">Всё начинается с земли</h2>
                        <p class="lead">
                            Бразилия, Колумбия, Эфиопия. Высота, тень и ручной
                            сбор решают, каким зерно приедет в цех.
                        </p>
                        <p class="journey__metric">
                            <span data-count-to="1800">1800</span
                            ><small>метров над уровнем моря</small>
                        </p>
                    </div>
                </article>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 4 }">
            <section class="section">
                <article class="journey__slide container">
                    <figure class="journey__media torn">
                        <img
                            src="/img/beans.webp"
                            width="1152"
                            height="864"
                            loading="lazy"
                            decoding="async"
                            alt="Зелёное кофейное зерно до обжарки"
                        />
                    </figure>
                    <div class="journey__copy">
                        <p class="eyebrow">Кадр 2 из 5</p>
                        <h2 class="h2">Отбор партии</h2>
                        <p class="lead">
                            Скрин 16/18 — размер зерна, при котором обжарка идёт
                            ровно. Дефекты выбираем вручную.
                        </p>
                        <p class="journey__metric">
                            16/18<small>размер скрина</small>
                        </p>
                    </div>
                </article>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 5 }">
            <section class="section section--sand">
                <article class="journey__slide container">
                    <figure class="journey__media torn">
                        <img
                            src="/img/roaster.webp"
                            width="1152"
                            height="864"
                            loading="lazy"
                            decoding="async"
                            alt="Барабан обжарочной машины"
                        />
                    </figure>
                    <div class="journey__copy">
                        <p class="eyebrow">Кадр 3 из 5</p>
                        <h2 class="h2">14 минут в барабане</h2>
                        <p class="lead">
                            Свой профиль под каждый способ заваривания:
                            эспрессо, фильтр, турка. Небольшие партии,
                            стабильный результат.
                        </p>
                        <p class="journey__metric">
                            <span data-count-to="205" data-count-from="20"
                                >205</span
                            >
                            °C<small>на выгрузке</small>
                        </p>
                    </div>
                </article>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 6 }">
            <section class="section">
                <article class="journey__slide container">
                    <figure class="journey__media torn">
                        <img
                            src="/img/bag-kraft.webp"
                            width="864"
                            height="1152"
                            loading="lazy"
                            decoding="async"
                            alt="Пачка кофе Al Jar"
                        />
                    </figure>
                    <div class="journey__copy">
                        <p class="eyebrow">Кадр 4 из 5</p>
                        <h2 class="h2">Фасуем небольшими партиями</h2>
                        <p class="lead">
                            Фасуем сразу после обжарки, небольшими партиями —
                            столько, сколько разойдётся, пока зерно в форме.
                        </p>
                    </div>
                </article>
            </section>
        </div>

        <!-- Последняя панель несёт подвал: у страницы одна ось прокрутки,
             и место для подвала есть только здесь. -->
        <div class="rail__panel" :class="{ 'is-current': current === 7 }">
            <section class="section section--sand">
                <article class="journey__slide container">
                    <figure class="journey__media torn">
                        <img
                            src="/img/brew.webp"
                            width="1152"
                            height="864"
                            loading="lazy"
                            decoding="async"
                            alt="Чашка кофе и гейзерная кофеварка на мешковине"
                        />
                    </figure>
                    <div class="journey__copy">
                        <p class="eyebrow">Кадр 5 из 5</p>
                        <h2 class="h2">Ваш ритуал</h2>
                        <p class="lead">
                            С XVI века кофе в Ливане — знак уважения и повод
                            собраться. Дальше история продолжается у вас дома.
                        </p>
                        <p>
                            <Link
                                class="btn btn--primary"
                                href="/catalog/coffee"
                                >Смотреть кофе →</Link
                            >
                        </p>
                    </div>
                </article>
            </section>

            <StorefrontFooter />
        </div>
    </div>

    <RailBar :stops="stops" :current="current" :progress="progress" @go="go" />
</template>
