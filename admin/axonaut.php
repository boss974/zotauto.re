<?php
/**
 * axonaut.php — Page admin : connexion Axonaut + synchronisation du stock.
 * Session admin obligatoire. La clé API est saisie ici, stockée côté serveur,
 * et jamais réaffichée en clair (seulement masquée).
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';
require __DIR__ . '/ui.php';

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
?>
<?php admin_head('Synchro Axonaut', 'axonaut'); ?>
<div class="page-head">
  <h1>🔄 Synchro Axonaut</h1>
  <p class="lead">Connecte ta clé API Axonaut et importe tes produits, prix et stocks.</p>
</div>

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
<?php admin_foot('axonaut'); ?>
