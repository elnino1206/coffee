/**
 * Помолы. Единый список для страницы товара и окна быстрого добавления —
 * значения совпадают с App\Enums\Grind, иначе корзина отклонит выбор.
 *
 * Две подписи на каждый вариант: короткая для узкой строки выбора в окне,
 * развёрнутая — для плиток на странице товара. Так было и в прототипе.
 */
export type Grind = {
    id: string;
    label: string;
    tile: string;
    note: string;
    image: string;
};

export const GRINDS: Grind[] = [
    {
        id: 'whole',
        label: 'В зёрнах',
        tile: 'В зёрнах, без помола',
        note: 'Смелете сами перед завариванием',
        image: '/img/method-beans.svg',
    },
    {
        id: 'espresso',
        label: 'Под эспрессо',
        tile: 'Рекомендуется для эспрессо',
        note: 'Тонкий помол под рожок',
        image: '/img/method-espresso.svg',
    },
    {
        id: 'filter',
        label: 'Под фильтр',
        tile: 'Рекомендуется для фильтр-кофе',
        note: 'Средний помол под воронку и кемекс',
        image: '/img/method-filter.svg',
    },
    {
        id: 'cezve',
        label: 'Под турку',
        tile: 'Рекомендуется для турки',
        note: 'Самый мелкий помол, почти пудра',
        image: '/img/method-cezve.svg',
    },
];
