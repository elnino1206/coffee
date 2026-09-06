<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';

/**
 * Безопасность: пароль, двухфакторная защита и ключи доступа. Разметка на
 * классах витрины, логика прежняя — контроллер и правила не менялись.
 */

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();
</script>

<template>
    <Head title="Безопасность" />

    <section class="panel stack">
        <div>
            <h2 class="h3">Смена пароля</h2>
            <p class="muted">
                Длинный случайный пароль — лучшая защита аккаунта.
            </p>
        </div>

        <Form
            v-bind="SecurityController.update.form()"
            :options="{ preserveScroll: true }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="stack"
            v-slot="{ errors, processing }"
        >
            <label class="label">
                <span class="label__text">Текущий пароль</span>
                <input
                    class="field"
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                />
                <InputError :message="errors.current_password" />
            </label>

            <label class="label">
                <span class="label__text">Новый пароль</span>
                <input
                    class="field"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password" />
            </label>

            <label class="label">
                <span class="label__text">Пароль ещё раз</span>
                <input
                    class="field"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    :passwordrules="props.passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </label>

            <div class="cluster">
                <button
                    class="btn btn--petrol btn--m"
                    type="submit"
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Сохранить
                </button>
            </div>
        </Form>
    </section>

    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />

    <ManagePasskeys
        :canManagePasskeys="canManagePasskeys"
        :passkeys="passkeys"
    />
</template>
