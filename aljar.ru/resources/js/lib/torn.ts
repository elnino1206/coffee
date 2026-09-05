/**
 * Рваный край фотографий. Перенесено из versions/v2-anim/js/main.js.
 *
 * Рвётся не снимок, а белый прямоугольник внутри маски: фильтр смещения
 * двигает все пиксели подряд и повёл бы содержимое кадра. Маска меняет
 * только прозрачность.
 *
 * У каждой фотографии своя маска и свой фильтр. Размер прямоугольника
 * задаётся в пикселях: проценты внутри скрытого блока определений
 * считаются от нулевого контейнера, и маска схлопнулась бы в ничто.
 * Разные зёрна шума — чтобы у соседних снимков одного размера не совпал
 * рисунок разрыва.
 *
 * От движения не зависит: это элемент оформления, а не анимация,
 * поэтому не спрашивает про is-enhanced.
 */

const TORN_SEEDS = [9, 23, 41, 57, 68, 84, 102, 119];

const tornImages = (): HTMLImageElement[] => [...document.querySelectorAll<HTMLImageElement>('.torn img')];

export function sizeTornMasks(): void {
    tornImages().forEach((img, i) => {
        const rect = document.querySelector(`[data-torn-rect="${i}"]`);

        if (!rect) {
return;
}

        const box = img.getBoundingClientRect();

        if (!box.width || !box.height) {
return;
}

        rect.setAttribute('width', String(Math.ceil(box.width)));
        rect.setAttribute('height', String(Math.ceil(box.height)));
    });
}

export function setupTornEdges(): void {
    const host = document.querySelector('[data-torn-defs]');
    const images = tornImages();

    if (!host || !images.length) {
return;
}

    host.innerHTML = images
        .map(
            (_, i) => `
        <filter id="torn-f-${i}" x="-6%" y="-8%" width="112%" height="116%">
          <feTurbulence type="fractalNoise" baseFrequency="0.025" numOctaves="4" seed="${TORN_SEEDS[i % TORN_SEEDS.length]}" result="n"></feTurbulence>
          <feDisplacementMap in="SourceGraphic" in2="n" scale="11" xChannelSelector="R" yChannelSelector="G"></feDisplacementMap>
        </filter>
        <mask id="torn-m-${i}" maskUnits="userSpaceOnUse" x="-20" y="-20" width="4000" height="4000">
          <rect data-torn-rect="${i}" x="0" y="0" width="0" height="0" fill="#fff" filter="url(#torn-f-${i})"></rect>
        </mask>`,
        )
        .join('');

    images.forEach((img, i) => {
        img.style.webkitMask = `url(#torn-m-${i})`;
        img.style.mask = `url(#torn-m-${i})`;

        /* Пока снимок не загружен, его размер неизвестен. Слушатель
           вешаем один раз на элемент: Inertia может позвать сборку
           повторно по той же разметке, и без метки обработчики
           накапливались бы. */
        if (img.dataset.tornBound) {
return;
}

        img.dataset.tornBound = '1';
        img.addEventListener('load', sizeTornMasks);
    });

    sizeTornMasks();
}
