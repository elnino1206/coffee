<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';

/** Оформление: светлая или тёмная тема. */
</script>

<template>
    <Head title="Оформление" />

    <section class="panel stack">
        <div>
            <h2 class="h3">Оформление</h2>
            <p class="muted">Светлая или тёмная тема.</p>
        </div>

        <AppearanceTabs />
    </section>
</template>
