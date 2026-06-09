/* =========================================================
   ZOT AUTO — boutique · interactions
   Les produits & services sont générés depuis data/catalogue.js
   ========================================================= */
(function () {
  "use strict";

  var WA = "262692000000"; // numéro WhatsApp (à remplacer)
  var DATA = window.ZOTAUTO || { products: [], services: [] };
  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var wa = function (text) { return "https://wa.me/" + WA + "?text=" + encodeURIComponent(text); };
  var euro = function (n) { return Number(n).toFixed(2).replace(".", ",") + " €"; };
  var esc = function (s) { return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); };
  var stars = function (n) { n = Math.max(0, Math.min(5, Math.round(n || 0))); return "★★★★★".slice(0, n) + "☆☆☆☆☆".slice(0, 5 - n); };

  /* =========================================================
     Génération du catalogue
     ========================================================= */
  function productCard(p) {
    var promo = /%|^-/.test(p.badge || "");
    var media = p.contain ? "product__media product__media--logo" : "product__media";
    var badge = p.badge ? '<span class="product__badge' + (promo ? " product__badge--promo" : "") + '">' + esc(p.badge) + "</span>" : "";
    var was = (p.oldPrice != null && p.oldPrice !== "") ? '<span class="was">' + euro(p.oldPrice) + "</span>" : "";
    var stockLabel = p.stock === "soon" ? "Sur commande" : "En stock";
    return (
      '<article class="product reveal" data-cat="' + esc(p.category) + '" data-name="' + esc(p.name) + '" data-price="' + esc(p.price) + '" data-brand="' + esc(p.brand) + '">' +
        '<div class="' + media + '">' + badge +
          '<img src="' + esc(p.image) + '" alt="' + esc(p.brand + " " + p.name) + '" loading="lazy" />' +
        "</div>" +
        '<div class="product__body">' +
          '<span class="product__brand">' + esc(p.brand) + "</span>" +
          '<h3 class="product__name">' + esc(p.name) + "</h3>" +
          '<div class="product__rating"><span class="stars">' + stars(p.rating) + "</span><small>(" + (p.reviews || 0) + ")</small></div>" +
          '<div class="product__stock ' + (p.stock === "soon" ? "soon" : "in") + '">' + stockLabel + "</div>" +
          '<div class="product__foot">' +
            '<div class="product__price"><span class="now">' + euro(p.price) + "</span>" + was + "</div>" +
            '<button class="btn-add" data-add aria-label="Ajouter au panier">Ajouter</button>' +
          "</div>" +
        "</div>" +
      "</article>"
    );
  }

  function serviceCard(s) {
    var badge = s.badge ? '<span class="service__badge">' + esc(s.badge) + "</span>" : "";
    var price = s.price ? '<span class="service__price">' + esc(s.price) + "</span>" : "<span></span>";
    var link = wa("Bonjour ZOT AUTO, je suis intéressé(e) par le service : " + s.name + ".");
    return (
      '<article class="service reveal">' +
        '<div class="service__top"><span class="service__ic">' + esc(s.icon || "🔧") + "</span>" + badge + "</div>" +
        '<h3 class="service__name">' + esc(s.name) + "</h3>" +
        '<p class="service__desc">' + esc(s.description) + "</p>" +
        '<div class="service__foot">' + price +
          '<a class="service__btn" href="' + link + '" target="_blank" rel="noopener">Demander →</a>' +
        "</div>" +
      "</article>"
    );
  }

  var productsWrap = $("#products");
  if (productsWrap) productsWrap.innerHTML = (DATA.products || []).map(productCard).join("");
  var servicesWrap = $("#servicesGrid");
  if (servicesWrap) servicesWrap.innerHTML = (DATA.services || []).map(serviceCard).join("");

  /* ---- Année ---- */
  var y = $("#year"); if (y) y.textContent = new Date().getFullYear();

  /* ---- Header sticky ---- */
  var header = $(".header");
  var onScroll = function () { header.classList.toggle("is-stuck", window.scrollY > 8); };
  onScroll(); window.addEventListener("scroll", onScroll, { passive: true });

  /* ---- Menu mobile ---- */
  var burger = $("#burger"), catnav = $("#catnav");
  if (burger && catnav) {
    burger.addEventListener("click", function () {
      var open = catnav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", String(open));
      catnav.style.display = open ? "block" : "";
    });
    $$("#catnav a").forEach(function (a) {
      a.addEventListener("click", function () { catnav.classList.remove("is-open"); catnav.style.display = ""; burger.setAttribute("aria-expanded", "false"); });
    });
  }

  /* ---- Sélecteur véhicule : onglets ---- */
  $$(".finder__tab").forEach(function (tab) {
    tab.addEventListener("click", function () {
      var name = tab.dataset.tab;
      $$(".finder__tab").forEach(function (t) { var a = t === tab; t.classList.toggle("is-active", a); t.setAttribute("aria-selected", String(a)); });
      $$(".finder__panel").forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    });
  });

  /* ---- Plaque (AB-123-CD) ---- */
  var plate = $("#plateInput");
  if (plate) plate.addEventListener("input", function () {
    var v = plate.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
    var out = v.slice(0, 2);
    if (v.length > 2) out += "-" + v.slice(2, 5);
    if (v.length > 5) out += "-" + v.slice(5, 7);
    plate.value = out;
  });

  /* ---- Sélecteur → WhatsApp ---- */
  var panelPlate = $("#panelPlate"), panelModel = $("#panelModel");
  if (panelPlate) panelPlate.addEventListener("submit", function (e) {
    e.preventDefault();
    var p = plate.value.trim();
    var msg = p ? "Bonjour ZOT AUTO, je cherche des pièces/produits pour le véhicule immatriculé " + p + " (974)."
                : "Bonjour ZOT AUTO, je cherche des pièces pour mon véhicule. Voici ma carte grise :";
    window.open(wa(msg), "_blank", "noopener");
  });
  if (panelModel) panelModel.addEventListener("submit", function (e) {
    e.preventDefault();
    var vals = $$("select", panelModel).map(function (s) { return s.value; }).filter(Boolean);
    window.open(wa("Bonjour ZOT AUTO, je cherche des pièces pour : " + (vals.join(" · ") || "mon véhicule") + "."), "_blank", "noopener");
  });

  /* ---- Recherche ---- */
  var searchForm = $("#searchForm"), searchInput = $("#searchInput");
  if (searchForm) searchForm.addEventListener("submit", function (e) {
    e.preventDefault();
    var q = (searchInput.value || "").trim();
    if (!q) { searchInput.focus(); return; }
    window.open(wa("Bonjour ZOT AUTO, je recherche : " + q), "_blank", "noopener");
  });

  /* ---- Filtres produits ---- */
  var chips = $$(".chip"), productsEmpty = $("#productsEmpty");
  function applyFilter(f) {
    chips.forEach(function (c) { c.classList.toggle("is-active", c.dataset.filter === f); });
    var visible = 0;
    $$(".product").forEach(function (p) {
      var show = f === "all" || p.dataset.cat === f;
      p.classList.toggle("is-hidden", !show);
      if (show) visible++;
    });
    if (productsEmpty) productsEmpty.hidden = visible > 0;
  }
  chips.forEach(function (chip) { chip.addEventListener("click", function () { applyFilter(chip.dataset.filter); }); });
  // Cartes catégories → filtre + scroll
  $$("[data-gocat]").forEach(function (card) {
    card.addEventListener("click", function () { applyFilter(card.dataset.gocat); });
  });

  /* =========================================================
     Panier
     ========================================================= */
  var STORE = "zotauto_cart";
  var cart = [];
  try { cart = JSON.parse(localStorage.getItem(STORE)) || []; } catch (e) { cart = []; }

  var cartEl = $("#cart"), cartItems = $("#cartItems"), cartEmpty = $("#cartEmpty"),
      cartFoot = $("#cartFoot"), cartTotal = $("#cartTotal"), cartCount = $("#cartCount"),
      cartCheckout = $("#cartCheckout"), toast = $("#toast");
  var toastTimer;

  var save = function () { try { localStorage.setItem(STORE, JSON.stringify(cart)); } catch (e) {} };
  var count = function () { return cart.reduce(function (n, i) { return n + i.qty; }, 0); };
  var total = function () { return cart.reduce(function (s, i) { return s + i.price * i.qty; }, 0); };

  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg; toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove("show"); }, 2600);
  }

  function renderCart() {
    var n = count();
    if (cartCount) {
      cartCount.textContent = n; cartCount.hidden = n === 0;
      if (n > 0) { cartCount.classList.remove("bump"); void cartCount.offsetWidth; cartCount.classList.add("bump"); }
    }
    if (!cartItems) return;
    cartItems.innerHTML = "";
    var empty = cart.length === 0;
    if (cartEmpty) cartEmpty.style.display = empty ? "" : "none";
    if (cartFoot) cartFoot.hidden = empty;
    cart.forEach(function (item, idx) {
      var row = document.createElement("div");
      row.className = "cart-item";
      row.innerHTML =
        '<div class="cart-item__info"><div class="cart-item__brand">' + esc(item.brand) + '</div>' +
        '<div class="cart-item__name">' + esc(item.name) + '</div>' +
        '<div class="cart-item__price">' + euro(item.price) + '</div></div>' +
        '<div class="cart-item__qty"><button type="button" data-dec="' + idx + '" aria-label="Moins">−</button>' +
        '<span>' + item.qty + '</span><button type="button" data-inc="' + idx + '" aria-label="Plus">+</button></div>';
      cartItems.appendChild(row);
    });
    if (cartTotal) cartTotal.textContent = euro(total());
    if (cartCheckout) {
      var lines = cart.map(function (i) { return "• " + i.qty + "× " + i.name + " (" + i.brand + ") — " + euro(i.price * i.qty); });
      cartCheckout.href = wa("Bonjour ZOT AUTO, je souhaite commander :\n" + lines.join("\n") + "\n\nTotal : " + euro(total()) + "\nMerci de me confirmer stock, prix et livraison (974).");
    }
    save();
  }

  function addToCart(p) {
    var found = cart.filter(function (i) { return i.name === p.name; })[0];
    if (found) found.qty += 1; else cart.push({ name: p.name, brand: p.brand, price: p.price, qty: 1 });
    renderCart(); showToast("Ajouté au panier");
  }

  // délégation : boutons "Ajouter" (produits générés dynamiquement)
  if (productsWrap) productsWrap.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-add]"); if (!btn) return;
    var card = btn.closest(".product");
    addToCart({ name: card.dataset.name, brand: card.dataset.brand, price: parseFloat(card.dataset.price) });
  });

  if (cartItems) cartItems.addEventListener("click", function (e) {
    var inc = e.target.getAttribute("data-inc"), dec = e.target.getAttribute("data-dec");
    if (inc !== null) { cart[+inc].qty += 1; renderCart(); }
    else if (dec !== null) { cart[+dec].qty -= 1; if (cart[+dec].qty <= 0) cart.splice(+dec, 1); renderCart(); }
  });

  var openCart = function () { cartEl.classList.add("open"); cartEl.setAttribute("aria-hidden", "false"); document.body.style.overflow = "hidden"; };
  var closeCart = function () { cartEl.classList.remove("open"); cartEl.setAttribute("aria-hidden", "true"); document.body.style.overflow = ""; };
  var cartOpenBtn = $("#cartOpen");
  if (cartOpenBtn) cartOpenBtn.addEventListener("click", openCart);
  $$("[data-cart-close]").forEach(function (el) { el.addEventListener("click", closeCart); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeCart(); });
  renderCart();

  /* ---- Scroll reveal ---- */
  var reveals = $$(".reveal");
  if (prefersReduced || !("IntersectionObserver" in window)) {
    reveals.forEach(function (el) { el.classList.add("in"); });
  } else {
    $$(".cats, .products, .services, .reviews").forEach(function (grid) {
      Array.prototype.forEach.call(grid.children, function (child, i) { child.style.transitionDelay = (i % 4) * 0.07 + "s"; });
    });
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) { if (entry.isIntersecting) { entry.target.classList.add("in"); obs.unobserve(entry.target); } });
    }, { threshold: 0.12, rootMargin: "0px 0px -6% 0px" });
    reveals.forEach(function (el) { io.observe(el); });
  }
})();
