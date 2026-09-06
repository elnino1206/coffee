<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

/**
 * Вход по почте и паролю. Кнопка входа по ключу доступа снята с этого
 * экрана по решению заказчика: остаётся один способ, без развилки.
 * Управление ключами в настройках безопасности осталось — там же ключом
 * можно подтвердить вход в защищённый раздел вместо пароля.
 *
 * Разметка переведена на классы витрины (панель, поля, кнопки) —
 * покупатель попадает сюда из шапки и с нижней панели, и стартовый вид
 * набора выбивался из остального сайта.
 *
 * Механика прежняя, фортифаевская: тот же маршрут, те же поля, те же
 * ключи доступа.
 */

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Вход" />

    <div class="container">
        <div style="max-width: 520px; margin-inline: auto">
            <div class="page-hero">
                <nav class="breadcrumbs">
                    <Link href="/">Главная</Link> · Вход
                </nav>
                <h1 class="h2">Вход в личный кабинет</h1>
                <p class="lead">
                    Заказы, повтор в один клик и управление подпиской — в одном
                    месте.
                </p>
            </div>

            <section class="panel stack">
                <p v-if="status" class="sub-status">{{ status }}</p>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="stack"
                >
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
                            autofocus
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
                            autocomplete="current-password"
                        />
                        <InputError :message="errors.password" />
                    </label>

                    <div class="cluster" style="justify-content: space-between">
                        <label class="checkbox">
                            <input type="checkbox" name="remember" />
                            <span>Запомнить меня</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            class="tiny"
                            :href="request()"
                        >
                            Забыли пароль?
                        </Link>
                    </div>

                    <button
                        class="btn btn--petrol btn--block"
                        type="submit"
                        :disabled="processing"
                    >
                        Войти
                    </button>
                </Form>

                <p class="tiny">
                    Нет аккаунта?
                    <Link :href="register()">Зарегистрируйтесь</Link> — это
                    займёт минуту.
                </p>
            </section>
        </div>
    </div>
</template>
