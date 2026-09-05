<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Шкала состояния рельса: линия, бегунок и подписи остановок.
 *
 * Приём из проекта O'Hara. Бегунок не прыгает к цели, а догоняет её —
 * доля прокрутки приходит извне, сглаживание живёт здесь.
 */

const props = defineProps<{
    stops: string[];
    current: number;
    /** Доля пройденного рельса, 0..1. */
    progress: number;
}>();

const emit = defineEmits<{ go: [index: number] }>();

/** Насколько бегунок догоняет цель за кадр: меньше — мягче. */
const RING_EASE = 0.085;

/** Отступы бегунка от краёв шкалы. */
const RING_INSET = 30;
const RING_TRACK_TRIM = 100;

const ring = ref<HTMLElement | null>(null);

let raf = 0;
let pos = 0;

const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function loop(): void {
    const step = () => {
        if (ring.value) {
            const target = props.progress * Math.max(0, window.innerWidth - RING_TRACK_TRIM);

            pos += (target - pos) * (reduced() ? 1 : RING_EASE);
            ring.value.style.transform = `translate(${RING_INSET + pos}px, 0)`;
        }

        raf = requestAnimationFrame(step);
    };

    raf = requestAnimationFrame(step);
}

onMounted(loop);
onBeforeUnmount(() => cancelAnimationFrame(raf));

/* Смена страницы сбрасывает бегунок в начало: иначе он приехал бы к новой
   шкале с позиции прежней. */
watch(
    () => props.stops,
    () => (pos = 0),
);
</script>

<template>
    <div class="railbar">
        <div class="railbar__line"></div>
        <div ref="ring" class="railbar__ring"></div>
        <div class="railbar__stops">
            <button
                v-for="(stop, i) in stops"
                :key="stop"
                class="railbar__stop"
                :class="{ 'is-current': current === i }"
                type="button"
                @click="emit('go', i)"
            >
                <span class="lbl">{{ stop }}</span>
                <span>{{ String(i + 1).padStart(2, '0') }}</span>
            </button>
        </div>
    </div>
</template>
