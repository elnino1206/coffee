/* Админка Al Jar Coffee — прототип.

   Устройство то же, что на витрине: сайдбар и шапка собираются в JS и
   вставляются в [data-admin-nav] / [data-admin-topbar], страницы
   различаются атрибутом data-admin-page на <body>. Так навигация
   правится в одном месте, а не в шести файлах.

   Данных два источника: товары из витринного js/data.js (чтобы каталог
   и админка не разъезжались) и js/demo.js для заказов, подписок и
   заявок. Ничего не сохраняется — это прототип интерфейса, не CRM. */

(function () {
  const PAGE = document.body.dataset.adminPage || "dashboard";
  const A = window.ALJAR || {};
  const D = window.ADMIN || {};

  /* Витринные пути к картинкам начинаются от корня сайта, а админка
     лежит на уровень глубже. Загруженные обложки приходят data-адресом
     и никакого префикса не терпят. */
  const asset = (p) => (p && !/^(https?:|data:|\/|\.\.)/.test(p) ? "../" + p : p);

  const ICONS = {
    grid: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2.5" y="2.5" width="6" height="6" rx="1.5"/><rect x="11.5" y="2.5" width="6" height="6" rx="1.5"/><rect x="2.5" y="11.5" width="6" height="6" rx="1.5"/><rect x="11.5" y="11.5" width="6" height="6" rx="1.5"/></svg>',
    bag: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4.5 6.5h11l-1 10h-9l-1-10z"/><path d="M7.5 6.5V5a2.5 2.5 0 0 1 5 0v1.5"/></svg>',
    box: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 2.6 17 6v8l-7 3.4L3 14V6l7-3.4z"/><path d="M3 6l7 3.4L17 6M10 9.4V17"/></svg>',
    repeat: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M3.2 10a6.8 6.8 0 0 1 11.6-4.8"/><path d="M16.8 10a6.8 6.8 0 0 1-11.6 4.8"/><path d="M14.9 2.4v3.1h-3.1"/><path d="M5.1 17.6v-3.1h3.1"/></svg>',
    users: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="8" cy="6.5" r="2.8"/><path d="M2.8 16.5c1-2.8 2.8-4.2 5.2-4.2s4.2 1.4 5.2 4.2"/><path d="M13.5 4.2a2.6 2.6 0 0 1 0 4.9"/></svg>',
    out: '<svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M8 17H4.5v-14H8"/><path d="M12.5 13.5 16 10l-3.5-3.5M16 10H8"/></svg>',
    doc: '<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"><path d="M11.5 2.5H5.5v15h9V5.5z"/><path d="M11.5 2.5v3h3"/><path d="M7.5 10h5M7.5 13h5"/></svg>',
  };

  const formatPrice = (n) =>
    new Intl.NumberFormat("ru-RU").format(Math.round(n)) + " ₽";

  const MONTHS = ["янв", "фев", "мар", "апр", "мая", "июн", "июл", "авг", "сен", "окт", "ноя", "дек"];
  const formatDate = (isoStr) => {
    if (!isoStr) return "—";
    const [y, m, d] = isoStr.split("-").map(Number);
    return `${d} ${MONTHS[m - 1]}`;
  };

  const DAY = 86400000;
  /* Отрицательное значение — дата ещё впереди */
  const daysFrom = (isoStr) => {
    const [y, m, d] = isoStr.split("-").map(Number);
    const then = Date.UTC(y, m - 1, d);
    const now = new Date();
    return Math.round((Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()) - then) / DAY);
  };

  const STATUS = {
    new: ["status--new", "Новый"],
    work: ["status--work", "В работе"],
    done: ["status--done", "Выполнен"],
    paused: ["status--paused", "На паузе"],
    canceled: ["status--canceled", "Отменён"],
  };
  const statusPill = (key, labels = {}) => {
    const [cls, def] = STATUS[key] || ["", key];
    return `<span class="status ${cls}">${labels[key] || def}</span>`;
  };

  /* ── Хранилище правок ────────────────────────────────────────────
     Каждый экран админки — отдельный документ, поэтому правка, живущая
     только в памяти, пропадала бы при переходе по меню. Складываем не
     копию данных, а патчи поверх исходных: демо-набор остаётся точкой
     отсчёта, и его всегда можно вернуть кнопкой сброса.

     Это по-прежнему прототип: localStorage — не база, ничего не уходит
     на сервер и не видно другому сотруднику. */
  const STORE_KEY = "aljar-admin-edits";

  const readStore = () => {
    try {
      return JSON.parse(localStorage.getItem(STORE_KEY) || "{}") || {};
    } catch (e) {
      return {};
    }
  };
  let STORE = readStore();

  const patchOf = (kind, id) => (STORE[kind] || {})[id] || {};

  function savePatch(kind, id, patch) {
    STORE[kind] = STORE[kind] || {};
    STORE[kind][id] = { ...STORE[kind][id], ...patch };
    localStorage.setItem(STORE_KEY, JSON.stringify(STORE));
  }

  function resetStore() {
    localStorage.removeItem(STORE_KEY);
    STORE = {};
  }

  /* Созданные записи хранятся целиком, а не патчем: накладывать патч
     не на что. Лежат отдельным разделом, чтобы их можно было удалить,
     не трогая исходный демо-набор. */
  const created = (kind) => (STORE.new || {})[kind] || [];
  const isCreated = (kind, id) => created(kind).some((x) => x.id === id);

  function createEntity(kind, obj) {
    STORE.new = STORE.new || {};
    STORE.new[kind] = created(kind).concat(obj);
    localStorage.setItem(STORE_KEY, JSON.stringify(STORE));
  }

  function deleteCreated(kind, id) {
    if (!STORE.new || !STORE.new[kind]) return;
    STORE.new[kind] = STORE.new[kind].filter((x) => x.id !== id);
    if (STORE[kind]) delete STORE[kind][id];
    localStorage.setItem(STORE_KEY, JSON.stringify(STORE));
  }

  /* Идентификатор собирается из названия: он попадёт в адрес страницы
     на витрине, поэтому латиницей. Хвост из времени — от совпадений. */
  const SLUG_MAP = { а:"a",б:"b",в:"v",г:"g",д:"d",е:"e",ё:"e",ж:"zh",з:"z",и:"i",й:"i",к:"k",л:"l",м:"m",н:"n",о:"o",п:"p",р:"r",с:"s",т:"t",у:"u",ф:"f",х:"h",ц:"c",ч:"ch",ш:"sh",щ:"sch",ъ:"",ы:"y",ь:"",э:"e",ю:"yu",я:"ya" };
  function slugify(text) {
    const base = String(text || "")
      .toLowerCase()
      .split("")
      .map((c) => (SLUG_MAP[c] !== undefined ? SLUG_MAP[c] : c))
      .join("")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "")
      .slice(0, 40);
    return `${base || "item"}-${Date.now().toString(36).slice(-4)}`;
  }

  const hasEdits = () =>
    Object.keys(STORE).some((k) =>
      k === "new"
        ? Object.values(STORE.new || {}).some((list) => list && list.length)
        : Object.keys(STORE[k] || {}).length
    );

  /* Цена позиции считается так же, как на витрине: базовая цена за
     250 г × множитель веса, минус скидка подписки. Держать сумму
     отдельным полем нельзя — она разъедется с составом. */
  function linePrice(line) {
    const p = (A.products || []).find((x) => x.id === line.product);
    if (!p) return 0;
    const mult = ((A.weights || []).find((w) => w.id === line.weight) || {}).multiplier || 1;
    const one = p.price * mult * (line.subscribe ? 1 - (A.subscribeDiscount || 0) : 1);
    return one * (line.qty || 1);
  }
  const orderTotal = (o) => (o.lines || []).reduce((s, l) => s + linePrice(l), 0);
  const orderCount = (o) => (o.lines || []).reduce((s, l) => s + (l.qty || 1), 0);

  /* Данные наружу всегда отдаются уже с наложенными правками, вместе с
     созданными записями. Новое идёт первым: только что заведённую
     позицию ищут вверху списка, а не в конце. */
  const orders = () => (D.orders || []).map((o) => ({ ...o, ...patchOf("orders", o.id) }));
  const products = () =>
    [...created("products"), ...(A.products || [])].map((p) => ({ ...p, ...patchOf("products", p.id) }));
  const articles = () =>
    [...created("articles"), ...(A.articles || [])]
      .map((a) => ({ ...a, ...patchOf("articles", a.id) }))
      .sort((a, b) => String(b.date).localeCompare(String(a.date)));

  const product = (id) => products().find((p) => p.id === id);
  const weightLabel = (id) => ((A.weights || []).find((w) => w.id === id) || {}).label || id + " г";
  const grindLabel = (id) => ((A.grinds || []).find((g) => g.id === id) || {}).label || id;

  /* ── Оболочка ────────────────────────────────────────────────────*/

  const NAV = [
    ["Обзор", [
      ["index.html", "Дашборд", "dashboard", ICONS.grid],
    ]],
    ["Продажи", [
      ["orders.html", "Заказы", "orders", ICONS.bag],
      ["subscriptions.html", "Подписки", "subscriptions", ICONS.repeat],
      ["leads.html", "Заявки опта", "leads", ICONS.users],
    ]],
    ["Контент", [
      ["products.html", "Товары", "products", ICONS.box],
      ["articles.html", "Журнал", "articles", ICONS.doc],
    ]],
  ];

  function nav() {
    /* Счётчики висят только там, где есть что разобрать: пустой ноль
       у пункта меню читается как поломка, а не как «дел нет». */
    const counts = {
      orders: orders().filter((o) => o.status === "new").length,
      leads: (D.leads || []).filter((l) => l.status === "new").length,
    };
    const groups = NAV.map(([label, items]) => `
      <div class="admin-nav__group">
        <p class="admin-nav__label">${label}</p>
        ${items.map(([href, title, id, icon]) => {
          const n = counts[id];
          return `<a href="${href}" class="${PAGE === id ? "is-active" : ""}"${PAGE === id ? ' aria-current="page"' : ""}>
            ${icon}<span>${title}</span>${n ? `<span class="admin-nav__count">${n}</span>` : ""}
          </a>`;
        }).join("")}
      </div>`).join("");

    return `
      <div class="admin-nav__brand">
        <img src="../img/logo.webp" alt="" width="187" height="138">
        <span>Al Jar<span class="admin-nav__tag">Панель управления</span></span>
      </div>
      ${groups}
      <div class="admin-nav__foot">
        ${hasEdits() ? `<button class="admin-btn admin-btn--ghost" type="button" data-reset-demo>Сбросить правки</button>` : ""}
        <a href="../index.html">← Открыть витрину</a>
        <a href="login.html">${ICONS.out}<span>Выйти</span></a>
      </div>`;
  }

  function topbar(host) {
    const title = host.dataset.title || "";
    const note = host.dataset.note || "";
    const actions = host.dataset.actions || "";
    return `
      <div class="admin-topbar__title">
        <h1>${title}</h1>
        ${note ? `<p class="tiny">${note}</p>` : ""}
      </div>
      ${actions ? `<div class="admin-topbar__actions">${actions}</div>` : ""}`;
  }

  /* ── Выдвижной редактор ──────────────────────────────────────────
     Одна панель на страницу, поля собираются из описания. Разметку
     вставляем из JS: она одинаковая на всех экранах, дублировать её по
     файлам незачем. */

  let drawerEl = null;
  let lastFocused = null;

  function ensureDrawer() {
    if (drawerEl) return drawerEl;
    document.body.insertAdjacentHTML(
      "beforeend",
      `<div class="drawer" data-drawer hidden>
        <div class="drawer__scrim" data-drawer-close></div>
        <aside class="drawer__panel" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
          <header class="drawer__head">
            <div>
              <h2 id="drawer-title" data-drawer-title></h2>
              <p class="tiny" data-drawer-note></p>
            </div>
            <button class="drawer__close" type="button" data-drawer-close aria-label="Закрыть">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 5l10 10M15 5L5 15"/></svg>
            </button>
          </header>
          <form class="drawer__body" id="drawer-form" data-drawer-form></form>
          <footer class="drawer__foot">
            <button class="btn btn--petrol btn--m" type="submit" form="drawer-form">Сохранить</button>
            <button class="btn btn--ghost btn--m" type="button" data-drawer-close>Отмена</button>
          </footer>
        </aside>
      </div>`
    );
    drawerEl = document.querySelector("[data-drawer]");
    drawerEl.querySelectorAll("[data-drawer-close]").forEach((b) =>
      b.addEventListener("click", closeDrawer)
    );
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !drawerEl.hidden) closeDrawer();
    });
    return drawerEl;
  }

  function closeDrawer() {
    if (!drawerEl) return;
    drawerEl.hidden = true;
    /* Фокус возвращается на кнопку, которой панель открыли: иначе он
       улетает в начало документа и клавиатурная навигация теряет место. */
    if (lastFocused && document.contains(lastFocused)) lastFocused.focus();
  }

  /* Описание поля → разметка. Типы: text, number, date, select,
     textarea, checkbox, readonly, hint. */
  function fieldHTML(f) {
    const val = f.value == null ? "" : String(f.value);
    if (f.type === "hint") return `<p class="drawer__hint">${f.label}</p>`;
    if (f.type === "lines") {
      const rows = f.lines.map((l) => {
        const p = product(l.product) || {};
        return `<li class="order-line">
          <img src="${asset(p.image)}" alt="" loading="lazy" decoding="async">
          <div class="order-line__body">
            <strong>${p.name || l.product}${l.qty > 1 ? ` × ${l.qty}` : ""}</strong>
            <span class="tiny">${weightLabel(l.weight)} · <b>${grindLabel(l.grind)}</b>${l.subscribe ? " · подписка" : ""}</span>
          </div>
          <span class="order-line__sum">${formatPrice(linePrice(l))}</span>
        </li>`;
      }).join("");
      return `<div class="drawer__readonly">
        <span class="tiny">${f.label}</span>
        <ul class="order-lines">${rows}</ul>
        ${f.note ? `<span class="tiny">${f.note}</span>` : ""}
        <span class="tiny">Помол выбран покупателем на сайте. Сумма считается по составу.</span>
      </div>`;
    }
    if (f.type === "readonly") {
      return `<div class="drawer__readonly">
        <span class="tiny">${f.label}</span>
        <strong>${val}</strong>
        ${f.note ? `<span class="tiny">${f.note}</span>` : ""}
      </div>`;
    }
    if (f.type === "image") {
      /* Значение хранится в скрытом поле: файловый input нельзя
         заполнить программно, а FormData должна отдать готовую картинку
         независимо от того, меняли её или нет. */
      return `<div class="upload" data-upload data-w="${f.width}" data-h="${f.height}" data-max="${f.maxKb}">
        <span class="opt-group__label">${f.label}</span>
        <div class="upload__row">
          <img class="upload__preview" data-upload-preview src="${asset(val)}" alt="">
          <div class="upload__control">
            <input class="field field--file" type="file" accept="image/jpeg,image/png,image/webp" data-upload-input>
            <p class="help" data-upload-hint>${f.note}</p>
          </div>
        </div>
        <input type="hidden" name="${f.name}" value="${val}" data-upload-value>
      </div>`;
    }
    if (f.type === "checkbox") {
      return `<label class="checkbox">
        <input type="checkbox" name="${f.name}"${f.value ? " checked" : ""}>
        <span>${f.label}</span>
      </label>`;
    }
    if (f.type === "select") {
      const opts = f.options
        .map(([v, t]) => `<option value="${v}"${String(v) === val ? " selected" : ""}>${t}</option>`)
        .join("");
      return `<label class="label">${f.label}
        <select class="select" name="${f.name}">${opts}</select>
      </label>`;
    }
    if (f.type === "textarea") {
      return `<label class="label">${f.label}
        <textarea class="textarea" name="${f.name}" rows="3">${val}</textarea>
      </label>`;
    }
    const attrs = [
      `type="${f.type || "text"}"`,
      `name="${f.name}"`,
      `value="${val.replace(/"/g, "&quot;")}"`,
      f.min != null ? `min="${f.min}"` : "",
      f.step ? `step="${f.step}"` : "",
      f.required ? "required" : "",
    ].filter(Boolean).join(" ");
    return `<label class="label">${f.label}
      <input class="field" ${attrs}>
      ${f.note ? `<span class="help">${f.note}</span>` : ""}
    </label>`;
  }

  /* ── Загрузка обложки ────────────────────────────────────────────
     Файл проверяется и тут же приводится к целевому размеру: браузер
     рисует его на канве и отдаёт WebP. Это и есть то, что делал бы
     бэкенд при загрузке, и заодно решает проблему объёма — исходный
     снимок с телефона в localStorage не влезет. */
  function bindUploads(root) {
    root.querySelectorAll("[data-upload]").forEach((box) => {
      const input = box.querySelector("[data-upload-input]");
      const preview = box.querySelector("[data-upload-preview]");
      const value = box.querySelector("[data-upload-value]");
      const hint = box.querySelector("[data-upload-hint]");
      const baseHint = hint.textContent;
      const W = Number(box.dataset.w);
      const H = Number(box.dataset.h);
      const maxKb = Number(box.dataset.max);

      const fail = (text) => {
        hint.textContent = text;
        hint.classList.add("help--error");
        input.value = "";
      };

      input.addEventListener("change", () => {
        const file = input.files && input.files[0];
        if (!file) return;
        hint.classList.remove("help--error");
        hint.textContent = baseHint;

        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
          return fail("Нужен JPG, PNG или WebP — этот формат не подойдёт.");
        }
        if (file.size > maxKb * 1024) {
          return fail(`Файл ${Math.round(file.size / 1024)} КБ, а можно до ${maxKb} КБ.`);
        }

        const img = new Image();
        img.onload = () => {
          if (img.naturalWidth < W || img.naturalHeight < H) {
            URL.revokeObjectURL(img.src);
            return fail(`Снимок ${img.naturalWidth}×${img.naturalHeight}, а нужно от ${W}×${H}.`);
          }
          /* Кадрируем по центру под нужную пропорцию, а не растягиваем:
             растянутая пачка на витрине выглядит браком. */
          const scale = Math.max(W / img.naturalWidth, H / img.naturalHeight);
          const dw = img.naturalWidth * scale;
          const dh = img.naturalHeight * scale;
          const canvas = document.createElement("canvas");
          canvas.width = W;
          canvas.height = H;
          canvas.getContext("2d").drawImage(img, (W - dw) / 2, (H - dh) / 2, dw, dh);
          const out = canvas.toDataURL("image/webp", 0.82);
          URL.revokeObjectURL(img.src);

          value.value = out;
          preview.src = out;
          hint.textContent = `Готово: ${W}×${H}, WebP, ${Math.round((out.length * 0.75) / 1024)} КБ.`;
        };
        img.onerror = () => fail("Не удалось прочитать файл.");
        img.src = URL.createObjectURL(file);
      });
    });
  }

  function openDrawer({ title, note, fields, onSave, trigger }) {
    const el = ensureDrawer();
    lastFocused = trigger || document.activeElement;
    el.querySelector("[data-drawer-title]").textContent = title;
    const noteEl = el.querySelector("[data-drawer-note]");
    noteEl.textContent = note || "";
    noteEl.hidden = !note;

    const form = el.querySelector("[data-drawer-form]");
    form.innerHTML = fields
      .map((f) => (f.row ? `<div class="drawer__row drawer__row--2">${f.row.map(fieldHTML).join("")}</div>` : fieldHTML(f)))
      .join("");

    bindUploads(form);

    form.onsubmit = (e) => {
      e.preventDefault();
      const data = {};
      new FormData(form).forEach((v, k) => (data[k] = v));
      /* FormData не отдаёт незачекнутые чекбоксы — восстанавливаем их
         явно, иначе снятая галка выглядит как «поле не меняли». */
      form.querySelectorAll("input[type=checkbox]").forEach((c) => (data[c.name] = c.checked));
      onSave(data);
      closeDrawer();
    };

    el.hidden = false;
    form.querySelector("input:not([type=checkbox]), select, textarea")?.focus();
  }

  /* Короткое подтверждение действия. Класс .toast есть в стилях
     витрины, свой заводить незачем. */
  function toast(text) {
    let el = document.querySelector(".toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      document.body.appendChild(el);
    }
    el.textContent = text;
    el.classList.add("is-show");
    clearTimeout(el.dataset.timer);
    el.dataset.timer = String(setTimeout(() => el.classList.remove("is-show"), 2200));
  }

  /* ── Редактор заказа ─────────────────────────────────────────────*/

  function editOrder(id, trigger) {
    const o = orders().find((x) => x.id === id);
    if (!o) return;
    openDrawer({
      trigger,
      title: `Заказ ${o.id}`,
      note: `Создан ${formatDate(o.date)}`,
      fields: [
        { type: "select", name: "status", label: "Статус", value: o.status, options: [
          ["new", "Новый"], ["work", "В работе"], ["done", "Выполнен"], ["canceled", "Отменён"],
        ]},
        { row: [
          { name: "customer", label: "Покупатель", value: o.customer, required: true },
          { name: "phone", label: "Телефон", type: "tel", value: o.phone, required: true },
        ]},
        { type: "select", name: "ship", label: "Доставка", value: o.ship, options: [
          ["Курьер", "Курьер"], ["ПВЗ", "ПВЗ"], ["Почта", "Почта"],
        ]},
        /* Помол покупатель выбрал на сайте — здесь он показывается,
           но не правится: менять чужой выбор молча нельзя, а сумма
           считается по составу и в поле не вводится. */
        { type: "lines", label: "Состав заказа", lines: o.lines || [],
          note: `${orderCount(o)} шт. · ${formatPrice(orderTotal(o))}` },
        { type: "textarea", name: "comment", label: "Комментарий для склада", value: o.comment || "" },
      ],
      onSave: (data) => {
        savePatch("orders", id, data);
        renderAll();
        toast(`Заказ ${id} обновлён`);
      },
    });
  }

  /* ── Редактор карточки товара ────────────────────────────────────*/

  const ROASTS = [["light", "Светлая обжарка"], ["medium", "Средняя обжарка"], ["dark", "Тёмная обжарка"]];
  const labelOf = (pairs, v) => (pairs.find(([k]) => k === v) || [, v])[1];

  function productFields(p) {
    return [
      { name: "name", label: "Название", value: p.name || "", required: true },
      { row: [
        { name: "price", label: "Цена за 250 г, ₽", type: "number", value: p.price ?? "", min: 0, step: "5", required: true },
        { name: "roastDate", label: "Дата обжарки", type: "date", value: p.roastDate || new Date().toISOString().slice(0, 10), required: true },
      ]},
      { type: "hint", label: "Дата обжарки управляет бейджем свежести на витрине и порядком в блоке «Свежесть склада»." },
      /* Помола здесь нет намеренно: его выбирает покупатель при
         заказе, и в карточке товара ему не место. */
      { type: "select", name: "roast", label: "Степень обжарки", value: p.roast || "medium", options: ROASTS },
      { row: [
        { name: "origin", label: "Происхождение", value: p.origin || "", required: true },
        { name: "region", label: "Регион", value: p.region || "" },
      ]},
      { name: "species", label: "Вид", value: p.species || "100% арабика" },
      { name: "notes", label: "Вкусовые ноты", value: p.notes || "", note: "Через запятую — на карточке они разделяются точками" },
      /* Требования взяты с реальных файлов проекта: снимки пачек лежат
         вертикальными 864×1152. */
      { type: "image", name: "image", label: "Фото упаковки", value: p.image || "img/bag-kraft.webp",
        width: 864, height: 1152, maxKb: 4096,
        note: "JPG, PNG или WebP. Вертикальный кадр 3:4, не меньше 864×1152, до 4 МБ. Обрежем по центру и переведём в WebP." },
      { type: "checkbox", name: "featured", label: "Показывать в блоке «Свежая обжарка этой недели»", value: !!p.featured },
      { type: "checkbox", name: "hidden", label: "Скрыть позицию с витрины", value: !!p.hidden },
    ];
  }

  /* Подпись денормализована в данных витрины — обновляем её вместе с
     кодом, иначе каталог покажет старый текст. */
  const normalizeProduct = (data) => ({
    ...data,
    price: Number(data.price) || 0,
    roastLabel: labelOf(ROASTS, data.roast),
  });

  function editProduct(id, trigger) {
    const p = product(id);
    if (!p) return;
    openDrawer({
      trigger,
      title: p.name,
      note: isCreated("products", id) ? "Создана в панели" : "Из исходного набора",
      fields: productFields(p),
      onSave: (data) => {
        savePatch("products", id, normalizeProduct(data));
        renderAll();
        toast(`«${data.name}» сохранена`);
      },
    });
  }

  function createProduct(trigger) {
    openDrawer({
      trigger,
      title: "Новая позиция",
      note: "Появится в каталоге сразу, если не отметить «скрыть»",
      fields: productFields({}),
      onSave: (data) => {
        createEntity("products", {
          id: slugify(data.name),
          ...normalizeProduct(data),
          /* Поля, которых нет в форме, но без которых карточка на
             витрине рисуется с пустотами. Способ приготовления убран из
             формы вместе с помолом, поэтому новинка попадает в фильтр
             каталога как эспрессо — если это неверно, способ придётся
             вернуть отдельным полем. */
          fullName: data.name,
          method: "espresso",
          methodLabel: "Эспрессо",
          process: "Уточняется",
          species: data.species || "100% арабика",
          profile: { fruity: 50, chocolate: 50, spice: 40, body: 60, acidity: 50 },
          rating: 0,
          reviews: 0,
        });
        renderAll();
        toast(`«${data.name}» добавлена в каталог`);
      },
    });
  }

  /* ── Журнал ──────────────────────────────────────────────────────*/

  const ARTICLE_STATUS = [["published", "Опубликована"], ["draft", "Черновик"]];

  function renderArticles() {
    const body = document.querySelector("[data-articles]");
    if (!body) return;
    const list = articles();
    if (!list.length) {
      body.innerHTML = `<tr><td colspan="5"><div class="admin-empty"><strong>Статей пока нет</strong><span class="tiny">Создайте первую — кнопка в шапке страницы.</span></div></td></tr>`;
      return;
    }
    body.innerHTML = list.map((a) => {
      const draft = a.status === "draft";
      return `
      <tr class="${draft ? "is-hidden" : ""}">
        <td>
          <div class="admin-cell-product">
            <img src="${asset(a.image)}" alt="" loading="lazy" decoding="async">
            <div>
              <strong>${a.title}</strong>
              <span class="tiny">${a.excerpt || ""}</span>
            </div>
          </div>
        </td>
        <td>${a.tag || "—"}</td>
        <td>${formatDate(a.date)}</td>
        <td>${statusPill(draft ? "paused" : "done", { paused: "Черновик", done: "Опубликована" })}</td>
        <td>
          <div class="admin-actions">
            <button class="admin-btn" type="button" data-edit-article="${a.id}">Изменить</button>
            ${isCreated("articles", a.id)
              ? `<button class="admin-btn admin-btn--ghost" type="button" data-delete-article="${a.id}">Удалить</button>`
              : ""}
          </div>
        </td>
      </tr>`;
    }).join("");

    body.querySelectorAll("[data-edit-article]").forEach((b) =>
      b.addEventListener("click", () => editArticle(b.dataset.editArticle, b))
    );
    /* Удалять можно только созданные здесь: демо-набор — точка отсчёта,
       его возвращает общий сброс, а не удаление по одной записи. */
    body.querySelectorAll("[data-delete-article]").forEach((b) =>
      b.addEventListener("click", () => {
        const a = articles().find((x) => x.id === b.dataset.deleteArticle);
        deleteCreated("articles", b.dataset.deleteArticle);
        renderAll();
        toast(`«${a ? a.title : "Статья"}» удалена`);
      })
    );
  }

  function articleFields(a) {
    const tags = [...new Set(articles().map((x) => x.tag).filter(Boolean))];
    return [
      { name: "title", label: "Заголовок", value: a.title || "", required: true },
      { row: [
        { name: "date", label: "Дата публикации", type: "date", value: a.date || new Date().toISOString().slice(0, 10), required: true },
        { type: "select", name: "status", label: "Состояние", value: a.status || "draft", options: ARTICLE_STATUS },
      ]},
      { name: "tag", label: "Рубрика", value: a.tag || "", note: tags.length ? `Уже есть: ${tags.join(", ")}` : "" },
      /* Требования взяты с реальных файлов проекта: обложки статей
         лежат горизонтальными 1152×864. */
      { type: "image", name: "image", label: "Обложка", value: a.image || "img/dallah.webp",
        width: 1152, height: 864, maxKb: 4096,
        note: "JPG, PNG или WebP. Горизонтальный кадр 4:3, не меньше 1152×864, до 4 МБ. Обрежем по центру и переведём в WebP." },
      { type: "textarea", name: "excerpt", label: "Анонс", value: a.excerpt || "" },
      { type: "textarea", name: "body", label: "Текст статьи", value: a.body || "" },
    ];
  }

  function editArticle(id, trigger) {
    const a = articles().find((x) => x.id === id);
    if (!a) return;
    openDrawer({
      trigger,
      title: a.title,
      note: isCreated("articles", id) ? "Создана в панели" : "Из исходного набора",
      fields: articleFields(a),
      onSave: (data) => {
        savePatch("articles", id, data);
        renderAll();
        toast("Статья сохранена");
      },
    });
  }

  function createArticle(trigger) {
    openDrawer({
      trigger,
      title: "Новая статья",
      note: "Черновик не показывается на витрине, пока состояние не переключат",
      fields: articleFields({}),
      onSave: (data) => {
        createEntity("articles", { id: slugify(data.title), ...data });
        renderAll();
        toast(`«${data.title}» создана`);
      },
    });
  }

  /* ── Дашборд ─────────────────────────────────────────────────────*/

  function renderDashboard() {
    const host = document.querySelector("[data-dashboard]");
    if (!host) return;

    const stats = document.querySelector("[data-stats]");
    if (stats) {
      /* Считаем из тех же данных, что показывает таблица. Держать
         показатели отдельными числами нельзя: после первой же правки
         статуса или состава они разойдутся с тем, что видно ниже. */
      const all = orders();
      const ordersToday = all.filter((o) => daysFrom(o.date) === 0).length;
      const revenueWeek = all
        .filter((o) => o.status !== "canceled" && daysFrom(o.date) <= 7)
        .reduce((s, o) => s + orderTotal(o), 0);
      const activeSubs = (D.subscriptions || []).filter((s) => s.status === "new").length;
      const newLeads = (D.leads || []).filter((l) => l.status === "new").length;

      stats.innerHTML = [
        ["Заказов сегодня", ordersToday, "новых на сайте", ""],
        ["Выручка за неделю", formatPrice(revenueWeek), "без отменённых", "is-up"],
        ["Активных подписок", activeSubs, "ключевая модель", ""],
        ["Новых заявок опта", newLeads, newLeads ? "ждут ответа" : "все разобраны", newLeads ? "is-down" : ""],
      ].map(([label, value, delta, cls]) => `
        <div class="admin-stat">
          <span class="admin-stat__label">${label}</span>
          <span class="admin-stat__value">${value}</span>
          <span class="admin-stat__delta ${cls}">${delta}</span>
        </div>`).join("");
    }

    /* Свежесть склада — то, ради чего этот экран вообще открывают:
       дата обжарки главный аргумент бренда, и просрочка на витрине
       бьёт по доверию сильнее, чем отсутствие позиции. */
    const roast = document.querySelector("[data-roast-watch]");
    if (roast) {
      const rows = products()
        .map((p) => ({ p, days: daysFrom(p.roastDate) }))
        .sort((a, b) => b.days - a.days)
        .slice(0, 6);
      roast.innerHTML = rows.map(({ p, days }) => {
        const step = (A.freshness || []).find((s) => days <= s.maxDays) || {};
        const key = days <= 3 ? "done" : days <= 7 ? "work" : "canceled";
        return `<div class="roast-row">
          <div class="roast-row__name">
            <span>${p.name}</span>
            <span class="tiny">${step.label || ""} · ${formatDate(p.roastDate)}</span>
          </div>
          ${statusPill(key, { done: "Свежий", work: "Проверить", canceled: `${days} дн.` })}
        </div>`;
      }).join("");
    }

  }

  /* ── Заказы ──────────────────────────────────────────────────────*/

  /* Состав прямо в строке таблицы: без него сборщику пришлось бы
     открывать каждый заказ, чтобы понять, что класть в коробку.
     Вес и помол — второй строкой: две позиции одного сорта в разном
     помоле иначе выглядели бы дубликатом. */
  function orderComposition(o) {
    const lines = o.lines || [];
    if (!lines.length) return `<span class="tiny">—</span>`;
    return `<ul class="order-cell">${lines.map((l) => {
      const p = product(l.product) || {};
      return `<li class="order-cell__row">
        <span class="order-cell__body">
          <strong>${p.name || l.product}</strong>
          <span class="tiny">${weightLabel(l.weight)} · ${grindLabel(l.grind)}${l.subscribe ? " · подписка" : ""}</span>
        </span>
        <span class="order-cell__qty">× ${l.qty || 1}</span>
      </li>`;
    }).join("")}</ul>`;
  }

  /* Лимит задаёт разметка: на дашборде это выжимка из четырёх строк,
     на странице заказов — весь список. Иначе пришлось бы звать рендер
     дважды с разными аргументами и затирать результат первого вызова. */
  function renderOrders() {
    const body = document.querySelector("[data-orders]");
    if (!body) return;
    const limit = Number(body.dataset.ordersLimit) || 0;
    /* На дашборде таблица зажата в узкую колонку рядом со свежестью
       склада: там состав раздувает строку втрое и мешает читать сводку.
       Полный состав — на странице заказов, где место есть. */
    const compact = body.hasAttribute("data-orders-compact");
    const all = orders();
    const list = limit ? all.slice(0, limit) : all;
    if (!list.length) {
      body.innerHTML = `<tr><td colspan="8"><div class="admin-empty"><strong>Заказов пока нет</strong><span class="tiny">Здесь появятся новые заказы с витрины.</span></div></td></tr>`;
      return;
    }
    body.innerHTML = list.map((o) => `
      <tr>
        <td><strong>${o.id}</strong></td>
        <td>${formatDate(o.date)}</td>
        <td>
          <div>${o.customer}</div>
          <span class="tiny">${o.phone}</span>
        </td>
        ${compact ? `<td class="num">${orderCount(o)}</td>` : `<td>${orderComposition(o)}</td>`}
        <td>${o.ship}</td>
        <td class="num">${formatPrice(orderTotal(o))}</td>
        <td>${statusPill(o.status)}</td>
        <td>
          <div class="admin-actions">
            <button class="admin-btn" type="button" data-edit-order="${o.id}">Изменить</button>
          </div>
        </td>
      </tr>`).join("");

    body.querySelectorAll("[data-edit-order]").forEach((b) =>
      b.addEventListener("click", () => editOrder(b.dataset.editOrder, b))
    );
  }

  /* ── Подписки ────────────────────────────────────────────────────*/

  function renderSubscriptions() {
    const body = document.querySelector("[data-subscriptions]");
    if (!body) return;
    body.innerHTML = (D.subscriptions || []).map((s) => {
      const p = product(s.product);
      /* Дату следующей отгрузки показываем только у активной подписки:
         у поставленной на паузу и отменённой отгрузки нет, а дата в
         строке читалась бы как обещание её привезти. */
      const next =
        s.status === "new" && s.next
          ? `${formatDate(s.next)} <span class="tiny">через ${-daysFrom(s.next)} дн.</span>`
          : "—";
      return `
      <tr>
        <td><strong>${s.id}</strong></td>
        <td>${s.customer}</td>
        <td>
          <div class="admin-cell-product">
            <img src="${asset(p && p.image)}" alt="" loading="lazy" decoding="async">
            <div>
              <strong>${p ? p.name : s.product}</strong>
              <span class="tiny">${weightLabel(s.weight)} · ${grindLabel(s.grind)}</span>
            </div>
          </div>
        </td>
        <td>каждые ${s.every} нед.</td>
        <td>${next}</td>
        <td class="num">${formatPrice(s.total)}</td>
        <td>${statusPill(s.status, { new: "Активна", paused: "На паузе", canceled: "Отменена" })}</td>
      </tr>`;
    }).join("");
  }

  /* ── Заявки опта ─────────────────────────────────────────────────*/

  function renderLeads() {
    const body = document.querySelector("[data-leads]");
    if (!body) return;
    body.innerHTML = (D.leads || []).map((l) => `
      <tr>
        <td><strong>${l.id}</strong><br><span class="tiny">${formatDate(l.date)}</span></td>
        <td>
          <div>${l.name}</div>
          <span class="tiny">${l.company}</span>
        </td>
        <td>
          <div>${l.phone}</div>
          <span class="tiny">${l.email}</span>
        </td>
        <td>${l.type}</td>
        <td>
          <div>${l.volume}</div>
          <span class="tiny">${l.comment}</span>
        </td>
        <td>${statusPill(l.status, { new: "Новая", work: "В работе", done: "Закрыта" })}</td>
      </tr>`).join("");
  }

  /* ── Товары ──────────────────────────────────────────────────────*/

  function renderProducts() {
    const body = document.querySelector("[data-products]");
    if (!body) return;
    body.innerHTML = products().map((p) => {
      const days = daysFrom(p.roastDate);
      const key = days <= 3 ? "done" : days <= 7 ? "work" : "canceled";
      return `
      <tr class="${p.hidden ? "is-hidden" : ""}">
        <td>
          <div class="admin-cell-product">
            <img src="${asset(p.image)}" alt="" loading="lazy" decoding="async">
            <div>
              <strong>${p.name}</strong>
              <span class="tiny">${p.origin} · ${p.region}</span>
            </div>
          </div>
        </td>
        <td>${p.roastLabel}</td>
        <td>${p.methodLabel}</td>
        <td>
          ${formatDate(p.roastDate)}
          ${statusPill(key, { done: "Свежий", work: "Проверить", canceled: `${days} дн.` })}
        </td>
        <td class="num">${formatPrice(p.price)}</td>
        <td class="num">${p.rating} <span class="tiny">/ ${p.reviews}</span></td>
        <td>
          <div class="admin-actions">
            <button class="admin-btn" type="button" data-edit-product="${p.id}">Изменить</button>
            <button class="admin-btn admin-btn--ghost" type="button" data-toggle-product="${p.id}">${p.hidden ? "Вернуть" : "Скрыть"}</button>
            ${isCreated("products", p.id)
              ? `<button class="admin-btn admin-btn--ghost" type="button" data-delete-product="${p.id}">Удалить</button>`
              : ""}
          </div>
        </td>
      </tr>`;
    }).join("");

    body.querySelectorAll("[data-edit-product]").forEach((b) =>
      b.addEventListener("click", () => editProduct(b.dataset.editProduct, b))
    );
    /* Скрыть и вернуть — самое частое действие в каталоге, ради него
       незачем открывать редактор целиком. */
    body.querySelectorAll("[data-toggle-product]").forEach((b) =>
      b.addEventListener("click", () => {
        const id = b.dataset.toggleProduct;
        const p = product(id);
        savePatch("products", id, { hidden: !p.hidden });
        renderAll();
        toast(p.hidden ? `«${p.name}» снова на витрине` : `«${p.name}» скрыта с витрины`);
      })
    );
    /* Удалять можно только созданные здесь: демо-набор возвращает общий
       сброс, а не удаление по одной позиции. */
    body.querySelectorAll("[data-delete-product]").forEach((b) =>
      b.addEventListener("click", () => {
        const p = product(b.dataset.deleteProduct);
        deleteCreated("products", b.dataset.deleteProduct);
        renderAll();
        toast(`«${p ? p.name : "Позиция"}» удалена`);
      })
    );
  }

  /* ── Запуск ──────────────────────────────────────────────────────*/

  /* Перерисовываем всё, что есть на текущей странице: после правки
     меняются и таблица, и счётчики в меню, и плитки дашборда. */
  function renderAll() {
    const navHost = document.querySelector("[data-admin-nav]");
    if (navHost) navHost.innerHTML = nav();
    bindNav();

    renderDashboard();
    renderOrders();
    renderSubscriptions();
    renderLeads();
    renderProducts();
    renderArticles();
  }

  function bindNav() {
    const reset = document.querySelector("[data-reset-demo]");
    if (!reset) return;
    reset.addEventListener("click", () => {
      resetStore();
      renderAll();
      toast("Демо-данные восстановлены");
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const topHost = document.querySelector("[data-admin-topbar]");
    if (topHost) topHost.innerHTML = topbar(topHost);

    /* Кнопки создания живут в шапке страницы: она не перерисовывается,
       поэтому привязка здесь, один раз. Внутри renderAll они теряли бы
       обработчик при отмене в панели редактора. */
    topHost?.querySelector("[data-create-product]")?.addEventListener("click", (e) => createProduct(e.currentTarget));
    topHost?.querySelector("[data-create-article]")?.addEventListener("click", (e) => createArticle(e.currentTarget));

    renderAll();

    /* Формы прототипа никуда не отправляются */
    document.querySelectorAll("[data-demo-form]").forEach((f) =>
      f.addEventListener("submit", (e) => {
        e.preventDefault();
        location.href = f.dataset.demoForm || "index.html";
      })
    );
  });
})();
