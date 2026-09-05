<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

/**
 * Журнал. Разметка и классы перенесены из прототипа
 * versions/v2-anim/blog.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Список приходит с сервера — сейчас из массива в контроллере, дальше из
 * таблицы статей.
 */

defineProps<{
    articles: {
        slug: string;
        title: string;
        tag: string;
        date: string;
        image: string;
        excerpt: string;
    }[];
}>();
</script>

<template>
    <Head title="Журнал" />

    <div class="container">
        <div class="page-hero">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> · Журнал
            </nav>
            <h1 class="h2">Журнал</h1>
            <p class="lead">
                Гайды по завариванию, обжарка, наследие. Спокойный экспертный
                тон — без крика.
            </p>
        </div>

        <div class="blog-grid">
            <Link
                v-for="article in articles"
                :key="article.slug"
                class="card article-card"
                :href="`/blog/${article.slug}`"
            >
                <img
                    :src="`/${article.image}`"
                    width="1152"
                    height="864"
                    loading="lazy"
                    decoding="async"
                    alt=""
                />
                <div class="card__body">
                    <span class="eyebrow">{{ article.tag }}</span>
                    <h2 class="h3">{{ article.title }}</h2>
                    <p class="tiny">{{ article.date }}</p>
                </div>
            </Link>
        </div>
    </div>
</template>
