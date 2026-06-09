# 🛠️ Guide facile — Ajouter des produits & services

Pas besoin de savoir coder. Tout se fait dans une page : **`admin.html`**.

---

## 1. Ouvrir l'éditeur

- **En local** : double-cliquez sur `admin.html` (ou ouvrez `votre-site.re/admin.html` une fois en ligne).
- Vous voyez à gauche la liste, à droite le formulaire + un **aperçu en direct**.

## 2. Ajouter un produit

1. Onglet **Produits** → bouton **➕ Ajouter**.
2. Remplissez : **Nom**, **Marque**, **Catégorie**, **Prix**.
   - *Ancien prix* (facultatif) = affiche un prix barré (promo).
   - *Badge* = petite étiquette (ex : `-20%`, `Top`, `Nouveau`).
   - *Disponibilité* = « En stock » ou « Sur commande ».
3. **Image** : soit un chemin (ex : `assets/img/ma-photo.jpg`), soit le bouton **📷 Téléverser**.
4. L'aperçu se met à jour tout seul à droite. 👍

## 3. Ajouter un service

1. Onglet **Services** → **➕ Ajouter**.
2. Remplissez : **Nom**, **Icône** (un emoji, ex : 🔧 🛢️ ✨ 🚚), **Description**, et **Prix/mention** (ex : « Sur devis », « Dès 39 € »).

## 4. Modifier, dupliquer, réordonner, supprimer

Sur chaque ligne de la liste :
- **↑ / ↓** : changer l'ordre d'affichage sur le site
- **⧉** : dupliquer (pratique pour créer un produit proche)
- **🗑** : supprimer

## 5. Publier sur le site (important !)

L'éditeur garde vos changements **dans votre navigateur**. Pour qu'ils apparaissent sur le vrai site :

1. Cliquez sur **💾 Télécharger catalogue.js**.
2. Remplacez le fichier **`data/catalogue.js`** du site par celui que vous venez de télécharger.
   - Sur **GitHub** : ouvrez `data/catalogue.js` → bouton crayon ✏️ ou « Upload files » → glissez le nouveau fichier → « Commit ».
   - Ou via votre hébergeur (FTP), au même endroit.
3. Rechargez le site : c'est à jour ! 🎉

## 💡 Astuces

- **Images** : préférez des photos **légères** (moins de 300 Ko) et carrées. Mettez-les dans le dossier `assets/` et indiquez le chemin.
- **Sauvegarde** : le bouton **📥 Importer** permet de recharger un `catalogue.js` existant pour le modifier.
- **Oups** : le bouton **↺ Réinitialiser** revient au catalogue d'origine.
- Rien n'est cassé tant que vous n'avez pas remplacé `data/catalogue.js` : vous pouvez expérimenter tranquillement.
