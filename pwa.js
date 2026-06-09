/* =========================================================
   ZOT AUTO — PWA + données structurées produits (SEO)
   Chargé en dernier (defer) : window.ZOTAUTO est disponible.
   ========================================================= */
(function () {
  "use strict";

  /* 1) Enregistrement du service worker (mode hors-ligne / installable) */
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker.register("sw.js").catch(function () { /* silencieux */ });
    });
  }

  /* 2) Données structurées Schema.org pour les produits (ItemList → Product) */
  try {
    var data = window.ZOTAUTO;
    if (data && data.products && data.products.length) {
      var base = "https://zotauto.re/";
      var elements = data.products.map(function (p, i) {
        var img;
        try { img = new URL(p.image, location.href).href.replace(location.origin + "/", base); }
        catch (e) { img = base + p.image; }
        var product = {
          "@type": "Product",
          "name": p.name,
          "brand": { "@type": "Brand", "name": p.brand },
          "category": p.category,
          "image": img,
          "description": p.description || p.name
        };
        if (p.reference) product.sku = p.reference;
        if (p.rating && p.reviews) {
          product.aggregateRating = { "@type": "AggregateRating", "ratingValue": p.rating, "reviewCount": p.reviews };
        }
        product.offers = {
          "@type": "Offer",
          "price": Number(p.price).toFixed(2),
          "priceCurrency": "EUR",
          "availability": p.stock === "soon" ? "https://schema.org/PreOrder" : "https://schema.org/InStock"
        };
        return { "@type": "ListItem", "position": i + 1, "item": product };
      });
      var ld = { "@context": "https://schema.org", "@type": "ItemList", "name": "Catalogue ZOT AUTO", "itemListElement": elements };
      var s = document.createElement("script");
      s.type = "application/ld+json";
      s.textContent = JSON.stringify(ld);
      document.head.appendChild(s);
    }
  } catch (e) { /* silencieux */ }
})();
