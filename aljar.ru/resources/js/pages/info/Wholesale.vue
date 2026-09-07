<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RailBar from '@/components/RailBar.vue';
import StorefrontFooter from '@/components/StorefrontFooter.vue';
import { useHeroSlides } from '@/composables/useHeroSlides';
import { useRail } from '@/composables/useRail';

/**
 * Для бизнеса. Разметка и классы перенесены из прототипа
 * versions/v2-anim/wholesale.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Заявка идёт отдельным потоком от розничных обращений — требование
 * ТЗ 5.4: своя таблица и свой адресат, отдел продаж B2B. После отправки
 * показывается подтверждение с номером: человеку нужно, на что сослаться
 * при звонке.
 */

defineProps<{
    businessTypes: { value: string; label: string }[];
    accepted?: string | null;
}>();

const rail = ref<HTMLElement | null>(null);

const { current, go, progress } = useRail(rail);

/* Кадры героя — как на главной: снимок во всю панель, кадры сменяются
   перекрёстным затуханием. */
const heroStage = ref<HTMLElement | null>(null);

const heroSlides = [
    { src: '/img/hero-wholesale-1.webp', alt: 'Свежеобжаренные зёрна' },
    {
        src: '/img/hero-wholesale-2.webp',
        alt: 'Зерно после обжарки крупным планом',
    },
    { src: '/img/hero-wholesale-3.webp', alt: 'Россыпь обжаренного зерна' },
];

const { slide } = useHeroSlides(heroStage, heroSlides.length);

/** Подписи остановок на шкале — по одной на панель, в порядке рельса. */
const stops = ['Опт', 'Кому', 'Заявка'];
</script>

<template>
    <Head title="Оптовым покупателям" />

    <div ref="rail" class="rail">
        <div
            class="rail__panel rail__panel--hero"
            :class="{ 'is-current': current === 0 }"
        >
            <section class="hero hero--full">
                <!-- Подложка героя. Вынесена из сетки: абсолютное
                     позиционирование должно считаться от секции, иначе
                     снимок садится по ширине контейнера. -->
                <div ref="heroStage" class="hero__visual">
                    <!-- Кадры лежат стопкой: показанный проявлен,
                         остальные прозрачны. Подменять src нельзя — на
                         новом кадре был бы разрыв. -->
                    <span class="island__rock island__stack">
                        <img
                            v-for="(frame, i) in heroSlides"
                            :key="frame.src"
                            :src="frame.src"
                            :alt="i === slide ? frame.alt : ''"
                            :class="{ 'is-shown': i === slide }"
                            width="1920"
                            height="1080"
                            :fetchpriority="i === 0 ? 'high' : 'auto'"
                            :loading="i === 0 ? 'eager' : 'lazy'"
                            decoding="async"
                        />
                    </span>
                </div>
                <div class="hero__grid container">
                    <div class="hero__copy">
                        <p class="eyebrow">Оптовым покупателям</p>
                        <h1 class="display">
                            Кофе для кофеен, ресторанов и офисов
                        </h1>
                        <p class="lead">
                            Стабильный профиль обжарки, поставки под объём,
                            логистика без сюрпризов. Это не розничная витрина —
                            здесь говорим о партиях, сроках и условиях.
                        </p>
                        <button
                            class="btn btn--primary"
                            type="button"
                            @click="go(2)"
                        >
                            Оставить заявку
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 1 }">
            <section class="section section--sand">
                <div class="container">
                    <div class="steps">
                        <article class="step">
                            <h3 class="h3">Кофейни</h3>
                            <p class="muted">
                                Подбор профиля под концепцию заведения.
                                Стабильность от партии к партии.
                            </p>
                        </article>
                        <article class="step">
                            <h3 class="h3">HoReCa</h3>
                            <p class="muted">
                                Рестораны и отели: сроки поставки, фасовка под
                                кухню и бар.
                            </p>
                        </article>
                        <article class="step">
                            <h3 class="h3">Офисы</h3>
                            <p class="muted">
                                Программы поставки под уровень потребления
                                команды.
                            </p>
                        </article>
                    </div>
                    <p class="tiny" style="margin-top: 20px">
                        Минимальная партия и цены от объёма уточняются
                        менеджером — на текущем сайте цифр нет, в новом они
                        появятся, как только заказчик их закрепит. Ориентир: от
                        10 кг.
                    </p>
                </div>
            </section>
        </div>

        <div class="rail__panel" :class="{ 'is-current': current === 2 }">
            <section class="section" id="lead">
                <div class="container" style="max-width: 820px">
                    <p class="eyebrow">Заявка для отдела продаж B2B</p>
                    <h2 class="h2">Расскажите о задаче</h2>
                    <p class="muted" style="margin-bottom: 20px">
                        Это не общая форма «Напишите нам». Заявку получит
                        менеджер по опту, не поддержка розницы.
                    </p>

                    <div v-if="accepted" class="success-box is-visible">
                        <h3 class="h3">Заявка {{ accepted }} принята</h3>
                        <p>
                            Менеджер свяжется в течение рабочего дня. Если
                            срочно — позвоните
                            <a href="tel:+79851379235">8 (985) 137-92-35</a>.
                        </p>
                    </div>

                    <Form
                        v-else
                        action="/wholesale"
                        method="post"
                        class="panel stack"
                        v-slot="{ errors, processing }"
                    >
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
                                <InputError :message="errors.name" />
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
                                <InputError :message="errors.company" />
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
                                <InputError :message="errors.phone" />
                            </label>
                            <label class="label">
                                <span class="label__text"
                                    >Email (необязательно)</span
                                >
                                <input
                                    class="field"
                                    name="email"
                                    type="email"
                                />
                                <InputError :message="errors.email" />
                            </label>
                            <label class="label">
                                <span class="label__text">Тип бизнеса</span>
                                <select class="select" name="business_type">
                                    <option
                                        v-for="type in businessTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="label">
                                <span class="label__text">
                                    Интересующий объём / позиции
                                </span>
                                <input
                                    class="field"
                                    name="volume"
                                    placeholder="Например, от 10 кг в месяц, эспрессо-смесь"
                                />
                            </label>
                            <label class="label full">
                                <span class="label__text">Комментарий</span>
                                <textarea
                                    class="textarea"
                                    name="comment"
                                    placeholder="Как вы пьёте кофе?"
                                ></textarea>
                            </label>
                            <label class="label full">
                                <span class="label__text">
                                    Файл (спецификация, бриф) — необязательно
                                </span>
                                <input
                                    class="field field--file"
                                    type="file"
                                    name="file"
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png"
                                />
                                <InputError :message="errors.file" />
                            </label>
                        </div>

                        <label class="checkbox">
                            <input
                                type="checkbox"
                                name="agreement"
                                value="1"
                                required
                            />
                            <span>
                                Согласен на обработку персональных данных и с
                                <Link href="/legal#privacy"
                                    >политикой конфиденциальности</Link
                                >
                            </span>
                        </label>
                        <InputError :message="errors.agreement" />

                        <button
                            class="btn btn--petrol btn--m"
                            type="submit"
                            :disabled="processing"
                        >
                            Отправить заявку
                        </button>
                    </Form>
                </div>
            </section>

            <StorefrontFooter />
        </div>
    </div>

    <RailBar :stops="stops" :current="current" :progress="progress" @go="go" />
</template>
