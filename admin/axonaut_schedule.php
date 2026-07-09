<?php
/**
 * axonaut_schedule.php — Page admin : planification de la synchronisation Axonaut.
 * Permet de choisir une fréquence de synchronisation et génère la ligne cron
 * exacte à coller dans le panel LWS. La préférence est mémorisée dans la config
 * Axonaut ; l'exécution réelle dépend de la tâche cron créée côté LWS.
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

// --- Table des fréquences supportées -----------------------------------
// Chaque entrée : libellé lisible + expression cron (min heure jour mois jsem).
$FREQS = [
    'off'        => ['label' => 'Désactivé (aucune synchro automatique)', 'cron' => null],
    'hourly'     => ['label' => 'Toutes les heures',                       'cron' => '0 * * * *'],
    '3h'         => ['label' => 'Toutes les 3 heures',                     'cron' => '0 */3 * * *'],
    'twicedaily' => ['label' => '2× par jour (8h & 18h)',                  'cron' => '0 8,18 * * *'],
    'daily'      => ['label' => '1× par jour (6h)',                        'cron' => '0 6 * * *'],
    'weekly'     => ['label' => '1× par semaine (lundi 6h)',              'cron' => '0 6 * * 1'],
];

$conf   = axonaut_conf();
$notice = '';
$error  = '';

// --- Enregistrement du choix de fréquence ------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, réessayez.';
    } else {
        $freq = (string) ($_POST['schedule_freq'] ?? '');
        if (!array_key_exists($freq, $FREQS)) {
            $error = 'Fréquence inconnue.';
        } else {
            $conf['schedule_freq']    = $freq;
            $conf['schedule_updated'] = date('c');
            if (axonaut_save_conf($conf)) {
                $notice = 'Fréquence enregistrée. Pensez à mettre à jour la tâche cron dans le panel LWS.';
            } else {
                $error = 'Impossible d\'enregistrer (permissions serveur sur admin/).';
            }
        }
    }
    $conf = axonaut_conf();
}

// Fréquence actuellement enregistrée (par défaut : off).
$currentFreq = (string) ($conf['schedule_freq'] ?? 'off');
if (!array_key_exists($currentFreq, $FREQS)) {
    $currentFreq = 'off';
}

// Commande cron complète pour la fréquence courante (si active).
$cronExpr = $FREQS[$currentFreq]['cron'];
$cronCmd  = $cronExpr !== null
    ? $cronExpr . '  php ' . __DIR__ . '/axonaut_cron.php'
    : null;

// Informations de statut.
$lastResult      = (string) ($conf['last_result'] ?? '');
$scheduleUpdated = (string) ($conf['schedule_updated'] ?? '');
?>
<?php admin_head('Planification', 'schedule'); ?>
<div class="page-head">
  <h1>🗓️ Planification</h1>
  <p class="lead">Programme une synchronisation Axonaut automatique via une tâche planifiée LWS.</p>
</div>

    <?php if ($notice !== ''): ?>
        <div class="banner banner--ok"><?= h($notice) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="banner banner--err"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Formulaire : choix de la fréquence -->
    <div class="card">
        <h2>Fréquence de synchronisation</h2>
        <form method="post" action="axonaut_schedule.php">
            <?= csrf_field() ?>
            <?php foreach ($FREQS as $slug => $meta): ?>
                <div class="row">
                    <label class="toggle">
                        <input type="radio" name="schedule_freq" value="<?= h($slug) ?>"
                            <?= $slug === $currentFreq ? 'checked' : '' ?>>
                        <span><?= h($meta['label']) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn">Enregistrer la fréquence</button>
        </form>
    </div>

    <!-- Commande cron à copier-coller -->
    <div class="card">
        <h2>Commande cron à coller dans LWS</h2>
        <?php if ($cronCmd !== null): ?>
            <p style="margin-top:0;font-size:14px;color:var(--muted);">
                Pour la fréquence « <?= h($FREQS[$currentFreq]['label']) ?> » :
            </p>
            <div class="cronbox">
                <code id="cronCmd"><?= h($cronCmd) ?></code>
                <button type="button" class="copybtn" id="copyBtn">Copier</button>
            </div>
        <?php else: ?>
            <p class="hint">Synchronisation automatique désactivée : aucune tâche cron n'est nécessaire.
            Supprimez l'éventuelle tâche existante dans votre panel LWS pour arrêter la synchro planifiée.</p>
        <?php endif; ?>
    </div>

    <!-- Statut courant -->
    <div class="card">
        <h2>Statut</h2>
        <div class="status-row">
            <span class="k">Fréquence enregistrée</span>
            <span class="v"><?= h($FREQS[$currentFreq]['label']) ?></span>
        </div>
        <div class="status-row">
            <span class="k">Dernière synchronisation</span>
            <span class="v"><?= $lastResult !== '' ? h($lastResult) : '— jamais exécutée —' ?></span>
        </div>
        <?php if ($scheduleUpdated !== ''): ?>
            <div class="status-row">
                <span class="k">Préférence modifiée le</span>
                <span class="v"><?= h($scheduleUpdated) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Aide : comment activer réellement la tâche -->
    <div class="card help">
        <h2>Comment activer réellement la synchronisation ?</h2>
        <p>Cette page <strong>mémorise votre préférence</strong> et <strong>génère la commande cron</strong>, mais
        elle ne lance rien toute seule. Chez LWS, seule une <strong>tâche cron créée dans le panel</strong> déclenche
        l'exécution planifiée. Procédez ainsi :</p>
        <ol>
            <li>Connectez-vous à votre <strong>panel LWS</strong>.</li>
            <li>Ouvrez <strong>Base de données &amp; PHP → Tâches cron</strong>.</li>
            <li>Créez une nouvelle tâche et collez la commande ci-dessus.</li>
            <li>Enregistrez. La synchro s'exécutera automatiquement à la fréquence choisie.</li>
        </ol>
        <p>Le script exécuté est <code><?= h(__DIR__ . '/axonaut_cron.php') ?></code>.
        Si vous changez de fréquence ici, pensez à <strong>mettre à jour la même tâche cron</strong> côté LWS.</p>
    </div>

<script>
    // Bouton « Copier » : copie la commande cron dans le presse-papiers.
    (function () {
        var btn = document.getElementById('copyBtn');
        var code = document.getElementById('cronCmd');
        if (!btn || !code) { return; }
        btn.addEventListener('click', function () {
            var text = code.textContent;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    btn.textContent = 'Copié !';
                    setTimeout(function () { btn.textContent = 'Copier'; }, 1800);
                });
            } else {
                // Repli pour les navigateurs sans Clipboard API.
                var range = document.createRange();
                range.selectNodeContents(code);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                try { document.execCommand('copy'); btn.textContent = 'Copié !'; } catch (e) {}
                setTimeout(function () { btn.textContent = 'Copier'; }, 1800);
            }
        });
    })();
</script>
<?php admin_foot('schedule'); ?>
