<?php
/**
 * axonaut.php — Page admin : connexion Axonaut + synchronisation du stock.
 * Session admin obligatoire. La clé API est saisie ici, stockée côté serveur,
 * et jamais réaffichée en clair (seulement masquée).
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';

// --- Garde d'accès (même logique que index.php) ------------------------
if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}
if (empty($_SESSION['admin_ok'])) {
    header('Location: login.php');
    exit;
}
$since = (int) ($_SESSION['admin_since'] ?? 0);
if ($since === 0 || (time() - $since) > ADMIN_SESSION_MAX_IDLE) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
$_SESSION['admin_since'] = time();

$conf   = axonaut_conf();
$notice = '';
$error  = '';

// --- Enregistrement de la clé / des options ----------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save') {
    if (!csrf_check()) {
        $error = 'Session expirée, réessayez.';
    } else {
        $newKey = trim((string) ($_POST['api_key'] ?? ''));
        $mode   = in_array($_POST['mode'] ?? '', ['stock', 'full'], true) ? $_POST['mode'] : 'stock';

        // Si le champ est laissé vide, on garde la clé existante (pratique).
        if ($newKey === '' && ($conf['key'] ?? '') !== '') {
            $newKey = $conf['key'];
        }
        if ($newKey === '') {
            $error = 'Veuillez saisir votre clé API Axonaut.';
        } else {
            $conf['key']  = $newKey;
            $conf['mode'] = $mode;
            if (axonaut_save_conf($conf)) {
                $notice = 'Réglages enregistrés. Vous pouvez lancer une synchronisation.';
            } else {
                $error = 'Impossible d\'enregistrer (permissions serveur sur admin/).';
            }
        }
    }
    $conf = axonaut_conf();
}

$hasKey    = ($conf['key'] ?? '') !== '';
$maskedKey = $hasKey ? (substr($conf['key'], 0, 4) . str_repeat('•', 12) . substr($conf['key'], -3)) : '';
$mode      = $conf['mode'] ?? 'stock';
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Synchronisation Axonaut — ZOT AUTO Admin</title>
<style>
  :root { --blue:#2a52e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; --err:#c0392b; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:680px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  h1 { font-size:1.5rem; margin:.2em 0 .1em; }
  .sub { color:#5b6376; margin:0 0 22px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:20px 22px; margin-bottom:18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .card h2 { font-size:1.05rem; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
  label { display:block; font-weight:600; font-size:.9rem; margin:12px 0 5px; }
  input[type=text], input[type=password] { width:100%; padding:11px 13px; border:1px solid var(--line); border-radius:9px; font-size:.95rem; }
  .radio { display:flex; gap:12px; flex-wrap:wrap; margin-top:6px; }
  .radio label { display:flex; gap:9px; align-items:flex-start; font-weight:500; border:1px solid var(--line); border-radius:10px; padding:12px 14px; cursor:pointer; flex:1 1 260px; margin:0; }
  .radio input { margin-top:3px; }
  .radio small { display:block; color:#5b6376; font-weight:400; }
  .btn { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:10px; padding:12px 20px; font-weight:700; font-size:.95rem; cursor:pointer; }
  .btn--primary { background:var(--blue); color:#fff; }
  .btn--go { background:var(--ok); color:#fff; }
  .btn:disabled { opacity:.6; cursor:progress; }
  .msg { padding:11px 14px; border-radius:9px; margin:0 0 16px; font-size:.9rem; }
  .msg--ok { background:#e7f6ec; color:var(--ok); border:1px solid #b6e2c4; }
  .msg--err { background:#fdecea; color:var(--err); border:1px solid #f3c2bc; }
  .keytag { font-family:ui-monospace,Menlo,monospace; background:#eef1fa; padding:3px 8px; border-radius:6px; font-size:.85rem; }
  .hint { font-size:.82rem; color:#5b6376; margin-top:8px; }
  #result { white-space:pre-wrap; }
  .steps { font-size:.86rem; color:#5b6376; margin:6px 0 0; padding-left:18px; }
  .steps li { margin:3px 0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a href="index.php">← Retour à l'éditeur</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>🔄 Synchronisation Axonaut</h1>
  <p class="sub">Reliez votre stock Axonaut au site. Axonaut reste votre référence ;
     le site affiche le stock et les prix récupérés depuis Axonaut.</p>

  <?php if ($notice !== ''): ?><div class="msg msg--ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="msg msg--err"><?= h($error) ?></div><?php endif; ?>

  <!-- 1. Clé API + mode -->
  <div class="card">
    <h2>1️⃣ Votre clé API Axonaut</h2>
    <?php if ($hasKey): ?>
      <p>Clé enregistrée : <span class="keytag"><?= h($maskedKey) ?></span></p>
      <p class="hint">Laissez le champ vide pour conserver cette clé, ou collez-en une nouvelle pour la remplacer.</p>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <label for="api_key">Clé API <?= $hasKey ? '(nouvelle, optionnel)' : '' ?></label>
      <input type="password" id="api_key" name="api_key" placeholder="Collez votre clé API Axonaut ici" autocomplete="new-password">
      <p class="hint">Dans Axonaut : <strong>Paramètres → API</strong> → copiez votre clé.
         Elle est stockée uniquement sur votre serveur, jamais affichée en clair.</p>

      <label>Que faut-il synchroniser ?</label>
      <div class="radio">
        <label>
          <input type="radio" name="mode" value="stock" <?= $mode === 'stock' ? 'checked' : '' ?>>
          <span><strong>Stock &amp; prix seulement</strong>
            <small>Met à jour le prix et la disponibilité des produits <em>déjà présents</em> sur le site (par référence). N'ajoute ni ne supprime rien. Recommandé.</small>
          </span>
        </label>
        <label>
          <input type="radio" name="mode" value="full" <?= $mode === 'full' ? 'checked' : '' ?>>
          <span><strong>Catalogue complet</strong>
            <small>Importe aussi les produits Axonaut absents du site (nom, prix, réf, stock). À affiner ensuite dans l'éditeur (images, catégories).</small>
          </span>
        </label>
      </div>

      <p style="margin-top:16px"><button class="btn btn--primary" type="submit">💾 Enregistrer</button></p>
    </form>
  </div>

  <!-- 2. Synchroniser -->
  <div class="card">
    <h2>2️⃣ Synchroniser maintenant</h2>
    <?php if (!$hasKey): ?>
      <p class="hint">Enregistrez d'abord votre clé API ci-dessus.</p>
    <?php else: ?>
      <p>Récupère vos produits depuis Axonaut et met à jour le site immédiatement
         (publication automatique).</p>
      <?php if (!empty($conf['last_result'])): ?>
        <p class="hint">Dernière synchro : <?= h($conf['last_result']) ?></p>
      <?php endif; ?>
      <p><button class="btn btn--go" id="syncBtn">🔄 Lancer la synchronisation</button></p>
      <div id="result" class="msg" style="display:none"></div>
    <?php endif; ?>
  </div>

  <!-- 3. Auto (cron) -->
  <div class="card">
    <h2>3️⃣ Automatiser (optionnel)</h2>
    <p class="hint">Pour une mise à jour automatique du stock (ex. toutes les heures),
       créez une tâche planifiée dans le panel LWS (<strong>Tâches cron</strong>) qui appelle&nbsp;:</p>
    <p class="keytag" style="display:block;padding:10px;word-break:break-all">
      php <?= h(__DIR__) ?>/axonaut_cron.php</p>
    <p class="hint">Le décrément automatique du stock se fait dans Axonaut au moment de la facturation :
       chaque vente facturée dans Axonaut baisse le stock, et le site le reflète à la synchro suivante.</p>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('syncBtn');
  if (!btn) return;
  var box = document.getElementById('result');
  var csrf = <?= json_encode(csrf_token()) ?>;
  var mode = <?= json_encode($mode) ?>;

  btn.addEventListener('click', function () {
    btn.disabled = true;
    var old = btn.textContent;
    btn.textContent = '⏳ Synchronisation en cours…';
    box.style.display = 'none';

    var body = new URLSearchParams({ csrf_token: csrf, mode: mode });
    fetch('axonaut_sync.php', { method: 'POST', body: body, headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json().catch(function () { return { ok:false, error:'Réponse serveur illisible (HTTP ' + r.status + ').' }; }); })
      .then(function (d) {
        box.style.display = 'block';
        if (d.ok) {
          box.className = 'msg msg--ok';
          box.textContent = '✅ ' + (d.message || 'Synchronisation réussie.');
        } else {
          box.className = 'msg msg--err';
          box.textContent = '⚠️ ' + (d.error || 'Échec de la synchronisation.');
        }
      })
      .catch(function (e) {
        box.style.display = 'block';
        box.className = 'msg msg--err';
        box.textContent = '⚠️ Erreur réseau : ' + e;
      })
      .finally(function () { btn.disabled = false; btn.textContent = old; });
  });
})();
</script>
</body>
</html>
