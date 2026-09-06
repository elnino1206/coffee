<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { send } from '@/routes/verification';

/**
 * Профиль. Разметка на классах витрины — те же панели, поля и кнопки,
 * что в кабинете и на оформлении заказа.
 */

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Настройки профиля" />

    <section class="panel stack">
        <div>
            <h2 class="h3">Профиль</h2>
            <p class="muted">Имя и адрес почты.</p>
        </div>

        <Form
            v-bind="ProfileController.update.form()"
            class="stack"
            v-slot="{ errors, processing }"
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
                    :value="user.name"
                    required
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
                    :value="user.email"
                    required
                    autocomplete="username"
                />
                <InputError :message="errors.email" />
            </label>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="tiny">
                    Почта не подтверждена.
                    <Link :href="send()" as="button">
                        Отправить письмо ещё раз.
                    </Link>
                </p>
                <p
                    v-if="page.props.status === 'verification-link-sent'"
                    class="sub-status"
                >
                    Новая ссылка отправлена на вашу почту.
                </p>
            </div>

            <div class="cluster">
                <button
                    class="btn btn--petrol btn--m"
                    type="submit"
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    Сохранить
                </button>
            </div>
        </Form>
    </section>

    <DeleteUser />
</template>
