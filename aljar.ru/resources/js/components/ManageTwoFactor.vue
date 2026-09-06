<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

/**
 * Двухфакторная защита. Разметка на классах витрины; логика прежняя —
 * включение, выключение и резервные коды остались фортифаевскими.
 */

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <section v-if="canManageTwoFactor" class="panel stack">
        <div>
            <h2 class="h3">Двухфакторная защита</h2>
            <p class="muted">Вход с подтверждением по коду.</p>
        </div>

        <template v-if="!twoFactorEnabled">
            <p class="tiny">
                После включения вход будет спрашивать код из
                приложения-аутентификатора на телефоне.
            </p>

            <div class="cluster">
                <button
                    v-if="hasSetupData"
                    class="btn btn--petrol btn--m"
                    type="button"
                    @click="showSetupModal = true"
                >
                    Продолжить настройку
                </button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <button
                        class="btn btn--petrol btn--m"
                        type="submit"
                        :disabled="processing"
                    >
                        Включить
                    </button>
                </Form>
            </div>
        </template>

        <template v-else>
            <p class="tiny">
                Вход спрашивает код из приложения-аутентификатора на телефоне.
            </p>

            <div class="cluster">
                <Form v-bind="disable.form()" #default="{ processing }">
                    <button
                        class="btn btn--danger btn--m"
                        type="submit"
                        :disabled="processing"
                    >
                        Выключить
                    </button>
                </Form>
            </div>

            <TwoFactorRecoveryCodes />
        </template>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </section>
</template>
