<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <div class="stack">
        <div>
            <h3 class="h3">Резервные коды</h3>
            <p class="muted">
                Вернут доступ, если телефон потеряется. Храните их в менеджере
                паролей.
            </p>
        </div>

        <div class="cluster">
            <button
                class="btn btn--ghost btn--m"
                type="button"
                @click="toggleRecoveryCodesVisibility"
            >
                {{ isRecoveryCodesVisible ? 'Скрыть коды' : 'Показать коды' }}
            </button>

            <Form
                v-if="isRecoveryCodesVisible"
                v-bind="regenerateRecoveryCodes.form()"
                @success="fetchRecoveryCodes"
                #default="{ processing }"
            >
                <button
                    class="btn btn--ghost btn--m"
                    type="submit"
                    :disabled="processing"
                >
                    Обновить коды
                </button>
            </Form>
        </div>

        <AlertError v-if="errors.length" :errors="errors" />

        <div v-if="isRecoveryCodesVisible" class="stack">
            <div ref="recoveryCodeSectionRef" class="panel recovery-codes">
                <p v-if="!recoveryCodesList.length" class="tiny">Загружаем…</p>
                <p v-for="(code, index) in recoveryCodesList" :key="index">
                    {{ code }}
                </p>
            </div>
            <p class="tiny">
                Каждый код срабатывает один раз и после этого исчезает. Когда
                закончатся, нажмите «Обновить коды».
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Коды — единственное место, где нужен моноширинный шрифт: их сверяют
   символ за символом. */
.recovery-codes p {
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    font-size: 14px;
    line-height: 1.7;
    margin: 0;
}
</style>
