<?php
/**
 * aide.php — Centre d'aide complet de l'espace admin ZOT AUTO.
 * Mode d'emploi de chaque outil, toujours accessible depuis le menu.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/ui.php';

// --- Garde d'accès ------------------------------------------------------
if (!file_exists(AUTH_FILE)) { header('Location: setup.php'); exit; }
if (empty($_SESSION['admin_ok'])) { header('Location: login.php'); exit; }
$since = (int) ($_SESSION['admin_since'] ?? 0);
if ($since === 0 || (time() - $since) > ADMIN_SESSION_MAX_IDLE) {
    session_unset(); session_destroy(); header('Location: login.php?timeout=1'); exit;
}
$_SESSION['admin_since'] = time();

admin_head('Centre d\'aide', 'aide');
?>
<div class="page-head">
  <h1>&#10067; Centre d'aide</h1>
  <p class="lead">Le mode d'emploi de votre site. Sur chaque page, le bouton <strong>« Comment faire ? »</strong> (en haut à droite) rappelle les étapes du moment.</p>
</div>

<div class="card">
  <div class="card__head"><span class="card__num">1</span><h2>Se connecter</h2></div>
  <ol class="steps">
    <li>Ouvrez <span class="keytag">zotauto.re/admin/</span>, entrez votre identifiant et votre mot de passe.</li>
    <li>Un <strong>code à 6 chiffres</strong> arrive sur <span class="keytag">webmaster@zotauto.re</span> — saisissez-le.</li>
    <li>Mot de passe oublié&nbsp;? Cliquez sur « Mot de passe oublié », un lien est envoyé au même e-mail.</li>
  </ol>
</div>

<div class="card">
  <div class="card__head"><span class="card__num">2</span><h2>&#128227; Gérer les offres du moment</h2></div>
  <p class="lead">Les cartes « En ce moment chez ZOT AUTO » de la page d'accueil.</p>
  <ol class="steps">
    <li>Menu <strong>Offres</strong> → remplissez un emplacement (étiquette, titre, description, bouton, message WhatsApp).</li>
    <li>Cochez <strong>« Afficher »</strong> pour publier, décochez pour mettre en pause.</li>
    <li>Cochez <strong>« Mettre en avant »</strong> pour l'offre vedette (fond bleu).</li>
    <li>Cliquez <strong>Publier</strong> : en ligne immédiatement. Un emplacement sans titre reste masqué.</li>
  </ol>
  <a class="btn" href="offres.php" style="margin-top:14px">Ouvrir les offres</a>
</div>

<div class="card">
  <div class="card__head"><span class="card__num">3</span><h2>&#128260; Mettre à jour les produits</h2></div>
  <p class="lead">Vos produits, prix et stocks viennent d'Axonaut.</p>
  <ol class="steps">
    <li>Menu <strong>Synchro Axonaut</strong> → collez votre clé API (une seule fois).</li>
    <li>Choisissez le mode <strong>« Catalogue complet »</strong> (pas « Stock seulement »).</li>
    <li>Cliquez <strong>Synchroniser</strong> : articles, prix et stocks se mettent à jour.</li>
  </ol>
  <div class="banner banner--warn" style="margin-top:14px">Tant que la synchro « Catalogue complet » n'a pas été lancée, le site n'affiche que quelques produits de démonstration.</div>
</div>

<div class="card">
  <div class="card__head"><span class="card__num">4</span><h2>&#128181; Tarifs, photos &amp; planification</h2></div>
  <ol class="steps">
    <li><strong>Tarifs &amp; promos</strong> : réglez votre marge de vente et activez des remises en pourcentage.</li>
    <li><strong>Photos produits</strong> : visuel gratuit par catégorie, puis <strong>IA 100% gratuite (Pollinations, sans clé)</strong> pour générer de vraies images — cochez « Enchaîner auto » pour tout le catalogue. Option OpenAI (payante) pour une qualité premium.</li>
    <li><strong>Éditeur catalogue</strong> : bouton <strong>« ＋ Nouveau produit / Nouveau service »</strong> en haut de la liste pour ajouter un article ; cliquez un produit pour le modifier, puis Publier.</li>
    <li><strong>Planification</strong> : programmez une synchro Axonaut automatique.</li>
  </ol>
</div>

<h2 class="section-title">&#128161; Bons réflexes</h2>
<div class="card">
  <div class="status-row"><span class="k">&#128260;</span><span class="v">Vous ne voyez pas un changement&nbsp;? Faites <strong>Ctrl + Maj + R</strong> pour rafraîchir (le site garde une copie en cache pour aller plus vite).</span></div>
  <div class="status-row"><span class="k">&#128231;</span><span class="v">Le code de connexion arrive toujours sur <strong>webmaster@zotauto.re</strong> — gardez l'accès à cette boîte.</span></div>
  <div class="status-row"><span class="k">&#128241;</span><span class="v">Les commandes passent par <strong>WhatsApp</strong> (0693 05 70 12). Aucun paiement n'est encaissé sur le site.</span></div>
  <div class="status-row"><span class="k">&#9940;</span><span class="v">Ne touchez pas à l'adresse <strong>services@zotauto.re</strong> ni aux boîtes mail existantes.</span></div>
  <div class="status-row"><span class="k">&#128064;</span><span class="v">Après un changement, vérifiez le rendu en ouvrant le site public.</span></div>
</div>

<div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap;">
  <a class="btn" href="dashboard.php">&#128202; Tableau de bord</a>
  <a class="btn btn--ghost" href="../index.html" target="_blank" rel="noopener">&#127760; Voir le site public</a>
</div>
<?php
admin_foot('aide');
