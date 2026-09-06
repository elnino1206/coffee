<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

/**
 * Подвал витрины. Вынесен из раскладки отдельным компонентом, потому что
 * на главной он живёт не после содержимого, а внутри последней панели
 * рельса: там страница занимает ровно экран, и второй оси прокрутки быть
 * не должно.
 *
 * Состав колонок — как в прототипе: раньше их было две, потому что
 * половины страниц ещё не существовало и ссылкам некуда было вести.
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
                <div class="socials">
                    <a
                        class="btn--icon"
                        href="https://t.me/aljarcoffee"
                        aria-label="Telegram"
                        title="Telegram"
                        rel="noopener"
                    >
                        <svg
                            width="19"
                            height="19"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                d="M21.9 4.2 2.9 11.5c-.9.35-.86 1.63.05 1.93l4.6 1.5 1.77 5.35c.22.66 1.06.85 1.54.35l2.6-2.7 4.65 3.42c.6.44 1.46.11 1.61-.62l3.1-14.9c.17-.8-.62-1.5-1.4-1.2Zm-3.3 3.05-7.9 6.9c-.28.24-.46.58-.51.95l-.27 1.98c-.03.2-.31.22-.37.03l-1.05-3.2a.62.62 0 0 1 .26-.72l9.5-6.2c.28-.18.57.2.34.44Z"
                            />
                        </svg>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Магазин</h4>
                <Link href="/catalog/coffee">Каталог</Link>
                <Link href="/subscription">Подписка</Link>
                <a href="/catalog/coffee?method[]=espresso">Для эспрессо</a>
                <a href="/catalog/coffee?method[]=filter">Для фильтра</a>
            </div>
            <div class="footer-col">
                <h4>Компания</h4>
                <Link href="/about">О бренде</Link>
                <Link href="/wholesale">Оптовым покупателям</Link>
                <Link href="/blog">Журнал</Link>
                <Link href="/contacts">Контакты</Link>
            </div>
            <div class="footer-col">
                <h4>Помощь</h4>
                <Link href="/delivery">Доставка и оплата</Link>
                <Link href="/legal#offer">Оферта</Link>
                <Link href="/legal#privacy">Конфиденциальность</Link>
                <Link href="/account">Личный кабинет</Link>
            </div>
            <div class="footer-col">
                <h4>Контакты</h4>
                <a href="tel:+79851379235">8 (985) 137-92-35</a>
                <a href="tel:+79067674725">8 (906) 767-47-25</a>
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
