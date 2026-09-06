<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

/**
 * Регистрация. Классы витрины, механика прежняя — маршрут и правила
 * остались фортифаевскими.
 */
</script>

<template>
    <Head title="Регистрация" />

    <div class="container">
        <div style="max-width: 520px; margin-inline: auto">
            <div class="page-hero">
                <nav class="breadcrumbs">
                    <Link href="/">Главная</Link> · Регистрация
                </nav>
                <h1 class="h2">Создать аккаунт</h1>
                <p class="lead">
                    Нужен, чтобы видеть свои заказы и повторять их в один клик.
                    Покупать можно и без него.
                </p>
            </div>

            <section class="panel stack">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="stack"
                >
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
                        <input
                            class="field"
                            name="name"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError :message="errors.name" />
                    </label>

                    <label class="label">
                        <span class="label__text">
                            Электронная почта
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input
                            class="field"
                            type="email"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="you@email.ru"
                        />
                        <InputError :message="errors.email" />
                    </label>

                    <label class="label">
                        <span class="label__text">
                            Пароль
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input
                            class="field"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError :message="errors.password" />
                    </label>

                    <label class="label">
                        <span class="label__text">
                            Пароль ещё раз
                            <span
                                class="req"
                                aria-hidden="true"
                                title="Обязательное поле"
                                >*</span
                            >
                        </span>
                        <input
                            class="field"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </label>

                    <button
                        class="btn btn--petrol btn--block"
                        type="submit"
                        :disabled="processing"
                    >
                        Зарегистрироваться
                    </button>
                </Form>

                <p class="tiny">
                    Уже есть аккаунт? <Link :href="login()">Войдите</Link>.
                </p>
            </section>
        </div>
    </div>
</template>
