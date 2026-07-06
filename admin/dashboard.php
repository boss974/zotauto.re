<?php
/**
 * dashboard.php — Tableau de bord admin : vue d'ensemble + accès rapide aux outils.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';

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
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Tableau de bord — ZOT AUTO Admin</title>
<style>
  :root { --blue:#2a52e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; --amber:#b8860b; --violet:#7c3aed; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:920px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  .top .sp { flex:1; }
  h1 { font-size:1.6rem; margin:.1em 0 .3em; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:14px; margin-bottom:24px; }
  .stat { background:#fff; border:1px solid var(--line); border-radius:14px; padding:16px 18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .stat b { display:block; font-size:2rem; font-weight:800; line-height:1.1; }
  .stat span { font-size:.82rem; color:#5b6376; }
  .stat .mini { height:6px; background:#eef1fa; border-radius:99px; overflow:hidden; margin-top:8px; }
  .stat .mini i { display:block; height:100%; background:var(--blue); }
  .tiles { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; }
  .tile { display:block; text-decoration:none; color:inherit; background:#fff; border:1px solid var(--line); border-radius:14px; padding:20px; box-shadow:0 3px 14px rgba(20,30,60,.04); transition:transform .12s, box-shadow .12s; }
  .tile:hover { transform:translateY(-3px); box-shadow:0 10px 24px rgba(20,30,60,.1); }
  .tile .ic { font-size:1.7rem; }
  .tile h3 { margin:8px 0 4px; font-size:1.05rem; }
  .tile p { margin:0; font-size:.85rem; color:#5b6376; }
  .banner { border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:.9rem; }
  .banner--ok { background:#e7f6ec; border:1px solid #b6e2c4; color:var(--ok); }
  .banner--warn { background:#fff7e6; border:1px solid #ffe0a3; color:#8a6d1a; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <strong style="font-size:1.05rem">🛠️ ZOT AUTO — Admin</strong>
    <span class="sp"></span>
    <a href="index.php">📝 Éditeur catalogue</a>
    <a href="../index.html" target="_blank">🌐 Voir le site</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>Tableau de bord</h1>

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
    <a class="tile" href="axonaut_schedule.php"><div class="ic">🗓️</div><h3>Planification</h3><p>Synchro automatique (tâche cron LWS).</p></a>
    <a class="tile" href="../index.html" target="_blank"><div class="ic">🌐</div><h3>Voir le site</h3><p>Ouvrir la boutique publique zotauto.re.</p></a>
  </div>
</div>
</body>
</html>
