<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * Подвал витрины. Вынесен из раскладки отдельным компонентом, потому что
 * на главной он живёт не после содержимого, а внутри последней панели
 * рельса: там страница занимает ровно экран, и второй оси прокрутки быть
 * не должно.
 */
</script>

<template>
    <footer class="site-footer">
        <div class="footer-grid container">
            <div class="footer-brand">
                <Link class="logo logo--lg" href="/">
                    <img
                        src="/img/logo.webp"
                        alt="Al Jar Coffee"
                        width="187"
                        height="138"
                        loading="lazy"
                        decoding="async"
                    />
                </Link>
                <p class="tiny">
                    Семейный бренд с ливанскими корнями и современной обжаркой
                    полного цикла в России. От зерна к чашке.
                </p>
            </div>
            <div class="footer-col">
                <h4>Магазин</h4>
                <Link href="/catalog/coffee">Каталог</Link>
                <a href="/catalog/coffee?method[]=espresso">Для эспрессо</a>
                <a href="/catalog/coffee?method[]=filter">Для фильтра</a>
            </div>
            <div class="footer-col">
                <h4>Контакты</h4>
                <a href="tel:+79851379235">8 (985) 137-92-35</a>
                <a href="mailto:hello@aljar.ru">hello@aljar.ru</a>
                <p class="tiny" style="margin-top: 8px">
                    Телефон — основной способ связи. Email по желанию.
                </p>
            </div>
        </div>
        <div class="footer-bottom container">
            <span>© 2026 Al Jar Coffee. Все права защищены.</span>
            <span>Обжариваем в России · Корни в Ливане</span>
        </div>
    </footer>
</template>
