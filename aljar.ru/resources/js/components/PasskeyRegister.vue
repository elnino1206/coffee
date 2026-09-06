<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = [
        { pattern: /Edg|Edge/, name: 'Edge' },
        { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
        { pattern: /Firefox|FxiOS/, name: 'Firefox' },
        { pattern: /Chrome|CriOS/, name: 'Chrome' },
        { pattern: /Safari/, name: 'Safari' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    const os = [
        { pattern: /iPhone/, name: 'iPhone' },
        { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
        { pattern: /Android/, name: 'Android' },
        { pattern: /Mac/, name: 'Mac' },
        { pattern: /Windows/, name: 'Windows' },
    ].find(({ pattern }) => pattern.test(ua))?.name;

    return [browser, os].filter(Boolean).join(' on ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!name.value.trim()) {
        return;
    }

    await register(name.value);
};

const handleCancel = () => {
    showForm.value = false;
    name.value = '';
};
</script>

<template>
    <p v-if="!isSupported" class="tiny">
        Этот браузер не поддерживает ключи доступа.
    </p>

    <div v-else-if="!showForm" class="cluster">
        <button
            class="btn btn--ghost btn--m"
            type="button"
            @click="showForm = true"
        >
            Добавить ключ
        </button>
    </div>

    <form v-else class="stack" @submit="handleSubmit">
        <label class="label">
            <span class="label__text">Название ключа</span>
            <input
                id="passkey-name"
                class="field"
                type="text"
                v-model="name"
                placeholder="например, ноутбук или телефон"
                autofocus
            />
            <span class="tiny"
                >Название поможет узнать этот ключ в списке.</span
            >
        </label>

        <InputError v-if="error" :message="error" />

        <div class="cluster">
            <button
                class="btn btn--petrol btn--m"
                type="submit"
                :disabled="isLoading || !name.trim()"
            >
                {{ isLoading ? 'Добавляем…' : 'Добавить ключ доступа' }}
            </button>
            <button
                class="btn btn--ghost btn--m"
                type="button"
                @click="handleCancel"
            >
                Отмена
            </button>
        </div>
    </form>
</template>
