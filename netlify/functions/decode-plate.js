/* =========================================================
   ZOT AUTO — Netlify Function : proxy d'identification par PLAQUE (SIV)
   ---------------------------------------------------------
   NIVEAU 2 — ne s'active QUE si déployé sur Netlify AVEC une clé.
   La clé reste côté serveur (variable d'environnement), jamais dans le front.

   Variables d'environnement (Netlify > Site settings > Environment variables) :
     SIV_API_KEY    (obligatoire)  — token/clé du fournisseur
     SIV_API_HOST   (optionnel)    — hôte RapidAPI (si fournisseur via RapidAPI)
     ALLOWED_ORIGIN (recommandé)   — ex https://zotauto.re

   Activation côté site : mettre CONFIG.PLATE_PROXY_URL = "/api/decode-plate"
   dans vehicle-lookup.js (un redirect /api/decode-plate -> cette fonction est
   défini dans netlify.toml).

   ⚠️ Ne JAMAIS journaliser la plaque ni la clé (RGPD + sécurité).
   ⚠️ Le mapping des champs (étape 7) est à ajuster après un vrai appel test.
   ========================================================= */

var hits = {}; // rate-limit best-effort en mémoire (par IP)

function cors(origin) {
  return {
    "Access-Control-Allow-Origin": origin || "*",
    "Access-Control-Allow-Methods": "GET, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type",
    "Vary": "Origin"
  };
}

exports.handler = async function (event) {
  var ALLOWED = process.env.ALLOWED_ORIGIN || "*";
  var H = cors(ALLOWED);

  if (event.httpMethod === "OPTIONS") return { statusCode: 204, headers: H, body: "" };
  if (event.httpMethod !== "GET") return { statusCode: 405, headers: H, body: JSON.stringify({ error: "method" }) };

  // Contrôle d'origine (défense en plus du CORS navigateur)
  var origin = (event.headers && (event.headers.origin || event.headers.referer)) || "";
  if (ALLOWED !== "*" && origin && origin.indexOf(ALLOWED) !== 0) {
    return { statusCode: 403, headers: H, body: JSON.stringify({ error: "forbidden" }) };
  }

  // Clé absente => service non configuré (le front bascule sur WhatsApp)
  var KEY = process.env.SIV_API_KEY;
  if (!KEY) return { statusCode: 503, headers: H, body: JSON.stringify({ error: "not_configured" }) };

  // Rate-limit best-effort : 5 requêtes / minute / IP (anti-abus = anti-facture)
  var ip = (event.headers && event.headers["x-nf-client-connection-ip"]) || "anon";
  var now = Date.now(), WIN = 60000, MAX = 5;
  hits[ip] = (hits[ip] || []).filter(function (t) { return now - t < WIN; });
  if (hits[ip].length >= MAX) return { statusCode: 429, headers: H, body: JSON.stringify({ error: "rate" }) };
  hits[ip].push(now);

  // Validation AVANT tout appel payant
  var plate = String((event.queryStringParameters || {}).plate || "").toUpperCase().trim();
  if (!/^[A-Z]{2}-?\d{3}-?[A-Z]{2}$/.test(plate)) {
    return { statusCode: 400, headers: H, body: JSON.stringify({ error: "bad_plate" }) };
  }

  try {
    var ctrl = new AbortController();
    var t = setTimeout(function () { ctrl.abort(); }, 8000);
    var url, opts;
    if (process.env.SIV_API_HOST) {
      // Variante RapidAPI
      url = "https://" + process.env.SIV_API_HOST + "/?immatriculation=" + encodeURIComponent(plate);
      opts = { headers: { "x-rapidapi-key": KEY, "x-rapidapi-host": process.env.SIV_API_HOST }, signal: ctrl.signal };
    } else {
      // Variante apiplaqueimmatriculation.com
      url = "https://api.apiplaqueimmatriculation.com/plaque?immatriculation=" + encodeURIComponent(plate) + "&token=" + KEY + "&pays=FR";
      opts = { method: "POST", headers: { "Accept": "application/json" }, signal: ctrl.signal };
    }
    var r = await fetch(url, opts);
    clearTimeout(t);
    if (!r.ok) return { statusCode: 502, headers: H, body: JSON.stringify({ error: "upstream" }) };
    var j = await r.json();
    var d = (j.data && j.data[0]) || j.data || j || {};

    // Normalisation : ne renvoyer QUE l'utile (jamais la réponse brute).
    // ⚠️ Ajuster ces clés après un vrai appel test du fournisseur retenu.
    var vehicle = {
      make: d.marque || d.make || "",
      model: d.modele || d.model || d.version || "",
      year: d.annee || d.year || (d.date1erCir_fr ? String(d.date1erCir_fr).slice(-4) : ""),
      fuel: d.energie || d.fuel || "",
      engine: [d.energie || d.fuel, (d.ccm ? (Math.round(d.ccm / 100) / 10 + " L") : (d.cylindree || ""))].filter(Boolean).join(" ").trim(),
      vin: d.vin || ""
    };
    if (!vehicle.make && !vehicle.model) return { statusCode: 404, headers: H, body: JSON.stringify({ error: "not_found" }) };

    return {
      statusCode: 200,
      headers: Object.assign({ "Content-Type": "application/json", "Cache-Control": "public, max-age=86400" }, H),
      body: JSON.stringify({ ok: true, vehicle: vehicle })
    };
  } catch (e) {
    return { statusCode: 502, headers: H, body: JSON.stringify({ error: "unavailable" }) };
  }
};
