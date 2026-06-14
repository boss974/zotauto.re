/* =========================================================
   ZOT AUTO — Recherche véhicule (plaque / VIN / marque-modèle)
   ---------------------------------------------------------
   - VIN  : décodage RÉEL gratuit via l'API publique NHTSA vPIC
            (100% côté client, sans clé). Complet pour les véhicules
            vendus aux USA ; PARTIEL pour beaucoup d'européens
            (souvent constructeur + année seulement) -> repli honnête.
   - PLAQUE (SIV FR) : aucune API gratuite n'existe. Tant qu'aucun
            proxy n'est configuré (CONFIG.PLATE_PROXY_URL vide), on
            valide le format + mode DÉMO (3 plaques) + repli WhatsApp.
            Pour activer la vraie identification : déployer la Netlify
            Function et renseigner CONFIG.PLATE_PROXY_URL.
   - Tout échec retombe TOUJOURS sur un message WhatsApp pré-rempli.
   - RGPD : la plaque / le VIN ne sont JAMAIS stockés (pas de
            localStorage / cookie) — utilisés uniquement à l'écran.
   ========================================================= */
(function () {
  "use strict";

  /* ----- Configuration ----- */
  var WA = "262693057012";
  var CONFIG = {
    // (Recommandé en prod) proxy serveur Netlify — le token reste caché côté serveur :
    PLATE_PROXY_URL: "",   // ex "/api/decode-plate"
    // (Pour TEST sur hébergement statique type GitHub Pages) appel DIRECT à l'API
    // avec un token d'ESSAI apiplaqueimmatriculation.com.
    // ⚠️ Ce token est VISIBLE dans le code public : n'utilisez qu'un token d'essai
    //    limité, JAMAIS une clé de production (préférez alors PLATE_PROXY_URL).
    PLATE_API_TOKEN: ""
  };
  // Jeu de démonstration (plaques fictives) — actif quand aucun proxy n'est configuré.
  var MOCK = {
    "AB-123-CD": { make: "Renault", model: "Clio IV", year: "2015", fuel: "Diesel", cc: "1.5 L" },
    "CD-456-EF": { make: "Peugeot", model: "208", year: "2019", fuel: "Essence", cc: "1.2 L" },
    "EF-789-GH": { make: "Toyota", model: "Yaris", year: "2021", fuel: "Hybride", cc: "1.5 L" }
  };

  var $ = function (s) { return document.querySelector(s); };
  var wa = function (text) { return "https://wa.me/" + WA + "?text=" + encodeURIComponent(text); };

  /* ----- Validation / normalisation ----- */
  var SIV_LETTER = "[A-HJ-NP-TV-Z]"; // exclut I, O, U
  var SIV_RE = new RegExp("^" + SIV_LETTER + "{2}-?[0-9]{3}-?" + SIV_LETTER + "{2}$");
  function normPlate(s) {
    var raw = String(s || "").toUpperCase().replace(/[^A-Z0-9]/g, "");
    var m = raw.match(/^([A-Z]{2})([0-9]{3})([A-Z]{2})$/);
    return m ? m[1] + "-" + m[2] + "-" + m[3] : raw;
  }
  function isPlate(s) { return SIV_RE.test(normPlate(s)); }
  function cleanVin(s) { return String(s || "").toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, ""); } // exclut I, O, Q
  function isVin(v) { return /^[A-HJ-NPR-Z0-9]{17}$/.test(cleanVin(v)); }

  /* ----- VIN : décodage NHTSA vPIC (gratuit, sans clé) ----- */
  function decodeVin(vin) {
    vin = cleanVin(vin);
    if (vin.length !== 17) return Promise.resolve({ ok: false, reason: "format" });
    var ctrl = new AbortController();
    var t = setTimeout(function () { ctrl.abort(); }, 6000);
    return fetch("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/" + vin + "?format=json", { signal: ctrl.signal })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        clearTimeout(t);
        var d = (j.Results || [])[0] || {};
        var v = {
          make: d.Make || "", model: d.Model || "", year: d.ModelYear || "",
          fuel: d.FuelTypePrimary || "",
          cc: d.DisplacementL ? (Math.round(parseFloat(d.DisplacementL) * 10) / 10 + " L") : "",
          maker: d.Manufacturer || "", country: d.PlantCountry || "", vin: vin
        };
        var full = !!(v.make && v.model);
        var partial = !full && !!(v.make || v.maker || v.year);
        if (full || partial) return { ok: true, full: full, data: v };
        return { ok: false, reason: "notfound" };
      })
      .catch(function () { clearTimeout(t); return { ok: false, reason: "network" }; });
  }

  /* ----- Plaque : proxy réel si configuré, sinon DÉMO ----- */
  function lookupPlate(plate) {
    if (CONFIG.PLATE_PROXY_URL) {
      var ctrl = new AbortController();
      var t = setTimeout(function () { ctrl.abort(); }, 7000);
      return fetch(CONFIG.PLATE_PROXY_URL + "?plate=" + encodeURIComponent(plate), { signal: ctrl.signal })
        .then(function (r) { if (!r.ok) throw 0; return r.json(); })
        .then(function (j) {
          clearTimeout(t);
          var ve = j.vehicle || j.data || j || {};
          var data = { make: ve.make || "", model: ve.model || "", year: ve.year || "", fuel: ve.fuel || "", cc: ve.engine || ve.cc || "", vin: ve.vin || "" };
          return { ok: !!(data.make || data.model), data: data };
        })
        .catch(function () { clearTimeout(t); return { ok: false }; });
    }
    // (Test sur hébergement statique) appel DIRECT avec un token d'essai — token visible !
    if (CONFIG.PLATE_API_TOKEN) {
      var c2 = new AbortController();
      var t2 = setTimeout(function () { c2.abort(); }, 8000);
      return fetch("https://api.apiplaqueimmatriculation.com/plaque?immatriculation=" + encodeURIComponent(plate) + "&token=" + encodeURIComponent(CONFIG.PLATE_API_TOKEN) + "&pays=FR",
        { method: "POST", headers: { "Accept": "application/json" }, signal: c2.signal })
        .then(function (r) { if (!r.ok) throw 0; return r.json(); })
        .then(function (j) {
          clearTimeout(t2);
          var d = (j.data && j.data[0]) || j.data || {};
          var data = {
            make: d.marque || "", model: d.modele || d.version || "",
            year: d.annee || (d.date1erCir_fr ? String(d.date1erCir_fr).slice(-4) : "") || "",
            fuel: d.energie || "", cc: d.ccm ? (Math.round(d.ccm / 100) / 10 + " L") : (d.cylindree || ""), vin: d.vin || ""
          };
          return { ok: !!(data.make || data.model), data: data };
        })
        .catch(function () { clearTimeout(t2); return { ok: false }; });
    }
    // Mode démonstration (aucun proxy ni token)
    if (MOCK[plate]) return Promise.resolve({ ok: true, demo: true, data: MOCK[plate] });
    return Promise.resolve({ ok: false, demo: true });
  }

  /* ----- Messages WhatsApp pré-remplis ----- */
  function buildVinMsg(v) {
    v = v || {};
    return "Bonjour ZOT AUTO 🌺\nJe cherche des pièces pour mon véhicule :\n" +
      "• Marque : " + (v.make || v.maker || "(à préciser)") + "\n" +
      "• Modèle : " + (v.model || "(à préciser)") + "\n" +
      "• Année : " + (v.year || "(à préciser)") + "\n" +
      "• Motorisation : " + ([v.fuel, v.cc].filter(Boolean).join(" ") || "(à préciser)") + "\n" +
      "• VIN : " + (v.vin || "(à préciser)") + "\n\nPièce(s) recherchée(s) : \nMerci de me confirmer la référence, le stock et le prix !";
  }
  function buildPlateMsg(plate, v) {
    var base = "Bonjour ZOT AUTO 🌺\nJe cherche des pièces pour le véhicule immatriculé " + (plate || "(plaque)") + " (974).\n";
    if (v && (v.make || v.model)) base += "Véhicule : " + [v.make, v.model, v.year].filter(Boolean).join(" ") + "\n";
    return base + "VIN (case E carte grise) : \nPièce recherchée : \nAu besoin je vous envoie une photo de la carte grise. Merci !";
  }
  function buildModelMsg(vals) {
    return "Bonjour ZOT AUTO 🌺\nJe cherche des pièces pour : " + (vals.join(" · ") || "mon véhicule") +
      ".\nPièce recherchée : \nMerci de confirmer référence, stock et prix !";
  }

  /* ----- Carte résultat / états ----- */
  var result = $("#finderResult");
  var vcDemo = $("#vcDemo"), vcParts = $("#vcParts"), vcWa = $("#vcWa");
  var finderErrText = $("#finderErrText"), finderErrWa = $("#finderErrWa");
  function setState(s) { if (result) result.dataset.state = s; }
  function set(sel, txt) { var el = $(sel); if (el) el.textContent = txt; }

  function prefillParts(v) {
    // Pas de base de compatibilité fine : on oriente le client vers le catalogue produits,
    // puis il finalise sa demande de pièce via WhatsApp (service "Recherche de pièce").
    var target = document.getElementById("bestsellers");
    if (target) target.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function renderCard(v, opts) {
    opts = opts || {};
    var make = v.make || v.maker || "";
    var partial = !(make && v.model);
    set("#vcMake", make || "—");
    set("#vcModel", v.model || (partial ? "À préciser" : "—"));
    set("#vcYear", v.year || "—");
    set("#vcEngine", [v.fuel, v.cc].filter(Boolean).join(" ") || "—");
    if (vcDemo) vcDemo.hidden = !opts.demo;
    if (vcWa) vcWa.href = wa(opts.plate ? buildPlateMsg(opts.plate, v) : buildVinMsg(v));
    if (vcParts) vcParts.onclick = function () { prefillParts(v); };
    setState("success");
    if (result) { try { result.focus(); } catch (e) {} }
  }
  function showError(text, waText) {
    if (finderErrText) finderErrText.textContent = text;
    if (finderErrWa) finderErrWa.href = wa(waText);
    setState("error");
    if (result) { try { result.focus(); } catch (e) {} }
  }

  /* ----- Anti double-clic ----- */
  function busy(btn, on) {
    if (!btn) return;
    if (on) { btn.dataset.label = btn.textContent; btn.disabled = true; btn.textContent = "Recherche…"; }
    else { btn.disabled = false; if (btn.dataset.label) btn.textContent = btn.dataset.label; }
  }

  /* ----- Masque de saisie VIN ----- */
  var vinInput = $("#vinInput");
  if (vinInput) vinInput.addEventListener("input", function () {
    vinInput.value = vinInput.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g, "").slice(0, 17);
  });

  /* ----- Handlers de soumission ----- */
  var panelVin = $("#panelVin");
  if (panelVin) panelVin.addEventListener("submit", function (e) {
    e.preventDefault();
    var vin = cleanVin(vinInput ? vinInput.value : "");
    if (!isVin(vin)) { showError("Un VIN comporte 17 caractères (sans I, O ni Q). Vérifiez la saisie.", buildVinMsg({ vin: vin })); return; }
    if (!navigator.onLine) { showError("Vous êtes hors-ligne. Envoyez-nous le VIN sur WhatsApp, on s'en occupe.", buildVinMsg({ vin: vin })); return; }
    var btn = panelVin.querySelector('button[type="submit"]');
    busy(btn, true); setState("loading");
    decodeVin(vin).then(function (res) {
      if (res.ok) renderCard(res.data, {});
      else if (res.reason === "format") showError("Un VIN comporte 17 caractères (sans I, O ni Q).", buildVinMsg({ vin: vin }));
      else showError("Véhicule non reconnu (les VIN européens sont souvent incomplets dans la base). Envoyez-nous votre carte grise.", buildVinMsg({ vin: vin }));
    }).then(function () { busy(btn, false); }, function () { busy(btn, false); });
  });

  var panelPlate = $("#panelPlate"), plateInput = $("#plateInput");
  if (panelPlate) panelPlate.addEventListener("submit", function (e) {
    e.preventDefault();
    var raw = plateInput ? plateInput.value : "";
    var plate = normPlate(raw);
    if (!isPlate(plate)) { showError("Format de plaque non reconnu (ex : AB-123-CD).", buildPlateMsg(plate || raw)); return; }
    if (CONFIG.PLATE_PROXY_URL && !navigator.onLine) { showError("Vous êtes hors-ligne. Envoyez-nous la plaque sur WhatsApp.", buildPlateMsg(plate)); return; }
    var btn = panelPlate.querySelector('button[type="submit"]');
    busy(btn, true); setState("loading");
    lookupPlate(plate).then(function (res) {
      if (res.ok) renderCard(res.data, { demo: res.demo, plate: plate });
      else if (res.demo) showError("Plaque non reconnue (mode démonstration). L'identification automatique par plaque sera activée prochainement — envoyez-nous votre carte grise.", buildPlateMsg(plate));
      else showError("Service d'identification momentanément indisponible. Envoyez-nous votre carte grise.", buildPlateMsg(plate));
    }).then(function () { busy(btn, false); }, function () { busy(btn, false); });
  });

  var panelModel = $("#panelModel");
  if (panelModel) panelModel.addEventListener("submit", function (e) {
    e.preventDefault();
    var vals = Array.prototype.slice.call(panelModel.querySelectorAll("select")).map(function (s) { return s.value; }).filter(Boolean);
    window.open(wa(buildModelMsg(vals)), "_blank", "noopener");
  });

  /* ----- Réinitialiser la carte au changement d'onglet ----- */
  Array.prototype.slice.call(document.querySelectorAll(".finder__tab")).forEach(function (tab) {
    tab.addEventListener("click", function () { setState("idle"); });
  });
})();
