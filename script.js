/* =========================================================
   ZOT AUTO — boutique · interactions
   Les produits & services sont générés depuis data/catalogue.js
   ========================================================= */
(function () {
  "use strict";

  var WA = "262693057012";
  var DATA = window.ZOTAUTO || { products: [], services: [] };
  var PRODUCTS = (DATA.products || []).slice(); // copie de travail
  var SERVICES = (DATA.services || []).slice();
  var PAGE_SIZE = 8; // nb de produits affichés par lot ("Voir plus")
  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var wa = function (text) { return "https://wa.me/" + WA + "?text=" + encodeURIComponent(text); };
  var euro = function (n) { return Number(n).toFixed(2).replace(".", ",") + " €"; };
  var esc = function (s) { return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); };
  var stars = function (n) { n = Math.max(0, Math.min(5, Math.round(n || 0))); return "★★★★★".slice(0, n) + "☆☆☆☆☆".slice(0, 5 - n); };
  // normalisation pour la recherche (insensible à la casse et aux accents)
  var norm = function (s) {
    s = String(s == null ? "" : s).toLowerCase();
    if (s.normalize) s = s.normalize("NFD").replace(/[̀-ͯ]/g, "");
    return s;
  };

  /* =========================================================
     Cartes produits / services (génération HTML)
     ========================================================= */
  function badgesHtml(p) {
    // empilement des badges : promo / libre + "Nouveau" + "Coup de cœur"
    var html = "";
    var promo = /%|^-/.test(p.badge || "");
    if (p.badge) html += '<span class="pbadge' + (promo ? " pbadge--promo" : "") + '">' + esc(p.badge) + "</span>";
    if (p.nouveau) html += '<span class="pbadge pbadge--new">Nouveau</span>';
    if (p.featured) html += '<span class="pbadge pbadge--heart" title="Coup de cœur" aria-label="Coup de cœur">❤</span>';
    return html ? '<span class="product__badges">' + html + "</span>" : "";
  }

  function productCard(p) {
    var media = p.contain ? "product__media product__media--logo" : "product__media";
    var was = (p.oldPrice != null && p.oldPrice !== "") ? '<span class="was">' + euro(p.oldPrice) + "</span>" : "";
    var stockLabel = p.stock === "soon" ? "Sur commande" : "En stock";
    var ref = p.reference ? '<span class="product__ref">Réf. ' + esc(p.reference) + "</span>" : "";
    return (
      '<article class="product reveal' + (p.featured ? " is-featured" : "") + '" data-id="' + esc(p.id) + '"' +
        ' data-cat="' + esc(p.category) + '" data-name="' + esc(p.name) + '" data-price="' + esc(p.price) + '" data-brand="' + esc(p.brand) + '">' +
        '<button class="product__open" type="button" aria-label="Aperçu rapide : ' + esc(p.name) + '"></button>' +
        '<div class="' + media + '">' + badgesHtml(p) +
          '<img src="' + esc(p.image) + '" alt="' + esc(p.brand + " " + p.name) + '" loading="lazy" />' +
        "</div>" +
        '<div class="product__body">' +
          '<span class="product__brand">' + esc(p.brand) + "</span>" +
          '<h3 class="product__name">' + esc(p.name) + "</h3>" +
          ref +
          '<div class="product__rating"><span class="stars">' + stars(p.rating) + "</span><small>(" + (p.reviews || 0) + ")</small></div>" +
          '<div class="product__stock ' + (p.stock === "soon" ? "soon" : "in") + '">' + stockLabel + "</div>" +
          '<div class="product__foot">' +
            '<div class="product__price"><span class="now">' + euro(p.price) + "</span>" + was + "</div>" +
            '<button class="btn-add" data-add aria-label="Ajouter ' + esc(p.name) + ' au panier">Ajouter</button>' +
          "</div>" +
        "</div>" +
      "</article>"
    );
  }

  // Carte "coup de cœur" (rangée de mise en avant, format compact horizontal)
  function featuredCard(p) {
    var was = (p.oldPrice != null && p.oldPrice !== "") ? '<span class="was">' + euro(p.oldPrice) + "</span>" : "";
    return (
      '<article class="fcard reveal" data-id="' + esc(p.id) + '">' +
        '<button class="fcard__open" type="button" aria-label="Aperçu rapide : ' + esc(p.name) + '"></button>' +
        '<span class="fcard__heart" aria-hidden="true">❤</span>' +
        '<div class="fcard__media' + (p.contain ? " fcard__media--logo" : "") + '">' +
          (p.nouveau ? '<span class="pbadge pbadge--new">Nouveau</span>' : "") +
          '<img src="' + esc(p.image) + '" alt="' + esc(p.brand + " " + p.name) + '" loading="lazy" />' +
        "</div>" +
        '<div class="fcard__body">' +
          '<span class="fcard__brand">' + esc(p.brand) + "</span>" +
          '<h3 class="fcard__name">' + esc(p.name) + "</h3>" +
          '<div class="product__rating"><span class="stars">' + stars(p.rating) + "</span><small>(" + (p.reviews || 0) + ")</small></div>" +
          '<div class="fcard__foot">' +
            '<div class="product__price"><span class="now">' + euro(p.price) + "</span>" + was + "</div>" +
            '<button class="btn-add" data-add-id="' + esc(p.id) + '" aria-label="Ajouter ' + esc(p.name) + ' au panier">Ajouter</button>' +
          "</div>" +
        "</div>" +
      "</article>"
    );
  }

  function serviceCard(s) {
    var badge = s.badge ? '<span class="service__badge">' + esc(s.badge) + "</span>" : "";
    var star = s.featured ? '<span class="service__star" title="Service phare" aria-label="Service phare">★</span>' : "";
    var price = s.price ? '<span class="service__price">' + esc(s.price) + "</span>" : "<span></span>";
    var link = wa("Bonjour ZOT AUTO, je suis intéressé(e) par le service : " + s.name + ".");
    return (
      '<article class="service reveal' + (s.featured ? " is-featured" : "") + '">' +
        '<div class="service__top"><span class="service__ic">' + esc(s.icon || "🔧") + "</span>" + star + badge + "</div>" +
        '<h3 class="service__name">' + esc(s.name) + "</h3>" +
        '<p class="service__desc">' + esc(s.description) + "</p>" +
        '<div class="service__foot">' + price +
          '<a class="service__btn" href="' + link + '" target="_blank" rel="noopener">Demander →</a>' +
        "</div>" +
      "</article>"
    );
  }

  /* =========================================================
     Coups de cœur (produits featured) + Services
     ========================================================= */
  var featuredWrap = $("#featuredGrid");
  var featuredSection = $("#coupsdecoeur");
  var featuredList = PRODUCTS.filter(function (p) { return p.featured; });
  if (featuredWrap && featuredList.length) {
    featuredWrap.innerHTML = featuredList.map(featuredCard).join("");
    if (featuredSection) featuredSection.hidden = false;
  }

  var servicesWrap = $("#servicesGrid");
  if (servicesWrap) {
    // services "featured" affichés en premier (ordre stable conservé pour le reste)
    var orderedServices = SERVICES.filter(function (s) { return s.featured; })
      .concat(SERVICES.filter(function (s) { return !s.featured; }));
    servicesWrap.innerHTML = orderedServices.map(serviceCard).join("");
  }

  /* =========================================================
     ÉTAT unique : recherche + filtre catégorie + tri + pagination
     ========================================================= */
  var productsWrap = $("#products");
  var productsEmpty = $("#productsEmpty");
  var productsMore = $("#productsMore");
  var moreBtn = $("#moreBtn");
  var resultsCount = $("#resultsCount");
  var emptyText = $("#emptyText");
  var emptyWa = $("#emptyWa");
  var sortSelect = $("#sortSelect");
  var searchTag = $("#searchTag");
  var searchTagText = $("#searchTagText");
  var chips = $$(".chip");

  var state = { query: "", filter: "all", sort: "relevance", shown: PAGE_SIZE };

  // ordre "pertinence/nouveautés" : nouveautés puis coups de cœur, ordre catalogue préservé ensuite
  var baseIndex = {};
  PRODUCTS.forEach(function (p, i) { baseIndex[p.id] = i; });
  function relevanceScore(p) { return (p.nouveau ? 0 : 1) * 100 + (p.featured ? 0 : 1) * 10; }

  function computeList() {
    var q = norm(state.query);
    var list = PRODUCTS.filter(function (p) {
      if (state.filter !== "all" && p.category !== state.filter) return false;
      if (!q) return true;
      var hay = norm([p.name, p.brand, p.reference, p.description, p.category].join(" "));
      return hay.indexOf(q) !== -1;
    });
    list.sort(function (a, b) {
      switch (state.sort) {
        case "price-asc": return a.price - b.price;
        case "price-desc": return b.price - a.price;
        case "name-asc": return a.name.localeCompare(b.name, "fr", { sensitivity: "base" });
        default: // relevance / nouveautés
          var d = relevanceScore(a) - relevanceScore(b);
          return d !== 0 ? d : baseIndex[a.id] - baseIndex[b.id];
      }
    });
    return list;
  }

  function renderProducts() {
    if (!productsWrap) return;
    var list = computeList();
    var total = list.length;
    var visible = list.slice(0, state.shown);

    productsWrap.innerHTML = visible.map(productCard).join("");
    applyRevealTo(productsWrap);

    // compteur de résultats
    if (resultsCount) {
      if (total === 0) resultsCount.textContent = "";
      else resultsCount.textContent = total + (total > 1 ? " produits" : " produit") +
        (state.shown < total ? " · " + visible.length + " affichés" : "");
    }

    // pastille de recherche active
    if (searchTag) {
      var has = !!state.query.trim();
      searchTag.hidden = !has;
      if (has && searchTagText) searchTagText.textContent = state.query.trim();
    }

    // état vide + repli WhatsApp
    if (productsEmpty) {
      productsEmpty.hidden = total > 0;
      if (total === 0) {
        if (emptyText) emptyText.textContent = state.query.trim()
          ? 'Aucun produit ne correspond à « ' + state.query.trim() + ' ».'
          : "Aucun produit dans ce rayon pour le moment.";
        if (emptyWa) emptyWa.href = wa("Bonjour ZOT AUTO, je recherche : " + (state.query.trim() || "un produit") + ". Pouvez-vous m'aider ?");
      }
    }

    // bouton "Voir plus"
    if (productsMore) productsMore.hidden = state.shown >= total;
  }

  // ré-applique l'animation reveal aux cartes nouvellement injectées
  function applyRevealTo(grid) {
    var kids = Array.prototype.slice.call(grid.children);
    if (prefersReduced || !("IntersectionObserver" in window)) {
      kids.forEach(function (el) { el.classList.add("in"); });
      return;
    }
    kids.forEach(function (el, i) {
      el.style.transitionDelay = (i % 4) * 0.06 + "s";
      io.observe(el);
    });
  }

  // change l'état puis re-render (remet la pagination à zéro)
  function update(partial, resetPage) {
    for (var k in partial) state[k] = partial[k];
    if (resetPage !== false) state.shown = PAGE_SIZE;
    syncChips();
    renderProducts();
  }

  function syncChips() {
    chips.forEach(function (c) { c.classList.toggle("is-active", c.dataset.filter === state.filter); });
  }

  function scrollToProducts() {
    var target = $("#bestsellers");
    if (target) target.scrollIntoView({ behavior: prefersReduced ? "auto" : "smooth", block: "start" });
  }

  /* =========================================================
     IntersectionObserver (reveal) — initialisé avant le 1er render
     ========================================================= */
  var io = ("IntersectionObserver" in window) ? new IntersectionObserver(function (entries, obs) {
    entries.forEach(function (entry) { if (entry.isIntersecting) { entry.target.classList.add("in"); obs.unobserve(entry.target); } });
  }, { threshold: 0.12, rootMargin: "0px 0px -6% 0px" }) : null;

  // décalage d'animation sur les grilles statiques
  if (!prefersReduced && io) {
    $$(".cats, .featured, .services, .reviews").forEach(function (grid) {
      Array.prototype.forEach.call(grid.children, function (child, i) { child.style.transitionDelay = (i % 4) * 0.07 + "s"; });
    });
  }
  // observe les .reveal statiques (hors #products, géré à part)
  $$(".reveal").forEach(function (el) {
    if (el.closest("#products")) return;
    if (prefersReduced || !io) el.classList.add("in"); else io.observe(el);
  });

  // premier rendu de la grille produits
  renderProducts();

  /* ---- Filtres : chips ---- */
  chips.forEach(function (chip) {
    chip.addEventListener("click", function () { update({ filter: chip.dataset.filter }); });
  });

  /* ---- Filtres : cartes "rayons" + liens catnav (data-gocat) ---- */
  $$("[data-gocat]").forEach(function (el) {
    el.addEventListener("click", function () {
      update({ filter: el.dataset.gocat, query: "" }); // un clic catégorie efface la recherche
      if (searchInput) searchInput.value = "";
      scrollToProducts();
    });
  });

  /* ---- Tri ---- */
  if (sortSelect) sortSelect.addEventListener("change", function () { update({ sort: sortSelect.value }); });

  /* ---- "Voir plus" ---- */
  if (moreBtn) moreBtn.addEventListener("click", function () {
    state.shown += PAGE_SIZE;
    renderProducts();
  });

  /* =========================================================
     Recherche LIVE (filtre la grille au lieu d'ouvrir WhatsApp)
     ========================================================= */
  var searchForm = $("#searchForm"), searchInput = $("#searchInput");
  var searchDebounce;
  function runSearch(scroll) {
    var q = (searchInput.value || "");
    update({ query: q });
    if (scroll && q.trim()) scrollToProducts();
  }
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(function () { runSearch(false); }, 120);
    });
  }
  if (searchForm) searchForm.addEventListener("submit", function (e) {
    e.preventDefault();
    clearTimeout(searchDebounce);
    runSearch(true); // sur "Entrée" : on filtre et on défile vers les produits
  });
  // effacer la recherche via la pastille
  if (searchTag) searchTag.addEventListener("click", function () {
    if (searchInput) searchInput.value = "";
    update({ query: "" });
    if (searchInput) searchInput.focus();
  });

  /* =========================================================
     Aperçu rapide (modale)
     ========================================================= */
  var qv = $("#quickView");
  var qvEls = {
    img: $("#qvImg"), badges: $("#qvBadges"), brand: $("#qvBrand"), name: $("#qvName"),
    ref: $("#qvRef"), starsEl: $("#qvStars"), reviews: $("#qvReviews"), desc: $("#qvDesc"),
    stock: $("#qvStock"), now: $("#qvNow"), was: $("#qvWas"), add: $("#qvAdd"), wa: $("#qvWa")
  };
  var qvCurrent = null; // produit en cours d'aperçu
  var qvLastFocus = null; // élément à re-focus à la fermeture

  function byId(id) { for (var i = 0; i < PRODUCTS.length; i++) if (PRODUCTS[i].id === id) return PRODUCTS[i]; return null; }

  function openQuickView(p) {
    if (!qv || !p) return;
    qvCurrent = p;
    qvLastFocus = document.activeElement;

    qvEls.img.src = p.image || "";
    qvEls.img.alt = (p.brand || "") + " " + (p.name || "");
    qvEls.img.parentNode.classList.toggle("qv__media--logo", !!p.contain);
    qvEls.badges.innerHTML = badgesHtml(p);
    qvEls.brand.textContent = p.brand || "";
    qvEls.name.textContent = p.name || "";
    if (p.reference) { qvEls.ref.textContent = "Réf. " + p.reference; qvEls.ref.hidden = false; }
    else qvEls.ref.hidden = true;
    qvEls.starsEl.textContent = stars(p.rating);
    qvEls.reviews.textContent = "(" + (p.reviews || 0) + " avis)";
    qvEls.desc.textContent = p.description || "";
    qvEls.stock.className = "qv__stock " + (p.stock === "soon" ? "soon" : "in");
    qvEls.stock.textContent = p.stock === "soon" ? "Sur commande" : "En stock";
    qvEls.now.textContent = euro(p.price);
    if (p.oldPrice != null && p.oldPrice !== "") { qvEls.was.textContent = euro(p.oldPrice); qvEls.was.hidden = false; }
    else qvEls.was.hidden = true;
    qvEls.wa.href = wa("Bonjour ZOT AUTO, je suis intéressé(e) par : " + p.name + (p.reference ? " (réf. " + p.reference + ")" : "") + " à " + euro(p.price) + ".");

    qv.classList.add("open");
    qv.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    // focus sur le bouton de fermeture pour l'accessibilité
    var closeBtn = qv.querySelector(".qv__close");
    if (closeBtn) closeBtn.focus();
  }

  function closeQuickView() {
    if (!qv) return;
    qv.classList.remove("open");
    qv.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    qvCurrent = null;
    if (qvLastFocus && qvLastFocus.focus) qvLastFocus.focus();
  }

  if (qv) {
    $$("[data-qv-close]", qv).forEach(function (el) { el.addEventListener("click", closeQuickView); });
    // bouton "Ajouter" dans la modale
    if (qvEls.add) qvEls.add.addEventListener("click", function () {
      if (qvCurrent) { addToCart({ name: qvCurrent.name, brand: qvCurrent.brand, price: qvCurrent.price }); }
    });
    // piège à focus simple (Tab reste dans la modale)
    qv.addEventListener("keydown", function (e) {
      if (e.key !== "Tab" || !qv.classList.contains("open")) return;
      var f = $$('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])', qv)
        .filter(function (el) { return el.offsetParent !== null; });
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
  }

  // ouverture de la modale : clic sur la carte produit (hors bouton "Ajouter")
  if (productsWrap) productsWrap.addEventListener("click", function (e) {
    if (e.target.closest("[data-add]")) return; // géré par le panier
    var card = e.target.closest(".product");
    if (!card) return;
    var p = byId(card.dataset.id);
    if (p) openQuickView(p);
  });
  // ouverture depuis les cartes "coup de cœur"
  if (featuredWrap) featuredWrap.addEventListener("click", function (e) {
    if (e.target.closest("[data-add-id]")) return;
    var card = e.target.closest(".fcard");
    if (!card) return;
    var p = byId(card.dataset.id);
    if (p) openQuickView(p);
  });

  /* =========================================================
     Année / header sticky / menu mobile / sélecteur véhicule
     ========================================================= */
  var y = $("#year"); if (y) y.textContent = new Date().getFullYear();

  var header = $(".header");
  if (header) {
    var onScroll = function () { header.classList.toggle("is-stuck", window.scrollY > 8); };
    onScroll(); window.addEventListener("scroll", onScroll, { passive: true });
  }

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

  $$(".finder__tab").forEach(function (tab) {
    tab.addEventListener("click", function () {
      var name = tab.dataset.tab;
      $$(".finder__tab").forEach(function (t) { var a = t === tab; t.classList.toggle("is-active", a); t.setAttribute("aria-selected", String(a)); });
      $$(".finder__panel").forEach(function (p) { p.hidden = p.dataset.panel !== name; });
    });
  });

  var plate = $("#plateInput");
  if (plate) plate.addEventListener("input", function () {
    var v = plate.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
    var out = v.slice(0, 2);
    if (v.length > 2) out += "-" + v.slice(2, 5);
    if (v.length > 5) out += "-" + v.slice(5, 7);
    plate.value = out;
  });

  // La recherche véhicule (plaque / VIN / marque-modèle + carte résultat)
  // est désormais gérée par vehicle-lookup.js (chargé après script.js).

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

  // délégation : boutons "Ajouter" sur les cartes produits (générées dynamiquement)
  if (productsWrap) productsWrap.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-add]"); if (!btn) return;
    var card = btn.closest(".product");
    addToCart({ name: card.dataset.name, brand: card.dataset.brand, price: parseFloat(card.dataset.price) });
  });
  // boutons "Ajouter" sur les cartes "coup de cœur" (résolus par id)
  if (featuredWrap) featuredWrap.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-add-id]"); if (!btn) return;
    var p = byId(btn.getAttribute("data-add-id"));
    if (p) addToCart({ name: p.name, brand: p.brand, price: p.price });
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

  // Échap : ferme la modale en priorité, sinon le panier
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    if (qv && qv.classList.contains("open")) { closeQuickView(); return; }
    closeCart();
  });
  renderCart();

  // ── Scroll-to-top ──────────────────────────────────────────────
  (function () {
    var btn = document.getElementById("scrollTop");
    if (!btn) return;
    window.addEventListener("scroll", function () {
      btn.classList.toggle("is-visible", window.scrollY > 400);
    }, { passive: true });
    btn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }());

  // ── Badge "Populaire" sur les services featured ─────────────────
  (function () {
    var grid = document.getElementById("servicesGrid");
    if (!grid) return;
    // Le grid est rempli après DOMContentLoaded — on observe avec MutationObserver
    var mo = new MutationObserver(function () {
      grid.querySelectorAll(".service.is-featured").forEach(function (card) {
        card.setAttribute("data-popular", "");
      });
    });
    mo.observe(grid, { childList: true });
  }());

  // ── Social proof notifications ──
  (function () {
    var notifications = [
      { emoji: "🌺", name: "Matthieu", ville: "Saint-Pierre", produit: "Plaquettes Valeo" },
      { emoji: "🔧", name: "Cindy", ville: "Saint-Denis", produit: "Filtre à huile Mann" },
      { emoji: "🌺", name: "Dorian", ville: "Le Tampon", produit: "Kit distribution Gates" },
      { emoji: "🔧", name: "Priscilla", ville: "Saint-Paul", produit: "Amortisseurs Monroe" },
      { emoji: "🌺", name: "Kévin", ville: "Saint-André", produit: "Batterie Varta 60Ah" },
      { emoji: "🔧", name: "Nathalie", ville: "Sainte-Marie", produit: "Courroie accessoires" },
      { emoji: "🌺", name: "Damien", ville: "Saint-Leu", produit: "Disques de frein TRW" },
      { emoji: "🔧", name: "Sandrine", ville: "Saint-Louis", produit: "Bougies NGK" }
    ];
    var el = document.getElementById("socialProof");
    var textEl = document.getElementById("socialProofText");
    var closeBtn = document.getElementById("socialProofClose");
    if (!el || !textEl) return;
    var lastIndex = -1;
    var hideTimer = null;
    var cycleTimer = null;
    function pickNext() {
      var idx;
      do { idx = Math.floor(Math.random() * notifications.length); } while (idx === lastIndex);
      lastIndex = idx;
      return notifications[idx];
    }
    function show() {
      var n = pickNext();
      textEl.innerHTML = n.emoji + " <strong>" + n.name + " (" + n.ville + ")</strong> vient de commander <em>" + n.produit + "</em>";
      el.classList.add("is-visible");
      hideTimer = setTimeout(hide, 5000);
    }
    function hide() {
      clearTimeout(hideTimer);
      el.classList.remove("is-visible");
    }
    function scheduleNext() {
      var delay = 12000 + Math.random() * 6000;
      cycleTimer = setTimeout(function () { show(); scheduleNext(); }, delay);
    }
    closeBtn.addEventListener("click", function () { hide(); clearTimeout(cycleTimer); scheduleNext(); });
    setTimeout(function () { show(); scheduleNext(); }, 6000);
  }());

  // ── Countdown promo J+7 ──
  (function () {
    var TARGET_KEY = 'zotauto_promo_end';
    var MS_7_DAYS  = 7 * 24 * 60 * 60 * 1000;
    function getTarget() {
      var stored = localStorage.getItem(TARGET_KEY);
      if (stored) { var ts = parseInt(stored, 10); if (!isNaN(ts) && ts > Date.now()) return ts; }
      var ts = Date.now() + MS_7_DAYS;
      try { localStorage.setItem(TARGET_KEY, String(ts)); } catch (e) {}
      return ts;
    }
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function tick(target) {
      var diff = target - Date.now();
      if (diff <= 0) {
        document.getElementById('cdDays').textContent = '00';
        document.getElementById('cdHours').textContent = '00';
        document.getElementById('cdMins').textContent = '00';
        return;
      }
      var days  = Math.floor(diff / (1000 * 60 * 60 * 24));
      var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var mins  = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      document.getElementById('cdDays').textContent  = pad(days);
      document.getElementById('cdHours').textContent = pad(hours);
      document.getElementById('cdMins').textContent  = pad(mins);
    }
    var target = getTarget();
    tick(target);
    setInterval(function () { tick(target); }, 60000);
  }());

  // ── Mob-bar : Chercher + Panier + overlay ──
  (function () {
    var mobSearch  = document.getElementById("mobSearch");
    var mobCart    = document.getElementById("mobCart");
    var cartBtn    = document.getElementById("cartOpen");
    var searchForm = document.getElementById("searchForm");
    if (mobSearch && searchForm) {
      mobSearch.addEventListener("click", function () {
        searchForm.scrollIntoView({ behavior: "smooth", block: "center" });
        var input = searchForm.querySelector("input[type='search'], input[type='text'], input");
        if (input) { setTimeout(function () { input.focus(); }, 350); }
      });
    }
    if (mobCart && cartBtn) { mobCart.addEventListener("click", function () { cartBtn.click(); }); }
    // Overlay menu
    var overlay = document.createElement("div");
    overlay.className = "menu-overlay";
    overlay.id = "menuOverlay";
    document.body.appendChild(overlay);
    var burger = document.getElementById("burger");
    var catnav = document.getElementById("catnav");
    function syncBodyClass() {
      if (catnav && catnav.classList.contains("is-open")) {
        document.body.classList.add("menu-open");
      } else {
        document.body.classList.remove("menu-open");
      }
    }
    overlay.addEventListener("click", function () {
      if (catnav) { catnav.classList.remove("is-open"); catnav.style.display = ""; }
      if (burger) { burger.setAttribute("aria-expanded", "false"); }
      document.body.classList.remove("menu-open");
    });
    if (catnav) {
      var moMenu = new MutationObserver(syncBodyClass);
      moMenu.observe(catnav, { attributes: true, attributeFilter: ["class"] });
    }
  }());

})();
