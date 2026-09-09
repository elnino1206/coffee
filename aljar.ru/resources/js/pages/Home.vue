<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { useHeroSlides } from '@/composables/useHeroSlides';
import { useScrollScene } from '@/composables/useScrollScene';
import { motionOn } from '@/lib/motion';

/**
 * Главная. Разметка и классы перенесены из прототипа
 * versions/v2-anim/index.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа одно: подборка «Свежая обжарка» приходит с
 * сервера, а не собирается на клиенте из data.js.
 */

/* Адрес ролика приходит с сервера: под встроенным сервером PHP он идёт
   через приложение, иначе перемотка невозможна. Подробности — в
   HomeController::film(). */
defineProps<{ film: string }>();

/**
 * Три входа в каталог с уже включённым фильтром по способу заваривания.
 *
 * Блок «Свежая партия» — не витрина товаров: по документу
 * `inst_designe/AlJar_Homepage_FreshRoast_Block.docx` в нём нет ни цены,
 * ни кнопки в корзину, ни конкретного сорта. Карточки не меняются от
 * недели к неделе — меняется то, что каталог по этим фильтрам выдаёт.
 */
const brews = [
    {
        value: 'cezve',
        label: 'Турка',
        note: 'Плотное тело, восточные специи',
        image: '/img/brew-cezve.webp',
        alt: 'Медная турка',
    },
    {
        value: 'espresso',
        label: 'Эспрессо',
        note: 'Шоколад, орех, плотная текстура',
        image: '/img/brew-espresso.webp',
        alt: 'Чашка с восточным орнаментом',
    },
    {
        value: 'filter',
        label: 'Фильтр',
        note: 'Цветы, цитрус, чистота чашки',
        image: '/img/brew-filter.webp',
        alt: 'Пуровер над стеклянным сервером',
    },
];

/* Обещанная скидка берётся с сервера: обещание на витрине и расчёт в
   корзине обязаны совпадать. */
const discount = computed(() => Number(usePage().props.subscribeDiscount ?? 0));

/** Кадры героя — макро обжаренного зерна из съёмки. */
const heroSlides = [
    { src: '/img/hero-1.webp', alt: 'Россыпь свежеобжаренного зерна' },
    { src: '/img/hero-2.webp', alt: 'Зёрна средней обжарки крупным планом' },
    { src: '/img/hero-3.webp', alt: 'Обжаренное зерно, макросъёмка' },
    { src: '/img/hero-4.webp', alt: 'Зерно тёмной обжарки' },
];

const heroStage = ref<HTMLElement | null>(null);

/* Съёмку ставим только там, где разрешено движение: без него сцена не
   прикалывается и листать нечего — на её месте остаётся тот же снимок,
   что был раньше.

   Решение принимается один раз при сборке страницы: `is-enhanced` ставит
   app.ts до монтирования, и переключаться на ходу здесь нечему. */
const video = motionOn();

const { slide } = useHeroSlides(heroStage, video ? 0 : heroSlides.length);

/* Сцена первого экрана: ролик листается прокруткой, подпись героя
   уходит, подпись «Свежей партии» приходит. */
const scene = ref<HTMLElement | null>(null);
const filmEl = ref<HTMLVideoElement | null>(null);

const { roastLive } = useScrollScene(scene, filmEl);
</script>

<template>
    <Head>
        <title>Al Jar Coffee — от зерна к чашке</title>
        <meta
            name="description"
            content="Семейный бренд с ливанскими корнями и современной обжаркой в России. Свежий кофе, подписка и прозрачное происхождение зерна."
        />
    </Head>

    <!-- Сцена первого экрана. Секция высотой в два экрана приколота, а
         внутри неё ролик, который листается прокруткой: вниз — вперёд,
         вверх — назад. Поверх ролика два слоя: подпись героя уходит,
         подпись «Свежей партии» приходит, между ними промежуток, где на
         экране только съёмка.

         Без движения сцена не прикалывается и слои просто идут один за
         другим — раскладку меняет CSS, разметка та же. -->
    <div ref="scene" class="scene">
        <div class="scene__stage">
            <div class="scene__film">
                <!-- Ролик не идёт сам: ни autoplay, ни loop. Его
                     положение задаёт прокрутка, поэтому здесь только
                     постер и предзагрузка. -->
                <video
                    v-if="video"
                    ref="filmEl"
                    poster="/img/hero-video-poster.webp"
                    width="1280"
                    height="720"
                    muted
                    playsinline
                    preload="auto"
                    aria-hidden="true"
                >
                    <source :src="film" type="video/mp4" />
                </video>

                <!-- Без движения на месте съёмки остаются прежние кадры;
                     листать их тоже не нужно, показывается первый. -->
                <span v-else ref="heroStage" class="island__rock island__stack">
                    <img
                        v-for="(frame, i) in heroSlides"
                        :key="frame.src"
                        :src="frame.src"
                        :alt="i === slide ? frame.alt : ''"
                        :class="{ 'is-shown': i === slide }"
                        width="1152"
                        height="998"
                        :fetchpriority="i === 0 ? 'high' : 'auto'"
                        :loading="i === 0 ? 'eager' : 'lazy'"
                        decoding="async"
                    />
                </span>
            </div>

            <div class="scene__layer scene__layer--hero">
                <div class="hero__grid container">
                    <div class="hero__copy">
                        <p class="eyebrow">Спешелти кофе · Ливанские корни</p>
                        <h1 class="display">От зерна<br />к чашке</h1>
                        <p class="lead">
                            Мы обжариваем кофе небольшими партиями, сохраняя
                            характер каждого зерна. От истоков в ливанских горах
                            — до вашей чашки.
                        </p>
                        <div class="cluster">
                            <Link
                                class="btn btn--primary"
                                href="/catalog/coffee"
                                >Смотреть кофе →</Link
                            >
                            <Link class="btn btn--ghost" href="/subscription"
                                >Подписка</Link
                            >
                        </div>
                        <!-- Значки нарисованы заказчиком: ливанский
                             кедр, зерно на обжарке и чашка. Растр, а не
                             SVG — так их прислали; для кружка 30px
                             160-пиксельного файла хватает и на экране с
                             тройной плотностью. -->
                        <div class="trust-row">
                            <span class="trust-item">
                                <img
                                    src="/img/trust-cedar.webp"
                                    alt=""
                                    width="160"
                                    height="160"
                                    loading="lazy"
                                    decoding="async"
                                />
                                Прямо из традиции Ливана
                            </span>
                            <span class="trust-item">
                                <img
                                    src="/img/trust-roast.webp"
                                    alt=""
                                    width="160"
                                    height="160"
                                    loading="lazy"
                                    decoding="async"
                                />
                                Мастерская обжарка
                            </span>
                            <span class="trust-item">
                                <img
                                    src="/img/trust-cup.webp"
                                    alt=""
                                    width="160"
                                    height="160"
                                    loading="lazy"
                                    decoding="async"
                                />
                                Свежесть и характер
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="scene__layer scene__layer--roast"
                :class="{ 'is-live': roastLive }"
            >
                <section class="section">
                    <div class="container">
                        <div class="section-head" data-reveal>
                            <div>
                                <p class="eyebrow">Свежая партия</p>
                                <h2 class="h2">Свежая обжарка этой недели</h2>
                                <p class="lead" style="margin-top: 10px">
                                    Выберите способ заваривания — откроется
                                    каталог с нужным фильтром.
                                </p>
                            </div>
                        </div>
                        <div class="brew-grid">
                            <!-- Ссылкой служит вся карточка; подпись
                             «Смотреть каталог» повторяет тот же переход,
                             а не ведёт куда-то ещё. -->
                            <Link
                                v-for="brew in brews"
                                :key="brew.value"
                                class="brew-card"
                                :href="`/catalog/coffee?method=${brew.value}`"
                                data-reveal
                            >
                                <!-- Рисунок обтекается текстом по своему
                                 контуру: `shape-outside` берёт форму из
                                 альфы того же файла, поэтому адрес
                                 задаётся здесь, а не в стилях. Слой
                                 отдельный — сдвиг он отрабатывает своим
                                 ходом, а обтекание считается по
                                 несдвинутой рамке и не пересчитывается. -->
                                <img
                                    class="brew-card__art"
                                    :src="brew.image"
                                    :alt="brew.alt"
                                    :style="{
                                        shapeOutside: `url(${brew.image})`,
                                    }"
                                    width="480"
                                    height="600"
                                    loading="eager"
                                    decoding="async"
                                />
                                <span class="brew-card__title">{{
                                    brew.label
                                }}</span>
                                <span class="brew-card__note">{{
                                    brew.note
                                }}</span>
                                <span class="brew-card__cta"
                                    >Смотреть каталог →</span
                                >
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <section class="section" style="padding-top: 0">
        <span
            class="lamp"
            aria-hidden="true"
            style="
                background: radial-gradient(
                    52% 60% at 18% 46%,
                    rgba(10, 186, 181, 0.12),
                    rgba(10, 186, 181, 0) 70%
                );
            "
        ></span>
        <div class="container">
            <div class="subscribe-band" data-reveal>
                <div class="stack-s">
                    <p class="eyebrow">Ключевая модель</p>
                    <h2 class="h3">Кофе по подписке — выгоднее и спокойнее</h2>
                    <p class="tiny">
                        Свежая обжарка под ваш ритм. Пауза и отмена — в один
                        клик.
                    </p>
                </div>
                <div class="perk-list">
                    <div class="perk">
                        <svg
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <path d="M5 12l4 4 10-10" />
                        </svg>
                        <span>−{{ discount }}% на каждый заказ</span>
                    </div>
                    <div class="perk">
                        <svg
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <path d="M4 12h16M12 4v16" />
                        </svg>
                        <span>Пауза в 1 клик</span>
                    </div>
                    <div class="perk">
                        <svg
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <circle cx="10" cy="10" r="7" />
                            <path d="M10 6v5l3 2" />
                        </svg>
                        <span>Обжарка под дату доставки</span>
                    </div>
                </div>
                <Link class="btn btn--primary" href="/subscription"
                    >Настроить подписку →</Link
                >
            </div>
        </div>
    </section>

    <section class="section section--sand">
        <span
            class="lamp"
            aria-hidden="true"
            style="
                background: radial-gradient(
                    64% 52% at 78% 30%,
                    rgba(255, 252, 247, 0.55),
                    rgba(255, 252, 247, 0) 72%
                );
            "
        ></span>
        <div class="heritage container">
            <div class="stack">
                <p class="eyebrow">Наше наследие</p>
                <h2 class="h2">Больше, чем кофе. Наша история.</h2>
                <p class="lead">
                    С XVI века кофе в Ливане — знак уважения и живая традиция.
                    Al Jar продолжает её в современном формате: семейный бренд,
                    полное производство в России, внимание к зерну и ритуалу.
                </p>
                <p class="muted">
                    Мы специализируемся на эспрессо и ливанском кофе, а также
                    обжариваем под гейзер, френч-пресс, фильтр и турку.
                </p>
                <div class="cluster">
                    <Link class="btn btn--petrol" href="/about"
                        >Узнать нашу историю</Link
                    >
                </div>
            </div>
            <div class="heritage__photo">
                <!-- Рваная кайма: белый лист под фотографией, его край
                     размывается фильтром смещения. Само фото фильтр не
                     трогает — иначе турка пошла бы волнами. -->
                <div class="torn">
                    <img
                        src="/img/dallah.webp"
                        width="1152"
                        height="864"
                        loading="lazy"
                        decoding="async"
                        alt="Медная турка и чашка кофе на грифельной доске"
                    />
                </div>

                <svg
                    class="stamp"
                    viewBox="0 0 130 130"
                    role="img"
                    aria-label="Корни в Ливане с 1970-х. Сделано с любовью"
                >
                    <defs>
                        <!-- Дуги для текста. Верхняя идёт слева направо поверху,
                             нижняя — слева направо понизу: на обеих буквы стоят
                             ровно, без переворота. -->
                        <path
                            id="stamp-top"
                            d="M 17,65 A 48,48 0 0 1 113,65"
                            fill="none"
                        ></path>
                        <path
                            id="stamp-bottom"
                            d="M 18,65 A 47,47 0 0 0 112,65"
                            fill="none"
                        ></path>
                    </defs>

                    <circle class="stamp__disc" cx="65" cy="65" r="64"></circle>
                    <circle class="stamp__ring" cx="65" cy="65" r="60"></circle>
                    <circle
                        class="stamp__ring stamp__ring--thin"
                        cx="65"
                        cy="65"
                        r="52"
                    ></circle>

                    <text class="stamp__arc">
                        <textPath
                            href="#stamp-top"
                            startOffset="50%"
                            text-anchor="middle"
                        >
                            Корни в Ливане
                        </textPath>
                    </text>
                    <text class="stamp__arc">
                        <textPath
                            href="#stamp-bottom"
                            startOffset="50%"
                            text-anchor="middle"
                        >
                            Сделано с любовью
                        </textPath>
                    </text>

                    <!-- Разделители на оси: отбивают начало и конец надписей -->
                    <circle class="stamp__dot" cx="13.5" cy="65" r="2"></circle>
                    <circle
                        class="stamp__dot"
                        cx="116.5"
                        cy="65"
                        r="2"
                    ></circle>

                    <text
                        class="stamp__year"
                        x="65"
                        y="72"
                        text-anchor="middle"
                    >
                        С 1970-х
                    </text>
                    <!-- Росчерк уводим под год: без сдвига листья приходились
                         на 69.6-80 по вертикали, а строка «С 1970-х» занимает
                         53.7-76.1 — орнамент ложился прямо на цифры. -->
                    <g transform="translate(0, 14)">
                        <path
                            class="stamp__leaf"
                            d="M65 80c5-.6 8.4-5 9-10.4-5 .6-8.4 4.6-9 10.4z"
                        ></path>
                        <path
                            class="stamp__leaf"
                            d="M65 80c-5-.6-8.4-5-9-10.4 5 .6 8.4 4.6 9 10.4z"
                        ></path>
                        <path class="stamp__stem" d="M65 81v-6"></path>
                    </g>
                </svg>
            </div>
        </div>
    </section>

    <section class="section section--petrol">
        <span
            class="lamp"
            aria-hidden="true"
            style="
                background: radial-gradient(
                    58% 62% at 34% 26%,
                    rgba(255, 240, 214, 0.16),
                    rgba(255, 240, 214, 0) 70%
                );
            "
        ></span>
        <div class="container">
            <p class="eyebrow">Что говорят о нас</p>
            <h2 class="h2" style="color: #fff; margin: 8px 0 28px">
                Любимый кофе. Настоящие истории.
            </h2>
            <div class="reviews">
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">
                        ★★★★★
                    </div>
                    <p class="review__text">
                        Потрясающий кофе с душой и историей. Чувствуется
                        качество в каждой чашке.
                    </p>
                    <footer class="review__author">
                        <span class="review__name">— Мария П.</span>
                        <span class="review__city">Москва</span>
                    </footer>
                </article>
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">
                        ★★★★★
                    </div>
                    <p class="review__text">
                        Подписка — лучшее решение. Кофе всегда свежий, а
                        доставка как по волшебству.
                    </p>
                    <footer class="review__author">
                        <span class="review__name">— Дмитрий К.</span>
                        <span class="review__city">Санкт-Петербург</span>
                    </footer>
                </article>
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">
                        ★★★★★
                    </div>
                    <p class="review__text">
                        Премиум-качество и вдохновляющая история. Гордимся тем,
                        что выбираем Al Jar.
                    </p>
                    <footer class="review__author">
                        <span class="review__name">— Елена К.</span>
                        <span class="review__city">Казань</span>
                    </footer>
                </article>
            </div>
        </div>
    </section>
    <section class="section">
        <span
            class="lamp"
            aria-hidden="true"
            style="
                background: radial-gradient(
                    56% 56% at 68% 62%,
                    rgba(181, 106, 74, 0.12),
                    rgba(181, 106, 74, 0) 72%
                );
            "
        ></span>
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <p class="eyebrow">Подборки</p>
                    <h2 class="h2">Вам может подойти</h2>
                </div>
            </div>
            <div
                class="product-grid"
                style="
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                "
            >
                <Link
                    class="card"
                    data-reveal
                    href="/catalog/coffee?method[]=espresso"
                    style="padding: 28px; background: var(--sand); border: 0"
                >
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для эспрессо</h3>
                    <p class="muted">
                        Плотное тело, шоколад, стабильность в рожке.
                    </p>
                </Link>
                <Link
                    class="card"
                    data-reveal
                    href="/catalog/coffee?method[]=filter"
                    style="padding: 28px; background: var(--sand); border: 0"
                >
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для фильтра</h3>
                    <p class="muted">Цветы, ягоды, чистая кислотность.</p>
                </Link>
                <Link
                    class="card"
                    data-reveal
                    href="/catalog/coffee?method[]=cezve"
                    style="padding: 28px; background: #fff"
                >
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для турки</h3>
                    <p class="muted">Ливанский ритуал и плотная чашка.</p>
                </Link>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="newsletter container">
            <div>
                <p class="eyebrow">Будьте в курсе</p>
                <h2 class="h3">Новости обжарок, историй и особых партий</h2>
                <p class="tiny">
                    Никакого спама. Только важное и вкусное. Можно в Telegram.
                </p>
            </div>
            <!-- Обёртка вокруг поля, а не вокруг пары «поле + кнопка»: по
                 CSS flex-элементом формы становится .field-wrap, и кнопка
                 обязана быть её соседом, иначе уезжает внутрь пилюли. -->
            <Form
                class="newsletter-form"
                action="/newsletter"
                method="post"
                reset-on-success
                :options="{ preserveScroll: true }"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="source" value="home" />
                <span class="field-wrap">
                    <input
                        class="field"
                        type="email"
                        name="email"
                        required
                        placeholder="Ваш e-mail"
                    />
                </span>
                <button
                    class="btn btn--petrol btn--m"
                    type="submit"
                    :disabled="processing"
                >
                    Подписаться
                </button>
                <InputError :message="errors.email" />
            </Form>
        </div>
    </section>
</template>
