/* Демо-данные админки.

   Товары не дублируются — они берутся из витринного js/data.js, чтобы
   каталог и админка не разъезжались. Здесь только те сущности, которых
   на витрине нет: заказы, подписки и заявки оптовиков.

   Даты заданы относительно сегодняшнего дня, а не строками: прототип
   не должен протухать через неделю после сборки. */

(function () {
  const DAY = 86400000;
  const iso = (daysAgo) => new Date(Date.now() - daysAgo * DAY).toISOString().slice(0, 10);

  window.ADMIN = {
    /* Состав хранится позициями, а не числом: помол выбирает покупатель
       на сайте, и этот выбор должен доехать до цеха. Количество и сумма
       заказа считаются из состава, а не лежат отдельным полем — иначе
       они разъедутся при первой же правке. */
    orders: [
      { id: "AJ-2417", date: iso(0), customer: "Мария Полякова",   phone: "+7 985 137-92-35", status: "new",  ship: "Курьер", lines: [
        { product: "pink-bourbon",          weight: "250",  grind: "espresso", qty: 1 },
        { product: "cattura",               weight: "500",  grind: "whole",    qty: 1 },
      ]},
      { id: "AJ-2416", date: iso(0), customer: "Дмитрий Кузнецов", phone: "+7 906 767-47-25", status: "new",  ship: "ПВЗ", lines: [
        { product: "geisha",                weight: "250",  grind: "filter",   qty: 1 },
      ]},
      { id: "AJ-2415", date: iso(1), customer: "Елена Ким",        phone: "+7 903 214-88-10", status: "work", ship: "Курьер", lines: [
        { product: "sidamo",                weight: "250",  grind: "filter",   qty: 2 },
        { product: "intensive",             weight: "250",  grind: "cezve",    qty: 1 },
        { product: "mojiana",               weight: "500",  grind: "espresso", qty: 1 },
      ]},
      { id: "AJ-2414", date: iso(1), customer: "Артём Соколов",    phone: "+7 916 552-40-71", status: "work", ship: "Почта", lines: [
        { product: "pink-bourbon-mojiana",  weight: "250",  grind: "espresso", qty: 1 },
      ]},
      { id: "AJ-2413", date: iso(2), customer: "Ольга Реброва",    phone: "+7 921 118-63-29", status: "done", ship: "Курьер", lines: [
        { product: "cerrado",               weight: "1000", grind: "whole",    qty: 1 },
        { product: "ground-arabica",        weight: "250",  grind: "cezve",    qty: 2 },
      ]},
      { id: "AJ-2412", date: iso(3), customer: "Игорь Ланской",    phone: "+7 962 704-15-88", status: "done", ship: "ПВЗ", lines: [
        { product: "sul-de-minas",          weight: "500",  grind: "espresso", qty: 1 },
        { product: "sidamo",                weight: "250",  grind: "whole",    qty: 1 },
      ]},
      { id: "AJ-2411", date: iso(4), customer: "Наталья Гущина",   phone: "+7 917 330-92-04", status: "done", ship: "ПВЗ", lines: [
        { product: "sidamo",                weight: "250",  grind: "filter",   qty: 1, subscribe: true },
      ]},
      { id: "AJ-2410", date: iso(5), customer: "Павел Демин",      phone: "+7 999 481-27-63", status: "canceled", ship: "Курьер", lines: [
        { product: "intensive",             weight: "250",  grind: "cezve",    qty: 1 },
        { product: "cattura",               weight: "500",  grind: "whole",    qty: 1 },
      ]},
    ],

    subscriptions: [
      { id: "SUB-118", customer: "Мария Полякова",  product: "pink-bourbon",         weight: "250", grind: "espresso", every: 2, next: iso(-3),  status: "new",      total: 945 },
      { id: "SUB-117", customer: "Дмитрий Кузнецов", product: "cattura",             weight: "500", grind: "whole",    every: 3, next: iso(-8),  status: "new",      total: 1368 },
      { id: "SUB-114", customer: "Елена Ким",        product: "sidamo",              weight: "250", grind: "filter",   every: 4, next: iso(-14), status: "paused",   total: 473 },
      { id: "SUB-109", customer: "Артём Соколов",    product: "pink-bourbon-mojiana", weight: "1000", grind: "espresso", every: 2, next: iso(-1), status: "new",     total: 2268 },
      { id: "SUB-103", customer: "Павел Демин",      product: "intensive",           weight: "250", grind: "cezve",    every: 3, next: null,      status: "canceled", total: 473 },
    ],

    /* Заявки опта идут отдельным потоком от розничных обращений —
       требование ТЗ, раздел 5.4: иначе оптовые лиды теряются. */
    leads: [
      { id: "B2B-046", date: iso(0), name: "Сергей Ватагин", company: "Кофейня «Полдень»", phone: "+7 903 771-20-40", email: "s.vatagin@polden.ru", type: "Кофейня",  volume: "20 кг/мес, эспрессо-смесь", status: "new",  comment: "Нужен стабильный профиль под рожок, дегустация до контракта." },
      { id: "B2B-045", date: iso(1), name: "Анна Терехова",  company: "ООО «Северград»",   phone: "+7 921 604-13-77", email: "a.terekhova@severgrad.ru", type: "Офис", volume: "8 кг/мес", status: "new",  comment: "Офис на 60 человек, нужна аренда кофемашины." },
      { id: "B2B-043", date: iso(3), name: "Рустам Ахметов", company: "Отель «Гранат»",    phone: "+7 917 245-90-12", email: "r.ahmetov@granat-hotel.ru", type: "Отель", volume: "35 кг/мес", status: "work", comment: "Завтраки, нужен фильтр и турка. Просят прайс от объёма." },
      { id: "B2B-040", date: iso(6), name: "Ирина Голубева", company: "Ресторан «Дым»",    phone: "+7 962 118-44-05", email: "irina@dym.rest", type: "Ресторан", volume: "12 кг/мес", status: "done", comment: "Договор подписан, первая поставка ушла." },
    ],

  };
})();
