<?php
/**
 * axonaut_pricing.php — Console : Tarifs & Promos.
 * Définit la MARGE appliquée aux prix Axonaut (coût → prix de vente) et gère les
 * PROMOS (remise % appliquée en un clic). Les prix se recalculent depuis les coûts
 * privés (jamais exposés au public).
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

$conf   = axonaut_conf();
$notice = '';
$error  = '';

// --- Actions ------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, réessayez.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save_margin' || $action === 'save_promo') {
            if ($action === 'save_margin') {
                $m = (float) str_replace(',', '.', (string) ($_POST['margin'] ?? '40'));
                $conf['margin'] = max(0, min(1000, $m));
            } else {
                $conf['promo_pct']    = max(0, min(90, (float) str_replace(',', '.', (string) ($_POST['promo_pct'] ?? '0'))));
                $conf['promo_active'] = !empty($_POST['promo_active']);
                $conf['promo_label']  = trim((string) ($_POST['promo_label'] ?? ''));
                $conf['promo_start']  = trim((string) ($_POST['promo_start'] ?? ''));
                $conf['promo_end']    = trim((string) ($_POST['promo_end'] ?? ''));
            }
            axonaut_save_conf($conf);
            // Recalcule tous les prix immédiatement.
            $r = axonaut_recompute_prices();
            if ($r['ok']) {
                $notice = 'Prix recalculés et publiés : ' . $r['total'] . ' produits ('
                    . $r['withCost'] . ' avec un coût Axonaut).'
                    . ($r['withCost'] === 0 ? ' ⚠️ Aucun coût connu : lancez d\'abord une synchro Axonaut.' : '');
            } else {
                $error = $r['error'] ?? 'Échec du recalcul.';
            }
        } elseif ($action === 'toggle_promo') {
            $conf['promo_active'] = empty($conf['promo_active']);
            axonaut_save_conf($conf);
            $r = axonaut_recompute_prices();
            $notice = $conf['promo_active'] ? 'Promo ACTIVÉE et appliquée.' : 'Promo désactivée, prix normaux rétablis.';
        }
        $conf = axonaut_conf();
    }
}

$margin      = isset($conf['margin']) ? (float) $conf['margin'] : 40.0;
$promoPct    = isset($conf['promo_pct']) ? (float) $conf['promo_pct'] : 0.0;
$promoActive = !empty($conf['promo_active']);
$promoLabel  = (string) ($conf['promo_label'] ?? '');
$promoStart  = (string) ($conf['promo_start'] ?? '');
$promoEnd    = (string) ($conf['promo_end'] ?? '');

// Nombre de coûts connus (indicateur).
$nCosts = count(ax_costs_read());

// Exemple de calcul (coût 100 €).
$exNormal = round(100 * (1 + $margin / 100), 2);
$exPromo  = $promoPct > 0 ? round($exNormal * (1 - $promoPct / 100), 2) : $exNormal;
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Tarifs & Promos — ZOT AUTO Admin</title>
<style>
  :root { --blue:#2a52e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; --err:#c0392b; --amber:#b8860b; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:720px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  h1 { font-size:1.5rem; margin:.2em 0 .1em; }
  .sub { color:#5b6376; margin:0 0 22px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:22px; margin-bottom:18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .card h2 { font-size:1.1rem; margin:0 0 6px; }
  label { display:block; font-weight:600; font-size:.9rem; margin:12px 0 5px; }
  input[type=text], input[type=number], input[type=date] { width:100%; padding:11px 13px; border:1px solid var(--line); border-radius:9px; font-size:.95rem; }
  .row { display:flex; gap:14px; flex-wrap:wrap; }
  .row > div { flex:1 1 160px; }
  .btn { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:10px; padding:12px 20px; font-weight:700; font-size:.95rem; cursor:pointer; }
  .btn--primary { background:var(--blue); color:#fff; }
  .btn--go { background:var(--ok); color:#fff; }
  .btn--amber { background:var(--amber); color:#fff; }
  .msg { padding:11px 14px; border-radius:9px; margin:0 0 16px; font-size:.9rem; }
  .msg--ok { background:#e7f6ec; color:var(--ok); border:1px solid #b6e2c4; }
  .msg--err { background:#fdecea; color:var(--err); border:1px solid #f3c2bc; }
  .hint { font-size:.83rem; color:#5b6376; }
  .ex { background:#f0f3fb; border-radius:10px; padding:12px 14px; margin-top:12px; font-size:.9rem; }
  .ex b { color:var(--blue); }
  .pill { display:inline-block; padding:3px 10px; border-radius:99px; font-size:.78rem; font-weight:700; }
  .pill--on { background:#e7f6ec; color:var(--ok); }
  .pill--off { background:#eef1fa; color:#5b6376; }
  .check { display:flex; align-items:center; gap:9px; font-weight:600; margin-top:10px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a href="index.php">← Éditeur</a>
    <a href="axonaut.php">🔄 Axonaut</a>
    <a href="axonaut_photos.php">🖼️ Photos</a>
    <a href="axonaut_schedule.php">🗓️ Planif</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>💶 Tarifs & Promos</h1>
  <p class="sub">Fixe ta marge sur les prix Axonaut et lance des promos en un clic.
     Les prix sont recalculés depuis les coûts (gardés privés sur le serveur).</p>

  <?php if ($notice !== ''): ?><div class="msg msg--ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="msg msg--err"><?= h($error) ?></div><?php endif; ?>

  <?php if ($nCosts === 0): ?>
    <div class="msg" style="background:#fff7e6;border:1px solid #ffe0a3;color:#8a6d1a">
      ⚠️ Aucun coût Axonaut connu pour l'instant. Va sur <strong>🔄 Axonaut</strong> et lance une
      <strong>synchronisation</strong> : les coûts seront récupérés, puis la marge s'appliquera.
    </div>
  <?php endif; ?>

  <!-- MARGE -->
  <div class="card">
    <h2>1️⃣ Marge</h2>
    <p class="hint">Prix de vente = coût Axonaut + marge. Ex. 40 % sur un coût de 100 € = 140 €.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_margin">
      <label for="margin">Marge appliquée (%)</label>
      <div class="row">
        <div><input type="number" step="1" min="0" max="1000" id="margin" name="margin" value="<?= h(rtrim(rtrim(number_format($margin, 2, '.', ''), '0'), '.')) ?>"></div>
        <div style="flex:2 1 260px;display:flex;align-items:flex-end">
          <button class="btn btn--primary" type="submit">💾 Enregistrer &amp; recalculer les prix</button>
        </div>
      </div>
      <div class="ex">Exemple : coût <b>100,00 €</b> → prix de vente <b><?= h(number_format($exNormal, 2, ',', ' ')) ?> €</b><?= $promoPct > 0 && $promoActive ? ' → en promo <b>' . h(number_format($exPromo, 2, ',', ' ')) . ' €</b>' : '' ?></div>
    </form>
  </div>

  <!-- PROMO -->
  <div class="card">
    <h2>2️⃣ Promo
      <?php if ($promoActive): ?><span class="pill pill--on">EN COURS</span><?php else: ?><span class="pill pill--off">inactive</span><?php endif; ?>
    </h2>
    <p class="hint">Une remise appliquée à tous les prix. Le prix barré s'affiche automatiquement sur le site.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_promo">
      <div class="row">
        <div>
          <label for="promo_pct">Remise (%)</label>
          <input type="number" step="1" min="0" max="90" id="promo_pct" name="promo_pct" value="<?= h(rtrim(rtrim(number_format($promoPct, 2, '.', ''), '0'), '.')) ?>">
        </div>
        <div>
          <label for="promo_label">Nom (optionnel)</label>
          <input type="text" id="promo_label" name="promo_label" placeholder="Promo rentrée" value="<?= h($promoLabel) ?>">
        </div>
      </div>
      <div class="row">
        <div><label for="promo_start">Début (info)</label><input type="date" id="promo_start" name="promo_start" value="<?= h($promoStart) ?>"></div>
        <div><label for="promo_end">Fin (info)</label><input type="date" id="promo_end" name="promo_end" value="<?= h($promoEnd) ?>"></div>
      </div>
      <label class="check"><input type="checkbox" name="promo_active" value="1" <?= $promoActive ? 'checked' : '' ?>> Promo active maintenant</label>
      <p style="margin-top:14px"><button class="btn btn--go" type="submit">💾 Enregistrer &amp; appliquer</button></p>
    </form>
    <form method="post" style="margin-top:6px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle_promo">
      <button class="btn <?= $promoActive ? 'btn--amber' : 'btn--go' ?>" type="submit">
        <?= $promoActive ? '⏹️ Arrêter la promo maintenant' : '▶️ Lancer la promo maintenant' ?>
      </button>
    </form>
    <p class="hint" style="margin-top:12px">Les dates sont indicatives. Pour une activation/désactivation
       automatique, on peut brancher une tâche cron (page 🗓️ Planif).</p>
  </div>

  <div class="card">
    <p class="hint">🔒 Les coûts d'achat sont stockés dans un fichier privé du serveur
       (<code>.axonaut_costs.php</code>, 0600) et ne sont <strong>jamais</strong> visibles sur le site public.
       Seuls les prix de vente calculés apparaissent.</p>
  </div>
</div>
</body>
</html>
