/* =========================================================
   ZOT AUTO — CATALOGUE
   ---------------------------------------------------------
   👉 Ce fichier contient TOUS les produits et services du site.
   Le plus simple pour le modifier : ouvrez "admin.html" (éditeur visuel).
   Le site se remplit automatiquement à partir de ce fichier.

   ─ SCHÉMA D'UN PRODUIT ───────────────────────────────────
   id          identifiant unique (texte court, sans espace)
   name        nom affiché
   brand       marque
   category    "detailing" | "outillage" | "huiles" | "accessoires"
   reference   référence / SKU (texte libre, peut être "")
   price       prix (nombre)
   oldPrice    ancien prix barré (nombre) ou null
   stock       "in" (En stock) | "soon" (Sur commande)
   featured    true = mis en avant (apparaît dans "Coups de cœur") | false
   nouveau     true = badge "Nouveau" | false
   badge       petite étiquette libre ("-17%", "Top"...) ou ""
   description texte de présentation (fiche produit / aperçu rapide)
   image       chemin/URL de l'image principale
   contain     true si logo/petite image (affichée centrée) | false
   rating      note de 0 à 5
   reviews     nombre d'avis

   ─ SCHÉMA D'UN SERVICE ───────────────────────────────────
   id, name, icon (emoji), description, price (texte libre),
   badge (texte ou ""), featured (true/false)

   ⚠️ L'ORDRE des éléments dans les listes = l'ordre d'affichage sur le site
      (le glisser-déposer de l'éditeur réordonne simplement ces listes).
   ========================================================= */

window.ZOTAUTO = {

  products: [
    {
      id: "huile-5w40-5l",
      name: "Huile moteur 5W-40 — 5L",
      brand: "Euroatlantic",
      category: "huiles",
      reference: "EA-5W40-5L",
      price: 34.90,
      oldPrice: 42.00,
      stock: "in",
      featured: true,
      nouveau: false,
      badge: "-17%",
      description: "Huile moteur synthèse 5W-40, bidon 5L. Protection moteur essence & diesel, idéale pour la vidange.",
      image: "assets/brands/euroatlantic.jpg",
      contain: false,
      rating: 5,
      reviews: 24
    },
    {
      id: "visseuse-20v",
      name: "Visseuse sans-fil 20V Brushless",
      brand: "Worcraft",
      category: "outillage",
      reference: "WC-CLB20V",
      price: 89.90,
      oldPrice: 109.90,
      stock: "in",
      featured: true,
      nouveau: false,
      badge: "Top",
      description: "Visseuse-perceuse sans-fil 20V Brushless, 2 vitesses, couple élevé. Livrée avec batterie et chargeur.",
      image: "assets/img/worcraft-tool.webp",
      contain: false,
      rating: 5,
      reviews: 58
    },
    {
      id: "shampoing-green-star",
      name: "Shampoing auto Green Star — 1L",
      brand: "Koch-Chemie",
      category: "detailing",
      reference: "KC-GS-1L",
      price: 14.50,
      oldPrice: null,
      stock: "in",
      featured: false,
      nouveau: false,
      badge: "",
      description: "Shampoing auto pH neutre, mousse active, brillance sans traces. Dilution généreuse pour un nettoyage en douceur.",
      image: "assets/brands/koch-chemie.webp",
      contain: true,
      rating: 4,
      reviews: 31
    },
    {
      id: "microfibres-x5",
      name: "Lot de 5 microfibres premium",
      brand: "Koch-Chemie",
      category: "detailing",
      reference: "KC-MF5",
      price: 12.90,
      oldPrice: null,
      stock: "in",
      featured: true,
      nouveau: true,
      badge: "",
      description: "Lot de 5 microfibres premium sans bordure, ultra-douces, parfaites pour la finition et les vitres.",
      image: "assets/brands/koch-chemie.webp",
      contain: true,
      rating: 5,
      reviews: 40
    },
    {
      id: "meuleuse-125",
      name: "Meuleuse d'angle 125mm — 900W",
      brand: "Power Maxx",
      category: "outillage",
      reference: "PM-AG125",
      price: 49.90,
      oldPrice: null,
      stock: "soon",
      featured: false,
      nouveau: false,
      badge: "",
      description: "Meuleuse d'angle 125 mm, 900W, idéale pour la découpe et l'ébavurage. Poignée latérale incluse.",
      image: "assets/brands/power-maxx.png",
      contain: true,
      rating: 4,
      reviews: 19
    },
    {
      id: "huile-5w30-5l",
      name: "Huile moteur 5W-30 — 5L",
      brand: "Euroatlantic",
      category: "huiles",
      reference: "EA-5W30-5L",
      price: 36.90,
      oldPrice: null,
      stock: "in",
      featured: true,
      nouveau: false,
      badge: "",
      description: "Huile moteur 5W-30 économie d'énergie, bidon 5L. Conforme aux préconisations des constructeurs récents.",
      image: "assets/brands/euroatlantic.jpg",
      contain: false,
      rating: 5,
      reviews: 16
    }
  ],

  services: [
    {
      id: "montage",
      name: "Montage & pose d'accessoires",
      icon: "🔧",
      description: "Pose de vos accessoires, balais, éclairage et petites pièces par nos soins.",
      price: "Sur devis",
      badge: "",
      featured: true
    },
    {
      id: "vidange",
      name: "Vidange & entretien",
      icon: "🛢️",
      description: "Vidange avec huile Euroatlantic et conseils adaptés à votre véhicule.",
      price: "Dès 39 €",
      badge: "Populaire",
      featured: true
    },
    {
      id: "detailing",
      name: "Detailing / lavage premium",
      icon: "✨",
      description: "Nettoyage intérieur & extérieur avec les produits pro Koch-Chemie.",
      price: "Sur devis",
      badge: "",
      featured: false
    },
    {
      id: "recherche-piece",
      name: "Recherche de pièce",
      icon: "🔎",
      description: "Vous cherchez une référence précise ? Envoyez la carte grise, on la trouve.",
      price: "Gratuit",
      badge: "",
      featured: false
    }
  ]

};
