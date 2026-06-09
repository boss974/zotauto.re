# ZOT AUTO — boutique pièces & produits auto (La Réunion)

Refonte moderne de **zotauto.re** façon **boutique de pièces auto en ligne**
(inspirée des meilleures : Mister-Auto, Oscaro, AutoDoc), aux **couleurs de la marque**
extraites du logo : **bleu `#2a52e0` · rouge `#e01f2b` · jaune `#ffcb00`** (tricolore péi 🌺).

Site **statique** : aucun build, aucune dépendance. S'ouvre tel quel dans un navigateur.

## ✨ Fonctionnalités

- **Sélecteur de véhicule** — recherche **par plaque d'immatriculation** (974) ou par marque/modèle ; renvoie vers WhatsApp avec les infos du véhicule (la fonction n°1 des grands sites de pièces).
- **Catalogue produits** — fiches avec marque, note, stock, prix (et prix barrés promo), filtres par rayon.
- **Panier → WhatsApp** — ajout au panier, quantités, total, et **« Finaliser sur WhatsApp »** qui génère le message de commande tout prêt. Idéal pour une petite structure sans paiement en ligne. (Panier mémorisé via `localStorage`.)
- **Barre de réassurance**, catégories, marques, avis, barre de paiement, footer complet.
- 100 % **responsive**, accessible, SEO de base (meta, Open Graph, favicon tricolore).

## 📁 Structure

```
zotauto-site/
├── index.html        ← la page
├── styles.css        ← thème clair + tricolore marque
├── script.js         ← sélecteur véhicule, filtres, panier, recherche
└── assets/
    ├── favicon.svg
    ├── brands/       ← logos marques + logo ZOT AUTO
    └── img/          ← visuels produits
```

## ✏️ À personnaliser avant mise en ligne

| Élément | Valeur actuelle (placeholder) | Où |
|---|---|---|
| Téléphone | `0692 00 00 00` | `index.html` |
| WhatsApp | `262692000000` (variable `WA`) | en haut de `script.js` + liens `wa.me` |
| Email | `contact@zotauto.re` | footer |
| Réseaux sociaux | liens `#` | header / footer |
| Produits & prix | 8 produits **d'exemple** | section `#bestsellers` de `index.html` |
| Avis clients | 3 avis d'exemple | section `#avis` |
| Photos produits | logos de marque en attendant | remplacer par de vraies photos |

> ⚠️ Le numéro `0692000000` venait du site d'origine (placeholder). Mettez le vrai numéro
> partout : il suffit de changer la constante `WA` dans `script.js` + les `href="tel:"`.

## 🎨 Couleurs

Centralisées en haut de `styles.css` (`:root`) : `--blue`, `--red`, `--yellow`.
Le bleu domine, rouge = actions/promo, jaune = accents/notes — comme le logo.

## 🚀 Aperçu en local

- Double-clic sur `index.html`, **ou**
- `npx serve zotauto-site` puis ouvrir l'URL affichée.

## ☁️ Mise en ligne (100 % statique)

1. **Netlify / Vercel / Cloudflare Pages** — glisser-déposer le dossier.
2. **Hébergement actuel (LWS) en FTP** — envoyer le contenu à la racine web.

Le domaine `zotauto.re` pourra ensuite pointer vers ce nouveau site.
