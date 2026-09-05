<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

/**
 * Статья. Разметка и классы перенесены из прототипа
 * versions/v2-anim/article.html без изменений: дизайн утверждён,
 * переписывать его на другие классы значит расходиться с эталоном.
 *
 * Текст есть не у всех статей: в прототипе страница показывала один и
 * тот же материал для любой ссылки. Где текста нет, страница говорит об
 * этом прямо, а не показывает чужой.
 */

defineProps<{
    article: {
        slug: string;
        title: string;
        tag: string;
        date: string;
        image: string;
        excerpt: string;
        body: string[];
    };
    more: {
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
    <Head :title="article.title" />

    <div class="container">
        <article class="prose" style="padding-top: 36px">
            <nav class="breadcrumbs">
                <Link href="/">Главная</Link> ·
                <Link href="/blog">Журнал</Link> · Статья
            </nav>
            <p class="eyebrow">{{ article.tag }}</p>
            <h1 class="h2">{{ article.title }}</h1>
            <p class="tiny">{{ article.date }}</p>

            <div class="torn torn--cover">
                <img
                    class="cover"
                    :src="`/${article.image}`"
                    width="1152"
                    height="864"
                    fetchpriority="high"
                    decoding="async"
                    :alt="article.title"
                />
            </div>

            <template v-if="article.body.length">
                <p v-for="(paragraph, index) in article.body" :key="index">
                    {{ paragraph }}
                </p>
            </template>
            <template v-else>
                <p class="lead">{{ article.excerpt }}</p>
                <p class="muted">
                    Полный текст этой статьи ещё не написан — ждём его от
                    редакции. Анонс выше показывает, о чём она будет.
                </p>
            </template>

            <p>
                <Link class="btn btn--primary" href="/catalog/coffee">
                    Смотреть кофе →
                </Link>
            </p>
        </article>

        <section class="section" v-if="more.length">
            <h2 class="h3" style="margin-bottom: 16px">Ещё в журнале</h2>
            <div class="blog-grid">
                <Link
                    v-for="item in more"
                    :key="item.slug"
                    class="card article-card"
                    :href="`/blog/${item.slug}`"
                >
                    <img
                        :src="`/${item.image}`"
                        width="1152"
                        height="864"
                        loading="lazy"
                        decoding="async"
                        alt=""
                    />
                    <div class="card__body">
                        <span class="eyebrow">{{ item.tag }}</span>
                        <h3 class="h3">{{ item.title }}</h3>
                        <p class="tiny">{{ item.date }}</p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
