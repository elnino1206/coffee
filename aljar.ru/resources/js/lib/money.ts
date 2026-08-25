/** Цены везде хранятся в копейках — рубли появляются только при выводе. */
export function formatPrice(kopecks: number): string {
    return `${new Intl.NumberFormat('ru-RU').format(Math.round(kopecks / 100))} ₽`;
}
