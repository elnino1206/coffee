import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import { motionOn } from '@/lib/motion';

/**
 * Сцена с прокруткой-скрабом: первый экран главной.
 *
 * Секция высотой в несколько экранов приколота, внутри неё лежит ролик, и
 * его положение задаётся не воспроизведением, а прокруткой: вниз —
 * вперёд, вверх — назад, кадр в кадр. Поверх ролика два слоя: подпись
 * героя уезжает вверх, подпись «Свежей партии» выезжает снизу, а между
 * ними остаётся промежуток, где на экране только съёмка.
 *
 * Подписи не растворяются, а именно едут — по устройству острова из
 * героя: сцена гонит одну долю, а каждая строка умножает её на свою
 * глубину. Разные глубины дают расслоение: строки не идут единой
 * плитой. Пустота посередине получается сама собой — обе группы к тому
 * времени за краем экрана.
 *
 * Ни `play()`, ни `pause()` здесь нет вовсе. Ролик не идёт сам по себе:
 * человек его листает.
 */

/** Доли прокрутки, на которых что происходит. Взяты из хореографии. */
const FILM_FROM = 0.08;
const FILM_TO = 0.88;
const HERO_FROM = 0.08;
const HERO_TO = 0.4;
const ROAST_FROM = 0.52;
const ROAST_TO = 0.88;

/** С этой доли вторая подпись уже принимает нажатия. */
const ROAST_LIVE = 0.88;

const clamp = (v: number): number => (v < 0 ? 0 : v > 1 ? 1 : v);

/** Доля внутри отрезка: 0 до его начала, 1 после конца. */
const span = (v: number, from: number, to: number): number =>
    clamp((v - from) / (to - from));

export function useScrollScene(
    scene: Ref<HTMLElement | null>,
    film: Ref<HTMLVideoElement | null>,
) {
    /** Пройденная доля сцены, 0..1. */
    const progress = ref(0);

    /** Вторая подпись доехала — по ней можно нажимать. */
    const roastLive = ref(false);

    let ticking = false;

    function paint(): void {
        const el = scene.value;

        if (!el) {
            return;
        }

        const run = el.offsetHeight - window.innerHeight;
        const p = run > 0 ? clamp(-el.getBoundingClientRect().top / run) : 0;

        progress.value = p;
        roastLive.value = p >= ROAST_LIVE;

        /* Пока сцена на экране, шапка лежит поверх съёмки и набрана
           светлым. Класс на корне, а не на самой шапке: шапку рисует
           раскладка, и страница до неё не дотягивается. */
        document.documentElement.classList.toggle('is-over-film', p < 0.98);

        /* Доли хода, а не прозрачности: 0 — строка на своём месте,
           1 — она полностью убрана за край. Насколько это далеко, решает
           глубина строки в стилях. */
        el.style.setProperty('--hero-out', String(span(p, HERO_FROM, HERO_TO)));
        el.style.setProperty(
            '--roast-in',
            String(1 - span(p, ROAST_FROM, ROAST_TO)),
        );

        /* Ролик стоит на месте до начала и после конца отрезка: первый и
           последний кадры должны держаться, чтобы подписи вставали на
           спокойную картинку. */
        const v = film.value;

        if (v && Number.isFinite(v.duration) && v.duration > 0) {
            const t = span(p, FILM_FROM, FILM_TO) * v.duration;

            /* Порог в кадр: без него каждая прокрутка дёргает перемотку
               на сотые доли и браузер захлёбывается запросами. */
            if (Math.abs(v.currentTime - t) > 0.03) {
                v.currentTime = t;
            }
        }
    }

    function onScroll(): void {
        if (ticking) {
            return;
        }

        ticking = true;
        requestAnimationFrame(() => {
            ticking = false;
            paint();
        });
    }

    /**
     * Расшевелить ролик.
     *
     * Пока видео ни разу не запускали, браузер держит его на метаданных:
     * `preload` — просьба, а не обязательство, и на телефоне её обычно
     * игнорируют ради трафика. Перемотка при этом молчит, и на экране
     * остаётся постер — ровно то, что выглядит как «видео не работает».
     *
     * Лечение известное: один раз запустить и тут же остановить. Кадры
     * декодируются, `readyState` доходит до готовности, и дальше
     * перемотка отвечает сразу. Звука нет, поэтому запуск разрешён без
     * участия человека; если всё же откажут — повторим при первом
     * касании или прокрутке.
     */
    function wake(): void {
        const v = film.value;

        if (!v || v.readyState >= 3) {
            return;
        }

        v.muted = true;
        void v
            .play()
            .then(() => {
                v.pause();
                paint();
            })
            .catch(() => {
                /* Отказали в запуске — попробуем ещё раз, когда человек
                   тронет страницу. Такой запуск уже считается ответом на
                   его действие. */
                window.addEventListener('pointerdown', wake, { once: true });
                window.addEventListener('touchstart', wake, { once: true });
            });
    }

    onMounted(() => {
        if (!motionOn()) {
            /* Без движения сцена не приколота и ролик не листается:
               подписи просто стоят одна под другой. Разметка та же,
               раскладку меняет CSS. */
            return;
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        /* Длительность приходит позже разметки: до неё перематывать
           нечего, поэтому первый расчёт повторяем по готовности. */
        film.value?.addEventListener('loadedmetadata', paint);

        wake();
        paint();
    });

    onBeforeUnmount(() => {
        document.documentElement.classList.remove('is-over-film');
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
        window.removeEventListener('pointerdown', wake);
        window.removeEventListener('touchstart', wake);
        film.value?.removeEventListener('loadedmetadata', paint);
    });

    return { progress, roastLive };
}
