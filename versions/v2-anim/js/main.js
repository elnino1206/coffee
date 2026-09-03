(function () {
  const PAGE = document.body.dataset.page || "home";
  const ICONS = {
    search: '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="9" r="6"/><path d="M14 14l4 4"/></svg>',
    user: '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10" cy="7" r="3.2"/><path d="M4 17c1.2-3 3.2-4.5 6-4.5S14.8 14 16 17"/></svg>',
    bag: '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 7h10l-1 11H6L5 7z"/><path d="M8 7V5.5A2 2 0 0 1 10 3.5 2 2 0 0 1 12 5.5V7"/></svg>',
    close: '<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 5l12 12M17 5L5 17"/></svg>',
    plus: '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v10M3 8h10"/></svg>',
    leaf: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 19c8-1 13-8 14-16-8 1-15 7-14 16z"/><path d="M5 19c3-6 8-10 14-12"/></svg>',
    check: '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12l5 5 11-11"/></svg>',
    telegram: '<svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.2 2.9 11.5c-.9.35-.86 1.63.05 1.93l4.6 1.5 1.77 5.35c.22.66 1.06.85 1.54.35l2.6-2.7 4.65 3.42c.6.44 1.46.11 1.61-.62l3.1-14.9c.17-.8-.62-1.5-1.4-1.2Zm-3.3 3.05-7.9 6.9c-.28.24-.46.58-.51.95l-.27 1.98c-.03.2-.31.22-.37.03l-1.05-3.2a.62.62 0 0 1 .26-.72l9.5-6.2c.28-.18.57.2.34.44Z"/></svg>',
    instagram: '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3.4" y="3.4" width="17.2" height="17.2" rx="5.2"/><circle cx="12" cy="12" r="4"/><circle cx="16.9" cy="7.1" r="1.15" fill="currentColor" stroke="none"/></svg>',
    vk: '<svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.9 16.6c-5 0-8.2-3.5-8.3-9.3h2.6c.1 4.3 2.1 6.1 3.6 6.5V7.3h2.4v3.6c1.4-.15 2.9-1.8 3.4-3.6h2.4c-.4 2.2-1.9 3.85-3 4.5 1.1.55 2.8 2 3.5 4.8h-2.6c-.5-1.7-1.85-3.05-3.7-3.25v3.25h-.3Z"/></svg>',
    home: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M3.5 10.5 12 3.5l8.5 7"/><path d="M5.5 9.6V20h13V9.6"/><path d="M9.8 20v-5.4h4.4V20"/></svg>',
    cup: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M4.5 7.5h12v6a6 6 0 0 1-12 0v-6z"/><path d="M16.5 9.2h1.6a2.6 2.6 0 0 1 0 5.2h-1.6"/><path d="M3 20.5h15"/></svg>',
    repeat: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 0 1 13.7-5.6"/><path d="M20 12a8 8 0 0 1-13.7 5.6"/><path d="M17.8 3v3.6h-3.6"/><path d="M6.2 21v-3.6h3.6"/></svg>',
    calendar: '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4.5" width="14" height="13" rx="2.5"/><path d="M3 8.5h14M7 2.5v3M13 2.5v3"/></svg>',
    tag: '<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8.5V4a1 1 0 0 1 1-1h4.5L17 11.5 11.5 17 3 8.5z"/><circle cx="6.6" cy="6.6" r="1.1"/></svg>',
    bean: '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><ellipse cx="10" cy="10" rx="5.5" ry="7.5" transform="rotate(-35 10 10)"/><path d="M7 13.5c1.5-2.5 4-4.5 6-5.5"/></svg>',
    bagSmall: '<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4.5 6.5h11l-1 10h-9l-1-10z"/><path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5"/></svg>',
  };

  const formatPrice = (n) =>
    new Intl.NumberFormat("ru-RU").format(Math.round(n)) + " ₽";

  /* Помол выбирает покупатель, и этот выбор обязан быть виден везде,
     где показывается состав: в корзине, на оформлении и дальше в заказе.
     Раньше подпись собиралась по месту и на оформлении её просто забыли. */
  const grindLabel = (id) =>
    (window.ALJAR.grinds.find((g) => g.id === id) || {}).label || id;

  /* Обжарку покупатель тоже выбирает при заказе, поэтому подпись нужна
     там же, где помол. Старые строки корзины её не содержат — тогда
     ничего не подставляем, а не пишем «undefined». */
  const ROAST_LABELS = { light: "Светлая обжарка", medium: "Средняя обжарка", dark: "Тёмная обжарка" };
  const roastChoiceLabel = (id) => ROAST_LABELS[id] || "";

  const MONTHS = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];
  const formatDate = (iso) => {
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
  };


  /* ── Слой анимаций (P0) ──────────────────────────────────────────
     План: docs/animation-plan.md

     Движение — надстройка, а не условие работы. Класс is-enhanced на
     <html> выдаётся один раз и только если движение уместно; вся CSS,
     которая что-то прячет ради появления, висит под этим классом.
     Не выдали класс — страница статична и полностью видима. */

  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  /* Режим экономии трафика: анимации тянут за собой лишние декодирования
     картинок, в этом режиме пользователь просил обратного. */
  const saveData = navigator.connection?.saveData === true;
  const motionAllowed = () => !reduceMotion.matches && !saveData;
  /* Движение реально включено: класс выдан и его ещё не сняла страховка.
     Всё, что анимирует по факту, спрашивает именно это, а не намерение. */
  const motionOn = () => document.documentElement.classList.contains("is-enhanced");

  /* Решение принимается один раз, на загрузке, и только если вкладка
     видима: на скрытой вкладке IntersectionObserver не отдаёт пересечения,
     и спрятанные ради появления блоки остались бы спрятанными. Обратно
     усиление не включаем никогда — иначе уже показанный контент прыгнет
     обратно в невидимость в момент переключения на вкладку. */
  function applyMotionPreference() {
    const on =
      motionAllowed() &&
      document.visibilityState === "visible" &&
      "IntersectionObserver" in window;
    document.documentElement.classList.toggle("is-enhanced", on);
  }

  /* Страховка: появление не имеет права съесть контент. Если через две
     секунды в зоне видимости остались непроявленные блоки — значит
     наблюдатель по какой-то причине молчит, и мы снимаем усиление
     целиком. Показанная страница важнее анимации. */
  function guardReveals() {
    setTimeout(() => {
      if (!document.documentElement.classList.contains("is-enhanced")) return;
      const stuck = [...document.querySelectorAll("[data-reveal]:not(.is-in)")].some((el) => {
        const r = el.getBoundingClientRect();
        return r.bottom > 0 && r.top < window.innerHeight;
      });
      if (stuck) document.documentElement.classList.remove("is-enhanced");
    }, 2000);
  }

  /* Один наблюдатель на всю страницу: элементы добавляются по мере
     того, как рендерятся динамические куски. */
  let revealObserver = null;

  function observeReveals(root = document) {
    if (!motionOn()) return;
    if (!revealObserver) {
      revealObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((e) => {
            if (!e.isIntersecting) return;
            e.target.classList.add("is-in");
            /* Появление одноразовое: обратно ничего не прячем, иначе
               прокрутка вверх превращается в мигание. */
            revealObserver.unobserve(e.target);
          });
        },
        { rootMargin: "0px 0px -10% 0px", threshold: 0.12 }
      );
    }
    /* Ступенька считается внутри группы, а группа — это общий родитель:
       карточки одной сетки едут каскадом, разрозненные блоки страницы
       появляются каждый сам по себе, без накопленной задержки.
       Потолок в шесть шагов — на каталоге из 10+ карточек хвост
       очереди читается как подтормаживание, а не как приём. */
    const seq = new Map();
    root.querySelectorAll("[data-reveal]:not(.is-in)").forEach((el) => {
      if (el.dataset.revealBound) return;
      el.dataset.revealBound = "1";
      const n = seq.get(el.parentElement) || 0;
      seq.set(el.parentElement, n + 1);
      el.style.setProperty("--reveal-i", String(Math.min(n, 5)));
      revealObserver.observe(el);
    });
  }

  /* Состояние шапки при отрыве от верха страницы. Раньше висел
     слушатель scroll — теперь метка высотой 8px и наблюдатель за ней:
     ноль вычислений на прокрутке. */
  function watchHeaderOffset() {
    const headerEl = document.getElementById("header");
    if (!headerEl) return;
    const sentinel = document.createElement("div");
    sentinel.className = "header-sentinel";
    sentinel.setAttribute("aria-hidden", "true");
    headerEl.insertAdjacentElement("beforebegin", sentinel);
    new IntersectionObserver(
      ([entry]) => headerEl.classList.toggle("is-scrolled", !entry.isIntersecting),
      { threshold: 0 }
    ).observe(sentinel);
  }

  /* Числовая прокрутка (P2). Цена, счётчик выдачи и метрики сценария
     меняются не подменой, а прокруткой — глаз успевает заметить, что
     значение изменилось, и в какую сторону.
     Движение запрещено — сразу ставим конечное значение. */
  function animateNumber(el, from, to, format = String, duration = 700) {
    if (!el) return;
    /* Конечное значение ставим всегда и сразу. requestAnimationFrame не
       выполняется в фоновой вкладке — без этой строки счётчик застрял бы
       на старом содержимом до момента, когда вкладку откроют. Прокрутка
       перезапишет значение в том же кадре, до отрисовки, поэтому мигания
       не будет. */
    el.textContent = format(to);
    if (!motionOn() || from === to) return;
    /* Метка прогона: если значение сменилось ещё раз до конца текущей
       прокрутки, старый кадр обязан замолчать, иначе два requestAnimationFrame
       начнут драться за один и тот же элемент. */
    const run = (Number(el.dataset.rollRun || 0) + 1) % 1024;
    el.dataset.rollRun = String(run);
    const t0 = performance.now();
    const tick = (now) => {
      if (el.dataset.rollRun !== String(run)) return;
      const p = Math.min(1, (now - t0) / duration);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = format(from + (to - from) * eased);
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  }

  /* Перезапуск CSS-анимации на элементе, который меняет содержимое */
  function replayAnimation(el, cls) {
    if (!el) return;
    el.classList.remove(cls);
    void el.offsetWidth;
    el.classList.add(cls);
  }

  const getCart = () => JSON.parse(localStorage.getItem("aljar-cart") || "[]");
  const setCart = (items) => {
    localStorage.setItem("aljar-cart", JSON.stringify(items));
    updateCartCount();
  };
  const cartCount = () => getCart().reduce((s, i) => s + i.qty, 0);

  /* bump — короткий импульс счётчика. Нужен только когда товар
     добавили: кнопка и счётчик стоят в разных углах экрана, и без
     импульса связь между нажатием и результатом теряется. */
  function updateCartCount(bump = false) {
    document.querySelectorAll("[data-cart-count]").forEach((el) => {
      const n = cartCount();
      el.dataset.count = n;
      el.textContent = n;
      if (bump && n > 0) replayAnimation(el, "is-bump");
    });
  }

  function addToCart(payload) {
    const cart = getCart();
    /* Обжарка входит в ключ наравне с весом и помолом: один и тот же
       сорт в разной обжарке — это разные строки корзины. */
    const key = [payload.id, payload.weight, payload.roast, payload.grind, payload.subscribe].join("|");
    const existing = cart.find((i) => i.key === key);
    if (existing) existing.qty += payload.qty || 1;
    else cart.push({ ...payload, key, qty: payload.qty || 1 });
    setCart(cart);
    updateCartCount(true);
    /* Экраны, показывающие состав, слушают это событие и
       перерисовываются. Раньше корзина обновлялась по таймеру сразу
       после клика по кнопке — теперь между кликом и добавлением стоит
       окно выбора, и таймер срабатывал бы раньше самого добавления. */
    document.dispatchEvent(new CustomEvent("aljar:cart-change"));
    toast("Добавлено в корзину");
  }

  function toast(text) {
    let el = document.querySelector(".toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      document.body.appendChild(el);
    }
    el.textContent = text;
    el.classList.add("is-show");
    setTimeout(() => el.classList.remove("is-show"), 2200);
  }

  function header() {
    const links = [
      ["catalog.html", "Кофе", "catalog"],
      ["subscription.html", "Подписка", "subscription"],
      ["about.html", "О нас", "about"],
      ["wholesale.html", "Для бизнеса", "wholesale"],
      ["blog.html", "Журнал", "blog"],
    ];
    return `
      <a class="skip-link" href="#main">К содержанию</a>

      <!-- Сюда собираются маски рваного края: по одной на снимок.
           Общей маской не обойтись — размер её прямоугольника задаётся
           в пикселях под конкретную фотографию. -->
      <svg class="svg-defs" data-torn-defs aria-hidden="true" focusable="false"></svg>
      <header class="site-header" id="header">
        <div class="container site-header__inner">
          <a class="logo" href="index.html">
            <img src="img/logo.webp" alt="Al Jar Coffee" width="187" height="138" decoding="async">
          </a>
          <nav class="nav-desktop" aria-label="Основное меню">
            ${links
              .map(
                ([href, label, id]) =>
                  `<a href="${href}" class="${PAGE === id ? "is-active" : ""}">${label}</a>`
              )
              .join("")}
          </nav>
          <div class="header-actions">
            <button class="icon-btn" data-open-search aria-label="Поиск">${ICONS.search}</button>
            <a class="icon-btn" href="account.html" aria-label="Личный кабинет">${ICONS.user}</a>
            <a class="icon-btn" href="cart.html" aria-label="Корзина">
              ${ICONS.bag}
              <span class="cart-count" data-cart-count>0</span>
            </a>
            <button class="icon-btn burger" data-open-menu aria-label="Меню">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h12M4 10h12M4 14h12"/></svg>
            </button>
          </div>
        </div>

        <!-- Панель поиска живёт внутри шапки: так она разворачивается
             ровно под её нижней границей, какой бы высоты шапка ни была.
             При прокрутке шапка сжимается с 76 до 64px — привязка к
             фиксированному отступу разъехалась бы. -->
        <div class="search-panel" id="search">
          <div class="search-panel__inner">
            <div class="container search-panel__body">
              <div class="search-panel__field">
                ${ICONS.search}
                <input class="field" type="search" placeholder="Найти кофе по названию, региону, вкусу…" data-search-input aria-label="Поиск по каталогу">
                <button class="icon-btn" type="button" data-close-search aria-label="Закрыть поиск">${ICONS.close}</button>
              </div>
              <div class="search-results" data-search-results></div>
            </div>
          </div>
        </div>
      </header>
      <div class="mobile-nav" id="mobile-nav">
        <div class="mobile-nav__top">
          <a class="logo" href="index.html">
            <img src="img/logo.webp" alt="Al Jar Coffee" width="187" height="138" loading="lazy" decoding="async">
          </a>
          <button class="icon-btn" data-close-menu aria-label="Закрыть">${ICONS.close}</button>
        </div>
        ${links.map(([href, label]) => `<a href="${href}">${label}</a>`).join("")}
        <a href="delivery.html">Доставка и оплата</a>
        <a href="contacts.html">Контакты</a>
        <a href="account.html">Личный кабинет</a>
      </div>
      </div>`;
  }


  function tabBar() {
    const tabs = [
      ["index.html", "Главная", "home", ICONS.home],
      ["catalog.html", "Кофе", "catalog", ICONS.cup],
      ["subscription.html", "Подписка", "subscription", ICONS.repeat],
      ["cart.html", "Корзина", "cart", ICONS.bag],
      ["account.html", "Кабинет", "account", ICONS.user],
    ];
    return `
      <nav class="tabbar" aria-label="Разделы сайта">
        ${tabs
          .map(([href, label, id, icon]) => {
            const active = PAGE === id;
            const badge = id === "cart" ? '<span class="cart-count" data-cart-count>0</span>' : "";
            return `<a href="${href}" class="tabbar__item${active ? " is-active" : ""}"${active ? ' aria-current="page"' : ""}>
              <span class="tabbar__icon">${icon}${badge}</span>
              <span class="tabbar__label">${label}</span>
            </a>`;
          })
          .join("")}
      </nav>`;
  }

  function footer() {
    const b = window.ALJAR.brand;
    return `
      <footer class="site-footer">
        <div class="container footer-grid">
          <div class="footer-brand">
            <a class="logo logo--lg" href="index.html">
              <img src="img/logo.webp" alt="Al Jar Coffee" width="187" height="138" loading="lazy" decoding="async">
            </a>
            <p class="tiny">Семейный бренд с ливанскими корнями и современной обжаркой полного цикла в России. От зерна к чашке.</p>
            <div class="socials">
              <a class="btn--icon" href="#" aria-label="Instagram" title="Instagram">${ICONS.instagram}</a>
              <a class="btn--icon" href="#" aria-label="Telegram" title="Telegram">${ICONS.telegram}</a>
              <a class="btn--icon" href="#" aria-label="ВКонтакте" title="ВКонтакте">${ICONS.vk}</a>
            </div>
          </div>
          <div class="footer-col">
            <h4>Магазин</h4>
            <a href="catalog.html">Каталог</a>
            <a href="subscription.html">Подписка</a>
            <a href="catalog.html?method=espresso">Для эспрессо</a>
            <a href="catalog.html?method=filter">Для фильтра</a>
          </div>
          <div class="footer-col">
            <h4>Компания</h4>
            <a href="about.html">О бренде</a>
            <a href="wholesale.html">Оптовым покупателям</a>
            <a href="blog.html">Журнал</a>
            <a href="contacts.html">Контакты</a>
          </div>
          <div class="footer-col">
            <h4>Помощь</h4>
            <a href="delivery.html">Доставка и оплата</a>
            <a href="legal.html">Оферта</a>
            <a href="legal.html#privacy">Конфиденциальность</a>
            <a href="account.html">Личный кабинет</a>
          </div>
          <div class="footer-col">
            <h4>Контакты</h4>
            <a href="${b.phoneHref}">${b.phone}</a>
            <a href="tel:+79067674725">${b.phone2}</a>
            <a href="mailto:${b.email}">${b.email}</a>
            <p class="tiny" style="margin-top:8px">Телефон — основной способ связи. Email по желанию.</p>
          </div>
        </div>
        <div class="container footer-bottom">
          <span>© 2026 Al Jar Coffee. Все права защищены.</span>
          <span>Обжариваем в России · Корни в Ливане</span>
        </div>
      </footer>`;
  }

  function productCard(p) {
    return `
      <article class="card" data-reveal>
        <div class="card__media">
          <button class="fav" type="button" aria-label="В избранное" data-fav="${p.id}">♡</button>
          <a href="product.html?id=${p.id}"><img src="${p.image}" alt="${p.name}" loading="lazy" decoding="async"></a>
        </div>
        <div class="card__body">
          <a class="card__title" href="product.html?id=${p.id}">${p.name}</a>
          <p class="card__notes">${p.notes}</p>
          <div class="card__meta">
            <span>${p.roastLabel}</span>
            <span>${ICONS.bean}${p.species}</span>
          </div>
          <div class="card__row">
            <span class="price">${formatPrice(p.price)}</span>
            <button class="add-quick" type="button" aria-label="Добавить ${p.name} в корзину" data-add='${JSON.stringify({
              id: p.id,
              name: p.name,
              price: p.price,
              image: p.image,
              weight: "250",
              grind: "whole",
              subscribe: false,
            })}'>${ICONS.bagSmall}</button>
          </div>
        </div>
      </article>`;
  }

  function catalogCard(p) {
    const payload = JSON.stringify({
      id: p.id, name: p.name, price: p.price, image: p.image, weight: "250", grind: "whole", subscribe: false,
    });
    return `
      <article class="catalog-card" data-reveal>
        <div class="catalog-card__media">
          <a href="product.html?id=${p.id}"><img src="${p.image}" alt="${p.name}" loading="lazy" decoding="async"></a>
        </div>
        <div class="catalog-card__body">
          <a class="catalog-card__title" href="product.html?id=${p.id}">${p.name}</a>
          <p class="catalog-card__origin">${p.origin}${p.region && p.region !== p.origin ? " · " + p.region : ""}</p>
          <p class="catalog-card__notes">${p.notes.split(", ").join(" · ")}</p>
          <p class="catalog-card__price">${ICONS.tag}${formatPrice(p.price)} <small>/ 250 г</small></p>
          <div class="catalog-card__cta">
            <button class="add-quick add-quick--wide" type="button" data-add='${payload}'>${ICONS.bagSmall}В корзину</button>
          </div>
        </div>
      </article>`;
  }

  function mountChrome() {
    const headerHost = document.querySelector("[data-header]");
    const footerHost = document.querySelector("[data-footer]");
    if (headerHost) headerHost.outerHTML = header();
    if (footerHost) footerHost.outerHTML = footer();
    /* Нижняя панель вкладок — на оформлении заказа скрыта: сфокусированный поток */
    if (PAGE !== "checkout") document.body.insertAdjacentHTML("beforeend", tabBar());
    updateCartCount();

    watchHeaderOffset();

    const menu = document.getElementById("mobile-nav");
    document.querySelector("[data-open-menu]")?.addEventListener("click", () => menu.classList.add("is-open"));
    document.querySelector("[data-close-menu]")?.addEventListener("click", () => menu.classList.remove("is-open"));

    /* Поиск — не модальное окно, а панель, разворачивающаяся под шапкой.
       Она не перекрывает страницу целиком, поэтому закрываться должна и
       по клику мимо, и по Escape: иначе останется висеть над контентом. */
    const search = document.getElementById("search");
    const searchToggle = document.querySelector("[data-open-search]");
    const searchInput = search?.querySelector("[data-search-input]");

    const setSearch = (open) => {
      if (!search) return;
      search.classList.toggle("is-open", open);
      searchToggle?.setAttribute("aria-expanded", String(open));
      if (open) searchInput?.focus();
      else if (search.contains(document.activeElement)) searchToggle?.focus();
    };

    searchToggle?.setAttribute("aria-expanded", "false");
    searchToggle?.setAttribute("aria-controls", "search");
    searchToggle?.addEventListener("click", () => setSearch(!search.classList.contains("is-open")));
    document.querySelector("[data-close-search]")?.addEventListener("click", () => setSearch(false));

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && search?.classList.contains("is-open")) setSearch(false);
    });
    document.addEventListener("pointerdown", (e) => {
      if (!search?.classList.contains("is-open")) return;
      /* Клик по самой кнопке обрабатывает её собственный слушатель —
         иначе панель закрылась бы здесь и тут же открылась снова. */
      if (search.contains(e.target) || searchToggle?.contains(e.target)) return;
      setSearch(false);
    });
    search.querySelector("[data-search-input]")?.addEventListener("input", (e) => {
      const q = e.target.value.toLowerCase().trim();
      const box = search.querySelector("[data-search-results]");
      if (!q) { box.innerHTML = ""; return; }
      const hits = window.ALJAR.products.filter(
        (p) =>
          p.name.toLowerCase().includes(q) ||
          p.notes.toLowerCase().includes(q) ||
          p.origin.toLowerCase().includes(q) ||
          p.region.toLowerCase().includes(q)
      );
      box.innerHTML = hits.length
        ? hits.map((p) => `<a class="chip" href="product.html?id=${p.id}">${p.name} · ${formatPrice(p.price)}</a>`).join("")
        : `<p class="empty">Ничего не нашли. Попробуйте «эспрессо» или «Эфиопия».</p>`;
    });
  }

  function bindFavButtons(root = document) {
    const favs = new Set(JSON.parse(localStorage.getItem("aljar-fav") || "[]"));
    root.querySelectorAll("[data-fav]").forEach((btn) => {
      const id = btn.dataset.fav;
      btn.classList.toggle("is-active", favs.has(id));
      btn.textContent = favs.has(id) ? "♥" : "♡";
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        const cur = new Set(JSON.parse(localStorage.getItem("aljar-fav") || "[]"));
        cur.has(id) ? cur.delete(id) : cur.add(id);
        localStorage.setItem("aljar-fav", JSON.stringify([...cur]));
        btn.classList.toggle("is-active", cur.has(id));
        btn.textContent = cur.has(id) ? "♥" : "♡";
      });
    });
  }

  /* ── Выбор варианта перед добавлением ────────────────────────────
     Раньше быстрая кнопка на карточке молча клала 250 г в зёрнах, и
     покупатель узнавал об этом уже в корзине. Теперь она открывает
     выбор: вес, обжарка, помол.

     Обжарка вынесена в выбор наравне с помолом — бренд обжаривает под
     заказ, а степень из карточки товара берётся как значение по
     умолчанию. Если обжарка должна быть жёстко привязана к позиции,
     это правится одной группой в разметке ниже. */

  let addModal = null;

  function ensureAddModal() {
    if (addModal) return addModal;
    document.body.insertAdjacentHTML(
      "beforeend",
      `<div class="modal" id="add-modal" data-add-modal>
        <div class="modal__box modal__box--add" role="dialog" aria-modal="true" aria-labelledby="add-modal-title">
          <div class="add-modal__head">
            <img data-add-img src="" alt="">
            <div>
              <h3 class="h3" id="add-modal-title" data-add-name></h3>
              <p class="tiny" data-add-notes></p>
            </div>
          </div>
          <form data-add-form>
            <div class="stack">
              <fieldset class="opt-group">
                <legend class="opt-group__label">Вес</legend>
                <div class="opt-row" data-add-weights></div>
              </fieldset>
              <fieldset class="opt-group">
                <legend class="opt-group__label">Обжарка</legend>
                <div class="opt-row" data-add-roasts></div>
              </fieldset>
              <fieldset class="opt-group">
                <legend class="opt-group__label">Помол</legend>
                <div class="opt-row" data-add-grinds></div>
              </fieldset>
              <label class="checkbox">
                <input type="checkbox" name="subscribe">
                <span>Оформить подпиской — −10% и свежая обжарка к дате доставки</span>
              </label>
            </div>
            <div class="add-modal__foot">
              <div class="add-modal__price">
                <span class="price" data-add-price></span>
                <span class="tiny" data-add-price-note></span>
              </div>
              <button class="btn btn--primary" type="submit">В корзину</button>
            </div>
          </form>
        </div>
      </div>`
    );
    addModal = document.querySelector("[data-add-modal]");
    /* Клик по затемнению и Escape закрывают — окно не удерживает
       человека, который передумал. */
    addModal.addEventListener("click", (e) => {
      if (e.target === addModal) closeAddModal();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && addModal.classList.contains("is-open")) closeAddModal();
    });
    return addModal;
  }

  let addModalOpener = null;
  function closeAddModal() {
    addModal?.classList.remove("is-open");
    if (addModalOpener && document.contains(addModalOpener)) addModalOpener.focus();
  }

  const ROAST_OPTIONS = [
    ["light", "Светлая"],
    ["medium", "Средняя"],
    ["dark", "Тёмная"],
  ];

  function openAddModal(payload, opener) {
    const A = window.ALJAR;
    const p = A.products.find((x) => x.id === payload.id) || payload;
    const el = ensureAddModal();
    addModalOpener = opener || null;

    el.querySelector("[data-add-img]").src = p.image;
    el.querySelector("[data-add-img]").alt = p.name;
    el.querySelector("[data-add-name]").textContent = p.name;
    el.querySelector("[data-add-notes]").textContent = p.notes || "";

    const radios = (host, name, items, checked) => {
      el.querySelector(host).innerHTML = items
        .map(([v, label]) => `<label class="opt">
          <input type="radio" name="${name}" value="${v}"${v === checked ? " checked" : ""}>
          <span>${label}</span>
        </label>`)
        .join("");
    };
    radios("[data-add-weights]", "weight", A.weights.map((w) => [w.id, w.label]), "250");
    radios("[data-add-roasts]", "roast", ROAST_OPTIONS, p.roast);
    radios("[data-add-grinds]", "grind", A.grinds.map((g) => [g.id, g.label]), "whole");

    const form = el.querySelector("[data-add-form]");
    form.subscribe.checked = false;

    const priceEl = el.querySelector("[data-add-price]");
    const noteEl = el.querySelector("[data-add-price-note]");
    let shown = null;

    const current = () => {
      const weight = form.weight.value;
      const mult = A.weights.find((w) => w.id === weight).multiplier;
      const base = p.price * mult;
      const price = form.subscribe.checked ? base * (1 - A.subscribeDiscount) : base;
      return { weight, base, price };
    };

    const paint = (animate) => {
      const { base, price } = current();
      if (animate && shown !== null) animateNumber(priceEl, shown, price, formatPrice, 400);
      else priceEl.textContent = formatPrice(price);
      shown = price;
      noteEl.textContent = form.subscribe.checked
        ? `Вместо ${formatPrice(base)} — экономия ${formatPrice(base - price)}`
        : `${p.roastLabel} · ${p.species}`;
    };
    form.onchange = () => paint(true);
    paint(false);

    form.onsubmit = (e) => {
      e.preventDefault();
      const { weight, price } = current();
      addToCart({
        id: p.id,
        name: p.name,
        image: p.image,
        weight,
        roast: form.roast.value,
        grind: form.grind.value,
        subscribe: form.subscribe.checked,
        price,
      });
      /* Кнопка, которой открыли окно, подтверждает добавление галочкой:
         тост может уехать из поля зрения, а кнопка под пальцем — нет. */
      const opener = addModalOpener;
      closeAddModal();
      if (opener) {
        opener.classList.add("is-done");
        clearTimeout(Number(opener.dataset.doneTimer));
        opener.dataset.doneTimer = String(
          setTimeout(() => opener.classList.remove("is-done"), 1100)
        );
      }
    };

    el.classList.add("is-open");
    el.querySelector("input:checked")?.focus();
  }

  function bindAddButtons(root = document) {
    root.querySelectorAll("[data-add]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        openAddModal(JSON.parse(btn.dataset.add), btn);
      });
    });
  }

  function renderHome() {
    const grid = document.querySelector("[data-featured]");
    if (!grid) return;
    grid.innerHTML = window.ALJAR.products.filter((p) => p.featured).map(productCard).join("");
    bindAddButtons(grid);
    bindFavButtons(grid);
  }

  function renderCatalog() {
    const grid = document.querySelector("[data-catalog]");
    if (!grid) return;
    const A = window.ALJAR;
    const params = new URLSearchParams(location.search);
    const prices = A.products.map((p) => p.price);
    const PMIN = Math.min(...prices);
    const PMAX = Math.max(...prices);

    const ROASTS = [
      { id: "light", label: "Светлая", level: 1 },
      { id: "medium", label: "Средняя", level: 3 },
      { id: "dark", label: "Тёмная", level: 5 },
    ];
    const SCALE = 5;

    const state = {
      roast: new Set(),
      origin: new Set(),
      method: params.get("method") ? new Set([params.get("method")]) : new Set(),
      q: "",
      sort: "rating",
      lo: PMIN,
      hi: PMAX,
      view: "grid",
    };
    /* Сколько позиций сейчас показано в счётчике — точка отсчёта для
       прокрутки. Таймер подмены сетки один: частые клики по фильтрам
       не должны копить отложенные перерисовки. */
    let shownCount = 0;
    let swapTimer = null;

    /* ── сайдбар ── */
    const roastHost = document.querySelector("[data-roast-list]");
    if (roastHost) {
      roastHost.innerHTML = ROASTS.map((r) => {
        const dots = Array.from({ length: SCALE }, (_, i) =>
          `<i class="${i < r.level ? "on" : ""}"></i>`).join("");
        return `<label class="facet">
          <input type="checkbox" data-filter-roast="${r.id}">
          <span class="facet__label">${r.label}</span>
          <span class="roast-scale" aria-hidden="true">${dots}</span>
        </label>`;
      }).join("");
    }

    const originHost = document.querySelector("[data-origin-list]");
    if (originHost) {
      const counts = A.products.reduce((acc, p) => ((acc[p.origin] = (acc[p.origin] || 0) + 1), acc), {});
      originHost.innerHTML = Object.keys(counts)
        .sort((a, b) => counts[b] - counts[a])
        .map((o) => `<label class="facet">
          <input type="checkbox" data-filter-origin="${o}">
          <span class="facet__label">${o}</span>
          <span class="facet__count">${counts[o]}</span>
        </label>`).join("");
    }

    const methodHost = document.querySelector("[data-method-list]");
    if (methodHost) {
      const M = [
        { id: "espresso", label: "Эспрессо", image: "img/method-espresso.svg" },
        { id: "filter", label: "Фильтр", image: "img/method-filter.svg" },
        { id: "cezve", label: "Турка", image: "img/method-cezve.svg" },
      ];
      methodHost.innerHTML = M.map((m) => `<label class="facet">
        <input type="checkbox" data-filter-method="${m.id}">
        <img src="${m.image}" alt="" loading="lazy" decoding="async">
        <span class="facet__label">${m.label}</span>
      </label>`).join("");
    }

    /* ── ползунок цены ── */
    const lo = document.querySelector("[data-price-min]");
    const hi = document.querySelector("[data-price-max]");
    const fill = document.querySelector("[data-price-fill]");
    if (lo && hi) {
      [lo, hi].forEach((el) => { el.min = PMIN; el.max = PMAX; el.step = 25; });
      lo.value = PMIN;
      hi.value = PMAX;
    }
    const paintPrice = () => {
      if (!lo || !hi) return;
      const a = Math.min(+lo.value, +hi.value);
      const b = Math.max(+lo.value, +hi.value);
      state.lo = a; state.hi = b;
      const span = PMAX - PMIN || 1;
      fill.style.left = ((a - PMIN) / span) * 100 + "%";
      fill.style.right = 100 - ((b - PMIN) / span) * 100 + "%";
      document.querySelector("[data-price-lo]").textContent = formatPrice(a);
      document.querySelector("[data-price-hi]").textContent = formatPrice(b);
    };

    /* ── применение фильтров ── */
    const apply = () => {
      let list = A.products.filter((p) => p.price >= state.lo && p.price <= state.hi);
      if (state.roast.size) list = list.filter((p) => state.roast.has(p.roast));
      if (state.origin.size) list = list.filter((p) => state.origin.has(p.origin));
      if (state.method.size) list = list.filter((p) => state.method.has(p.method));
      if (state.q) {
        const q = state.q.toLowerCase();
        list = list.filter((p) => (p.name + p.notes + p.origin + p.region).toLowerCase().includes(q));
      }
      if (state.sort === "price-asc") list.sort((a, b) => a.price - b.price);
      if (state.sort === "price-desc") list.sort((a, b) => b.price - a.price);
      if (state.sort === "rating") list.sort((a, b) => b.rating - a.rating);

      const n = list.length;
      const count = document.querySelector("[data-results-count]");
      if (count) {
        /* Слово склоняется на каждом кадре прокрутки — иначе на «3»
           стояло бы «позиций», пока число уже подъехало к десяти. */
        animateNumber(count, shownCount, n, (v) => {
          const k = Math.round(v);
          const word = k % 10 === 1 && k % 100 !== 11 ? "позиция"
            : [2, 3, 4].includes(k % 10) && ![12, 13, 14].includes(k % 100) ? "позиции" : "позиций";
          return `${k} ${word}`;
        }, 450);
        shownCount = n;
      }

      const paintGrid = () => {
        grid.classList.toggle("is-list", state.view === "list");
        grid.innerHTML = n
          ? list.map(catalogCard).join("")
          : `<p class="empty" style="grid-column:1/-1">Ничего не нашлось. Смягчите фильтры или сбросьте их.</p>`;
        bindAddButtons(grid);
        /* Набор карточек пересобран — новым элементам нужен наблюдатель,
           и ступенька считается заново от начала выдачи. */
        observeReveals(grid);
      };

      /* Старый набор гасим до подмены: иначе карточки меняются рывком
         прямо под курсором. Новые проявляются сами, через [data-reveal].
         Первая отрисовка и режим без движения идут напрямую. */
      if (motionOn() && grid.children.length) {
        grid.classList.add("is-swapping");
        clearTimeout(swapTimer);
        swapTimer = setTimeout(() => {
          paintGrid();
          grid.classList.remove("is-swapping");
        }, 130);
      } else {
        paintGrid();
      }

      const chips = document.querySelector("[data-chips]");
      if (chips) {
        const items = [
          ...[...state.roast].map((v) => ({ k: "roast", v, label: (ROASTS.find((r) => r.id === v) || {}).label })),
          ...[...state.origin].map((v) => ({ k: "origin", v, label: v })),
          ...[...state.method].map((v) => ({ k: "method", v, label: { espresso: "Эспрессо", filter: "Фильтр", cezve: "Турка" }[v] })),
        ];
        chips.innerHTML = items
          .map((i) => `<span class="chip">${i.label} <button type="button" data-remove="${i.k}:${i.v}" aria-label="Сбросить">×</button></span>`)
          .join("");
        chips.querySelectorAll("[data-remove]").forEach((b) =>
          b.addEventListener("click", () => {
            const [k, v] = b.dataset.remove.split(":");
            state[k].delete(v);
            document.querySelectorAll(`[data-filter-${k}="${v}"]`).forEach((el) => (el.checked = false));
            apply();
          })
        );
      }
    };

    /* ── подписки на события ── */
    const bindFacet = (attr, key) => {
      const prop = "filter" + attr[0].toUpperCase() + attr.slice(1);
      document.querySelectorAll(`[data-filter-${attr}]`).forEach((el) => {
        const val = el.dataset[prop];
        if (state[key].has(val)) el.checked = true;
        el.addEventListener("change", () => {
          el.checked ? state[key].add(val) : state[key].delete(val);
          apply();
        });
      });
    };
    bindFacet("roast", "roast");
    bindFacet("origin", "origin");
    bindFacet("method", "method");

    [lo, hi].forEach((el) => el && el.addEventListener("input", () => { paintPrice(); apply(); }));

    document.querySelector("[data-sort]")?.addEventListener("change", (e) => {
      state.sort = e.target.value;
      apply();
    });
    document.querySelector("[data-catalog-q]")?.addEventListener("input", (e) => {
      state.q = e.target.value;
      apply();
    });
    document.querySelector("[data-clear-filters]")?.addEventListener("click", () => {
      state.roast.clear(); state.origin.clear(); state.method.clear();
      document.querySelectorAll(".filters input[type=checkbox]").forEach((i) => (i.checked = false));
      if (lo && hi) { lo.value = PMIN; hi.value = PMAX; }
      /* Заливка едет к новым краям только здесь, при программном сбросе.
         На перетаскивании переход читался бы как отставание от пальца,
         поэтому класс снимается сразу после доезда. */
      if (fill && motionOn()) {
        fill.classList.add("is-animating");
        setTimeout(() => fill.classList.remove("is-animating"), 320);
      }
      paintPrice();
      apply();
    });
    document.querySelector("[data-apply-filters]")?.addEventListener("click", () => {
      document.getElementById("filters-panel")?.classList.remove("is-open");
      document.querySelector("[data-catalog]")?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
    document.querySelectorAll("[data-view]").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.view = btn.dataset.view;
        document.querySelectorAll("[data-view]").forEach((b) => {
          const on = b === btn;
          b.classList.toggle("is-active", on);
          b.setAttribute("aria-pressed", String(on));
        });
        apply();
      });
    });
    document.querySelectorAll("[data-group-toggle]").forEach((btn) => {
      btn.addEventListener("click", () => {
        btn.setAttribute("aria-expanded", btn.getAttribute("aria-expanded") === "true" ? "false" : "true");
      });
    });
    document.getElementById("filters-toggle")?.addEventListener("click", () => {
      document.getElementById("filters-panel")?.classList.toggle("is-open");
    });

    paintPrice();
    apply();
  }

  function renderProduct() {
    const root = document.querySelector("[data-pdp]");
    if (!root) return;
    const id = new URLSearchParams(location.search).get("id") || window.ALJAR.products[0].id;
    const p = window.ALJAR.products.find((x) => x.id === id) || window.ALJAR.products[0];
    const state = { weight: "250", grind: "whole", subscribe: false };

    document.title = `${p.name} — Al Jar Coffee`;
    const fill = (sel, html) => {
      const el = root.querySelector(sel);
      if (el) el.innerHTML = html;
    };
    root.querySelector("[data-pdp-img]") && (root.querySelector("[data-pdp-img]").src = p.image);
    root.querySelector("[data-pdp-img]") && (root.querySelector("[data-pdp-img]").alt = p.name);
    fill("[data-pdp-name]", p.name);
    fill("[data-pdp-full]", p.fullName);
    fill("[data-pdp-origin]", `${p.region}, ${p.origin}`);
    fill("[data-pdp-species]", `${p.species} · ${p.process}`);
    fill("[data-pdp-notes]", p.notes.split(", ").map((n) => n).join(" · "));
    fill("[data-pdp-method]", p.methodLabel);
    fill("[data-pdp-roast]", p.roastLabel);
    const labels = window.ALJAR.profileLabels;
    fill(
      "[data-taste]",
      Object.keys(labels)
        .map(
          /* --v — доля заполнения для scaleX, --i — номер шкалы для ступеньки */
          (k, i) => `<div class="bar" style="--i:${i}">
            <span>${labels[k]}</span>
            <div class="bar__track"><div class="bar__fill" style="--v:${p.profile[k] / 100}"></div></div>
            <span class="bar__value">${p.profile[k]}%</span>
          </div>`
        )
        .join("")
    );
    root.querySelector("[data-taste]")?.setAttribute("data-reveal", "");
    fill("[data-crumb-name]", p.name);

    const priceNow = () => {
      const w = window.ALJAR.weights.find((x) => x.id === state.weight);
      let price = p.price * w.multiplier;
      if (state.subscribe) price *= 1 - window.ALJAR.subscribeDiscount;
      return price;
    };

    /* animate = true только для правок, вызванных пользователем: при
       первой отрисовке страницы дёргать строку выгоды незачем. */
    /* Последняя показанная цена — точка отсчёта для прокрутки: значение
       должно ехать от того, что человек видел, а не от нуля. */
    let shownPrice = null;

    const paint = (animate = false) => {
      const base = p.price * window.ALJAR.weights.find((x) => x.id === state.weight).multiplier;
      const now = priceNow();
      root.querySelector("[data-price]").innerHTML = state.subscribe
        ? `<span class="price">${formatPrice(now)}</span> <span class="price--old">${formatPrice(base)}</span> <span class="tiny">при подписке</span>`
        : `<span class="price">${formatPrice(now)}</span>`;
      if (animate && shownPrice !== null) {
        animateNumber(root.querySelector("[data-price] .price"), shownPrice, now, formatPrice, 500);
      }
      shownPrice = now;
      const save = root.querySelector("[data-save]");
      save.textContent = state.subscribe
        ? `Экономия ${formatPrice(base - now)}`
        : "−10% при подписке";
      if (animate) replayAnimation(save, "is-swap");
      root.querySelector("[data-cta]").textContent = state.subscribe ? "Оформить подписку" : "Добавить в корзину";
    };

    root.querySelector("[data-weight]")?.addEventListener("change", (e) => {
      state.weight = e.target.value;
      paint(true);
    });
    const grindGrid = root.querySelector("[data-grind-grid]");
    if (grindGrid) {
      grindGrid.innerHTML = window.ALJAR.grinds
        .map(
          (g) => `<label class="grind-tile">
            <input type="radio" name="grind" value="${g.id}"${g.id === state.grind ? " checked" : ""}>
            <img src="${g.image}" alt="" loading="lazy" decoding="async">
            <span class="grind-tile__title">${g.tile}</span>
            <span class="grind-tile__note">${g.note}</span>
          </label>`
        )
        .join("");
      grindGrid.addEventListener("change", (e) => {
        if (e.target.name !== "grind") return;
        state.grind = e.target.value;
        paint();
      });
    }
    root.querySelector("[data-sub-switch]")?.addEventListener("change", (e) => {
      state.subscribe = e.target.checked;
      root.querySelector("[data-sub-toggle]")?.classList.toggle("is-on", state.subscribe);
      paint(true);
    });
    root.querySelector("[data-pdp-add]")?.addEventListener("click", () => {
      addToCart({
        id: p.id,
        name: p.name,
        image: p.image,
        weight: state.weight,
        /* Своего выбора обжарки на странице товара нет — берём степень
           самой позиции, иначе строка в корзине окажется без неё, хотя
           та же позиция из каталога обжарку показывает. */
        roast: p.roast,
        grind: state.grind,
        subscribe: state.subscribe,
        price: priceNow(),
      });
    });
    paint();

    const related = document.querySelector("[data-related]");
    if (related) {
      related.innerHTML = window.ALJAR.products
        .filter((x) => x.id !== p.id && (x.method === p.method || x.origin === p.origin))
        .slice(0, 4)
        .map(productCard)
        .join("");
      bindAddButtons(related);
      bindFavButtons(related);
    }
  }

  /* ── Сценарий «От зерна к чашке» (P1) ────────────────────────────
     План: docs/animation-plan.md, раздел 1.

     Разметка в HTML — самодостаточный список из пяти блоков. Всё, что
     делает этот код, — надстройка: запинить сцену и переключать кадры
     по меткам прокрутки. Не сложилось (нет наблюдателя, низкий экран,
     reduce-motion, экономия трафика, пользователь уже внутри секции) —
     остаётся список, и это нормальный, законченный вид страницы. */
  function renderJourney() {
    const section = document.querySelector("[data-journey]");
    if (!section) return;

    const track = section.querySelector(".journey__track");
    const progressHost = section.querySelector("[data-journey-progress]");
    const sentinelHost = section.querySelector("[data-journey-sentinels]");
    const allSlides = [...section.querySelectorAll("[data-slide]")];
    if (!track || !progressHost || !sentinelHost || !allSlides.length) return;

    /* На узких экранах прогон короче: пять запиненных экранов пальцем —
       перебор, а мобильный трафик здесь преобладающий. Второй и
       четвёртый кадры помечены data-optional и выпадают. */
    const compact = () => window.matchMedia("(max-width: 767px)").matches;
    const pickSlides = () =>
      allSlides.filter((s) => !(compact() && s.hasAttribute("data-optional")));

    let slides = [];
    let observer = null;
    let current = -1;

    const canPin = () =>
      /* motionOn покрывает и настройку движения, и видимость вкладки, и
         наличие наблюдателя, и срабатывание страховки */
      motionOn() &&
      /* на низком экране запиненная сцена превращается в щель */
      window.innerHeight >= 520;

    function runCounters(slide) {
      slide.querySelectorAll("[data-count-to]").forEach((el) => {
        const to = Number(el.dataset.countTo);
        const from = Number(el.dataset.countFrom || 0);
        if (!Number.isFinite(to)) return;
        animateNumber(el, from, to, (v) => String(Math.round(v)), 900);
      });
    }

    function setFrame(i) {
      if (i === current || i < 0 || i >= slides.length) return;
      current = i;
      slides.forEach((s, n) => s.classList.toggle("is-active", n === i));
      [...progressHost.children].forEach((li, n) =>
        li.classList.toggle("is-active", n === i)
      );
      runCounters(slides[i]);
    }

    function build() {
      slides = pickSlides();
      /* Выпавшие кадры прячем атрибутом, а не через JS-состояние:
         снялся is-pinned или is-enhanced — они возвращаются сами. */
      allSlides.forEach((s) => s.toggleAttribute("data-skip", !slides.includes(s)));

      track.style.setProperty("--frames", String(slides.length));
      sentinelHost.innerHTML = slides.map(() => '<div class="journey__sentinel"></div>').join("");
      progressHost.innerHTML = slides.map(() => "<li></li>").join("");

      /* Полоса в 1px поперёк середины экрана: метка её пересекла —
         кадр сменился. Никаких вычислений позиции на прокрутке. */
      observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((e) => {
            if (!e.isIntersecting) return;
            setFrame([...sentinelHost.children].indexOf(e.target));
          });
        },
        { rootMargin: "-50% 0px -50% 0px", threshold: 0 }
      );
      [...sentinelHost.children].forEach((el) => observer.observe(el));

      section.classList.add("is-pinned");
      current = -1;
      setFrame(0);
    }

    /* Кадры нельзя оставлять ленивыми: первый же кросс-фейд показал бы
       пустоту. Грузим и декодируем заранее, пиним только после. */
    function preload() {
      const all = Promise.all(
        pickSlides().map((s) => {
          const img = s.querySelector("img");
          if (!img) return Promise.resolve();
          img.loading = "eager";
          return img.decode().catch(() => {});
        })
      );
      /* Предохранитель: decode() у не начавшего грузиться кадра может не
         разрешиться вовсе. Без ограничения по времени промис зависает,
         флаг подготовки остаётся поднятым — и галерея не собирается уже
         никогда. Три секунды кадры почти наверняка успевают, а если нет,
         показать сцену важнее, чем дождаться декодирования. */
      return Promise.race([all, new Promise((r) => setTimeout(r, 3000))]);
    }

    /* Наблюдатель не отключается при первом же срабатывании: если сборку
       отменили (не догрузились кадры, пользователь уже внутри секции),
       попытка должна повториться, а не пропасть навсегда. Отключаем
       только после удачной сборки. */
    let preparing = false;
    const prep = new IntersectionObserver(
      ([entry]) => {
        if (!entry.isIntersecting || preparing) return;
        if (!canPin()) return;
        preparing = true;
        preload().then(() => {
          preparing = false;
          /* Пока грузились, пользователь мог доскроллить до секции.
             Прыжок возможен только если высота меняется ВЫШЕ текущей
             позиции прокрутки, то есть когда пользователь уже вошёл в
             секцию. Пока её верх на экране или ниже, сцена растёт под
             ним и прокрутка не сдвигается — пинить безопасно.
             Прежнее условие (top < innerHeight) отменяло сборку всегда:
             к концу предзагрузки секция успевала показаться, и галерея
             не собиралась ни разу. */
          if (!canPin()) return;
          if (section.getBoundingClientRect().top < 0) return;
          prep.disconnect();
          build();
        });
      },
      /* Запас на предзагрузку: пять кадров должны успеть скачаться и
         декодироваться, пока секция идёт к экрану. */
      { rootMargin: "2200px 0px" }
    );
    if (canPin()) prep.observe(section);

    /* Смена брейкпоинта меняет число кадров. Пересобираем только когда
       секция вне экрана — иначе перестройка отдаётся скачком. */
    let resizeTimer;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        if (!section.classList.contains("is-pinned")) return;
        if (pickSlides().length === slides.length) return;
        const r = section.getBoundingClientRect();
        if (r.bottom > 0 && r.top < window.innerHeight) return;
        observer?.disconnect();
        build();
      }, 200);
    });
  }

  /* ── Герой «Остров» ──────────────────────────────────────────────
     Параллакс целиком на CSS: прогресс прокрутки гонит переменную,
     слои читают её со своей глубиной. Здесь остаётся единственное,
     чего CSS не умеет, — снять покачивание с паузы, когда герой в
     кадре, и вернуть на паузу, когда он ушёл. */
  /* ── Рваный край фотографий ──────────────────────────────────────
     Рвётся не снимок, а белый прямоугольник внутри маски: фильтр
     смещения двигает все пиксели подряд и повёл бы содержимое кадра.
     Маска меняет только прозрачность.

     У каждой фотографии своя маска и свой фильтр. Размер прямоугольника
     задаётся в пикселях: проценты внутри скрытого блока определений
     считаются от нулевого контейнера, и маска схлопнулась бы в ничто.
     Разные зёрна шума — чтобы у соседних снимков одного размера не
     совпал рисунок разрыва. */
  const TORN_SEEDS = [9, 23, 41, 57, 68, 84, 102, 119];
  const tornImages = () => [...document.querySelectorAll(".torn img")];

  function setupTornEdges() {
    const host = document.querySelector("[data-torn-defs]");
    const images = tornImages();
    if (!host || !images.length) return;

    host.innerHTML = images
      .map((_, i) => `
        <filter id="torn-f-${i}" x="-6%" y="-8%" width="112%" height="116%">
          <feTurbulence type="fractalNoise" baseFrequency="0.025" numOctaves="4" seed="${TORN_SEEDS[i % TORN_SEEDS.length]}" result="n"></feTurbulence>
          <feDisplacementMap in="SourceGraphic" in2="n" scale="11" xChannelSelector="R" yChannelSelector="G"></feDisplacementMap>
        </filter>
        <mask id="torn-m-${i}" maskUnits="userSpaceOnUse" x="-20" y="-20" width="4000" height="4000">
          <rect data-torn-rect="${i}" x="0" y="0" width="0" height="0" fill="#fff" filter="url(#torn-f-${i})"></rect>
        </mask>`)
      .join("");

    images.forEach((img, i) => {
      img.style.webkitMask = `url(#torn-m-${i})`;
      img.style.mask = `url(#torn-m-${i})`;
      /* Пока снимок не загружен, его размер неизвестен */
      img.addEventListener("load", sizeTornMasks);
    });
    sizeTornMasks();
  }

  function sizeTornMasks() {
    tornImages().forEach((img, i) => {
      const rect = document.querySelector(`[data-torn-rect="${i}"]`);
      if (!rect) return;
      const box = img.getBoundingClientRect();
      if (!box.width || !box.height) return;
      rect.setAttribute("width", String(Math.ceil(box.width)));
      rect.setAttribute("height", String(Math.ceil(box.height)));
    });
  }

  function renderIsland() {
    const stage = document.querySelector("[data-island]");
    if (!stage) return;

    /* Покачивание — единственная бесконечная анимация в проекте,
       значит, обязана стоять, пока её никто не видит. */
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(
        ([entry]) => stage.classList.toggle("is-live", entry.isIntersecting),
        { threshold: 0 }
      ).observe(stage);
    } else {
      stage.classList.add("is-live");
    }
  }

  function renderCart() {
    const box = document.querySelector("[data-cart]");
    if (!box) return;
    const draw = () => {
      const cart = getCart();
      if (!cart.length) {
        box.innerHTML = `<div class="empty panel"><p>Корзина пуста.</p><p class="tiny">Свежая обжарка уже ждёт в каталоге.</p><p><a class="btn btn--primary" href="catalog.html">Смотреть кофе</a></p></div>`;
        document.querySelector("[data-summary]")?.setAttribute("hidden", "");
        return;
      }
      document.querySelector("[data-summary]")?.removeAttribute("hidden");
      box.innerHTML = cart
        .map(
          (i, idx) => `
        <div class="line-item">
          <img src="${i.image}" alt="" loading="lazy" decoding="async">
          <div>
            <strong>${i.name}</strong>
            <div class="tiny">${i.weight} г${roastChoiceLabel(i.roast) ? " · " + roastChoiceLabel(i.roast) : ""} · ${grindLabel(i.grind)}${i.subscribe ? " · Подписка −10%" : ""}</div>
            <div class="qty" style="margin-top:8px">
              <button type="button" data-qty="${idx}:-1">−</button>
              <span>${i.qty}</span>
              <button type="button" data-qty="${idx}:1">+</button>
            </div>
          </div>
          <div>
            <div class="price">${formatPrice(i.price * i.qty)}</div>
            <button class="tiny" data-remove="${idx}" type="button">Удалить</button>
          </div>
        </div>`
        )
        .join("");

      const sub = cart.reduce((s, i) => s + i.price * i.qty, 0);
      const delivery = sub >= 3000 ? 0 : 350;
      document.querySelector("[data-subtotal]").textContent = formatPrice(sub);
      document.querySelector("[data-delivery]").textContent = delivery ? formatPrice(delivery) : "Бесплатно";
      document.querySelector("[data-grand]").textContent = formatPrice(sub + delivery);

      box.querySelectorAll("[data-qty]").forEach((b) =>
        b.addEventListener("click", () => {
          const [idx, d] = b.dataset.qty.split(":");
          const c = getCart();
          c[+idx].qty = Math.max(1, c[+idx].qty + +d);
          setCart(c);
          draw();
        })
      );
      box.querySelectorAll("[data-remove]").forEach((b) =>
        b.addEventListener("click", () => {
          const idx = +b.dataset.remove;
          const drop = () => {
            const c = getCart();
            c.splice(idx, 1);
            setCart(c);
            draw();
          };
          const row = b.closest(".line-item");
          if (!motionOn() || !row) return drop();
          /* Высоту фиксируем замером: auto не анимируется, а без
             схлопывания соседние строки прыгнут вверх рывком. */
          row.style.height = `${row.offsetHeight}px`;
          void row.offsetWidth;
          row.classList.add("is-removing");
          setTimeout(drop, 240);
        })
      );
    };

    const upsell = document.querySelector("[data-upsell]");
    if (upsell) {
      const inCart = new Set(getCart().map((i) => i.id));
      upsell.innerHTML = window.ALJAR.products
        .filter((p) => !inCart.has(p.id))
        /* Три позиции — ровно под три колонки на десктопе. Сетка выдержит
           и больше: лишние уйдут на новые ряды, ширина карточки не
           изменится. */
        .slice(0, 3)
        .map(productCard)
        .join("");
      bindAddButtons(upsell);
      bindFavButtons(upsell);
    }
    /* Слушатель вне блока допродажи: состав может измениться и без неё */
    document.addEventListener("aljar:cart-change", draw);
    draw();
  }

  function renderCheckout() {
    const list = document.querySelector("[data-checkout-items]");
    if (!list) return;
    if (new URLSearchParams(location.search).get("success") === "1") return;
    const cart = getCart();
    if (!cart.length) {
      location.href = "cart.html";
      return;
    }
    list.innerHTML = cart
      .map(
        (i) => `
      <div class="line-item">
        <img src="${i.image}" alt="" loading="lazy" decoding="async">
        <div>
          <strong>${i.name}${i.qty > 1 ? ` × ${i.qty}` : ""}</strong>
          <div class="tiny">${i.weight} г${roastChoiceLabel(i.roast) ? " · " + roastChoiceLabel(i.roast) : ""} · ${grindLabel(i.grind)}${i.subscribe ? " · Подписка" : ""}</div>
        </div>
        <div class="price">${formatPrice(i.price * i.qty)}</div>
      </div>`
      )
      .join("");
    const sub = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const deliveryEl = document.querySelector("[data-ship]");
    const updateTotals = () => {
      const ship = document.querySelector("[name=ship]:checked")?.value === "pickup" ? 0 : sub >= 3000 ? 0 : 350;
      document.querySelector("[data-subtotal]").textContent = formatPrice(sub);
      document.querySelector("[data-delivery]").textContent = ship ? formatPrice(ship) : "Бесплатно";
      document.querySelector("[data-grand]").textContent = formatPrice(sub + ship);
      const payBtn = document.querySelector("[data-pay]");
      if (payBtn) payBtn.textContent = `Оплатить ${formatPrice(sub + ship)}`;
    };
    document.querySelectorAll("[name=ship]").forEach((el) => {
      el.addEventListener("change", () => {
        document.querySelectorAll(".pay-option[data-ship-card]").forEach((c) =>
          c.classList.toggle("is-selected", c.querySelector("input").checked)
        );
        updateTotals();
      });
    });
    document.querySelectorAll("[name=pay]").forEach((el) => {
      el.addEventListener("change", () => {
        document.querySelectorAll(".pay-option[data-pay-card]").forEach((c) =>
          c.classList.toggle("is-selected", c.querySelector("input").checked)
        );
      });
    });
    updateTotals();

    document.querySelector("[data-checkout-form]")?.addEventListener("submit", (e) => {
      e.preventDefault();
      const phone = e.target.phone.value.trim();
      const err = e.target.querySelector("[data-phone-error]");
      if (!/^\+?\d[\d\s\-()]{9,}$/.test(phone)) {
        err.hidden = false;
        /* Перезапуск, а не add: класс уже висит после первой неудачной
           отправки, и вторая прошла бы вообще без отклика. */
        replayAnimation(e.target.phone, "is-error");
        e.target.phone.focus();
        return;
      }
      setCart([]);
      location.href = "checkout.html?success=1";
    });
  }

  function renderConstructor() {
    const form = document.querySelector("[data-constructor]");
    if (!form) return;
    const out = form.querySelector("[data-constructor-price]");
    const calc = () => {
      const id = form.coffee.value;
      const w = form.weight.value;
      const p = window.ALJAR.products.find((x) => x.id === id);
      const mult = window.ALJAR.weights.find((x) => x.id === w).multiplier;
      const now = p.price * mult * (1 - window.ALJAR.subscribeDiscount);
      out.textContent = `Добавить подписку · ${formatPrice(now)}`;
    };
    form.addEventListener("change", calc);
    calc();
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const p = window.ALJAR.products.find((x) => x.id === form.coffee.value);
      const mult = window.ALJAR.weights.find((x) => x.id === form.weight.value).multiplier;
      addToCart({
        id: p.id,
        name: p.name,
        image: p.image,
        weight: form.weight.value,
        roast: p.roast,
        grind: form.grind.value,
        subscribe: true,
        price: p.price * mult * (1 - window.ALJAR.subscribeDiscount),
      });
    });
  }

  function renderSuccess() {
    if (new URLSearchParams(location.search).get("success") !== "1") return;
    const form = document.querySelector("[data-checkout-wrap]");
    const ok = document.querySelector("[data-success]");
    if (form && ok) {
      form.hidden = true;
      ok.hidden = false;
    }
  }

  /* Поля формы из кита: состояние валидности и счётчик символов */
  function enhanceFields(root = document) {
    root.querySelectorAll("input[type=email], input[data-validate]").forEach((el) => {
      if (el.closest(".field-wrap")) return;
      const wrap = document.createElement("span");
      wrap.className = "field-wrap";
      el.parentNode.insertBefore(wrap, el);
      wrap.appendChild(el);
      /* Настоящий SVG вместо фоновой картинки: галочку можно отрисовать
         штрихом, а фон — только проявить. Разметку добавляем здесь, а не
         в HTML: поля разбросаны по десятку страниц. */
      wrap.insertAdjacentHTML(
        "beforeend",
        '<svg class="field-check" viewBox="0 0 20 20" aria-hidden="true">' +
          '<circle cx="10" cy="10" r="8"/><path d="M6.5 10.2l2.4 2.4 4.6-4.8"/></svg>'
      );
      const check = () => wrap.classList.toggle("is-valid", el.value.trim() !== "" && el.checkValidity());
      el.addEventListener("input", check);
      el.addEventListener("blur", check);
      check();
    });

    root.querySelectorAll("textarea[maxlength], textarea[data-counter]").forEach((el) => {
      if (el.closest(".textarea-wrap")) return;
      const max = Number(el.getAttribute("maxlength")) || Number(el.dataset.counter) || 120;
      if (!el.getAttribute("maxlength")) el.setAttribute("maxlength", max);
      const wrap = document.createElement("span");
      wrap.className = "textarea-wrap";
      el.parentNode.insertBefore(wrap, el);
      wrap.appendChild(el);
      const out = document.createElement("span");
      out.className = "char-count";
      wrap.appendChild(out);
      const draw = () => {
        out.textContent = `${el.value.length} / ${max}`;
        out.classList.toggle("is-over", el.value.length >= max);
      };
      el.addEventListener("input", draw);
      draw();
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    /* Решение о движении принимается до первой отрисовки динамики,
       иначе карточки успеют появиться уже видимыми и потом дёрнутся. */
    applyMotionPreference();
    /* Переключили системную настройку — усиление только снимаем */
    reduceMotion.addEventListener("change", () => {
      if (reduceMotion.matches) document.documentElement.classList.remove("is-enhanced");
    });

    mountChrome();
    enhanceFields();
    bindAddButtons();
    bindFavButtons();
    renderHome();
    renderCatalog();
    renderProduct();
    renderJourney();
    renderIsland();
    renderCart();
    renderCheckout();
    renderConstructor();
    renderSuccess();

    /* После всей динамики: к этому моменту карточки, шкалы и связанные
       товары уже в DOM. Куски, которые перерисовываются позже (фильтры
       каталога), зовут observeReveals сами. */
    observeReveals();
    guardReveals();

    /* Маски рваного края зависят от фактических размеров снимков, а те
       меняются вместе с шириной окна. */
    setupTornEdges();
    window.addEventListener("resize", sizeTornMasks);

    document.querySelector("[data-wholesale]")?.addEventListener("submit", (e) => {
      e.preventDefault();
      e.target.hidden = true;
      document.querySelector("[data-wholesale-ok]")?.classList.add("is-visible");
    });
    document.querySelector("[data-news]")?.addEventListener("submit", (e) => {
      e.preventDefault();
      toast("Спасибо — напишем, когда будет свежая партия");
      e.target.reset();
    });
    /* Модальные окна личного кабинета: разметка была, обработчиков не
       было — кнопки «Пауза» и «Отменить» ничего не открывали. */
    const modalPairs = [
      ["[data-open-pause]", "pause-modal"],
      ["[data-open-cancel]", "cancel-modal"],
    ];
    modalPairs.forEach(([sel, id]) => {
      const modal = document.getElementById(id);
      if (!modal) return;
      document.querySelector(sel)?.addEventListener("click", () => modal.classList.add("is-open"));
      modal.addEventListener("click", (e) => {
        if (e.target === modal || e.target.closest("[data-close-modal]")) {
          modal.classList.remove("is-open");
        }
      });
    });
    document.addEventListener("keydown", (e) => {
      if (e.key !== "Escape") return;
      document.querySelectorAll(".modal.is-open").forEach((m) => m.classList.remove("is-open"));
    });
  });
})();
