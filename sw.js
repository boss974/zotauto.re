/* =========================================================
   ZOT AUTO — Service Worker (PWA, mode hors-ligne)
   Stratégie :
   - Documents HTML & catalogue → réseau d'abord (mises à jour visibles),
     repli sur le cache si hors-ligne.
   - Assets statiques (css/js/images/polices) → cache d'abord.
   ⚠️ Incrémentez CACHE pour forcer la mise à jour après un déploiement.
   ========================================================= */
var CACHE = "zotauto-v5";
var CORE = [
  "./",
  "./index.html",
  "./styles.css",
  "./script.js",
  "./pwa.js",
  "./manifest.json",
  "./assets/favicon.svg",
  "./assets/brands/logo-zotauto.png",
  "./assets/icons/icon-192.png",
  "./assets/icons/icon-512.png"
];

self.addEventListener("install", function (e) {
  e.waitUntil(
    caches.open(CACHE).then(function (c) { return c.addAll(CORE); }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener("activate", function (e) {
  e.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener("fetch", function (e) {
  var req = e.request;
  if (req.method !== "GET") return;
  var url = new URL(req.url);
  if (url.origin !== self.location.origin) return; // laisse passer Google Fonts, wa.me, etc.

  var isDoc = req.mode === "navigate" || url.pathname.endsWith(".html") || url.pathname === "/" || url.pathname.endsWith("/");
  var isData = url.pathname.indexOf("catalogue.js") !== -1;

  if (isDoc || isData) {
    // réseau d'abord
    e.respondWith(
      fetch(req).then(function (res) {
        var copy = res.clone();
        caches.open(CACHE).then(function (c) { c.put(req, copy); });
        return res;
      }).catch(function () {
        return caches.match(req).then(function (r) { return r || caches.match("./index.html"); });
      })
    );
  } else {
    // cache d'abord
    e.respondWith(
      caches.match(req).then(function (r) {
        return r || fetch(req).then(function (res) {
          var copy = res.clone();
          caches.open(CACHE).then(function (c) { c.put(req, copy); });
          return res;
        });
      })
    );
  }
});
