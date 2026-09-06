<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import '../../../css/storefront.css';
import '../../../css/admin.css';

/**
 * Вход в админку. Разметка и классы перенесены из макета
 * versions/v2-anim/admin/login.html.
 *
 * Стили админки надстроены над витринными, поэтому подключаются оба
 * файла: карточка входа своя, а поля и кнопка — общие с сайтом.
 *
 * Отличие от макета: форма настоящая. Регистрации и восстановления
 * пароля здесь нет — учётные записи сотрудников заводит администратор.
 */
</script>

<template>
    <Head title="Вход в панель управления" />

    <main class="admin-login">
        <Form
            action="/admin/login"
            method="post"
            class="admin-login__card"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
        >
            <div class="admin-login__brand">
                <img
                    src="/img/logo.webp"
                    alt="Al Jar Coffee"
                    width="187"
                    height="138"
                />
                <div>
                    <h1 class="h3">Панель управления</h1>
                    <p class="tiny">Доступ только для сотрудников</p>
                </div>
            </div>

            <label class="label">
                <span class="label__text">Логин</span>
                <input
                    class="field"
                    type="email"
                    name="email"
                    autocomplete="username"
                    placeholder="admin@aljar.ru"
                    required
                    autofocus
                />
                <InputError :message="errors.email" />
            </label>

            <label class="label">
                <span class="label__text">Пароль</span>
                <input
                    class="field"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    required
                />
                <InputError :message="errors.password" />
            </label>

            <label class="checkbox">
                <input type="checkbox" name="remember" value="1" />
                <span>Запомнить на этом устройстве</span>
            </label>

            <button
                class="btn btn--petrol btn--block"
                type="submit"
                :disabled="processing"
            >
                Войти
            </button>

            <p class="tiny" style="text-align: center">
                Забыли пароль — обратитесь к администратору сайта.
            </p>
        </Form>
    </main>
</template>
