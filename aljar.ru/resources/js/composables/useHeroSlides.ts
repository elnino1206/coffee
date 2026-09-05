import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { Ref } from 'vue';
import { motionOn } from '@/lib/motion';

/**
 * Смена кадров в герое.
 *
 * Кадры сменяются перекрёстным затуханием, а не подменой: подмена
 * читается как сбой загрузки, затухание — как приём.
 *
 * Таймер идёт только пока герой в кадре. Это единственная в проекте
 * анимация по таймеру, и она обязана стоять, когда её никто не видит:
 * иначе крутится на невидимой панели рельса и жжёт батарею впустую.
 */

/** Сколько кадр держится на экране. */
const HOLD = 6000;

export function useHeroSlides(stage: Ref<HTMLElement | null>, count: number) {
    const slide = ref(0);

    let timer: ReturnType<typeof setTimeout> | undefined;
    let visibility: IntersectionObserver | null = null;

    /**
     * Следующий кадр показываем только декодированным. Иначе на смене
     * проявится пустое место: браузер отложил загрузку, потому что
     * кадр прозрачный и в глаза не бросается.
     */
    function decode(i: number): Promise<void> {
        const img = stage.value?.querySelectorAll('img')[i] as
            HTMLImageElement | undefined;

        if (!img) {
            return Promise.resolve();
        }

        img.loading = 'eager';

        /* Предохранитель: decode() у не начавшего грузиться кадра может
           не разрешиться вовсе — тогда просто показываем как есть. */
        return Promise.race([
            img.decode().catch(() => {}),
            new Promise<void>((r) => setTimeout(r, 2000)),
        ]) as Promise<void>;
    }

    function step(): void {
        const next = (slide.value + 1) % count;

        decode(next).then(() => {
            if (!timer) {
                return;
            }

            slide.value = next;
            timer = setTimeout(step, HOLD);
        });
    }

    function start(): void {
        /* Без движения кадр остаётся один: смена картинок — это тоже
           движение, и в reduce-motion её быть не должно. */
        if (timer || !motionOn() || count < 2) {
            return;
        }

        timer = setTimeout(step, HOLD);
    }

    function stop(): void {
        clearTimeout(timer);
        timer = undefined;
    }

    onMounted(() => {
        if (!stage.value) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            start();

            return;
        }

        visibility = new IntersectionObserver(
            ([entry]) => (entry.isIntersecting ? start() : stop()),
            {
                threshold: 0,
            },
        );
        visibility.observe(stage.value);
    });

    onBeforeUnmount(() => {
        stop();
        visibility?.disconnect();
    });

    return { slide };
}
