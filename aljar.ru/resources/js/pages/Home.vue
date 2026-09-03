<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AddToCartModal from '@/components/AddToCartModal.vue';
import { formatPrice } from '@/lib/money';

/**
 * Главная. Разметка и классы перенесены из прототипа
 * versions/v2-anim/index.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Отличие от прототипа одно: подборка «Свежая обжарка» приходит с
 * сервера, а не собирается на клиенте из data.js.
 */

interface Variant {
    id: number;
    title: string;
    price: number;
    in_stock: boolean;
}

interface Product {
    slug: string;
    name: string;
    notes: string | null;
    image: string | null;
    species: string | null;
    roast: string | null;
    roast_value: string | null;
    price_from: number;
    variants: Variant[];
}

defineProps<{ featured: Product[] }>();

/** Товар, для которого открыт выбор веса и помола. */
const picked = ref<Product | null>(null);
</script>

<template>
    <Head>
        <title>Al Jar Coffee — от зерна к чашке</title>
        <meta
            name="description"
            content="Семейный бренд с ливанскими корнями и современной обжаркой в России. Свежий кофе, подписка и прозрачное происхождение зерна."
        />
    </Head>

    <section class="hero">
        <div class="container hero__grid">
            <div class="hero__copy">
                <p class="eyebrow">Спешелти кофе · Ливанские корни</p>
                <h1 class="display">От зерна<br />к чашке</h1>
                <p class="lead">
                    Мы обжариваем кофе небольшими партиями, сохраняя характер каждого зерна.
                    От истоков в ливанских горах — до вашей чашки.
                </p>
                <div class="cluster">
                    <Link class="btn btn--primary" href="/catalog/coffee">Смотреть кофе →</Link>
                    <a class="btn btn--ghost" href="#">Подписка</a>
                </div>
                <div class="trust-row">
                    <span class="trust-item">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 16l8-14 8 14H4z" />
                        </svg>
                        Прямо из традиции Ливана
                    </span>
                    <span class="trust-item">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M12 3c4 4 4 10 0 14-4-4-4-10 0-14z" />
                            <path d="M12 7v6" />
                        </svg>
                        Мастерская обжарка
                    </span>
                    <span class="trust-item">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 14c4-8 12-8 16 0" />
                            <path d="M12 4v4" />
                        </svg>
                        Свежесть и характер
                    </span>
                </div>
            </div>
            <!-- «Остров»: плита с чашкой оторвана от фона и парит. Прототип
                 работает на текущем кадре без вырезки — форму держит
                 органическая маска, глубину даёт отдельный слой тени. -->
            <div class="hero__visual" data-island>
                <span class="blob blob--baby"></span>
                <span class="blob blob--sand"></span>
                <span class="island__shadow" aria-hidden="true"></span>
                <img
                    class="island__rock"
                    src="/img/hero.webp"
                    width="1152"
                    height="998"
                    fetchpriority="high"
                    decoding="async"
                    alt="Горсть свежеобжаренных зёрен Al Jar в руке обжарщика"
                />
                <span class="island__beans" aria-hidden="true">
                    <span class="bean" style="--x: 6%; --y: 14%; --s: 0.9; --dur: 11s; --lag: -2s"></span>
                    <span class="bean" style="--x: 88%; --y: 26%; --s: 1.1; --dur: 13s; --lag: -6s"></span>
                    <span class="bean" style="--x: 16%; --y: 74%; --s: 1; --dur: 9s; --lag: -4s"></span>
                    <span class="bean bean--far" style="--x: 78%; --y: 82%; --s: 0.75; --dur: 14s; --lag: -9s"></span>
                    <span class="bean bean--far" style="--x: 52%; --y: 6%; --s: 0.8; --dur: 12s; --lag: -1s"></span>
                </span>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <p class="eyebrow">Свежая партия</p>
                    <h2 class="h2">Свежая обжарка этой недели</h2>
                </div>
                <Link class="btn btn--ghost btn--m" href="/catalog/coffee">Смотреть все</Link>
            </div>
            <div class="product-grid">
                <article v-for="product in featured" :key="product.slug" class="card" data-reveal>
                    <div class="card__media">
                        <Link :href="`/catalog/coffee/${product.slug}`">
                            <img :src="`/${product.image}`" :alt="product.name" loading="lazy" decoding="async" />
                        </Link>
                    </div>
                    <div class="card__body">
                        <Link class="card__title" :href="`/catalog/coffee/${product.slug}`">
                            {{ product.name }}
                        </Link>
                        <p class="card__notes">{{ product.notes }}</p>
                        <div class="card__meta">
                            <span>{{ product.roast }}</span>
                            <span>{{ product.species }}</span>
                        </div>
                        <div class="card__row">
                            <span class="price">{{ formatPrice(product.price_from) }}</span>
                            <button
                                class="add-quick"
                                type="button"
                                :aria-label="`Выбрать вес и помол: ${product.name}`"
                                @click="picked = product"
                            >
                                <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M4.5 6.5h11l-1 10h-9l-1-10z" />
                                    <path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0">
        <div class="container">
            <div class="subscribe-band" data-reveal>
                <div class="stack-s">
                    <p class="eyebrow">Ключевая модель</p>
                    <h2 class="h3">Кофе по подписке — выгоднее и спокойнее</h2>
                    <p class="tiny">Свежая обжарка под ваш ритм. Пауза и отмена — в один клик.</p>
                </div>
                <div class="perk-list">
                    <div class="perk">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M5 12l4 4 10-10" />
                        </svg>
                        <span>−10% на каждый заказ</span>
                    </div>
                    <div class="perk">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M4 12h16M12 4v16" />
                        </svg>
                        <span>Пауза в 1 клик</span>
                    </div>
                    <div class="perk">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <circle cx="10" cy="10" r="7" />
                            <path d="M10 6v5l3 2" />
                        </svg>
                        <span>Обжарка под дату доставки</span>
                    </div>
                </div>
                <a class="btn btn--primary" href="#">Настроить подписку →</a>
            </div>
        </div>
    </section>

    <section class="section section--sand">
        <div class="container heritage">
            <div class="stack">
                <p class="eyebrow">Наше наследие</p>
                <h2 class="h2">Больше, чем кофе. Наша история.</h2>
                <p class="lead">
                    С XVI века кофе в Ливане — знак уважения и живая традиция. Al Jar продолжает её
                    в современном формате: семейный бренд, полное производство в России, внимание
                    к зерну и ритуалу.
                </p>
                <p class="muted">
                    Мы специализируемся на эспрессо и ливанском кофе, а также обжариваем под гейзер,
                    френч-пресс, фильтр и турку.
                </p>
                <div class="cluster">
                    <Link class="btn btn--petrol" href="/about">Узнать нашу историю</Link>
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

                <svg class="stamp" viewBox="0 0 130 130" role="img" aria-label="Корни в Ливане с 1970-х. Сделано с любовью">
                    <defs>
                        <!-- Дуги для текста. Верхняя идёт слева направо поверху,
                             нижняя — слева направо понизу: на обеих буквы стоят
                             ровно, без переворота. -->
                        <path id="stamp-top" d="M 17,65 A 48,48 0 0 1 113,65" fill="none"></path>
                        <path id="stamp-bottom" d="M 18,65 A 47,47 0 0 0 112,65" fill="none"></path>
                    </defs>

                    <circle class="stamp__disc" cx="65" cy="65" r="64"></circle>
                    <circle class="stamp__ring" cx="65" cy="65" r="60"></circle>
                    <circle class="stamp__ring stamp__ring--thin" cx="65" cy="65" r="52"></circle>

                    <text class="stamp__arc">
                        <textPath href="#stamp-top" startOffset="50%" text-anchor="middle">Корни в Ливане</textPath>
                    </text>
                    <text class="stamp__arc">
                        <textPath href="#stamp-bottom" startOffset="50%" text-anchor="middle">Сделано с любовью</textPath>
                    </text>

                    <!-- Разделители на оси: отбивают начало и конец надписей -->
                    <circle class="stamp__dot" cx="13.5" cy="65" r="2"></circle>
                    <circle class="stamp__dot" cx="116.5" cy="65" r="2"></circle>

                    <text class="stamp__year" x="65" y="72" text-anchor="middle">С 1970-х</text>
                    <!-- Росчерк уводим под год: без сдвига листья приходились
                         на 69.6-80 по вертикали, а строка «С 1970-х» занимает
                         53.7-76.1 — орнамент ложился прямо на цифры. -->
                    <g transform="translate(0, 14)">
                        <path class="stamp__leaf" d="M65 80c5-.6 8.4-5 9-10.4-5 .6-8.4 4.6-9 10.4z"></path>
                        <path class="stamp__leaf" d="M65 80c-5-.6-8.4-5-9-10.4 5 .6 8.4 4.6 9 10.4z"></path>
                        <path class="stamp__stem" d="M65 81v-6"></path>
                    </g>
                </svg>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head" data-reveal>
                <div>
                    <p class="eyebrow">Подборки</p>
                    <h2 class="h2">Вам может подойти</h2>
                </div>
            </div>
            <div class="product-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr))">
                <Link class="card" data-reveal href="/catalog/coffee?method[]=espresso" style="padding: 28px; background: var(--sand); border: 0">
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для эспрессо</h3>
                    <p class="muted">Плотное тело, шоколад, стабильность в рожке.</p>
                </Link>
                <Link class="card" data-reveal href="/catalog/coffee?method[]=filter" style="padding: 28px; background: var(--sand); border: 0">
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для фильтра</h3>
                    <p class="muted">Цветы, ягоды, чистая кислотность.</p>
                </Link>
                <Link class="card" data-reveal href="/catalog/coffee?method[]=cezve" style="padding: 28px; background: #fff">
                    <p class="eyebrow">Способ</p>
                    <h3 class="h3">Для турки</h3>
                    <p class="muted">Ливанский ритуал и плотная чашка.</p>
                </Link>
            </div>
        </div>
    </section>

    <section class="section section--petrol">
        <div class="container">
            <p class="eyebrow">Что говорят о нас</p>
            <h2 class="h2" style="color: #fff; margin: 8px 0 28px">Любимый кофе. Настоящие истории.</h2>
            <div class="reviews">
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">★★★★★</div>
                    <p class="review__text">Потрясающий кофе с душой и историей. Чувствуется качество в каждой чашке.</p>
                    <footer class="review__author">
                        <span class="review__name">— Мария П.</span>
                        <span class="review__city">Москва</span>
                    </footer>
                </article>
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">★★★★★</div>
                    <p class="review__text">Подписка — лучшее решение. Кофе всегда свежий, а доставка как по волшебству.</p>
                    <footer class="review__author">
                        <span class="review__name">— Дмитрий К.</span>
                        <span class="review__city">Санкт-Петербург</span>
                    </footer>
                </article>
                <article class="review" data-reveal>
                    <div class="stars" role="img" aria-label="Оценка 5 из 5">★★★★★</div>
                    <p class="review__text">Премиум-качество и вдохновляющая история. Гордимся тем, что выбираем Al Jar.</p>
                    <footer class="review__author">
                        <span class="review__name">— Елена К.</span>
                        <span class="review__city">Казань</span>
                    </footer>
                </article>
            </div>
        </div>
    </section>

    <AddToCartModal :product="picked" @close="picked = null" />

    <section class="section">
        <div class="container newsletter">
            <div>
                <p class="eyebrow">Будьте в курсе</p>
                <h2 class="h3">Новости обжарок, историй и особых партий</h2>
                <p class="tiny">Никакого спама. Только важное и вкусное. Можно в Telegram.</p>
            </div>
            <!-- Обёртка вокруг поля, а не вокруг пары «поле + кнопка»: по
                 CSS flex-элементом формы становится .field-wrap, и кнопка
                 обязана быть её соседом, иначе уезжает внутрь пилюли. -->
            <form class="newsletter-form" @submit.prevent>
                <span class="field-wrap">
                    <input class="field" type="email" name="email" required placeholder="Ваш e-mail" />
                </span>
                <button class="btn btn--petrol btn--m" type="submit">Подписаться</button>
            </form>
        </div>
    </section>
</template>
