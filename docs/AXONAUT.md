# Synchronisation Axonaut & catalogue — ZOT AUTO

Ce document explique comment le stock **Axonaut** est relié au site **zotauto.re**,
comment planifier la synchro, et comment les photos produits sont gérées.

## 1. Principe

**Axonaut = source de vérité du stock.** Le site affiche le stock, les prix et les
produits récupérés depuis Axonaut. Le site étant statique (commande via WhatsApp),
il ne peut pas décrémenter Axonaut : le stock baisse **dans Axonaut** au moment de la
facturation, et le site le reflète à la synchro suivante.

## 2. Fichiers (dossier `admin/`)

| Fichier | Rôle |
|---|---|
| `axonaut.php` | Page : saisie de la clé API + choix du mode + bouton « Synchroniser » |
| `axonaut_sync.php` | Endpoint POST (session+CSRF) : lance la synchro à la demande |
| `axonaut_cron.php` | Synchro planifiée, **CLI uniquement** (appelée par une tâche cron) |
| `axonaut_schedule.php` | Page : choix de la fréquence + génère la ligne cron LWS |
| `axonaut_lib.php` | Bibliothèque (appel API, mapping, écriture atomique de `data/catalogue.js`) |
| `.axonaut.php` | Config serveur (clé API + options), `0600`, protégé par `.htaccess` — **jamais** exposé |

## 3. Utilisation

1. Se connecter sur `zotauto.re/admin/` (login + code par mail webmaster@).
2. Menu **🔄 Axonaut** → coller la **clé API** (Axonaut : *Paramètres → API*).
3. Choisir le mode :
   - **Stock & prix seulement** (recommandé, non destructif) : met à jour prix + dispo
     des produits **déjà présents** sur le site (correspondance par référence).
   - **Catalogue complet** : importe aussi les produits Axonaut absents du site.
4. **Enregistrer**, puis **Lancer la synchronisation**.

### Détails techniques
- Auth API : en-tête HTTP `userApiKey: <clé>`.
- **Pagination** : Axonaut exige un **en-tête HTTP `page`** (entier), 500 résultats/page.
  La lib parcourt toutes les pages automatiquement.
- Écriture atomique de `data/catalogue.js` (`window.ZOTAUTO = { products, services }`).
- **Les services ne sont jamais gérés par Axonaut** : s'ils manquent (ancien format,
  écrasement), la lib rétablit les 4 services par défaut (`ax_default_services()`).

## 4. Planification (page 🗓️ Planif)

La page `axonaut_schedule.php` mémorise la fréquence voulue et **génère la commande cron**
à coller dans le panel LWS (**Base de données & PHP → Tâches cron**). Exemples :

| Fréquence | Cron |
|---|---|
| Toutes les heures | `0 * * * *  php /var/www/zotauto.re/htdocs/admin/axonaut_cron.php` |
| Toutes les 3 h | `0 */3 * * * …` |
| 2×/jour (8h & 18h) | `0 8,18 * * * …` |
| 1×/jour (6h) | `0 6 * * * …` |
| 1×/semaine (lun 6h) | `0 6 * * 1 …` |

⚠️ LWS n'exécute la synchro que si **la tâche cron est créée dans le panel**. La page ne
fait que générer la commande et mémoriser la préférence.

## 5. Photos produits

Beaucoup de produits importés d'Axonaut n'ont pas de photo. Deux niveaux :

1. **Visuel auto (par défaut, gratuit, immédiat)** — géré **côté site** (`script.js`,
   fonction `phImg`) : chaque produit sans photo reçoit un **visuel SVG généré**
   (couleur par catégorie + icône + nom). Aucun stockage, aucune clé requise.
2. **Vraies photos par IA (optionnel)** — à venir : génération d'images via une API
   (ex. OpenAI Images) avec une clé fournie par l'owner (coût par image).

### Prix
Les produits sans tarif public (`price = 0`) affichent **« Prix sur demande »** au lieu de
`0,00 €` (`script.js`, fonction `priceLabel`). La commande se fait via WhatsApp.

## 6. Cache (PWA)

Le service worker (`sw.js`) est en cache **`zotauto-v3`**. Le catalogue (`data/catalogue.js`)
n'est **plus** pré-caché → toujours rechargé frais, pour que les mises à jour de stock
soient visibles immédiatement. ⚠️ **Incrémenter `CACHE` dans `sw.js`** après toute grosse
mise à jour de `script.js` / `styles.css` pour forcer la propagation.

## 7. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Clé API refusée (403) » avec message *Too many results* | pagination | déjà géré (en-tête `page`) |
| « Clé API refusée (401) » | mauvaise clé | recopier la clé complète depuis Axonaut |
| Le site reste sur l'écran de chargement | ancien `script.js` en cache | `Ctrl+Maj+R`, ou bumper `sw.js` |
| Catalogue vide / anciens produits | cache navigateur/PWA | `Ctrl+Maj+R` ; catalogue désormais network-first |
| Prix tous à 0 | produits Axonaut sans tarif | normal → affiché « Prix sur demande » |
