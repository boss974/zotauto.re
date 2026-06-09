# 🚀 Mettre le site en ligne

Le site est **100 % statique** : il se met en ligne en quelques minutes, gratuitement, avec **HTTPS automatique** (nécessaire pour la PWA / « ajouter à l'écran d'accueil »).

## Option A — Netlify connecté à GitHub (recommandé)

1. Créez un compte sur **netlify.com** (gratuit).
2. **Add new site → Import an existing project → GitHub** → choisissez le dépôt **`boss974/zotauto.re`**.
3. Laissez les réglages par défaut (publish directory = `.`, pas de build). Cliquez **Deploy**.
4. ✅ Le site est en ligne sur une adresse `xxxx.netlify.app`. **À chaque `git push`, il se met à jour tout seul.**

## Option B — Netlify sans Git (glisser-déposer)

1. Sur **app.netlify.com/drop**, glissez le dossier `zotauto-site` entier.
2. C'est en ligne. (Pour mettre à jour : reglissez le dossier.)

## Option C — Vercel ou Cloudflare Pages

Même principe : importez le dépôt, **aucune commande de build**, dossier racine = `.`. Ça marche sans configuration.

## 🌐 Brancher le domaine `zotauto.re`

1. Dans Netlify : **Domain settings → Add a domain** → `zotauto.re`.
2. Chez votre registrar (là où le domaine est acheté), pointez le DNS vers Netlify (enregistrements indiqués par Netlify, généralement un `CNAME`/`A`).
3. Netlify active le **HTTPS** automatiquement (Let's Encrypt).

## ✅ Après la première mise en ligne

- Remplacez les **placeholders** : numéro `0692 00 00 00` / WhatsApp `262692000000` (constante `WA` dans `script.js`, `pwa.js`, `sw.js` non concerné), e-mail, réseaux sociaux, et complétez les **pages légales** (SIRET, hébergeur…).
- Déclarez le site dans **Google Search Console** et soumettez `sitemap.xml`.
- Créez la **fiche Google Business Profile** (pour apparaître sur Maps / recherche locale).
- Vérifiez l'aperçu de partage (image OG) avec l'outil de debug de Facebook / le validateur de votre choix.

## 🚘 Activer la recherche par plaque (optionnel — Niveau 2)

La recherche **par VIN fonctionne déjà** (gratuite, API publique NHTSA, 100 % côté client) : complète pour les véhicules vendus aux USA, partielle pour beaucoup d'européens (souvent constructeur + année) — avec repli WhatsApp.

La recherche **par plaque** (SIV français) nécessite un fournisseur **payant** + le proxy serveur (déjà codé dans `netlify/functions/decode-plate.js`, inactif tant qu'aucune clé n'est posée). Pour l'activer :

1. Créez un compte chez un fournisseur SIV (recommandé : **apiplaqueimmatriculation.com**, ou un fournisseur **RapidAPI**) et récupérez la clé/token.
2. Dans **Netlify → Site settings → Environment variables** (jamais dans le dépôt) :
   - `SIV_API_KEY` = votre clé
   - `SIV_API_HOST` = l'hôte RapidAPI (seulement si vous passez par RapidAPI)
   - `ALLOWED_ORIGIN` = `https://zotauto.re`
3. **Re-déployez** (une variable d'env n'est lue qu'après un nouveau déploiement).
4. Faites un appel test et ajustez si besoin le mapping des champs dans `decode-plate.js` (voir commentaire « ajuster… »).
5. Dans `vehicle-lookup.js`, passez `CONFIG.PLATE_PROXY_URL = "/api/decode-plate"`. La plaque devient réelle, sans aucune autre modification d'UI.

> Sans clé, l'onglet plaque reste en **mode démonstration + repli WhatsApp** — déjà livrable et honnête. La fonction verrouille l'origine, limite le débit (5 req/min/IP) et valide le format avant tout appel payant.
> ⚠️ Pensez à remplacer le numéro `WA = "262692000000"` dans **`script.js`** ET **`vehicle-lookup.js`**, et à compléter le nom du sous-traitant plaque dans `confidentialite.html`.

## ⚙️ Détails techniques

- **PWA** : `manifest.json` + `sw.js` (mode hors-ligne, installable). Pensez à incrémenter `CACHE` dans `sw.js` après une grosse mise à jour pour forcer le rafraîchissement.
- **SEO** : `robots.txt`, `sitemap.xml`, données structurées Schema.org (boutique + produits), balises Open Graph (`assets/og-image.png`).
- **En-têtes** : sécurité + cache via `netlify.toml`.
- Les URL canoniques/OG pointent vers `https://zotauto.re/` — pensez à brancher le domaine pour qu'elles résolvent.
