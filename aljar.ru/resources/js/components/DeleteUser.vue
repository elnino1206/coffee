<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';

/**
 * Удаление аккаунта.
 *
 * Подтверждение — окно из прототипа (.modal / .modal__box), а не диалог
 * стартового набора: у сайта своё оформление окон, и держать два разных
 * незачем.
 */

const open = ref(false);
</script>

<template>
    <section class="panel stack">
        <div>
            <h2 class="h3">Удалить аккаунт</h2>
            <p class="muted">Удалить аккаунт вместе со всеми данными.</p>
        </div>

        <p class="tiny">
            <strong>Внимание.</strong> Действие необратимо: вместе с аккаунтом
            навсегда удалятся заказы и вся история.
        </p>

        <div class="cluster">
            <button
                class="btn btn--danger btn--m"
                type="button"
                data-test="delete-user-button"
                @click="open = true"
            >
                Удалить аккаунт
            </button>
        </div>
    </section>

    <div
        class="modal"
        :class="{ 'is-open': open }"
        role="dialog"
        aria-modal="true"
        aria-label="Удаление аккаунта"
        @click.self="open = false"
    >
        <div class="modal__box">
            <Form
                v-bind="ProfileController.destroy.form()"
                reset-on-success
                :options="{ preserveScroll: true }"
                class="stack"
                v-slot="{ errors, processing, reset, clearErrors }"
            >
                <h3 class="h3">Точно удалить аккаунт?</h3>
                <p class="muted">
                    Вместе с аккаунтом навсегда удалятся заказы и все данные.
                    Введите пароль, чтобы подтвердить удаление.
                </p>

                <label class="label">
                    <span class="label__text">Пароль</span>
                    <input
                        class="field"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                    />
                    <InputError :message="errors.password" />
                </label>

                <div class="cluster">
                    <button
                        class="btn btn--danger btn--m"
                        type="submit"
                        :disabled="processing"
                        data-test="confirm-delete-user-button"
                    >
                        Удалить аккаунт
                    </button>
                    <button
                        class="btn btn--ghost btn--m"
                        type="button"
                        @click="
                            () => {
                                clearErrors();
                                reset();
                                open = false;
                            }
                        "
                    >
                        Отмена
                    </button>
                </div>
            </Form>
        </div>
    </div>
</template>
