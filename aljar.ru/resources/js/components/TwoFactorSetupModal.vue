<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

/**
 * Настройка двухфакторной защиты: QR-код, ключ для ручного ввода и
 * подтверждение шестизначным кодом.
 *
 * Окно — из прототипа (.modal / .modal__box), как и остальные окна сайта.
 * Логика прежняя: данные берутся тем же композаблом, подтверждение уходит
 * на тот же маршрут Fortify.
 */

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Двухфакторная защита включена',
            description:
                'Отсканируйте QR-код или введите ключ в приложении-аутентификаторе.',
            buttonText: 'Готово',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Проверка кода',
            description:
                'Введите шестизначный код из приложения-аутентификатора.',
            buttonText: 'Продолжить',
        };
    }

    return {
        title: 'Включить двухфакторную защиту',
        description:
            'Отсканируйте QR-код или введите ключ в приложении-аутентификаторе.',
        buttonText: 'Продолжить',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

/** Код только из цифр: приложение выдаёт шесть цифр, и ничего кроме. */
const onCodeInput = (event: Event) => {
    const input = event.target as HTMLInputElement;

    code.value = input.value.replace(/\D/g, '').slice(0, 6);
    input.value = code.value;
};

watch(
    () => isOpen.value,
    async (open) => {
        if (!open) {
            resetModalState();

            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <div
        class="modal"
        :class="{ 'is-open': isOpen }"
        role="dialog"
        aria-modal="true"
        :aria-label="modalConfig.title"
        @click.self="isOpen = false"
    >
        <div class="modal__box">
            <div>
                <h3 class="h3">{{ modalConfig.title }}</h3>
                <p class="muted">{{ modalConfig.description }}</p>
            </div>

            <template v-if="!showVerificationStep">
                <AlertError v-if="errors?.length" :errors="errors" />

                <template v-else>
                    <div class="two-factor-qr">
                        <p v-if="!qrCodeSvg" class="tiny">Готовим код…</p>
                        <div
                            v-else
                            v-html="qrCodeSvg"
                            :style="{
                                filter:
                                    resolvedAppearance === 'dark'
                                        ? 'invert(1) brightness(1.5)'
                                        : undefined,
                            }"
                        />
                    </div>

                    <label class="label">
                        <span class="label__text">Ключ для ручного ввода</span>
                        <input
                            class="field"
                            type="text"
                            readonly
                            :value="manualSetupKey ?? 'Готовим ключ…'"
                        />
                    </label>

                    <div class="cluster">
                        <button
                            class="btn btn--petrol btn--m"
                            type="button"
                            @click="handleModalNextStep"
                        >
                            {{ modalConfig.buttonText }}
                        </button>
                        <button
                            class="btn btn--ghost btn--m"
                            type="button"
                            :disabled="!manualSetupKey"
                            @click="copy(manualSetupKey || '')"
                        >
                            {{ copied ? 'Скопировано' : 'Скопировать ключ' }}
                        </button>
                    </div>
                </template>
            </template>

            <template v-else>
                <Form
                    v-bind="confirm.form()"
                    error-bag="confirmTwoFactorAuthentication"
                    reset-on-error
                    @finish="code = ''"
                    @success="isOpen = false"
                    class="stack"
                    v-slot="{ errors: formErrors, processing }"
                >
                    <input type="hidden" name="code" :value="code" />

                    <label ref="pinInputContainerRef" class="label">
                        <span class="label__text">Код из приложения</span>
                        <input
                            class="field two-factor-code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="000000"
                            :disabled="processing"
                            :value="code"
                            @input="onCodeInput"
                        />
                        <InputError :message="formErrors?.code" />
                    </label>

                    <div class="cluster">
                        <button
                            class="btn btn--petrol btn--m"
                            type="submit"
                            :disabled="processing || code.length < 6"
                        >
                            Подтвердить
                        </button>
                        <button
                            class="btn btn--ghost btn--m"
                            type="button"
                            :disabled="processing"
                            @click="showVerificationStep = false"
                        >
                            Назад
                        </button>
                    </div>
                </Form>
            </template>
        </div>
    </div>
</template>

<style scoped>
/* QR-код приходит готовым SVG: задаём ему рамку и размер, внутрь не
   вмешиваемся. */
.two-factor-qr {
    display: grid;
    place-items: center;
    padding: 16px;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: #fff;
}
.two-factor-qr :deep(svg) {
    width: 200px;
    height: 200px;
}

/* Код вводят по цифре: моноширинный шрифт и разрядка помогают свериться. */
.two-factor-code {
    font-family: ui-monospace, 'SF Mono', Menlo, Consolas, monospace;
    letter-spacing: 0.4em;
    text-align: center;
}
</style>
