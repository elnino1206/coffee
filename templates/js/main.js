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
    calendar: '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4.5" width="14" height="13" rx="2.5"/><path d="M3 8.5h14M7 2.5v3M13 2.5v3"/></svg>',
    tag: '<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8.5V4a1 1 0 0 1 1-1h4.5L17 11.5 11.5 17 3 8.5z"/><circle cx="6.6" cy="6.6" r="1.1"/></svg>',
    bean: '<svg width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><ellipse cx="10" cy="10" rx="5.5" ry="7.5" transform="rotate(-35 10 10)"/><path d="M7 13.5c1.5-2.5 4-4.5 6-5.5"/></svg>',
    bagSmall: '<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4.5 6.5h11l-1 10h-9l-1-10z"/><path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5"/></svg>',
  };

  const formatPrice = (n) =>
    new Intl.NumberFormat("ru-RU").format(Math.round(n)) + " ₽";

  const MONTHS = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];
  const formatDate = (iso) => {
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
  };
  const formatBadgeDate = (iso) => {
    const [, m, d] = iso.split("-");
    return `${Number(d)} ${MONTHS[Number(m) - 1]}`;
  };

  const DAY = 86400000;
  const daysSinceRoast = (iso) => {
    const [y, m, d] = iso.split("-").map(Number);
    const roast = Date.UTC(y, m - 1, d);
    const today = new Date();
    const now = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
    return Math.max(0, Math.round((now - roast) / DAY));
  };
  const freshness = (iso) => {
    const days = daysSinceRoast(iso);
    const steps = window.ALJAR.freshness;
    const idx = steps.findIndex((s) => days <= s.maxDays);
    const i = idx === -1 ? steps.length - 1 : idx;
    return { ...steps[i], index: i, days };
  };
  const roastBadge = (iso) => {
    const f = freshness(iso);
    return `<span class="badge badge--roast badge--fresh-${f.index}" title="${f.note}">${ICONS.calendar}Обжарено: ${formatBadgeDate(iso)}</span>`;
  };

  const getCart = () => JSON.parse(localStorage.getItem("aljar-cart") || "[]");
  const setCart = (items) => {
    localStorage.setItem("aljar-cart", JSON.stringify(items));
    updateCartCount();
  };
  const cartCount = () => getCart().reduce((s, i) => s + i.qty, 0);

  function updateCartCount() {
    document.querySelectorAll("[data-cart-count]").forEach((el) => {
      const n = cartCount();
      el.dataset.count = n;
      el.textContent = n;
    });
  }

  function addToCart(payload) {
    const cart = getCart();
    const key = [payload.id, payload.weight, payload.grind, payload.subscribe].join("|");
    const existing = cart.find((i) => i.key === key);
    if (existing) existing.qty += payload.qty || 1;
    else cart.push({ ...payload, key, qty: payload.qty || 1 });
    setCart(cart);
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
      <header class="site-header" id="header">
        <div class="container site-header__inner">
          <a class="logo" href="index.html" aria-label="Al Jar Coffee">
            <img src="img/logo.svg" alt="">
            <span class="logo__text">
              <span class="logo__name">Al Jar</span>
              <span class="logo__sub">Coffee</span>
            </span>
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
      </header>
      <div class="mobile-nav" id="mobile-nav">
        <div class="mobile-nav__top">
          <a class="logo" href="index.html">
            <img src="img/logo.svg" alt=""><span class="logo__text"><span class="logo__name">Al Jar</span><span class="logo__sub">Coffee</span></span>
          </a>
          <button class="icon-btn" data-close-menu aria-label="Закрыть">${ICONS.close}</button>
        </div>
        ${links.map(([href, label]) => `<a href="${href}">${label}</a>`).join("")}
        <a href="delivery.html">Доставка и оплата</a>
        <a href="contacts.html">Контакты</a>
        <a href="account.html">Личный кабинет</a>
      </div>
      <div class="search-overlay" id="search">
        <div class="container" style="display:flex;justify-content:flex-end">
          <button class="icon-btn" data-close-search aria-label="Закрыть">${ICONS.close}</button>
        </div>
        <input class="field" type="search" placeholder="Найти кофе по названию, региону, вкусу…" data-search-input>
        <div class="search-results" data-search-results></div>
      </div>`;
  }

  function footer() {
    const b = window.ALJAR.brand;
    return `
      <footer class="site-footer">
        <div class="container footer-grid">
          <div class="footer-brand">
            <a class="logo" href="index.html">
              <img src="img/logo.svg" alt="">
              <span class="logo__text"><span class="logo__name">Al Jar</span><span class="logo__sub">Coffee</span></span>
            </a>
            <p class="tiny">Семейный бренд с ливанскими корнями и современной обжаркой полного цикла в России. От зерна к чашке.</p>
            <div class="socials">
              <a class="btn--icon" href="#" aria-label="Telegram">Tg</a>
              <a class="btn--icon" href="#" aria-label="Instagram">Ig</a>
              <a class="btn--icon" href="#" aria-label="VK">Vk</a>
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
      <article class="card">
        <div class="card__media">
          ${roastBadge(p.roastDate)}
          <button class="fav" type="button" aria-label="В избранное" data-fav="${p.id}">♡</button>
          <a href="product.html?id=${p.id}"><img src="${p.image}" alt="${p.name}"></a>
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
              roastDate: p.roastDate,
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
      id: p.id, name: p.name, price: p.price, image: p.image,
      roastDate: p.roastDate, weight: "250", grind: "whole", subscribe: false,
    });
    return `
      <article class="catalog-card">
        <div class="catalog-card__media">
          <a href="product.html?id=${p.id}"><img src="${p.image}" alt="${p.name}"></a>
        </div>
        <div class="catalog-card__body">
          ${roastBadge(p.roastDate)}
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
    updateCartCount();

    const headerEl = document.getElementById("header");
    window.addEventListener("scroll", () => {
      headerEl?.classList.toggle("is-scrolled", window.scrollY > 8);
    });

    const menu = document.getElementById("mobile-nav");
    document.querySelector("[data-open-menu]")?.addEventListener("click", () => menu.classList.add("is-open"));
    document.querySelector("[data-close-menu]")?.addEventListener("click", () => menu.classList.remove("is-open"));

    const search = document.getElementById("search");
    document.querySelector("[data-open-search]")?.addEventListener("click", () => {
      search.classList.add("is-open");
      search.querySelector("[data-search-input]").focus();
    });
    document.querySelector("[data-close-search]")?.addEventListener("click", () => search.classList.remove("is-open"));
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

  function bindAddButtons(root = document) {
    root.querySelectorAll("[data-add]").forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        addToCart(JSON.parse(btn.dataset.add));
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
      sort: "fresh",
      lo: PMIN,
      hi: PMAX,
      view: "grid",
    };

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
        <img src="${m.image}" alt="">
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
      if (state.sort === "fresh") list.sort((a, b) => b.roastDate.localeCompare(a.roastDate));
      if (state.sort === "rating") list.sort((a, b) => b.rating - a.rating);

      const n = list.length;
      const count = document.querySelector("[data-results-count]");
      if (count) {
        const word = n % 10 === 1 && n % 100 !== 11 ? "позиция"
          : [2, 3, 4].includes(n % 10) && ![12, 13, 14].includes(n % 100) ? "позиции" : "позиций";
        count.textContent = `${n} ${word}`;
      }

      grid.classList.toggle("is-list", state.view === "list");
      grid.innerHTML = n
        ? list.map(catalogCard).join("")
        : `<p class="empty" style="grid-column:1/-1">Ничего не нашлось. Смягчите фильтры или сбросьте их.</p>`;
      bindAddButtons(grid);

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
    fill("[data-pdp-roast-date]", `${roastBadge(p.roastDate)} <span class="tiny">${formatDate(p.roastDate)}</span>`);
    fill("[data-pdp-method]", p.methodLabel);
    fill("[data-pdp-roast]", p.roastLabel);
    const labels = window.ALJAR.profileLabels;
    fill(
      "[data-taste]",
      Object.keys(labels)
        .map(
          (k) => `<div class="bar">
            <span>${labels[k]}</span>
            <div class="bar__track"><div class="bar__fill" style="width:${p.profile[k]}%"></div></div>
            <span class="bar__value">${p.profile[k]}%</span>
          </div>`
        )
        .join("")
    );
    fill("[data-crumb-name]", p.name);

    const priceNow = () => {
      const w = window.ALJAR.weights.find((x) => x.id === state.weight);
      let price = p.price * w.multiplier;
      if (state.subscribe) price *= 1 - window.ALJAR.subscribeDiscount;
      return price;
    };

    const paint = () => {
      const base = p.price * window.ALJAR.weights.find((x) => x.id === state.weight).multiplier;
      const now = priceNow();
      root.querySelector("[data-price]").innerHTML = state.subscribe
        ? `<span class="price">${formatPrice(now)}</span> <span class="price--old">${formatPrice(base)}</span> <span class="tiny">при подписке</span>`
        : `<span class="price">${formatPrice(now)}</span>`;
      root.querySelector("[data-save]").textContent = state.subscribe
        ? `Экономия ${formatPrice(base - now)}`
        : "−10% при подписке";
      root.querySelector("[data-cta]").textContent = state.subscribe ? "Оформить подписку" : "Добавить в корзину";
    };

    root.querySelector("[data-weight]")?.addEventListener("change", (e) => {
      state.weight = e.target.value;
      paint();
    });
    const grindGrid = root.querySelector("[data-grind-grid]");
    if (grindGrid) {
      grindGrid.innerHTML = window.ALJAR.grinds
        .map(
          (g) => `<label class="grind-tile">
            <input type="radio" name="grind" value="${g.id}"${g.id === state.grind ? " checked" : ""}>
            <img src="${g.image}" alt="">
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
      paint();
    });
    root.querySelector("[data-pdp-add]")?.addEventListener("click", () => {
      addToCart({
        id: p.id,
        name: p.name,
        image: p.image,
        roastDate: p.roastDate,
        weight: state.weight,
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
          <img src="${i.image}" alt="">
          <div>
            <strong>${i.name}</strong>
            <div class="tiny">${i.weight} г · ${window.ALJAR.grinds.find((g) => g.id === i.grind)?.label || i.grind}${i.subscribe ? " · Подписка −10%" : ""}</div>
            <div class="tiny">Обжарено ${formatDate(i.roastDate)}</div>
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
          const c = getCart();
          c.splice(+b.dataset.remove, 1);
          setCart(c);
          draw();
        })
      );
    };

    const upsell = document.querySelector("[data-upsell]");
    if (upsell) {
      const inCart = new Set(getCart().map((i) => i.id));
      upsell.innerHTML = window.ALJAR.products
        .filter((p) => !inCart.has(p.id))
        .slice(0, 2)
        .map(productCard)
        .join("");
      bindAddButtons(upsell);
      bindFavButtons(upsell);
      upsell.addEventListener("click", () => setTimeout(draw, 50));
    }
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
        <img src="${i.image}" alt="">
        <div>
          <strong>${i.name}</strong>
          <div class="tiny">${i.weight} г · ${i.subscribe ? "Подписка" : "Разовая"}</div>
          <div class="tiny">Обжарено ${formatDate(i.roastDate)}</div>
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
        e.target.phone.classList.add("is-error");
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
        roastDate: p.roastDate,
        weight: form.weight.value,
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
    mountChrome();
    enhanceFields();
    bindAddButtons();
    bindFavButtons();
    renderHome();
    renderCatalog();
    renderProduct();
    renderCart();
    renderCheckout();
    renderConstructor();
    renderSuccess();

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
    document.querySelectorAll("[data-faq] details").forEach((d) => {
      d.addEventListener("toggle", () => {});
    });
  });
})();
