import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';

/**
 * Горизонтальный рельс главной.
 *
 * Механика взята из проекта O'Hara (site/src/useBar.js): секции стоят в
 * ряд, колесо перехватывается, дельты копятся, и при переходе порога
 * рельс уезжает к соседней панели. Одна прокрутка колеса — одна панель,
 * как бы резко её ни крутнули. Снизу идёт шкала, по которой плавно
 * догоняет позицию кольцо-бегунок.
 *
 * Из референса взято только устройство прокрутки: цвета, шрифты и
 * раскладка секций остались нашими.
 */

/** Порог накопителя, после которого происходит переход. */
const THRESHOLD = 70;

/** Пауза без колеса, после которой накопитель обнуляется. */
const ACC_RESET = 160;

/** Сколько держим замок, пока идёт плавный переход. */
const LOCK = 720;
const LOCK_REDUCED = 40;

/** Ниже этой ширины рельс разворачивается по вертикали и не листается. */
const DESKTOP_FROM = 900;

/**
 * Насколько содержимое уходящей панели отстаёт от неё — доля ширины
 * панели.
 *
 * При 0.46 содержимое идёт примерно вдвое медленнее самой панели, и
 * следующая наезжает сверху. Больше половины брать нельзя: содержимое
 * вышло бы за свою рамку, и слева открылась бы пустота.
 */
const SLIP = 0.46;

export function useRail(rail: Ref<HTMLElement | null>) {
    /** Индекс текущей панели — по нему подсвечивается подпись на шкале. */
    const current = ref(0);

    /** Доля пройденного рельса, 0..1 — по ней едет бегунок шкалы. */
    const progress = ref(0);

    let acc = 0;
    let locked = false;
    let accTimer: ReturnType<typeof setTimeout> | undefined;
    let lockTimer: ReturnType<typeof setTimeout> | undefined;

    const reduced = () =>
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const desktop = () => window.innerWidth >= DESKTOP_FROM;

    const panels = (): HTMLElement[] => [
        ...(rail.value?.querySelectorAll<HTMLElement>('.rail__panel') ?? []),
    ];

    /**
     * Разложить панели по глубине: та, что уехала влево, отстаёт от
     * прокрутки, та, что въезжает справа, идёт вровень.
     *
     * Считается здесь, а не привязанной к прокрутке анимацией в CSS: у
     * панели включена своя вертикальная прокрутка, из-за чего
     * `view(inline)` у содержимого разрешается в саму панель вместо
     * рельса.
     */
    function layer(): void {
        const el = rail.value;

        if (!el || !el.clientWidth) {
            return;
        }

        const width = el.clientWidth;
        const on = desktop() && !reduced();

        panels().forEach((panel, i) => {
            /* Насколько панель уехала влево от кадра: 0 — она в кадре,
               1 — ушла целиком. Отрицательное (панель справа) отбрасываем. */
            const away = Math.min(
                1,
                Math.max(0, (el.scrollLeft - i * width) / width),
            );

            /* Где левая кромка панели: 0 — у левого края экрана, 1 — у
               правого. Тень на ней нужна только в проезде, поэтому в
               обоих покоях гасим, а посередине даём в полную силу. */
            const rim = Math.min(
                1,
                Math.max(0, (i * width - el.scrollLeft) / width),
            );

            panel.style.setProperty(
                '--slip',
                on ? `${away * SLIP * width}px` : '0px',
            );
            panel.style.setProperty(
                '--rim',
                on ? String(1 - Math.abs(2 * rim - 1)) : '0',
            );
        });
    }

    /** Индекс панели по фактическому положению рельса. */
    function indexFromScroll(): number {
        const el = rail.value;

        if (!el || !el.clientWidth) {
            return 0;
        }

        return Math.max(
            0,
            Math.min(
                panels().length - 1,
                Math.round(el.scrollLeft / el.clientWidth),
            ),
        );
    }

    function unlockSoon(ms?: number): void {
        clearTimeout(lockTimer);
        lockTimer = setTimeout(
            () => (locked = false),
            ms ?? (reduced() ? LOCK_REDUCED : LOCK),
        );
    }

    function go(i: number): void {
        const el = rail.value;
        const list = panels();
        const n = Math.max(0, Math.min(list.length - 1, i));

        if (!el || !list[n]) {
            return;
        }

        /* Прыжок больше чем на панель и режим без движения идут мгновенно:
           долгий проезд через промежуточные секции читается как сбой. */
        const jump = reduced() || Math.abs(n - indexFromScroll()) > 1;

        locked = true;
        acc = 0;
        current.value = n;

        el.scrollTo({
            left: n * el.clientWidth,
            behavior: jump ? 'auto' : 'smooth',
        });
        unlockSoon(jump ? 50 : undefined);
    }

    /**
     * Перехватывать колесо можно не везде: внутри поля ввода, открытого
     * окна или самой панели, пока она не докрутилась до своего края.
     */
    function canPage(target: EventTarget | null, delta: number): boolean {
        const el = target as HTMLElement | null;

        if (!el?.closest) {
            return true;
        }

        if (el.closest('input, textarea, select')) {
            return false;
        }

        if (document.querySelector('[data-add-modal]')) {
            return false;
        }

        if (document.querySelector('.search-panel.is-open')) {
            return false;
        }

        const panel = el.closest<HTMLElement>('.rail__panel');

        if (panel && panel.scrollHeight > panel.clientHeight + 2) {
            const max = panel.scrollHeight - panel.clientHeight;

            if (delta > 0 && panel.scrollTop < max - 2) {
                return false;
            }

            if (delta < 0 && panel.scrollTop > 2) {
                return false;
            }
        }

        return true;
    }

    function onWheel(e: WheelEvent): void {
        if (!desktop() || !rail.value) {
            return;
        }

        const along =
            Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;

        if (!along) {
            return;
        }

        if (!canPage(e.target, along)) {
            return;
        }

        /* С последней панели вперёд прокрутку отпускаем: ниже рельса
           лежит подвал, и до него нужно доехать обычным образом. */
        const at = indexFromScroll();

        if (along > 0 && at === panels().length - 1) {
            return;
        }

        e.preventDefault();

        if (locked) {
            return;
        }

        /* deltaMode: 0 — пиксели, 1 — строки, 2 — экраны. Без приведения
           одно движение на разных устройствах даёт разный шаг. */
        const step =
            e.deltaMode === 1
                ? along * 16
                : e.deltaMode === 2
                  ? along * window.innerWidth
                  : along;

        acc += step;
        clearTimeout(accTimer);
        accTimer = setTimeout(() => (acc = 0), ACC_RESET);

        if (acc > THRESHOLD) {
            go(at + 1);
        } else if (acc < -THRESHOLD) {
            go(at - 1);
        }
    }

    function onKey(e: KeyboardEvent): void {
        if (!desktop() || locked) {
            return;
        }

        if (!canPage(e.target, 1)) {
            return;
        }

        const forward = e.key === 'ArrowRight' || e.key === 'PageDown';
        const back = e.key === 'ArrowLeft' || e.key === 'PageUp';

        if (!forward && !back) {
            return;
        }

        e.preventDefault();
        go(indexFromScroll() + (forward ? 1 : -1));
    }

    /** Рельс могли прокрутить и мимо колеса — тянем за ним. */
    function onScroll(): void {
        const el = rail.value;

        if (!el) {
            return;
        }

        const max = el.scrollWidth - el.clientWidth;

        progress.value = max > 0 ? el.scrollLeft / max : 0;

        if (!locked) {
            current.value = indexFromScroll();
        }

        layer();
    }

    function onResize(): void {
        /* Ширина панели изменилась — положение рельса нужно пересчитать,
           иначе он останется между секциями. */
        const el = rail.value;

        if (el && desktop()) {
            el.scrollTo({
                left: current.value * el.clientWidth,
                behavior: 'auto',
            });
        }

        onScroll();
    }

    onMounted(() => {
        /* passive: false — иначе preventDefault не сработает и страница
           уедет обычной прокруткой поверх нашей. */
        window.addEventListener('wheel', onWheel, { passive: false });
        window.addEventListener('keydown', onKey);
        window.addEventListener('resize', onResize);
        rail.value?.addEventListener('scroll', onScroll, { passive: true });

        onScroll();
    });

    onBeforeUnmount(() => {
        clearTimeout(accTimer);
        clearTimeout(lockTimer);
        window.removeEventListener('wheel', onWheel);
        window.removeEventListener('keydown', onKey);
        window.removeEventListener('resize', onResize);
        rail.value?.removeEventListener('scroll', onScroll);
    });

    return { current, go, progress };
}
