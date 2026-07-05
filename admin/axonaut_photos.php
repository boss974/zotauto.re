<?php
/**
 * axonaut_photos.php — Génère et ENREGISTRE une image filigranée par produit
 * (produits sans photo). Les images sont de vrais fichiers SVG dans
 * ../assets/generated/<id>.svg, et le champ `image` du produit est mis à jour
 * → visibles à la fois sur le site ET dans l'éditeur admin.
 *
 * Traitement par lots (les 1300+ produits ne tiennent pas dans un seul appel PHP).
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

const GEN_DIR   = __DIR__ . '/../assets/generated';
const PH_LOGO   = 'assets/brands/logo-zotauto.png';
const BATCH     = 250; // produits traités par lot

/** Un produit a-t-il besoin d'un visuel ? (pas de vraie photo) */
function needs_photo(array $p): bool
{
    $img = (string) ($p['image'] ?? '');
    return $img === '' || strpos($img, PH_LOGO) !== false;
}

/** Échappe le texte pour insertion dans du XML/SVG. */
function svg_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/** Génère le SVG filigrané ZOT AUTO d'un produit. */
function photo_svg(array $p): string
{
    $cats = [
        'detailing'   => ['#7c3aed', '✨'],
        'outillage'   => ['#ea580c', '🔧'],
        'huiles'      => ['#16a34a', '🛢️'],
        'accessoires' => ['#2a52e0', '🚗'],
    ];
    $cv   = $cats[$p['category'] ?? ''] ?? ['#2a52e0', '📦'];
    $name = svg_esc(mb_substr((string) ($p['name'] ?? ''), 0, 46));
    return '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
        . '<rect width="400" height="300" fill="' . $cv[0] . '"/>'
        . '<rect width="400" height="300" fill="#000" opacity="0.06"/>'
        . '<text x="200" y="165" font-size="46" fill="#fff" opacity="0.13" font-weight="bold" font-family="Arial,sans-serif" text-anchor="middle" transform="rotate(-20 200 165)">ZOT AUTO</text>'
        . '<text x="200" y="138" font-size="92" text-anchor="middle" dominant-baseline="central">' . $cv[1] . '</text>'
        . '<text x="200" y="236" font-size="19" fill="#fff" font-family="Arial,sans-serif" text-anchor="middle">' . $name . '</text>'
        . '<text x="390" y="290" font-size="13" fill="#fff" opacity="0.8" font-weight="bold" font-family="Arial,sans-serif" text-anchor="end">zotauto.re</text>'
        . '</svg>';
}

// --- Comptage -----------------------------------------------------------
$cat      = catalogue_read();
$products = $cat['products'];
$total    = count($products);
$todo     = 0;
foreach ($products as $p) { if (needs_photo($p)) { $todo++; } }

$done    = 0;
$notice  = '';
$error   = '';

// --- Action : générer un lot -------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'gen') {
    if (!csrf_check()) {
        $error = 'Session expirée, réessayez.';
    } elseif (!is_dir(GEN_DIR) && !@mkdir(GEN_DIR, 0755, true)) {
        $error = "Impossible de créer le dossier assets/generated/ (permissions).";
    } else {
        @set_time_limit(120);
        $count = 0;
        foreach ($products as $i => $p) {
            if ($count >= BATCH) { break; }
            if (!needs_photo($p)) { continue; }
            $id  = (string) ($p['id'] ?? ax_slug((string) ($p['name'] ?? 'p')));
            $fn  = ax_slug($id);
            $rel = 'assets/generated/' . $fn . '.svg';
            if (@file_put_contents(GEN_DIR . '/' . $fn . '.svg', photo_svg($p)) !== false) {
                $products[$i]['image']   = $rel;
                $products[$i]['contain'] = false;
                $count++;
            }
        }
        $cat['products'] = $products;
        if ($count > 0) {
            $w = catalogue_write($cat);
            if ($w['ok']) {
                $done   = $count;
                $notice = $count . ' visuel(s) filigrané(s) générés et enregistrés.';
                // recompte le reste
                $todo = 0;
                foreach ($products as $p) { if (needs_photo($p)) { $todo++; } }
            } else {
                $error = $w['error'] ?? 'Écriture du catalogue impossible.';
            }
        } else {
            $notice = 'Aucun produit à traiter.';
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Photos produits — ZOT AUTO Admin</title>
<style>
  :root { --blue:#2a52e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; --err:#c0392b; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:680px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; gap:16px; margin-bottom:20px; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  h1 { font-size:1.5rem; margin:.2em 0 .1em; }
  .sub { color:#5b6376; margin:0 0 22px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:22px; margin-bottom:18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .big { font-size:2rem; font-weight:800; }
  .btn { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:10px; padding:13px 22px; font-weight:700; font-size:1rem; cursor:pointer; background:var(--ok); color:#fff; }
  .btn:disabled { opacity:.6; cursor:progress; }
  .msg { padding:11px 14px; border-radius:9px; margin:0 0 16px; font-size:.9rem; }
  .msg--ok { background:#e7f6ec; color:var(--ok); border:1px solid #b6e2c4; }
  .msg--err { background:#fdecea; color:var(--err); border:1px solid #f3c2bc; }
  .hint { font-size:.85rem; color:#5b6376; }
  .bar { height:12px; background:#eef1fa; border-radius:99px; overflow:hidden; margin:10px 0; }
  .bar > i { display:block; height:100%; background:var(--blue); }
  .prev { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
  .prev img { width:120px; height:90px; border-radius:8px; border:1px solid var(--line); }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a href="index.php">← Éditeur</a>
    <a href="axonaut.php">🔄 Axonaut</a>
    <a href="axonaut_schedule.php">🗓️ Planif</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>🖼️ Photos produits (filigrane ZOT AUTO)</h1>
  <p class="sub">Génère une image filigranée pour chaque produit <strong>sans photo</strong>.
     Les images sont enregistrées et visibles sur le site <em>et</em> dans l'éditeur.</p>

  <?php if ($notice !== ''): ?><div class="msg msg--ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="msg msg--err"><?= h($error) ?></div><?php endif; ?>

  <div class="card">
    <div><span class="big"><?= (int) $todo ?></span> produit(s) sans photo · <?= (int) $total ?> au total</div>
    <div class="bar"><i style="width:<?= $total ? (int) round(($total - $todo) / $total * 100) : 100 ?>%"></i></div>
    <?php if ($todo > 0): ?>
      <form method="post" id="genForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="gen">
        <button class="btn" id="genBtn" type="submit">✨ Générer le prochain lot (<?= min(BATCH, (int) $todo) ?>)</button>
        <p class="hint" style="margin-top:12px">Clique autant de fois que nécessaire (par lots de <?= BATCH ?>) jusqu'à ce qu'il reste 0.
           <label style="display:inline-flex;align-items:center;gap:6px;margin-left:8px"><input type="checkbox" id="auto"> Enchaîner automatiquement</label>
        </p>
      </form>
    <?php else: ?>
      <p class="msg msg--ok" style="margin-top:14px">✅ Tous les produits ont une photo. C'est terminé !</p>
    <?php endif; ?>

    <!-- Aperçu de 3 visuels d'exemple -->
    <div class="prev">
      <?php foreach (['detailing' => ['#7c3aed', '✨'], 'outillage' => ['#ea580c', '🔧'], 'huiles' => ['#16a34a', '🛢️']] as $k => $cv): ?>
        <img alt="<?= h($k) ?>" src="data:image/svg+xml,<?= rawurlencode(photo_svg(['category' => $k, 'name' => ucfirst($k)])) ?>">
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <p class="hint">💡 Pour de <strong>vraies photos</strong> (au lieu des visuels filigranés), remplace l'image
       d'un produit dans l'éditeur, ou demande l'ajout de la génération par IA (OpenAI, avec une clé).</p>
  </div>
</div>
<script>
  // Enchaînement auto : re-soumet tant qu'il reste des produits et que la case est cochée.
  (function () {
    var auto = document.getElementById('auto');
    var form = document.getElementById('genForm');
    if (!auto || !form) return;
    var remaining = <?= (int) $todo ?>;
    if (sessionStorage.getItem('zot_photos_auto') === '1' && remaining > 0) {
      auto.checked = true;
      setTimeout(function () { form.submit(); }, 600);
    }
    form.addEventListener('submit', function () {
      sessionStorage.setItem('zot_photos_auto', auto.checked ? '1' : '0');
      var b = document.getElementById('genBtn');
      if (b) { b.disabled = true; b.textContent = '⏳ Génération…'; }
    });
  }());
</script>
</body>
</html>
