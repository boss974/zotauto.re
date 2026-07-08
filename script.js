/* =========================================================
   ZOT AUTO — boutique · interactions
   Les produits & services sont générés depuis data/catalogue.js
   ========================================================= */
(function () {
  "use strict";

  var WA = "262693057012";
  var DATA = window.ZOTAUTO || { products: [], services: [] };
  var PRODUCTS = (DATA.products || []).slice(); // copie de travail
  // Services : ceux du catalogue, ou repli sur les services par défaut si la
  // synchro Axonaut a écrasé le catalogue sans services.
  var DEFAULT_SERVICES = [
    { id: "montage", name: "Montage & pose d'accessoires", icon: "🔧", description: "Pose de vos accessoires, balais, éclairage et petites pièces par nos soins.", price: "Sur devis", badge: "", featured: true },
    { id: "vidange", name: "Vidange & entretien", icon: "🛢️", description: "Vidange avec huile Euroatlantic et conseils adaptés à votre véhicule.", price: "Dès 39 €", badge: "Populaire", featured: false },
    { id: "detailing", name: "Detailing / lavage premium", icon: "✨", description: "Nettoyage intérieur & extérieur avec les produits pro Koch-Chemie.", price: "Sur devis", badge: "", featured: false },
    { id: "recherche-piece", name: "Recherche de pièce", icon: "🔎", description: "Vous cherchez une référence précise ? Envoyez-nous votre carte grise, nous identifions la référence exacte.", price: "Gratuit", badge: "", featured: false }
  ];
  var SERVICES = (DATA.services && DATA.services.length) ? DATA.services.slice() : DEFAULT_SERVICES.slice();
  var PAGE_SIZE = 8; // nb de produits affichés par lot ("Voir plus")
  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var wa = function (text) { return "https://wa.me/" + WA + "?text=" + encodeURIComponent(text); };
  var euro = function (n) { return Number(n).toFixed(2).replace(".", ",") + " €"; };
  // Prix affiché : « Prix sur demande » si 0 ou vide (produits Axonaut sans tarif public).
  var priceLabel = function (p) {
    var v = Number(p);
    return (!v || isNaN(v)) ? "Prix sur demande" : euro(v);
  };
  // Visuel produit : la vraie photo si elle existe, sinon un visuel généré
  // (couleur par catégorie + icône + nom) — pour que les produits importés
  // sans image restent présentables. Retourne une valeur prête pour src="".
  var PH_LOGO = "assets/brands/logo-zotauto.png";
  var CAT_VISUAL = {
    detailing:   { c: "#7c3aed", c2: "#a274f0", ic: "✨", lbl: "Detailing" },
    outillage:   { c: "#ea580c", c2: "#f59e42", ic: "🔧", lbl: "Outillage" },
    huiles:      { c: "#16a34a", c2: "#4ade80", ic: "🛢️", lbl: "Huiles" },
    accessoires: { c: "#2a52e0", c2: "#6b8cff", ic: "🚗", lbl: "Accessoires" }
  };
  var phImg = function (p) {
    var img = p && p.image ? String(p.image) : "";
    // Placeholder = vide, logo, ou visuel SVG généré (régénéré ici en version premium).
    // Une vraie photo (png/jpg/webp, ou dossier assets/products) est respectée.
    var isPlaceholder = !img
      || img.indexOf(PH_LOGO) !== -1
      || (img.indexOf("assets/generated/") !== -1 && /\.svg(\?|$)/i.test(img));
    if (!isPlaceholder) { return esc(img); } // vraie photo
    var cv = CAT_VISUAL[p && p.category] || { c: "#2a52e0", c2: "#6b8cff", ic: "📦", lbl: "Produit" };
    var name = String((p && p.name) || "").slice(0, 42);
    var brand = String((p && p.brand) || "").slice(0, 22);
    var gid = "g" + Math.abs((name.length * 31 + cv.c.charCodeAt(1)) % 9999);
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">' +
      '<defs><linearGradient id="' + gid + '" x1="0" y1="0" x2="0" y2="1">' +
        '<stop offset="0" stop-color="' + cv.c2 + '"/><stop offset="1" stop-color="' + cv.c + '"/></linearGradient></defs>' +
      '<rect width="400" height="300" fill="url(#' + gid + ')"/>' +
      // filigrane ZOT AUTO en diagonale
      '<text x="200" y="170" font-size="52" fill="#fff" opacity="0.10" font-weight="bold" font-family="Arial,sans-serif" text-anchor="middle" transform="rotate(-20 200 170)">ZOT AUTO</text>' +
      // pastille catégorie (haut)
      '<text x="24" y="38" font-size="14" fill="#fff" opacity="0.9" font-weight="bold" font-family="Arial,sans-serif">' + esc(cv.lbl) + '</text>' +
      (brand ? '<text x="376" y="38" font-size="14" fill="#fff" opacity="0.85" font-family="Arial,sans-serif" text-anchor="end">' + esc(brand) + '</text>' : '') +
      // cadre blanc arrondi + icône (effet "photo")
      '<rect x="140" y="74" width="120" height="120" rx="22" fill="#ffffff" opacity="0.93"/>' +
      '<text x="200" y="140" font-size="66" text-anchor="middle" dominant-baseline="central">' + cv.ic + '</text>' +
      // barre nom (bas)
      '<rect x="0" y="242" width="400" height="58" fill="#0d1220" opacity="0.22"/>' +
      '<text x="200" y="270" font-size="18" fill="#fff" font-weight="600" font-family="Arial,sans-serif" text-anchor="middle">' + esc(name) + '</text>' +
      // tag site (coin bas-droit)
      '<text x="390" y="293" font-size="12" fill="#fff" opacity="0.85" font-weight="bold" font-family="Arial,sans-serif" text-anchor="end">zotauto.re</text>' +
      '</svg>';
    return "data:image/svg+xml," + encodeURIComponent(svg);
  };
  var esc = function (s) { return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); };
  var stars = function (n) { n = Math.max(0, Math.min(5, Math.round(n || 0))); return "★★★★★".slice(0, n) + "☆☆☆☆☆".slice(0, 5 - n); };
  // normalisation pour la recherche (insensible à la casse et aux accents)
  var norm = function (s) {
    s = String(s == null ? "" : s).toLowerCase();
    if (s.normalize) s = s.normalize("NFD").replace(/[̀-ͯ]/g, "");
    return s;
  };

  // Devine la catégorie d'un produit d'après son nom/référence/marque.
  // (Les imports Axonaut arrivent tous en "accessoires" → on les reclasse.)
  var CATS_VALID = { detailing: 1, outillage: 1, huiles: 1, accessoires: 1 };
  function guessCategory(p) {
    var t = norm((p.name || "") + " " + (p.reference || "") + " " + (p.brand || ""));
    if (/huil|lubrifi|graiss|antigel|antifreeze|coolant|adblue|additif|\d+w\d+|\bsae\b|bidon|liquide de frein|liquide refroid|degrippant|wd.?40|spray silicone/.test(t)) return "huiles";
    if (/nettoy|polish|\bcire\b|microfibr|shampo|lavage|chiffon|detail|koch|renov|brillant|lustr|decontamin|cera|\bwax\b|jante.*nettoy|plastique.*renov/.test(t)) return "detailing";
    if (/\bcle\b|\bcles\b|\bclef\b|outil|visseus|perceuse|\bpince\b|tournevis|\bcric\b|chariot|douille|marteau|\bscie\b|meuleus|cliquet|serre.?joint|etabli|\bfraise\b|\bforet\b|\bmeche\b|coffret.*outil|kit.*outil|extracteur|manometre|compresseur|multimetre/.test(t)) return "outillage";
    return "accessoires";
  }
  PRODUCTS.forEach(function (p) {
    if (!p.category || !CATS_VALID[p.category] || p.category === "accessoires") {
      p.category = guessCategory(p);
    }
    // Index de recherche pré-calculé (recherche instantanée même sur 1300+ produits).
    p._hay = norm([p.name, p.brand, p.reference, p.description, p.category].join(" "));
  });

  // Badge hero « +N références en stock » : toujours à jour avec le vrai catalogue
  // (arrondi en dessous à la centaine pour un affichage propre, ex. 1327 -> "+1300").
  (function () {
    var badge = document.getElementById("heroLiveBadge");
    if (!badge || !PRODUCTS.length) return;
    var rounded = Math.floor(PRODUCTS.length / 100) * 100;
    var label = rounded >= 100 ? rounded : PRODUCTS.length;
    var dot = badge.querySelector(".hero__live-badge__dot");
    badge.textContent = "";
    if (dot) badge.appendChild(dot);
    badge.appendChild(document.createTextNode(" 🌺 +" + label + " références en stock"));
  }());

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
          '<img src="' + phImg(p) + '" alt="' + esc(p.brand + " " + p.name) + '" loading="lazy" />' +
        "</div>" +
        '<div class="product__body">' +
          '<span class="product__brand">' + esc(p.brand) + "</span>" +
          '<h3 class="product__name">' + esc(p.name) + "</h3>" +
          ref +
          '<div class="product__rating"><span class="stars">' + stars(p.rating) + "</span><small>(" + (p.reviews || 0) + ")</small></div>" +
          '<div class="product__stock ' + (p.stock === "soon" ? "soon" : "in") + '">' + stockLabel + "</div>" +
          '<div class="product__foot">' +
            '<div class="product__price"><span class="now">' + priceLabel(p.price) + "</span>" + was + "</div>" +
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
          '<img src="' + phImg(p) + '" alt="' + esc(p.brand + " " + p.name) + '" loading="lazy" />' +
        "</div>" +
        '<div class="fcard__body">' +
          '<span class="fcard__brand">' + esc(p.brand) + "</span>" +
          '<h3 class="fcard__name">' + esc(p.name) + "</h3>" +
          '<div class="product__rating"><span class="stars">' + stars(p.rating) + "</span><small>(" + (p.reviews || 0) + ")</small></div>" +
          '<div class="fcard__foot">' +
            '<div class="product__price"><span class="now">' + priceLabel(p.price) + "</span>" + was + "</div>" +
            '<button class="btn-add" data-add-id="' + esc(p.id) + '" aria-label="Ajouter ' + esc(p.name) + ' au panier">Ajouter</button>' +
          "</div>" +
        "</div>" +
      "</article>"
    );
  }

  function serviceCard(s) {
    // Un seul indicateur "Populaire" (texte du badge) — pas d'étoile en plus, pour éviter la redondance.
    var badge = s.badge ? '<span class="service__badge">' + esc(s.badge) + "</span>" : "";
    var price = s.price ? '<span class="service__price">' + esc(s.price) + "</span>" : "<span></span>";
    var link = wa("Bonjour ZOT AUTO, je suis intéressé(e) par le service : " + s.name + ".");
    return (
      '<article class="service reveal' + (s.featured ? " is-featured" : "") + '">' +
        '<div class="service__top"><span class="service__ic">' + esc(s.icon || "🔧") + "</span>" + badge + "</div>" +
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
  // Si aucun produit n'est marqué "coup de cœur" (ex. après un import Axonaut),
  // on met en avant automatiquement les meilleurs : en stock, avec un prix, un par
  // catégorie d'abord pour la variété, jusqu'à 8. La section ne reste jamais vide.
  if (!featuredList.length) {
    var pool = PRODUCTS.filter(function (p) { return p.stock === "in" && Number(p.price) > 0; });
    if (pool.length < 4) {
      pool = PRODUCTS.filter(function (p) { return p.stock === "in"; });
    }
    if (pool.length < 4) { pool = PRODUCTS.slice(); }
    var seenCat = {}, pick = [], rest = [];
    pool.forEach(function (p) {
      if (!seenCat[p.category]) { seenCat[p.category] = 1; pick.push(p); }
      else { rest.push(p); }
    });
    featuredList = pick.concat(rest).slice(0, 8);
  }
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
      var hay = p._hay || norm([p.name, p.brand, p.reference, p.description, p.category].join(" "));
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
  // Les produits sont-ils déjà visibles à l'écran ? (évite un défilement inutile)
  function productsInView() {
    var sec = document.getElementById("bestsellers");
    if (!sec) return true;
    var r = sec.getBoundingClientRect();
    return r.top < (window.innerHeight * 0.7) && r.bottom > 0;
  }
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(function () {
        runSearch(false);
        // Dès qu'on tape, on amène doucement les résultats à l'écran s'ils sont plus bas.
        if (searchInput.value.trim() && !productsInView()) scrollToProducts();
      }, 140);
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
    qvEls.wa.href = wa("Bonjour ZOT AUTO, je suis intéressé(e) par :\n" +
      "• " + (p.brand ? p.brand + " " : "") + p.name +
      (p.reference ? "\n• Réf. : " + p.reference : "") +
      "\n• Prix : " + euro(p.price) +
      "\n• Dispo : " + (p.stock === "soon" ? "sur commande" : "en stock") +
      "\n\nMerci de me confirmer disponibilité et livraison (974).");

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
  function setDrawer(open) {
    if (!catnav) return;
    catnav.classList.toggle("is-open", open);
    catnav.style.display = "";
    if (burger) burger.setAttribute("aria-expanded", String(open));
    var mobRayons = $("#mobRayons");
    if (mobRayons) mobRayons.setAttribute("aria-expanded", String(open));
    document.body.classList.toggle("menu-open", open);
  }
  // exposé pour les autres blocs (mob-bar « Rayons »)
  window.__zotToggleDrawer = function () { setDrawer(!(catnav && catnav.classList.contains("is-open"))); };
  if (burger && catnav) {
    burger.addEventListener("click", function () { setDrawer(!catnav.classList.contains("is-open")); });
    $$("#catnav a").forEach(function (a) {
      a.addEventListener("click", function () { setDrawer(false); });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && catnav.classList.contains("is-open")) setDrawer(false);
    });
  }

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
    var mobCartCount = $("#mobCartCount");
    if (mobCartCount) { mobCartCount.textContent = n; mobCartCount.hidden = n === 0; }
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
      var lines = cart.map(function (i) {
        var ref = "";
        for (var k = 0; k < PRODUCTS.length; k++) { if (PRODUCTS[k].name === i.name && PRODUCTS[k].reference) { ref = " [réf. " + PRODUCTS[k].reference + "]"; break; } }
        return "• " + i.qty + "× " + i.name + " (" + i.brand + ")" + ref + " — " + euro(i.price * i.qty);
      });
      cartCheckout.href = wa("Bonjour ZOT AUTO, je souhaite commander :\n" + lines.join("\n") + "\n\nTotal : " + euro(total()) + "\nMerci de me confirmer disponibilité, prix et livraison (974).");
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

  // (Le badge "Populaire" est géré directement dans serviceCard() via s.badge —
  // pas de 2e indicateur superposé, pour éviter la redondance visuelle.)

  // ── Compteurs animés stats ──
  (function () {
    var grid = document.querySelector('.stats__grid');
    if (!grid || !('IntersectionObserver' in window)) return;
    var DURATION = 1500;
    function easeOut(t) { return 1 - Math.pow(1 - t, 3); }
    function formatFr(value, decimals) {
      if (decimals > 0) return value.toFixed(decimals).replace('.', ',');
      return String(Math.round(value));
    }
    function animateCounter(el) {
      if (!el) return;
      var textNode = null;
      for (var i = 0; i < el.childNodes.length; i++) {
        var node = el.childNodes[i];
        if (node.nodeType === 3 && node.nodeValue.trim() !== '') { textNode = node; break; }
      }
      if (!textNode) return;
      var raw = textNode.nodeValue.trim();
      var decimals = raw.indexOf(',') !== -1 ? raw.split(',')[1].length : 0;
      var target = parseFloat(raw.replace(',', '.'));
      if (isNaN(target)) return;
      var start = null;
      function step(ts) {
        if (start === null) start = ts;
        var progress = Math.min((ts - start) / DURATION, 1);
        textNode.nodeValue = formatFr(target * easeOut(progress), decimals);
        if (progress < 1) { requestAnimationFrame(step); } else { textNode.nodeValue = raw; }
      }
      textNode.nodeValue = formatFr(0, decimals);
      requestAnimationFrame(step);
    }
    var observer = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        obs.unobserve(entry.target);
        entry.target.querySelectorAll('.stat__n').forEach(animateCounter);
      });
    }, { threshold: 0.3 });
    observer.observe(grid);
  }());

  // ── Cookie banner RGPD ──
  (function () {
    var KEY = 'cookie_consent';
    var banner = document.getElementById('cookieBanner');
    if (!banner) return;
    var consent = null;
    try { consent = localStorage.getItem(KEY); } catch (e) {}
    if (consent === '1') { banner.remove(); return; }
    window.addEventListener('load', function () {
      setTimeout(function () { banner.classList.add('is-visible'); }, 2000);
    });
    var btn = document.getElementById('cookieBannerBtn');
    if (btn) btn.addEventListener('click', function () {
      try { localStorage.setItem(KEY, '1'); } catch (e) {}
      banner.classList.remove('is-visible');
      banner.addEventListener('transitionend', function () { banner.remove(); }, { once: true });
    });
  }());

  // ── Statut boutique ouvert/fermé (heure Réunion) ──
  (function () {
    var SCHEDULE = { 0: null, 1: [510,1050], 2: [510,1050], 3: [510,1050], 4: [510,1050], 5: [510,1050], 6: [510,750] };
    var DAY_NAMES = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
    function getReunionNow() {
      try {
        var str = new Date().toLocaleString('fr-FR', { timeZone: 'Indian/Reunion' });
        var m = str.match(/(\d{2})\/(\d{2})\/(\d{4})\D+(\d{2}):(\d{2})/);
        if (!m) return null;
        var d = new Date(+m[3], +m[2]-1, +m[1], +m[4], +m[5]);
        return { day: d.getDay(), minutes: +m[4]*60 + +m[5] };
      } catch (e) { return null; }
    }
    function formatTime(minutes) {
      var h = Math.floor(minutes/60), mn = minutes%60;
      return h + 'h' + (mn < 10 ? '0' : '') + mn;
    }
    function nextOpening(day, minutes) {
      for (var i = 0; i < 7; i++) {
        var d = (day + i) % 7;
        var slot = SCHEDULE[d];
        if (!slot) continue;
        if (i === 0 && minutes >= slot[0]) continue;
        var label = i === 0 ? "aujourd'hui" : (i === 1 ? 'demain' : DAY_NAMES[d]);
        return label + ' ' + formatTime(slot[0]);
      }
      return null;
    }
    var card = document.querySelector('.hours-card');
    if (!card) return;
    var now = getReunionNow();
    if (!now) return;
    var rows = card.querySelectorAll('tr[data-days]');
    rows.forEach(function (row) {
      var spec = row.getAttribute('data-days');
      if (!spec) return;
      var match = spec.split(',').some(function (p) {
        p = p.trim();
        if (p.indexOf('-') > -1) {
          var r = p.split('-');
          return now.day >= +r[0] && now.day <= +r[1];
        }
        return +p === now.day;
      });
      if (match) row.classList.add('is-today');
    });
    var statusEl = document.getElementById('shopStatus');
    if (!statusEl) return;
    var slot = SCHEDULE[now.day];
    var isOpen = !!slot && now.minutes >= slot[0] && now.minutes < slot[1];
    if (isOpen) {
      statusEl.textContent = '● Ouvert maintenant';
      statusEl.classList.remove('is-closed');
    } else {
      var next = nextOpening(now.day, now.minutes);
      statusEl.textContent = next ? '● Fermé — réouverture ' + next : '● Fermé';
      statusEl.classList.add('is-closed');
    }
  }());

  // ── Share FAB ──
  (function () {
    var fab = document.getElementById('shareFab');
    if (!fab) return;
    var btn = document.getElementById('shareFabBtn');
    var wa = document.getElementById('shareWa');
    var fb = document.getElementById('shareFb');
    var copyBtn = document.getElementById('shareCopy');
    var tooltip = document.getElementById('shareTooltip');
    var shareTitle = 'ZOT AUTO Multiservices';
    var shareText = "Pièces auto à La Réunion — livraison toute l'île !";
    function refreshLinks() {
      var url = location.href;
      if (wa) wa.href = 'https://wa.me/?text=' + encodeURIComponent(shareText + ' ' + url);
      if (fb) fb.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    }
    refreshLinks();
    if (btn) btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (navigator.share) {
        navigator.share({ title: shareTitle, text: shareText, url: location.href }).catch(function () {});
      } else {
        refreshLinks();
        var open = fab.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
    });
    if (copyBtn) copyBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      navigator.clipboard.writeText(location.href).then(function () {
        if (tooltip) {
          tooltip.classList.add('visible');
          setTimeout(function () { tooltip.classList.remove('visible'); }, 1500);
        }
      });
    });
    document.addEventListener('click', function (e) {
      if (fab.classList.contains('open') && !fab.contains(e.target)) {
        fab.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    });
  }());

  // ── Reading progress + scroll-top SVG ring ──
  (function () {
    var progressBar = document.getElementById('readingProgress');
    var wrapper = document.getElementById('scrollTopWrapper');
    var circle  = document.getElementById('scrollProgressCircle');
    var CIRCUMFERENCE = 2 * Math.PI * 22;
    var ticking = false;
    function updateScrollUI() {
      var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      var docHeight = (document.documentElement.scrollHeight || document.body.scrollHeight) - window.innerHeight;
      var progress  = docHeight > 0 ? Math.min(scrollTop / docHeight, 1) : 0;
      if (progressBar) { progressBar.style.width = (progress * 100).toFixed(2) + '%'; }
      if (wrapper) { wrapper.classList.toggle('visible', scrollTop > 400); }
      if (circle) { circle.style.strokeDashoffset = (CIRCUMFERENCE * (1 - progress)).toFixed(3); }
      ticking = false;
    }
    window.addEventListener('scroll', function () {
      if (!ticking) { requestAnimationFrame(updateScrollUI); ticking = true; }
    }, { passive: true });
    updateScrollUI();
  }());

  // ── Search bar pills ──
  (function () {
    var form = document.getElementById('searchBarForm');
    var input = document.getElementById('searchBarInput');
    var pills = document.querySelectorAll('.search-pill');
    if (!form || !input) return;
    pills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        input.value = this.getAttribute('data-value') || this.textContent;
        input.focus();
      });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var target = document.getElementById('bestsellers');
      if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  }());

  // ── Flash sale countdown ──
  (function () {
    var DURATION_MS = 24 * 60 * 60 * 1000;
    var KEY = 'flash_sale_end';
    var el = document.getElementById('flash-countdown');
    if (!el) return;
    function getEnd() {
      var stored = localStorage.getItem(KEY);
      var end = stored ? parseInt(stored, 10) : NaN;
      if (isNaN(end) || end <= Date.now()) {
        end = Date.now() + DURATION_MS;
        localStorage.setItem(KEY, String(end));
      }
      return end;
    }
    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function tick() {
      var end = getEnd();
      var diff = end - Date.now();
      if (diff <= 0) { localStorage.removeItem(KEY); diff = DURATION_MS; }
      var h = Math.floor(diff / 3600000);
      var m = Math.floor((diff % 3600000) / 60000);
      var s = Math.floor((diff % 60000) / 1000);
      el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
    }
    tick();
    setInterval(tick, 1000);
  }());

  // ── Newsletter form ──
  (function () {
    var form = document.getElementById('newsletterForm');
    var confirm = document.getElementById('newsletterConfirm');
    if (!form || !confirm) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = document.getElementById('newsletterTel');
      var value = input ? input.value.trim() : '';
      if (!value) {
        if (input) { input.focus(); input.style.borderColor = '#e01f2b'; setTimeout(function () { input.style.borderColor = ''; }, 1500); }
        return;
      }
      form.style.display = 'none';
      confirm.classList.add('is-visible');
    });
  }());

  // ── Progress bar + IntersectionObserver fade-in-up ──
  (function () {
    var bar = document.getElementById('pageProgress');
    if (bar) {
      bar.addEventListener('animationend', function () {
        bar.classList.add('done');
        bar.addEventListener('transitionend', function () { bar.remove(); }, { once: true });
      }, { once: true });
    }
    var cards = document.querySelectorAll('.why-card, .testimonial-card, .blog-card');
    cards.forEach(function (el) { el.classList.add('anim-ready'); });
    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.remove('anim-ready');
            entry.target.classList.add('fade-in-up');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.15 });
      cards.forEach(function (el) { observer.observe(el); });
    } else {
      cards.forEach(function (el) { el.classList.remove('anim-ready'); });
    }
  }());

  // ── WA Bubble ──
  (function () {
    var STORAGE_KEY = 'wa_bubble_closed';
    if (localStorage.getItem(STORAGE_KEY) === '1') return;
    var bubble = document.getElementById('waBubble');
    var closeBtn = document.getElementById('waBubbleClose');
    if (!bubble || !closeBtn) return;
    setTimeout(function () {
      bubble.classList.add('wa-bubble--visible');
      bubble.setAttribute('aria-hidden', 'false');
    }, 4000);
    closeBtn.addEventListener('click', function () {
      bubble.classList.remove('wa-bubble--visible');
      bubble.setAttribute('aria-hidden', 'true');
      localStorage.setItem(STORAGE_KEY, '1');
    });
  }());

  // ── Sticky promo banner ──
  (function () {
    'use strict';
    var STORAGE_KEY = 'sticky_promo_closed';
    var SCROLL_THRESHOLD = 600;
    var banner = document.getElementById('sticky-promo');
    var closeBtn = document.getElementById('sticky-promo-close');
    if (!banner || !closeBtn) return;
    if (sessionStorage.getItem(STORAGE_KEY) === '1') { banner.style.display = 'none'; return; }
    function showBanner() { banner.classList.add('is-visible'); document.body.classList.add('has-sticky'); }
    function hideBanner() { banner.classList.remove('is-visible'); document.body.classList.remove('has-sticky'); }
    function onScroll() {
      var scrollY = window.scrollY || window.pageYOffset || 0;
      if (scrollY >= SCROLL_THRESHOLD) { showBanner(); } else { hideBanner(); }
    }
    closeBtn.addEventListener('click', function () {
      sessionStorage.setItem(STORAGE_KEY, '1');
      hideBanner(); banner.style.display = 'none';
      window.removeEventListener('scroll', onScroll);
    });
    var promoLink = banner.querySelector('.sticky-promo__btn');
    if (promoLink) {
      promoLink.addEventListener('click', function (e) {
        var target = document.getElementById('promo');
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }());

  // ── Hero typewriter ──
  (function () {
    var el = document.getElementById('heroSubtitle');
    if (!el) return;
    var phrases = [
      "Pièces auto pour tous véhicules à La Réunion 🏝️",
      "Huiles, batteries, filtres aux meilleurs prix 974 💰",
      "Livraison rapide Saint-Denis → Saint-Pierre 🚚"
    ];
    var phraseIndex = 0, charIndex = 0, isDeleting = false;
    var SPEED_TYPE = 80, SPEED_DEL = 40, PAUSE_END = 2000, PAUSE_START = 400;
    function tick() {
      var current = phrases[phraseIndex];
      if (!isDeleting) {
        el.textContent = current.slice(0, charIndex + 1);
        charIndex++;
        if (charIndex === current.length) { isDeleting = true; setTimeout(tick, PAUSE_END); return; }
        setTimeout(tick, SPEED_TYPE);
      } else {
        el.textContent = current.slice(0, charIndex - 1);
        charIndex--;
        if (charIndex === 0) { isDeleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; setTimeout(tick, PAUSE_START); return; }
        setTimeout(tick, SPEED_DEL);
      }
    }
    setTimeout(tick, 600);
  }());

  // ── Social proof notifications ──
  (function () {
    var notifications = [
      { emoji: "🌺", name: "Matthieu", ville: "Saint-Pierre", produit: "Huile moteur 5W-40 Euroatlantic" },
      { emoji: "🔧", name: "Cindy", ville: "Saint-Denis", produit: "Lot de 5 microfibres Koch-Chemie" },
      { emoji: "🌺", name: "Dorian", ville: "Le Tampon", produit: "Visseuse sans-fil Worcraft" },
      { emoji: "🔧", name: "Priscilla", ville: "Saint-Paul", produit: "Shampoing auto Green Star" },
      { emoji: "🌺", name: "Kévin", ville: "Saint-André", produit: "Meuleuse d'angle Power Maxx" },
      { emoji: "🔧", name: "Nathalie", ville: "Sainte-Marie", produit: "Huile moteur 5W-30 Euroatlantic" },
      { emoji: "🌺", name: "Damien", ville: "Saint-Leu", produit: "Detailing / lavage premium" },
      { emoji: "🔧", name: "Sandrine", ville: "Saint-Louis", produit: "Vidange & entretien" }
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
    if (!document.getElementById('cdDays')) return; // section #promo supprimée
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
    // Bouton « Rayons » de la barre mobile → ouvre/ferme le drawer
    var mobRayons = document.getElementById("mobRayons");
    if (mobRayons) {
      mobRayons.addEventListener("click", function () {
        if (typeof window.__zotToggleDrawer === "function") window.__zotToggleDrawer();
      });
    }
    // Overlay menu (ferme le drawer au clic en dehors)
    var overlay = document.createElement("div");
    overlay.className = "menu-overlay";
    overlay.id = "menuOverlay";
    document.body.appendChild(overlay);
    var burger = document.getElementById("burger");
    var catnav = document.getElementById("catnav");
    overlay.addEventListener("click", function () {
      if (catnav) { catnav.classList.remove("is-open"); catnav.style.display = ""; }
      if (burger) burger.setAttribute("aria-expanded", "false");
      if (mobRayons) mobRayons.setAttribute("aria-expanded", "false");
      document.body.classList.remove("menu-open");
    });
  }());

  // ── Hero counter animation ──
  (function () {
    var counters = document.querySelectorAll('.hero-counter__num[data-target]');
    if (!counters.length || prefersReduced) {
      counters.forEach(function (el) { el.textContent = el.getAttribute('data-target'); });
      return;
    }
    var DURATION = 1800;
    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }
    function animateNum(el) {
      var target = parseInt(el.getAttribute('data-target'), 10);
      if (isNaN(target)) return;
      var start = null;
      function step(ts) {
        if (start === null) start = ts;
        var progress = Math.min((ts - start) / DURATION, 1);
        el.textContent = Math.round(target * easeOutCubic(progress));
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = target;
      }
      el.textContent = '0';
      requestAnimationFrame(step);
    }
    if ('IntersectionObserver' in window) {
      var obs = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            observer.unobserve(entry.target);
            counters.forEach(animateNum);
          }
        });
      }, { threshold: 0.5 });
      obs.observe(counters[0].closest('.hero-counter'));
    } else {
      counters.forEach(animateNum);
    }
  }());


  // ── Mode sombre ──
  (function () {
    var STORAGE_KEY = 'zotauto_theme';
    var toggle = document.getElementById('themeToggle');
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

    function getPreferred() {
      var stored = null;
      try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
      if (stored === 'dark' || stored === 'light') return stored;
      return prefersDark.matches ? 'dark' : 'light';
    }

    function apply(theme, animate) {
      if (animate) {
        document.body.classList.add('dark-transition');
        setTimeout(function () { document.body.classList.remove('dark-transition'); }, 350);
      }
      if (theme === 'dark') {
        document.body.classList.add('dark');
        if (toggle) toggle.textContent = '☀️'; // sun
      } else {
        document.body.classList.remove('dark');
        if (toggle) toggle.textContent = '🌙'; // moon
      }
    }

    apply(getPreferred(), false);

    if (toggle) {
      toggle.addEventListener('click', function () {
        var next = document.body.classList.contains('dark') ? 'light' : 'dark';
        try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
        apply(next, true);
      });
    }

    // React to OS preference change if no manual override
    try {
      prefersDark.addEventListener('change', function () {
        var stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) apply(prefersDark.matches ? 'dark' : 'light', true);
      });
    } catch (e) {}
  }());

  // ── Loading skeleton dismissal ──
  (function () {
    var skeleton = document.getElementById('skeletonScreen');
    if (!skeleton) return;
    function dismiss() {
      skeleton.classList.add('is-hidden');
      setTimeout(function () { skeleton.remove(); }, 500);
    }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      dismiss();
    } else {
      document.addEventListener('DOMContentLoaded', dismiss);
    }
  }());


  // ── Reassurance strip fade-in staggered on scroll ──
  (function () {
    var items = document.querySelectorAll('.reassurance-reveal');
    if (!items.length) return;
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
      items.forEach(function (el) { el.classList.add('in'); });
      return;
    }
    if (!('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('in'); });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.2 });
    items.forEach(function (el, i) {
      el.style.transitionDelay = (i * 0.12) + 's';
      observer.observe(el);
    });
  }());


  // ── Roue de la chance (Spin to Win) ──
  (function () {
    var SPIN_KEY = 'zotauto_spin_done';
    var fabSpin = document.getElementById('fabSpin');
    var modal = document.getElementById('spinModal');
    var wheel = document.getElementById('spinWheel');
    var goBtn = document.getElementById('spinGo');
    var resultDiv = document.getElementById('spinResult');
    var prizeEl = document.getElementById('spinPrize');
    var codeEl = document.getElementById('spinCode');
    var spinWaEl = document.getElementById('spinWa');
    if (!fabSpin || !modal) return;

    if (sessionStorage.getItem(SPIN_KEY) === '1') { fabSpin.style.display = 'none'; return; }

    var segments = [
      { label: '-5% sur votre commande', code: 'ZOTSPIN5', angle: 0 },
      { label: '-10% sur votre commande', code: 'ZOTSPIN10', angle: 60 },
      { label: 'Cadeau mystere avec votre commande', code: 'ZOTCADEAU', angle: 120 },
      { label: '-15% sur votre commande', code: 'ZOTSPIN15', angle: 180 },
      { label: 'Livraison offerte !', code: 'ZOTFREE', angle: 240 },
      { label: 'Presque... Retentez la prochaine fois !', code: '', angle: 300 }
    ];

    function openSpin() {
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
    function closeSpin() {
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    fabSpin.addEventListener('click', openSpin);
    document.getElementById('spinModalClose').addEventListener('click', closeSpin);
    document.getElementById('spinModalX').addEventListener('click', closeSpin);

    var spinning = false;
    goBtn.addEventListener('click', function () {
      if (spinning) return;
      spinning = true;
      goBtn.disabled = true;
      resultDiv.hidden = true;

      var weights = [20, 20, 15, 10, 15, 20];
      var totalW = 0;
      for (var i = 0; i < weights.length; i++) totalW += weights[i];
      var r = Math.random() * totalW, acc = 0, chosen = 0;
      for (var j = 0; j < weights.length; j++) {
        acc += weights[j];
        if (r < acc) { chosen = j; break; }
      }

      var segCenter = chosen * 60 + 30;
      var targetAngle = 360 - segCenter;
      var totalSpin = 360 * 6 + targetAngle + (Math.random() * 20 - 10);

      wheel.style.transition = 'transform 4s cubic-bezier(0.17,0.67,0.12,0.99)';
      wheel.style.transform = 'rotate(' + totalSpin + 'deg)';
      wheel.classList.add('is-spinning');

      setTimeout(function () {
        spinning = false;
        wheel.classList.remove('is-spinning');
        goBtn.style.display = 'none';
        resultDiv.hidden = false;
        var seg = segments[chosen];
        prizeEl.textContent = seg.label;
        if (seg.code) {
          codeEl.textContent = seg.code;
          codeEl.parentElement.style.display = '';
          spinWaEl.href = 'https://wa.me/262693057012?text=' + encodeURIComponent('Bonjour ZOT AUTO ! J\'ai gagne le code ' + seg.code + ' sur la roue de la chance. Je souhaite l\'utiliser pour ma commande.');
          spinWaEl.style.display = '';
        } else {
          codeEl.parentElement.style.display = 'none';
          spinWaEl.style.display = 'none';
        }
        sessionStorage.setItem(SPIN_KEY, '1');
        fabSpin.style.display = 'none';
      }, 4200);
    });
  }());

  // ── Barre livraison gratuite ──
  (function () {
    var bar = document.getElementById('freeShippingBar');
    var textEl = document.getElementById('freeShippingText');
    var fillEl = document.getElementById('freeShippingFill');
    if (!bar) return;

    var FREE_THRESHOLD = 50;

    function updateBar() {
      var cartTotal = 0;
      try {
        var items = JSON.parse(localStorage.getItem('zotauto_cart')) || [];
        for (var i = 0; i < items.length; i++) cartTotal += (items[i].price || 0) * (items[i].qty || 0);
      } catch (e) { cartTotal = 35; }
      if (cartTotal === 0) cartTotal = 35;

      var pct = Math.min(cartTotal / FREE_THRESHOLD * 100, 100);
      fillEl.style.width = pct + '%';

      if (cartTotal >= FREE_THRESHOLD) {
        textEl.innerHTML = '🎉 Livraison gratuite débloquée !';
        bar.classList.add('is-complete');
      } else {
        var remaining = (FREE_THRESHOLD - cartTotal).toFixed(2).replace('.', ',');
        textEl.innerHTML = 'Plus que <strong>' + remaining + ' €</strong> pour la livraison gratuite !';
        bar.classList.remove('is-complete');
      }
    }

    updateBar();
    window.addEventListener('storage', function (e) { if (e.key === 'zotauto_cart') updateBar(); });
    setInterval(updateBar, 2000);
  }());

  // ── Vus recemment ──
  (function () {
    var VIEWED_KEY = 'zotauto_viewed';
    var section = document.getElementById('recentlyViewed');
    var track = document.getElementById('recentlyViewedTrack');
    if (!section || !track) return;

    var DATA = window.ZOTAUTO || { products: [] };
    var PRODUCTS = DATA.products || [];
    if (!PRODUCTS.length) return;

    function getViewed() {
      try { return JSON.parse(localStorage.getItem(VIEWED_KEY)) || []; } catch (e) { return []; }
    }
    function saveViewed(ids) {
      try { localStorage.setItem(VIEWED_KEY, JSON.stringify(ids.slice(0, 8))); } catch (e) {}
    }
    function findById(id) {
      for (var i = 0; i < PRODUCTS.length; i++) if (PRODUCTS[i].id === id) return PRODUCTS[i];
      return null;
    }

    var qv = document.getElementById('quickView');
    if (qv) {
      new MutationObserver(function () {
        if (!qv.classList.contains('open')) return;
        var nameEl = document.getElementById('qvName');
        if (!nameEl) return;
        var name = nameEl.textContent;
        for (var i = 0; i < PRODUCTS.length; i++) {
          if (PRODUCTS[i].name === name) {
            var ids = getViewed();
            ids = ids.filter(function (x) { return x !== PRODUCTS[i].id; });
            ids.unshift(PRODUCTS[i].id);
            saveViewed(ids);
            renderViewed();
            break;
          }
        }
      }).observe(qv, { attributes: true, attributeFilter: ['class'] });
    }

    function renderViewed() {
      var ids = getViewed();
      if (!ids.length) { section.hidden = true; return; }
      var html = '';
      var count = 0;
      for (var i = 0; i < ids.length && count < 4; i++) {
        var p = findById(ids[i]);
        if (!p) continue;
        count++;
        var mediaClass = p.contain ? 'rv-card__media rv-card__media--logo' : 'rv-card__media';
        html += '<div class="rv-card" data-rv-id="' + p.id + '">' +
          '<div class="' + mediaClass + '"><img src="' + (p.image||'') + '" alt="' + (p.brand||'') + ' ' + (p.name||'') + '" loading="lazy" /></div>' +
          '<div class="rv-card__body">' +
          '<span class="rv-card__brand">' + (p.brand||'') + '</span>' +
          '<div class="rv-card__name">' + (p.name||'') + '</div>' +
          '<div class="rv-card__price">' + Number(p.price).toFixed(2).replace('.',',') + ' €</div>' +
          '</div></div>';
      }
      if (!count) { section.hidden = true; return; }
      track.innerHTML = html;
      section.hidden = false;
    }

    track.addEventListener('click', function (e) {
      var card = e.target.closest('.rv-card');
      if (!card) return;
      var p = findById(card.getAttribute('data-rv-id'));
      if (p) {
        // Try to call the openQuickView from the outer IIFE scope
        var btn = document.querySelector('.product[data-id="' + p.id + '"] .product__open');
        if (btn) btn.click();
      }
    });

    renderViewed();
  }());

  // ── Liens placeholder (#) : ne pas faire sauter la page en haut ──
  // (réseaux sociaux Instagram/TikTok pas encore renseignés)
  (function () {
    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href="#"]');
      if (a) { e.preventDefault(); }
    });
  }());

  // ── Scroll-spy : surligne le lien catnav de la section visible (desktop) ──
  (function () {
    var nav = document.getElementById("catnav");
    if (!nav || !("IntersectionObserver" in window)) return;
    // associe chaque section à un lien de la barre de rayons
    var map = [
      { id: "bestsellers", sel: '.catnav__all' },
      { id: "services",    sel: 'a[href="#services"]' },
      { id: "marques",     sel: 'a[href="#marques"]' },
      { id: "promo",       sel: '.catnav__promo' }
    ];
    var pairs = [];
    map.forEach(function (m) {
      var sec = document.getElementById(m.id);
      var link = nav.querySelector(m.sel);
      if (sec && link) pairs.push({ sec: sec, link: link });
    });
    if (!pairs.length) return;
    function clearAll() { pairs.forEach(function (p) { p.link.classList.remove("is-current"); }); }
    var visible = {};
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { visible[e.target.id] = e.isIntersecting ? e.intersectionRatio : 0; });
      var best = null, bestRatio = 0;
      pairs.forEach(function (p) {
        var r = visible[p.sec.id] || 0;
        if (r > bestRatio) { bestRatio = r; best = p; }
      });
      clearAll();
      if (best) best.link.classList.add("is-current");
    }, { threshold: [0.15, 0.4, 0.7], rootMargin: "-100px 0px -45% 0px" });
    pairs.forEach(function (p) { io.observe(p.sec); });
  }());

})();
