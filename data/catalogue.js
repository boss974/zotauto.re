/* =========================================================
   ZOT AUTO — CATALOGUE
   ---------------------------------------------------------
   👉 Ce fichier contient TOUS les produits et services du site.
   Le plus simple pour le modifier : ouvrez "admin.html" (éditeur visuel).
   Pas besoin de toucher au reste du site : il se remplit tout seul d'ici.

   Vous pouvez aussi éditer à la main en copiant un bloc { ... } existant.
   - category (produit) : "detailing", "outillage", "huiles", "pieces" ou "accessoires"
   - stock : "in" (En stock) ou "soon" (Sur commande)
   - oldPrice : ancien prix barré, ou null s'il n'y en a pas
   - badge : petit texte (ex "-17%", "Top", "Nouveau") ou "" pour rien
   - contain : true si l'image est un logo/petite image (affichée centrée), sinon false
   ========================================================= */

window.ZOTAUTO = {

  products: [
    {
      id: "huile-5w40-5l",
      name: "Huile moteur 5W-40 — 5L",
      brand: "Euroatlantic",
      category: "huiles",
      price: 34.90,
      oldPrice: 42.00,
      stock: "in",
      badge: "-17%",
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
      price: 89.90,
      oldPrice: 109.90,
      stock: "in",
      badge: "Top",
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
      price: 14.50,
      oldPrice: null,
      stock: "in",
      badge: "",
      image: "assets/brands/koch-chemie.webp",
      contain: true,
      rating: 4,
      reviews: 31
    },
    {
      id: "essuie-glace",
      name: "Balais d'essuie-glace (paire)",
      brand: "Valeo",
      category: "pieces",
      price: 19.90,
      oldPrice: null,
      stock: "in",
      badge: "",
      image: "assets/brands/valeo.png",
      contain: true,
      rating: 5,
      reviews: 12
    },
    {
      id: "microfibres-x5",
      name: "Lot de 5 microfibres premium",
      brand: "Koch-Chemie",
      category: "detailing",
      price: 12.90,
      oldPrice: null,
      stock: "in",
      badge: "",
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
      price: 49.90,
      oldPrice: null,
      stock: "soon",
      badge: "",
      image: "assets/brands/power-maxx.png",
      contain: true,
      rating: 4,
      reviews: 19
    },
    {
      id: "plaquettes-frein",
      name: "Plaquettes de frein — avant",
      brand: "Valeo",
      category: "pieces",
      price: 29.90,
      oldPrice: null,
      stock: "in",
      badge: "",
      image: "assets/brands/valeo.png",
      contain: true,
      rating: 5,
      reviews: 27
    },
    {
      id: "huile-5w30-5l",
      name: "Huile moteur 5W-30 — 5L",
      brand: "Euroatlantic",
      category: "huiles",
      price: 36.90,
      oldPrice: null,
      stock: "in",
      badge: "",
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
      badge: ""
    },
    {
      id: "vidange",
      name: "Vidange & entretien",
      icon: "🛢️",
      description: "Vidange avec huile Euroatlantic et conseils adaptés à votre véhicule.",
      price: "Dès 39 €",
      badge: "Populaire"
    },
    {
      id: "detailing",
      name: "Detailing / lavage premium",
      icon: "✨",
      description: "Nettoyage intérieur & extérieur avec les produits pro Koch-Chemie.",
      price: "Sur devis",
      badge: ""
    },
    {
      id: "recherche-piece",
      name: "Recherche de pièce",
      icon: "🔎",
      description: "Vous cherchez une référence précise ? Envoyez la carte grise, on la trouve.",
      price: "Gratuit",
      badge: ""
    }
  ]

};
