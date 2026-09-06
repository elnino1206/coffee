<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { FlashToast } from '@/types/ui';

/**
 * Всплывающее уведомление витрины.
 *
 * Тосты сервер шлёт флеш-сообщением, но показывать их было некому:
 * контейнер стартового набора живёт только в его раскладках, а витрина
 * ушла на свою. Добавление в корзину и подписка на рассылку сообщали об
 * успехе в пустоту.
 *
 * Разметка — из прототипа (.toast), поэтому уведомление выглядит как
 * часть сайта, а не как всплывашка чужой библиотеки.
 */

const message = ref('');
const shown = ref(false);

let hideTimer: ReturnType<typeof setTimeout>;
let clearTimer: ReturnType<typeof setTimeout>;

const show = (text: string): void => {
    clearTimeout(hideTimer);
    clearTimeout(clearTimer);

    message.value = text;
    shown.value = true;

    hideTimer = setTimeout(() => (shown.value = false), 3200);
    // Текст убираем позже, чем прячем: иначе он исчезнет посреди ухода.
    clearTimer = setTimeout(() => (message.value = ''), 3600);
};

let stop: (() => void) | undefined;

onMounted(() => {
    stop = router.on('flash', (event) => {
        const data = (event as CustomEvent).detail?.flash?.toast as
            FlashToast | undefined;

        if (data?.message) {
            show(data.message);
        }
    });
});

onBeforeUnmount(() => {
    clearTimeout(hideTimer);
    clearTimeout(clearTimer);
    stop?.();
});
</script>

<template>
    <div
        class="toast"
        :class="{ 'is-show': shown }"
        role="status"
        aria-live="polite"
    >
        {{ message }}
    </div>
</template>
