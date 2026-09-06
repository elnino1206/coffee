<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

/** Переключатель темы на витринных кнопках вместо кнопок набора. */

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Светлая' },
    { value: 'dark', Icon: Moon, label: 'Тёмная' },
    { value: 'system', Icon: Monitor, label: 'Как в системе' },
] as const;
</script>

<template>
    <div class="cluster">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            class="btn btn--m"
            :class="appearance === value ? 'btn--petrol' : 'btn--ghost'"
            :aria-pressed="appearance === value"
            @click="updateAppearance(value)"
        >
            <component :is="Icon" width="16" height="16" />
            {{ label }}
        </button>
    </div>
</template>
