# ZOT AUTO — boutique pièces & produits auto (La Réunion)

Refonte moderne de **zotauto.re** façon **boutique de pièces auto en ligne**
(inspirée des meilleures : Mister-Auto, Oscaro, AutoDoc), aux **couleurs de la marque**
extraites du logo : **bleu `#2a52e0` · rouge `#e01f2b` · jaune `#ffcb00`** (tricolore péi 🌺).

Site **statique** : aucun build, aucune dépendance. S'ouvre tel quel dans un navigateur.

## 🛠️ Ajouter des produits / services SANS coder

Ouvrez **`admin.html`** : éditeur visuel (formulaires + aperçu en direct).
Vous ajoutez/modifiez produits et services, vous cliquez **💾 Télécharger catalogue.js**,
puis vous remplacez `data/catalogue.js` par le fichier téléchargé. 👉 Voir **[GUIDE.md](GUIDE.md)**.

> Le catalogue vit dans **`data/catalogue.js`**. Le site se remplit tout seul à partir de ce fichier.

## ✨ Fonctionnalités

- **Catalogue éditable sans code** — `admin.html` (ajout/édition/réordonnancement, export du fichier).
- **Sélecteur de véhicule** — recherche **par plaque d'immatriculation** (974) ou marque/modèle ; renvoie vers WhatsApp avec les infos du véhicule.
- **Catalogue produits** — fiches avec marque, note, stock, prix (et prix barrés promo), filtres par rayon.
- **Section Services** — prestations (montage, vidange, detailing…), bouton « Demander » → WhatsApp.
- **Panier → WhatsApp** — ajout, quantités, total, et **« Finaliser sur WhatsApp »** qui génère le message de commande tout prêt (panier mémorisé via `localStorage`).
- **Réassurance**, catégories, marques, avis, barre de paiement, footer complet.
- 100 % **responsive**, accessible.
- **PWA** : site installable + mode hors-ligne (`manifest.json` + `sw.js`).
- **SEO** : données structurées Schema.org (boutique + produits), Open Graph (image de partage `assets/og-image.png`), `sitemap.xml`, `robots.txt`.
- **Pages légales** (mentions légales, CGV, confidentialité) + page **404**, à compléter (SIRET, hébergeur…).
- **Déploiement prêt** : `netlify.toml` + guide **[DEPLOY.md](DEPLOY.md)** (Netlify / Vercel / Cloudflare).

## 📁 Structure

```
zotauto-site/
├── index.html            ← la page (vitrine + boutique)
├── admin.html            ← éditeur du catalogue (sans code)
├── styles.css            ← thème clair + tricolore marque
├── script.js             ← rendu catalogue, sélecteur, filtres, panier
├── pwa.js                ← service worker + données structurées produits
├── sw.js                 ← cache hors-ligne (PWA)
├── manifest.json         ← PWA (installable)
├── page.css              ← style des pages secondaires
├── 404.html
├── mentions-legales.html · cgv.html · confidentialite.html
├── robots.txt · sitemap.xml
├── netlify.toml · DEPLOY.md   ← mise en ligne
├── GUIDE.md              ← mode d'emploi de l'éditeur
├── data/
│   └── catalogue.js      ← TOUS les produits & services (éditable)
└── assets/
    ├── favicon.svg · og-image.png
    ├── icons/            ← icônes d'app (PWA)
    ├── brands/           ← logos marques + logo ZOT AUTO
    └── img/              ← visuels produits
```

## ✏️ À personnaliser avant mise en ligne

| Élément | Valeur actuelle (placeholder) | Où |
|---|---|---|
| Téléphone | `0692 00 00 00` | `index.html` |
| WhatsApp | `262692000000` (variable `WA`) | en haut de `script.js` (+ `admin.html`) |
| Email | `contact@zotauto.re` | footer |
| Réseaux sociaux | liens `#` | header / footer |
| Produits & prix | 8 produits **d'exemple** | via `admin.html` / `data/catalogue.js` |
| Services | 4 services **d'exemple** | via `admin.html` / `data/catalogue.js` |
| Avis clients | 3 avis d'exemple | section `#avis` de `index.html` |
| Photos produits | logos de marque en attendant | remplacer par de vraies photos |

## 🎨 Couleurs

Centralisées en haut de `styles.css` (`:root`) : `--blue`, `--red`, `--yellow`.

## 🚀 Aperçu en local

- Double-clic sur `index.html`, **ou**
- `npx serve zotauto-site` puis ouvrir l'URL affichée.

## ☁️ Mise en ligne (100 % statique)

1. **Netlify / Vercel / Cloudflare Pages** — glisser-déposer le dossier.
2. **Hébergement (FTP)** — envoyer le contenu à la racine web.

Le domaine `zotauto.re` pourra ensuite pointer vers ce nouveau site.
