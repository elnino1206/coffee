import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import { motionOn } from '@/lib/motion';

/**
 * Сцена с прокруткой-скрабом: первые экраны главной.
 *
 * Секция высотой в несколько экранов приколота, внутри неё лежат ролики,
 * и положение ролика задаётся не воспроизведением, а прокруткой: вниз —
 * вперёд, вверх — назад, кадр в кадр. Поверх роликов лежат подписи
 * блоков: одна уезжает вверх, следующая выезжает снизу, между ними
 * остаётся промежуток, где на экране только съёмка.
 *
 * Переходов столько, сколько передали роликов, блоков на один больше.
 * Сцена делится между переходами поровну, и внутри своей доли каждый
 * идёт по одной и той же хореографии.
 *
 * Подписи не растворяются, а едут — по устройству острова из героя:
 * сцена гонит доли, а каждая строка умножает их на свою глубину. Разные
 * глубины дают расслоение: строки не идут единой плитой.
 *
 * Ни `play()`, ни `pause()` здесь нет вовсе. Ролик не идёт сам по себе:
 * человек его листает.
 */

/** Доли внутри одного перехода. Взяты из хореографии. */
const FILM_FROM = 0.08;
const FILM_TO = 0.88;
const OUT_FROM = 0.08;
const OUT_TO = 0.4;
const IN_FROM = 0.52;
const IN_TO = 0.88;

/** Полуширина стыка, на котором следующий ролик подменяет предыдущий. */
const SWAP = 0.03;

const clamp = (v: number): number => (v < 0 ? 0 : v > 1 ? 1 : v);

/** Доля внутри отрезка: 0 до его начала, 1 после конца. */
const span = (v: number, from: number, to: number): number =>
    clamp((v - from) / (to - from));

export function useScrollScene(
    scene: Ref<HTMLElement | null>,
    films: Ref<(HTMLVideoElement | null)[]>,
) {
    /** Пройденная доля сцены, 0..1. */
    const progress = ref(0);

    /**
     * Какая подпись сейчас принимает нажатия.
     *
     * Только одна: у остальных строки либо ещё за краем экрана, либо уже
     * за ним. `-1` — переход в разгаре, на экране одна съёмка.
     */
    const live = ref(0);

    let ticking = false;

    function paint(): void {
        const el = scene.value;

        if (!el) {
            return;
        }

        const run = el.offsetHeight - window.innerHeight;
        const p = run > 0 ? clamp(-el.getBoundingClientRect().top / run) : 0;

        progress.value = p;

        /* Пока сцена на экране, шапка лежит поверх съёмки и набрана
           светлым. Класс на корне, а не на самой шапке: шапку рисует
           раскладка, и страница до неё не дотягивается. */
        document.documentElement.classList.toggle('is-over-film', p < 0.98);

        const steps = films.value.length;
        const out: number[] = [];
        const arrive: number[] = [];

        films.value.forEach((v, i) => {
            /* Доля внутри своего перехода: до него 0, после 1. */
            const q = span(p, i / steps, (i + 1) / steps);

            /* Доли хода, а не прозрачности: 0 — строка на своём месте,
               1 — она полностью убрана за край. Насколько это далеко,
               решает глубина строки в стилях. */
            out[i] = span(q, OUT_FROM, OUT_TO);
            arrive[i] = 1 - span(q, IN_FROM, IN_TO);

            el.style.setProperty(`--out-${i + 1}`, String(out[i]));
            el.style.setProperty(`--in-${i + 1}`, String(arrive[i]));

            /* Ролики лежат стопкой: следующий проявляется на стыке, где
               на экране одна съёмка и подписей не видно. Там подмена
               незаметна, а держать оба видимыми нельзя — нижний
               просвечивал бы сквозь верхний. */
            if (i > 0) {
                el.style.setProperty(
                    `--film-${i + 1}`,
                    String(span(p, i / steps - SWAP, i / steps + SWAP)),
                );
            }

            /* Ролик стоит на месте до начала и после конца своего
               отрезка: первый и последний кадры должны держаться, чтобы
               подписи вставали на спокойную картинку. */
            if (v && Number.isFinite(v.duration) && v.duration > 0) {
                const t = span(q, FILM_FROM, FILM_TO) * v.duration;

                /* Порог в кадр: без него каждая прокрутка дёргает
                   перемотку на сотые доли и браузер захлёбывается
                   запросами. */
                if (Math.abs(v.currentTime - t) > 0.03) {
                    v.currentTime = t;
                }
            }
        });

        /* Подпись живая, когда она уже приехала и ещё не тронулась. У
           первой приезда нет, у последней — отъезда. */
        let at = -1;

        for (let i = 0; i <= steps; i += 1) {
            const came = i === 0 || arrive[i - 1] === 0;
            const left = i < steps && out[i] > 0;

            if (came && !left) {
                at = i;
            }
        }

        live.value = at;
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
     * Расшевелить ролики.
     *
     * Пока видео ни разу не запускали, браузер держит его на метаданных:
     * `preload` — просьба, а не обязательство, и на телефоне её обычно
     * игнорируют ради трафика. Перемотка при этом молчит, и на экране
     * остаётся постер — ровно то, что выглядит как «видео не работает».
     *
     * Лечение известное: один раз запустить и тут же остановить. Кадры
     * декодируются, готовность доходит до нужной, и дальше перемотка
     * отвечает сразу. Звука нет, поэтому запуск разрешён без участия
     * человека; если всё же откажут — повторим при первом касании.
     */
    function wake(): void {
        films.value.forEach((v) => {
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
                    window.addEventListener('pointerdown', wake, {
                        once: true,
                    });
                    window.addEventListener('touchstart', wake, { once: true });
                });
        });
    }

    onMounted(() => {
        if (!motionOn()) {
            /* Без движения сцена не приколота и ролики не листаются:
               подписи просто стоят одна под другой. Разметка та же,
               раскладку меняет CSS. */
            return;
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        /* Длительность приходит позже разметки: до неё перематывать
           нечего, поэтому первый расчёт повторяем по готовности. */
        films.value.forEach((v) => v?.addEventListener('loadedmetadata', paint));

        wake();
        paint();
    });

    onBeforeUnmount(() => {
        document.documentElement.classList.remove('is-over-film');
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
        window.removeEventListener('pointerdown', wake);
        window.removeEventListener('touchstart', wake);
        films.value.forEach((v) =>
            v?.removeEventListener('loadedmetadata', paint),
        );
    });

    return { progress, live };
}
