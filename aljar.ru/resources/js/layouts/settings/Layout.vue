<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

/**
 * Оболочка настроек. Раньше это был экран стартового набора с боковой
 * панелью и своими компонентами — он выбивался из сайта, хотя покупатель
 * попадает сюда прямо из кабинета.
 *
 * Теперь та же навигация и те же панели, что в кабинете: настройки —
 * его продолжение, а не отдельное приложение.
 */

const links = [
    { title: 'Профиль', href: editProfile() },
    { title: 'Безопасность', href: editSecurity() },
    { title: 'Оформление', href: editAppearance() },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="container">
        <nav class="account-nav" aria-label="Настройки">
            <Link href="/account">Кабинет</Link>
            <Link
                v-for="link in links"
                :key="link.title"
                :href="link.href"
                :class="{ 'is-active': isCurrentOrParentUrl(link.href) }"
            >
                {{ link.title }}
            </Link>
        </nav>

        <div class="stack-l">
            <div>
                <p class="eyebrow">Учётная запись</p>
                <h1 class="h2">Настройки</h1>
                <p class="muted">Профиль, безопасность и оформление.</p>
            </div>

            <slot />
        </div>
    </div>
</template>
