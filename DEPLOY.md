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

## ⚙️ Détails techniques

- **PWA** : `manifest.json` + `sw.js` (mode hors-ligne, installable). Pensez à incrémenter `CACHE` dans `sw.js` après une grosse mise à jour pour forcer le rafraîchissement.
- **SEO** : `robots.txt`, `sitemap.xml`, données structurées Schema.org (boutique + produits), balises Open Graph (`assets/og-image.png`).
- **En-têtes** : sécurité + cache via `netlify.toml`.
- Les URL canoniques/OG pointent vers `https://zotauto.re/` — pensez à brancher le domaine pour qu'elles résolvent.
