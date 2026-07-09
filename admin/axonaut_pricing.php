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
require __DIR__ . '/ui.php';

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
?>
<?php admin_head('Tarifs & promos', 'pricing'); ?>
<div class="page-head">
  <h1>💶 Tarifs &amp; promos</h1>
  <p class="lead">Règle ta marge de vente et active des remises en un clic.</p>
</div>

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
<?php admin_foot('pricing'); ?>
