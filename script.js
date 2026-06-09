/* =========================================================
   ZOT AUTO — boutique · interactions
   ========================================================= */
(function () {
  "use strict";

  var WA = "262692000000"; // numéro WhatsApp (à remplacer)
  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var wa = function (text) { return "https://wa.me/" + WA + "?text=" + encodeURIComponent(text); };
  var euro = function (n) { return n.toFixed(2).replace(".", ",") + " €"; };

  /* ---- Année ---- */
  var y = $("#year"); if (y) y.textContent = new Date().getFullYear();

  /* ---- Header sticky ---- */
  var header = $(".header");
  var onScroll = function () { header.classList.toggle("is-stuck", window.scrollY > 8); };
  onScroll(); window.addEventListener("scroll", onScroll, { passive: true });

  /* ---- Menu mobile (catnav) ---- */
  var burger = $("#burger"), catnav = $("#catnav");
  if (burger && catnav) {
    burger.addEventListener("click", function () {
      var open = catnav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", String(open));
      catnav.style.display = open ? "block" : "";
    });
    $$("#catnav a").forEach(function (a) {
      a.addEventListener("click", function () {
        catnav.classList.remove("is-open"); catnav.style.display = "";
        burger.setAttribute("aria-expanded", "false");
      });
    });
  }

  /* ---- Sélecteur véhicule : onglets ---- */
  $$(".finder__tab").forEach(function (tab) {
    tab.addEventListener("click", function () {
      var name = tab.dataset.tab;
      $$(".finder__tab").forEach(function (t) {
        var active = t === tab;
        t.classList.toggle("is-active", active);
        t.setAttribute("aria-selected", String(active));
      });
      $$(".finder__panel").forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    });
  });

  /* ---- Formatage plaque (AB-123-CD) ---- */
  var plate = $("#plateInput");
  if (plate) {
    plate.addEventListener("input", function () {
      var v = plate.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
      var out = v.slice(0, 2);
      if (v.length > 2) out += "-" + v.slice(2, 5);
      if (v.length > 5) out += "-" + v.slice(5, 7);
      plate.value = out;
    });
  }

  /* ---- Sélecteur : envoi vers WhatsApp ---- */
  var panelPlate = $("#panelPlate"), panelModel = $("#panelModel");
  if (panelPlate) panelPlate.addEventListener("submit", function (e) {
    e.preventDefault();
    var p = plate.value.trim();
    var msg = p
      ? "Bonjour ZOT AUTO, je cherche des pièces/produits pour le véhicule immatriculé " + p + " (974)."
      : "Bonjour ZOT AUTO, je cherche des pièces pour mon véhicule. Voici ma carte grise :";
    window.open(wa(msg), "_blank", "noopener");
  });
  if (panelModel) panelModel.addEventListener("submit", function (e) {
    e.preventDefault();
    var vals = $$("select", panelModel).map(function (s) { return s.value; }).filter(function (v) { return v; });
    var msg = "Bonjour ZOT AUTO, je cherche des pièces pour : " + (vals.join(" · ") || "mon véhicule") + ".";
    window.open(wa(msg), "_blank", "noopener");
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
  $$(".chip").forEach(function (chip) {
    chip.addEventListener("click", function () {
      $$(".chip").forEach(function (c) { c.classList.remove("is-active"); });
      chip.classList.add("is-active");
      var f = chip.dataset.filter;
      $$(".product").forEach(function (p) {
        p.classList.toggle("is-hidden", f !== "all" && p.dataset.cat !== f);
      });
    });
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
      cartCount.textContent = n;
      cartCount.hidden = n === 0;
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
        '<div class="cart-item__info">' +
          '<div class="cart-item__brand">' + item.brand + '</div>' +
          '<div class="cart-item__name">' + item.name + '</div>' +
          '<div class="cart-item__price">' + euro(item.price) + '</div>' +
        '</div>' +
        '<div class="cart-item__qty">' +
          '<button type="button" data-dec="' + idx + '" aria-label="Moins">−</button>' +
          '<span>' + item.qty + '</span>' +
          '<button type="button" data-inc="' + idx + '" aria-label="Plus">+</button>' +
        '</div>';
      cartItems.appendChild(row);
    });
    if (cartTotal) cartTotal.textContent = euro(total());
    if (cartCheckout) {
      var lines = cart.map(function (i) { return "• " + i.qty + "× " + i.name + " (" + i.brand + ") — " + euro(i.price * i.qty); });
      var msg = "Bonjour ZOT AUTO, je souhaite commander :\n" + lines.join("\n") + "\n\nTotal : " + euro(total()) + "\nMerci de me confirmer stock, prix et livraison (974).";
      cartCheckout.href = wa(msg);
    }
    save();
  }

  function addToCart(p) {
    var found = cart.filter(function (i) { return i.name === p.name; })[0];
    if (found) found.qty += 1; else cart.push({ name: p.name, brand: p.brand, price: p.price, qty: 1 });
    renderCart();
    showToast("Ajouté au panier");
  }

  $$("[data-add]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var card = btn.closest(".product");
      addToCart({ name: card.dataset.name, brand: card.dataset.brand, price: parseFloat(card.dataset.price) });
    });
  });

  // delegation pour +/-
  if (cartItems) cartItems.addEventListener("click", function (e) {
    var inc = e.target.getAttribute("data-inc"), dec = e.target.getAttribute("data-dec");
    if (inc !== null) { cart[+inc].qty += 1; renderCart(); }
    else if (dec !== null) { cart[+dec].qty -= 1; if (cart[+dec].qty <= 0) cart.splice(+dec, 1); renderCart(); }
  });

  // ouverture / fermeture du drawer
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
    $$(".cats, .products, .reviews").forEach(function (grid) {
      Array.prototype.forEach.call(grid.children, function (child, i) {
        child.style.transitionDelay = (i % 4) * 0.07 + "s";
      });
    });
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { entry.target.classList.add("in"); obs.unobserve(entry.target); }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -6% 0px" });
    reveals.forEach(function (el) { io.observe(el); });
  }
})();
