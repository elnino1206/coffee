/**
 * Слой анимаций витрины. Перенесён из versions/v2-anim/js/main.js —
 * поведение и оговорки те же, изменился только способ подключения:
 * в прототипе это IIFE на всю страницу, здесь — модуль, который
 * вызывают раскладка и отдельные страницы.
 *
 * Движение — надстройка, а не условие работы. Класс is-enhanced на
 * <html> выдаётся один раз и только если движение уместно; вся CSS,
 * которая что-то прячет ради появления, висит под этим классом.
 * Не выдали класс — страница статична и полностью видима.
 */

const reduceMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)');

/**
 * Режим экономии трафика: анимации тянут за собой лишние декодирования
 * картинок, а в этом режиме пользователь просил обратного.
 */
const saveData = (): boolean =>
    (navigator as Navigator & { connection?: { saveData?: boolean } })
        .connection?.saveData === true;

const motionAllowed = (): boolean => !reduceMotion().matches && !saveData();

/**
 * Движение реально включено: класс выдан и его ещё не сняла страховка.
 * Всё, что анимирует по факту, спрашивает именно это, а не намерение.
 */
export const motionOn = (): boolean =>
    document.documentElement.classList.contains('is-enhanced');

/**
 * Решение принимается один раз и только если вкладка видима: на скрытой
 * вкладке IntersectionObserver не отдаёт пересечения, и спрятанные ради
 * появления блоки остались бы спрятанными. Обратно усиление не включаем
 * никогда — иначе уже показанный контент прыгнет в невидимость в момент
 * переключения на вкладку.
 */
export function enableMotion(): void {
    const on =
        motionAllowed() &&
        document.visibilityState === 'visible' &&
        'IntersectionObserver' in window;

    document.documentElement.classList.toggle('is-enhanced', on);
}

let revealObserver: IntersectionObserver | null = null;

/**
 * Один наблюдатель на всю витрину: элементы добавляются по мере того,
 * как Inertia подставляет новые страницы.
 */
export function observeReveals(root: ParentNode = document): void {
    if (!motionOn()) {
        return;
    }

    revealObserver ??= new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-in');
                /* Появление одноразовое: обратно ничего не прячем, иначе
                   прокрутка вверх превращается в мигание. */
                revealObserver?.unobserve(entry.target);
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.12 },
    );

    /* Ступенька считается внутри группы, а группа — это общий родитель:
       карточки одной сетки едут каскадом, разрозненные блоки страницы
       появляются каждый сам по себе, без накопленной задержки. Потолок
       в шесть шагов — на каталоге из 10+ карточек хвост очереди читается
       как подтормаживание, а не как приём. */
    const seq = new Map<Element | null, number>();

    root.querySelectorAll<HTMLElement>('[data-reveal]:not(.is-in)').forEach(
        (el) => {
            if (el.dataset.revealBound) {
                return;
            }

            el.dataset.revealBound = '1';

            const n = seq.get(el.parentElement) ?? 0;
            seq.set(el.parentElement, n + 1);
            el.style.setProperty('--reveal-i', String(Math.min(n, 5)));

            revealObserver?.observe(el);
        },
    );
}

/**
 * Страховка: появление не имеет права съесть контент. Если через две
 * секунды в зоне видимости остались непроявленные блоки — значит
 * наблюдатель по какой-то причине молчит, и мы снимаем усиление целиком.
 * Показанная страница важнее анимации.
 */
export function guardReveals(): void {
    setTimeout(() => {
        if (!motionOn()) {
            return;
        }

        const stuck = [
            ...document.querySelectorAll('[data-reveal]:not(.is-in)'),
        ].some((el) => {
            const r = el.getBoundingClientRect();

            return r.bottom > 0 && r.top < window.innerHeight;
        });

        if (stuck) {
            document.documentElement.classList.remove('is-enhanced');
        }
    }, 2000);
}

/**
 * Прокрутка числа.
 *
 * Конечное значение ставим всегда и сразу: requestAnimationFrame не
 * выполняется в фоновой вкладке, и без этой строки счётчик застрял бы на
 * старом содержимом до момента, когда вкладку откроют. Прокрутка
 * перезапишет значение в том же кадре, до отрисовки, поэтому мигания не
 * будет.
 */
export function animateNumber(
    el: HTMLElement | null,
    from: number,
    to: number,
    format: (value: number) => string = String,
    duration = 700,
): void {
    if (!el) {
        return;
    }

    el.textContent = format(to);

    if (!motionOn() || from === to) {
        return;
    }

    /* Метка прогона: если значение сменилось ещё раз до конца текущей
       прокрутки, старый кадр обязан замолчать, иначе два
       requestAnimationFrame начнут драться за один элемент. */
    const run = (Number(el.dataset.rollRun ?? 0) + 1) % 1024;
    el.dataset.rollRun = String(run);

    const t0 = performance.now();

    const tick = (now: number) => {
        if (el.dataset.rollRun !== String(run)) {
            return;
        }

        const p = Math.min(1, (now - t0) / duration);
        const eased = 1 - Math.pow(1 - p, 3);

        el.textContent = format(from + (to - from) * eased);

        if (p < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
}

/**
 * Перезапуск CSS-анимации: класс снимается, стили пересчитываются, класс
 * возвращается. Без чтения offsetWidth браузер склеит снятие и возврат в
 * один кадр, и анимация не начнётся заново.
 */
export function replayAnimation(el: HTMLElement | null, cls: string): void {
    if (!el) {
        return;
    }

    el.classList.remove(cls);
    void el.offsetWidth;
    el.classList.add(cls);
}

/**
 * Состояние шапки при отрыве от верха страницы.
 *
 * Метка высотой в несколько пикселей и наблюдатель за ней вместо
 * слушателя scroll: ноль вычислений на прокрутке.
 */
export function watchHeaderOffset(): void {
    const headerEl = document.getElementById('header');

    if (!headerEl || document.querySelector('.header-sentinel')) {
        return;
    }

    const sentinel = document.createElement('div');
    sentinel.className = 'header-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    headerEl.insertAdjacentElement('beforebegin', sentinel);

    new IntersectionObserver(
        ([entry]) =>
            headerEl.classList.toggle('is-scrolled', !entry.isIntersecting),
        {
            threshold: 0,
        },
    ).observe(sentinel);
}

let islandObserver: IntersectionObserver | null = null;

/**
 * Герой «Остров». Параллакс целиком на CSS; здесь остаётся единственное,
 * чего CSS не умеет, — снять покачивание с паузы, когда герой в кадре, и
 * вернуть на паузу, когда он ушёл.
 *
 * Покачивание — единственная бесконечная анимация в проекте, значит,
 * обязана стоять, пока её никто не видит.
 */
export function initIsland(): void {
    const stage = document.querySelector<HTMLElement>('[data-island]');

    /* Наблюдателя переносим на новый герой: Inertia меняет страницу без
       перезагрузки, и прежний узел уже выброшен. */
    islandObserver?.disconnect();
    islandObserver = null;

    if (!stage) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        stage.classList.add('is-live');

        return;
    }

    islandObserver = new IntersectionObserver(
        ([entry]) => stage.classList.toggle('is-live', entry.isIntersecting),
        {
            threshold: 0,
        },
    );
    islandObserver.observe(stage);
}
