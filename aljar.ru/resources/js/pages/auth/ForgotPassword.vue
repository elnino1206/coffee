<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

/**
 * Восстановление пароля. Классы витрины, механика фортифаевская.
 */

defineProps<{ status?: string }>();
</script>

<template>
    <Head title="Восстановление пароля" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> · Восстановление пароля
            </nav>
            <h1 class="h2">Забыли пароль</h1>
            <p class="lead">
                Пришлём на почту ссылку, по которой можно задать новый.
            </p>
        </div>

        <div style="max-width: 520px">
            <section class="panel stack">
                <p v-if="status" class="sub-status">{{ status }}</p>

                <Form
                    v-bind="email.form()"
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

                    <button
                        class="btn btn--petrol btn--block"
                        type="submit"
                        :disabled="processing"
                    >
                        Отправить ссылку
                    </button>
                </Form>

                <p class="tiny">
                    Вспомнили пароль? <Link :href="login()">Войдите</Link>.
                </p>
            </section>
        </div>
    </div>
</template>
