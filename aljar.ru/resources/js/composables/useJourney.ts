import { onBeforeUnmount, onMounted } from 'vue';
import type { Ref } from 'vue';
import { animateNumber, motionOn } from '@/lib/motion';

/**
 * Сценарий «От зерна к чашке». Перенесён из versions/v2-anim/js/main.js,
 * план — docs/animation-plan.md, раздел 1.
 *
 * Разметка в шаблоне самодостаточна: пять блоков «кадр + подпись». Всё,
 * что делает этот код, — надстройка: запинить сцену и переключать кадры
 * по меткам прокрутки. Не сложилось (нет наблюдателя, низкий экран,
 * reduce-motion, экономия трафика, пользователь уже внутри секции) —
 * остаётся список, и это нормальный, законченный вид страницы.
 */
export function useJourney(section: Ref<HTMLElement | null>) {
    let observer: IntersectionObserver | null = null;
    let prep: IntersectionObserver | null = null;
    let slides: HTMLElement[] = [];
    let current = -1;
    let preparing = false;
    let resizeTimer: ReturnType<typeof setTimeout> | undefined;
    let onResize: (() => void) | null = null;

    onMounted(() => {
        const root = section.value;

        if (!root) {
            return;
        }

        const track = root.querySelector<HTMLElement>('.journey__track');
        const progressHost = root.querySelector<HTMLElement>(
            '[data-journey-progress]',
        );
        const sentinelHost = root.querySelector<HTMLElement>(
            '[data-journey-sentinels]',
        );
        const allSlides = [
            ...root.querySelectorAll<HTMLElement>('[data-slide]'),
        ];

        if (!track || !progressHost || !sentinelHost || !allSlides.length) {
            return;
        }

        /* На узких экранах прогон короче: пять запиненных экранов пальцем —
           перебор, а мобильный трафик здесь преобладающий. Второй и
           четвёртый кадры помечены data-optional и выпадают. */
        const compact = () => window.matchMedia('(max-width: 767px)').matches;
        const pickSlides = () =>
            allSlides.filter(
                (s) => !(compact() && s.hasAttribute('data-optional')),
            );

        const canPin = () =>
            /* motionOn покрывает и настройку движения, и видимость вкладки,
               и наличие наблюдателя, и срабатывание страховки */
            motionOn() &&
            /* на низком экране запиненная сцена превращается в щель */
            window.innerHeight >= 520;

        function runCounters(slide: HTMLElement): void {
            slide
                .querySelectorAll<HTMLElement>('[data-count-to]')
                .forEach((el) => {
                    const to = Number(el.dataset.countTo);
                    const from = Number(el.dataset.countFrom ?? 0);

                    if (!Number.isFinite(to)) {
                        return;
                    }

                    animateNumber(
                        el,
                        from,
                        to,
                        (v) => String(Math.round(v)),
                        900,
                    );
                });
        }

        function setFrame(i: number): void {
            if (i === current || i < 0 || i >= slides.length) {
                return;
            }

            current = i;
            slides.forEach((s, n) => s.classList.toggle('is-active', n === i));
            [...progressHost!.children].forEach((li, n) =>
                li.classList.toggle('is-active', n === i),
            );
            runCounters(slides[i]);
        }

        function build(): void {
            slides = pickSlides();
            /* Выпавшие кадры прячем атрибутом, а не через JS-состояние:
               снялся is-pinned или is-enhanced — они возвращаются сами. */
            allSlides.forEach((s) =>
                s.toggleAttribute('data-skip', !slides.includes(s)),
            );

            track!.style.setProperty('--frames', String(slides.length));
            sentinelHost!.innerHTML = slides
                .map(() => '<div class="journey__sentinel"></div>')
                .join('');
            progressHost!.innerHTML = slides.map(() => '<li></li>').join('');

            /* Полоса в 1px поперёк середины экрана: метка её пересекла —
               кадр сменился. Никаких вычислений позиции на прокрутке. */
            observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((e) => {
                        if (!e.isIntersecting) {
                            return;
                        }

                        setFrame([...sentinelHost!.children].indexOf(e.target));
                    });
                },
                { rootMargin: '-50% 0px -50% 0px', threshold: 0 },
            );
            [...sentinelHost!.children].forEach((el) => observer!.observe(el));

            root!.classList.add('is-pinned');
            current = -1;
            setFrame(0);
        }

        /* Кадры нельзя оставлять ленивыми: первый же кросс-фейд показал бы
           пустоту. Грузим и декодируем заранее, пиним только после. */
        function preload(): Promise<unknown> {
            const all = Promise.all(
                pickSlides().map((s) => {
                    const img = s.querySelector('img');

                    if (!img) {
                        return Promise.resolve();
                    }

                    img.loading = 'eager';

                    return img.decode().catch(() => {});
                }),
            );

            /* Предохранитель: decode() у не начавшего грузиться кадра может
               не разрешиться вовсе. Без ограничения по времени промис
               зависает, флаг подготовки остаётся поднятым — и галерея не
               собирается уже никогда. Три секунды кадры почти наверняка
               успевают, а если нет, показать сцену важнее, чем дождаться
               декодирования. */
            return Promise.race([all, new Promise((r) => setTimeout(r, 3000))]);
        }

        /* Наблюдатель не отключается при первом же срабатывании: если сборку
           отменили (не догрузились кадры, пользователь уже внутри секции),
           попытка должна повториться, а не пропасть навсегда. Отключаем
           только после удачной сборки. */
        prep = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting || preparing) {
                    return;
                }

                if (!canPin()) {
                    return;
                }

                preparing = true;

                preload().then(() => {
                    preparing = false;

                    /* Пока грузились, пользователь мог доскроллить до секции.
                       Прыжок возможен только если высота меняется ВЫШЕ текущей
                       позиции прокрутки, то есть когда пользователь уже вошёл
                       в секцию. Пока её верх на экране или ниже, сцена растёт
                       под ним и прокрутка не сдвигается — пинить безопасно. */
                    if (!canPin()) {
                        return;
                    }

                    if (root!.getBoundingClientRect().top < 0) {
                        return;
                    }

                    prep?.disconnect();
                    build();
                });
            },
            /* Запас на предзагрузку: пять кадров должны успеть скачаться и
               декодироваться, пока секция идёт к экрану. */
            { rootMargin: '2200px 0px' },
        );

        if (canPin()) {
            prep.observe(root);
        }

        /* Смена брейкпоинта меняет число кадров. Пересобираем только когда
           секция вне экрана — иначе перестройка отдаётся скачком. */
        onResize = () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!root!.classList.contains('is-pinned')) {
                    return;
                }

                if (pickSlides().length === slides.length) {
                    return;
                }

                const r = root!.getBoundingClientRect();

                if (r.bottom > 0 && r.top < window.innerHeight) {
                    return;
                }

                observer?.disconnect();
                build();
            }, 200);
        };

        window.addEventListener('resize', onResize);
    });

    /* В прототипе страница жила до перезагрузки, здесь Inertia меняет её на
       лету: наблюдатели и слушатель обязаны сняться вместе с компонентом,
       иначе останутся висеть на выброшенных узлах. */
    onBeforeUnmount(() => {
        observer?.disconnect();
        prep?.disconnect();
        clearTimeout(resizeTimer);

        if (onResize) {
            window.removeEventListener('resize', onResize);
        }
    });
}
