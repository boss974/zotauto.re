<?php
/**
 * dashboard.php — Tableau de bord admin : vue d'ensemble + accès rapide aux outils.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';
require __DIR__ . '/ui.php';

// --- Garde d'accès ------------------------------------------------------
if (!file_exists(AUTH_FILE)) { header('Location: setup.php'); exit; }
if (empty($_SESSION['admin_ok'])) { header('Location: login.php'); exit; }
$since = (int) ($_SESSION['admin_since'] ?? 0);
if ($since === 0 || (time() - $since) > ADMIN_SESSION_MAX_IDLE) {
    session_unset(); session_destroy(); header('Location: login.php?timeout=1'); exit;
}
$_SESSION['admin_since'] = time();

$cat  = catalogue_read();
$prod = $cat['products'];
$conf = axonaut_conf();

$total      = count($prod);
$withPrice  = 0; $withPhoto = 0; $aiPhoto = 0; $inStock = 0;
foreach ($prod as $p) {
    if ((float) ($p['price'] ?? 0) > 0) { $withPrice++; }
    $img = (string) ($p['image'] ?? '');
    if ($img !== '' && strpos($img, 'assets/brands/logo-zotauto.png') === false) { $withPhoto++; }
    if (strpos($img, 'assets/generated/') !== false && substr($img, -4) === '.png') { $aiPhoto++; }
    if (($p['stock'] ?? '') === 'in') { $inStock++; }
}
$services   = count($cat['services'] ?? []);
$margin     = isset($conf['margin']) ? (float) $conf['margin'] : 40.0;
$promoOn    = !empty($conf['promo_active']);
$promoPct   = (float) ($conf['promo_pct'] ?? 0);
$lastSync   = (string) ($conf['last_result'] ?? '—');
$hasKey     = ($conf['key'] ?? '') !== '';

function pct(int $n, int $t): int { return $t > 0 ? (int) round($n / $t * 100) : 0; }
?>
<?php admin_head('Tableau de bord', 'dashboard'); ?>
<div class="page-head">
  <h1>Tableau de bord</h1>
  <p class="lead">Vue d'ensemble de votre catalogue et accès rapide à tous les outils.</p>
</div>

  <?php if (!$hasKey): ?>
    <div class="banner banner--warn">⚙️ Axonaut n'est pas encore connecté. Va sur <strong>🔄 Axonaut</strong> pour coller ta clé API et synchroniser ton stock.</div>
  <?php else: ?>
    <div class="banner banner--ok">✅ Axonaut connecté. Dernière synchro : <?= h($lastSync) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="grid">
    <div class="stat"><b><?= (int) $total ?></b><span>Produits</span></div>
    <div class="stat"><b><?= (int) $inStock ?></b><span>En stock</span></div>
    <div class="stat"><b><?= (int) $withPrice ?></b><span>Avec prix (<?= pct($withPrice, $total) ?>%)</span><div class="mini"><i style="width:<?= pct($withPrice, $total) ?>%"></i></div></div>
    <div class="stat"><b><?= (int) $withPhoto ?></b><span>Avec photo (<?= pct($withPhoto, $total) ?>%)</span><div class="mini"><i style="width:<?= pct($withPhoto, $total) ?>%"></i></div></div>
    <div class="stat"><b><?= (int) $aiPhoto ?></b><span>Photos IA</span></div>
    <div class="stat"><b><?= (int) $services ?></b><span>Services</span></div>
    <div class="stat"><b><?= h(rtrim(rtrim(number_format($margin, 1, ',', ''), '0'), ',')) ?>%</b><span>Marge</span></div>
    <div class="stat"><b style="color:<?= $promoOn ? 'var(--ok)' : '#9aa3b2' ?>"><?= $promoOn ? '−' . h(rtrim(rtrim(number_format($promoPct, 1, ',', ''), '0'), ',')) . '%' : 'off' ?></b><span>Promo</span></div>
  </div>

  <!-- Outils -->
  <div class="tiles">
    <a class="tile" href="index.php"><div class="ic">📝</div><h3>Éditeur catalogue</h3><p>Modifier produits, services, photos à la main puis publier.</p></a>
    <a class="tile" href="axonaut.php"><div class="ic">🔄</div><h3>Synchro Axonaut</h3><p>Clé API + import stock/prix depuis Axonaut.</p></a>
    <a class="tile" href="axonaut_pricing.php"><div class="ic">💶</div><h3>Tarifs &amp; Promos</h3><p>Marge de vente et remises en 1 clic.</p></a>
    <a class="tile" href="axonaut_photos.php"><div class="ic">🖼️</div><h3>Photos produits</h3><p>Visuel filigrané gratuit ou vraies photos par IA.</p></a>
    <a class="tile" href="photos_web.php"><div class="ic">🌐</div><h3>Photos web</h3><p>Chercher de vraies photos par référence, rangées par catégorie.</p></a>
    <a class="tile" href="offres.php"><div class="ic">📣</div><h3>Offres du moment</h3><p>Gérer les promos affichées sur la page d'accueil.</p></a>
    <a class="tile" href="axonaut_schedule.php"><div class="ic">🗓️</div><h3>Planification</h3><p>Synchro automatique (tâche cron LWS).</p></a>
    <a class="tile" href="../index.html" target="_blank"><div class="ic">🌐</div><h3>Voir le site</h3><p>Ouvrir la boutique publique zotauto.re.</p></a>
  </div>
<?php admin_foot('dashboard'); ?>
