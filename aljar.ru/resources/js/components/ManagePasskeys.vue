<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type { Passkey } from '@/types/auth';

/**
 * Ключи доступа. Разметка на классах витрины; работа с ключами прежняя.
 *
 * Входа по ключу на странице входа сейчас нет — ключом подтверждают
 * пароль в защищённых разделах.
 */

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), { preserveScroll: true, onError });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <section v-if="canManagePasskeys" class="panel stack">
        <div>
            <h2 class="h3">Ключи доступа</h2>
            <p class="muted">
                Подтверждение вместо пароля — по отпечатку, лицу или PIN.
            </p>
        </div>

        <template v-if="passkeys.length">
            <PasskeyItem
                v-for="passkey in passkeys"
                :key="passkey.id"
                :passkey="passkey"
                @remove="handleDelete"
            />
        </template>

        <p v-else class="tiny">
            Ключей пока нет. Добавьте ключ, чтобы не вводить пароль в защищённых
            разделах.
        </p>

        <PasskeyRegister @success="handleRegisterSuccess" />
    </section>
</template>
