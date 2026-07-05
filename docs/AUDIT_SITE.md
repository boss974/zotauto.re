# 🔍 Audit complet zotauto.re — Rapport final

Testé en direct (rubriques cliquées, interactions vérifiées) + revue de code.
Le site est **techniquement sain** : 0 erreur JS, chargement < 1 s, recherche/filtres/panier
fonctionnels. Voici tout ce qu'il faut corriger, **par priorité**.

---

## 🔴 CRITIQUE — Conformité légale (à faire vite, e-commerce)

| # | Problème | Détail |
|---|---|---|
| 1 | **CGV absentes** | Le pied de page dit « Mentions légales & CGV » mais la page ne contient **pas** de conditions générales de vente. Obligatoire pour vendre en ligne. |
| 2 | **Hébergeur non nommé** | Les mentions légales ne citent pas l'hébergeur (**LWS – Ligne Web Services**, nom + adresse). Obligation loi LCEN. |
| 3 | **Pas de page Confidentialité / cookies** | Il y a un bandeau cookies et une PWA, mais **aucune politique de confidentialité (RGPD)**. `confidentialite.html` → 404. |
| 4 | **Droit de rétractation + médiateur** | Pour la vente B2C : mentionner le **droit de rétractation 14 jours** et le **médiateur de la consommation**. Absents. |

## 🟠 IMPORTANT — Crédibilité & conversion

| # | Problème | Détail |
|---|---|---|
| 5 | **Faux feed Instagram** | Section « Suivez-nous sur Instagram @zotauto.re » avec 6 emojis 🎥 factices et un bouton « Voir le profil » qui pointe vers `#` (mort). Fait amateur → **retirer** ou brancher le vrai compte. |
| 6 | **Liens Instagram & TikTok morts** | Les icônes Instagram et TikTok pointent vers `#`. Mettre les vraies URL ou les enlever. (Facebook OK.) |
| 7 | **Prix à 0 € → « Prix sur demande » partout** | Les coûts Axonaut ne sont pas encore synchronisés. Action : page 💶 Tarifs (marge 40 %) + 🔄 Axonaut (synchro) → vrais prix. |
| 8 | **Photos non enregistrées** | Les visuels filigranés sont générés à l'affichage mais pas stockés (éditeur admin les montre vides). Action : bouton 🖼️ Photos → Générer. |
| 9 | **Email `contact@zotauto.re`** | Vérifier que cette boîte existe côté LWS, sinon les mails clients sont perdus. |

## 🟡 MINEUR — Polish

| # | Problème | Détail |
|---|---|---|
| 10 | **Bouton mode sombre/clair** | Ne bascule pas en clair (le fond reste sombre après clic). À corriger. |
| 11 | **Badge hero « +500 références »** | Il y a maintenant **1327 produits** → afficher « +1300 références en stock ». |
| 12 | **Aperçu « Rendu réel du site » (admin)** | Affiche un 404 dans l'éditeur (l'aperçu intégré pointe une mauvaise URL). Cosmétique, admin uniquement — le vrai site marche. |
| 13 | **Catalogue lourd (≈ 745 Ko)** | Chargé une seule fois (site mono-page), load reste < 1 s. Non urgent ; on pourra l'alléger (chargement différé) quand le trafic mobile augmentera. |

## ✅ CE QUI MARCHE BIEN (à garder)

- **0 erreur JavaScript**, chargement rapide (DOM 977 nœuds, load ≈ 0,9 s).
- **Recherche instantanée** (nom / référence / marque) + **repli WhatsApp** pré-rempli.
- **Filtres par rayon** OK (Detailing / Outillage / Huiles / Accessoires), 1327 produits classés.
- **Panier → Commander sur WhatsApp** fonctionnel.
- **FAQ** (accordéon), **catégories**, **marques**, **avis**, **services** OK.
- **PWA installable**, **SEO technique** (robots.txt, sitemap.xml, manifest.json, JSON-LD Schema.org),
  **404 personnalisé**, **responsive** (barre mobile + menu burger), lien Google Maps présent.

---

## 🎯 Plan d'action recommandé (ordre)

1. **Légal** (#1–4) : compléter mentions légales (hébergeur), ajouter CGV + page Confidentialité + rétractation/médiateur. → je peux les rédiger et déployer.
2. **Nettoyer le faux Instagram + liens sociaux morts** (#5–6). → rapide, je peux le faire.
3. **Prix & photos** (#7–8) : synchro Axonaut + marge, puis générer les photos. → côté toi (admin).
4. **Vérifier l'email `contact@`** (#9). → côté toi (LWS).
5. **Polish** (#10–11) : réparer le mode clair, mettre à jour le badge. → je peux le faire.

> Dis-moi « corrige tout » et je traite les points **1, 2, 10, 11** (ceux de mon ressort) :
> rédaction légale conforme + retrait du faux feed + fix mode clair + badge à jour, puis déploiement.
