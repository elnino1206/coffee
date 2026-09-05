<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useJourney } from '@/composables/useJourney';

/**
 * О бренде. Разметка и классы перенесены из прототипа
 * versions/v2-anim/about.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Порядок блоков — как в прототипе после правок: «Ценности» идут
 * перед «Производством».
 *
 * Страница статическая — данных с сервера не требует.
 */

const journey = ref<HTMLElement | null>(null);

useJourney(journey);
</script>

<template>
    <Head title="О бренде" />

    <section class="hero">
        <div class="hero__grid container">
            <div class="hero__copy">
                <p class="eyebrow">О бренде</p>
                <h1 class="display">История семьи и ритуала</h1>
                <p class="lead">
                    С XVI века кофе в Ливане — часть культуры, знак уважения и
                    живая традиция. Al Jar продолжает её в современном формате:
                    семейный бренд, опыт, внимание к деталям.
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

    <section class="section section--sand">
        <div class="container">
            <p class="eyebrow">Ценности</p>
            <h2 class="h2" style="margin-bottom: 24px">Как мы работаем</h2>
            <div class="steps">
                <article class="step">
                    <h3 class="h3">Честность</h3>
                    <p class="muted">
                        Происхождение партии, степень обжарки, вкусовой профиль
                        — на виду, не в подвале карточки.
                    </p>
                </article>
                <article class="step">
                    <h3 class="h3">Ритуал</h3>
                    <p class="muted">
                        Кофе — способ собраться вместе. Мы уважаем гостя так же,
                        как хороший хозяин.
                    </p>
                </article>
                <article class="step">
                    <h3 class="h3">Ремесло</h3>
                    <p class="muted">
                        Небольшие партии, стабильный профиль, никакой агрессии в
                        продаже.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="heritage container">
            <div class="stack" data-reveal>
                <p class="eyebrow">Производство</p>
                <h2 class="h2">Полный цикл в России</h2>
                <p class="lead">
                    Закупка сырого зерна, обжарка до 20 тонн в месяц, фасовка.
                    Не прячем цех: показываем, откуда зерно и как идёт обжарка.
                </p>
                <p class="muted">
                    Специализация: эспрессо и ливанский кофе. Также — гейзер,
                    френч-пресс, фильтр, турка (арабская и турецкая). На
                    российский рынок бренд пришёл с многолетним опытом работы на
                    Ближнем Востоке.
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
                    style="height: 380px; width: 100%; object-fit: cover"
                />
            </div>
        </div>
    </section>

    <!-- Сценарий «От зерна к чашке». Без JS и при reduce-motion читается
         как обычный список из пяти блоков «кадр + подпись». -->
    <section
        ref="journey"
        class="section section--sand journey"
        aria-label="От зерна к чашке"
    >
        <div class="journey__track container">
            <div class="journey__stage">
                <ol
                    class="journey__progress"
                    data-journey-progress
                    aria-hidden="true"
                ></ol>

                <article class="journey__slide" data-slide>
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

                <article class="journey__slide" data-slide data-optional>
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

                <article class="journey__slide" data-slide>
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

                <article class="journey__slide" data-slide data-optional>
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
                        <h2 class="h2">Фасуем небольшими партиями</h2>
                        <p class="lead">
                            Фасуем сразу после обжарки, небольшими партиями —
                            столько, сколько разойдётся, пока зерно в форме.
                        </p>
                    </div>
                </article>

                <article class="journey__slide" data-slide>
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
            </div>
            <div
                class="journey__sentinels"
                data-journey-sentinels
                aria-hidden="true"
            ></div>
        </div>
    </section>
</template>
