<script setup lang="ts">
import { ref } from 'vue';
import type { Passkey } from '@/types/auth';

/** Строка списка ключей: сведения и удаление с подтверждением. */

const props = defineProps<{ passkey: Passkey }>();

const emit = defineEmits<{ remove: [id: number, onError: () => void] }>();

const isDeleting = ref(false);
const confirming = ref(false);

const handleDelete = () => {
    isDeleting.value = true;
    emit('remove', props.passkey.id, () => {
        isDeleting.value = false;
        confirming.value = false;
    });
};
</script>

<template>
    <div class="order-row">
        <div>
            <strong>{{ passkey.name }}</strong>
            <div v-if="passkey.authenticator" class="tiny">
                {{ passkey.authenticator }}
            </div>
            <div class="tiny">
                Добавлен {{ passkey.created_at_diff }}
                <template v-if="passkey.last_used_at_diff">
                    · последний вход {{ passkey.last_used_at_diff }}
                </template>
            </div>
        </div>

        <div class="cluster">
            <template v-if="confirming">
                <button
                    class="btn btn--danger btn--s"
                    type="button"
                    :disabled="isDeleting"
                    @click="handleDelete"
                >
                    {{ isDeleting ? 'Удаляем…' : 'Точно удалить' }}
                </button>
                <button
                    class="btn btn--ghost btn--s"
                    type="button"
                    @click="confirming = false"
                >
                    Отмена
                </button>
            </template>
            <button
                v-else
                class="btn btn--ghost btn--s"
                type="button"
                @click="confirming = true"
            >
                Удалить
            </button>
        </div>
    </div>
</template>
