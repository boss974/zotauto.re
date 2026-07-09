<?php
/**
 * offres.php — Gestion des « Offres du moment » affichées sur la page d'accueil.
 * Écrit data/offres.js (window.ZOTAUTO_OFFERS = [...]). Session + CSRF obligatoires.
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

const OFFERS_FILE = __DIR__ . '/../data/offres.js';
const MAX_SLOTS   = 4;

/** Lecture des offres existantes depuis data/offres.js. */
function offers_read(): array
{
    if (!is_file(OFFERS_FILE)) { return []; }
    $raw = (string) file_get_contents(OFFERS_FILE);
    $start = strpos($raw, '[');
    $end   = strrpos($raw, ']');
    if ($start === false || $end === false || $end <= $start) { return []; }
    $json = substr($raw, $start, $end - $start + 1);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/** Écriture atomique de data/offres.js. */
function offers_write(array $offers): bool
{
    $js  = "/* Offres du moment — géré depuis /admin/offres.php (ne pas éditer à la main) */\n";
    $js .= 'window.ZOTAUTO_OFFERS = ';
    $js .= json_encode(array_values($offers), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $js .= ";\n";
    $tmp = OFFERS_FILE . '.tmp';
    if (@file_put_contents($tmp, $js, LOCK_EX) === false) { return false; }
    return @rename($tmp, OFFERS_FILE);
}

$notice = '';
$error  = '';

// --- Traitement du formulaire ------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de recharger la page.';
    } else {
        $tags   = $_POST['tag']   ?? [];
        $titles = $_POST['title'] ?? [];
        $texts  = $_POST['text']  ?? [];
        $btns   = $_POST['btn']   ?? [];
        $was    = $_POST['wa']    ?? [];
        $active = $_POST['active'] ?? [];   // active[i] = "1" si coché
        $accent = $_POST['accent'] ?? [];   // accent[i] = "1" si coché

        $offers = [];
        for ($i = 0; $i < MAX_SLOTS; $i++) {
            $title = trim((string) ($titles[$i] ?? ''));
            $text  = trim((string) ($texts[$i] ?? ''));
            if ($title === '' && $text === '') { continue; } // emplacement vide → ignoré
            $offers[] = [
                'active' => !empty($active[$i]),
                'accent' => !empty($accent[$i]),
                'tag'    => mb_substr(trim((string) ($tags[$i] ?? '')), 0, 40),
                'title'  => mb_substr($title, 0, 90),
                'text'   => mb_substr($text, 0, 320),
                'btn'    => mb_substr(trim((string) ($btns[$i] ?? '')), 0, 40) ?: 'Nous contacter',
                'wa'     => mb_substr(trim((string) ($was[$i] ?? '')), 0, 400),
            ];
        }

        if (offers_write($offers)) {
            $notice = count($offers) > 0
                ? '✅ Offres publiées ! Elles sont en ligne sur la page d\'accueil.'
                : '✅ Enregistré. Aucune offre active — la section est masquée sur le site.';
        } else {
            $error = '❌ Impossible d\'écrire le fichier. Vérifie les permissions de /data.';
        }
    }
}

$offers = offers_read();
// Complète jusqu'à MAX_SLOTS pour l'affichage du formulaire
for ($i = count($offers); $i < MAX_SLOTS; $i++) {
    $offers[$i] = ['active' => false, 'accent' => false, 'tag' => '', 'title' => '', 'text' => '', 'btn' => '', 'wa' => ''];
}
?>
<?php admin_head('Offres du moment', 'offres'); ?>
<div class="page-head">
  <h1>📣 Offres du moment</h1>
  <p class="lead">Ces cartes s'affichent sur la page d'accueil. Remplis jusqu'à 4 offres, coche « Afficher », puis Publie.</p>
</div>

  <?php if ($notice): ?><div class="banner banner--ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="banner banner--err"><?= h($error) ?></div><?php endif; ?>

  <form method="post" action="offres.php">
    <?= csrf_field() ?>
    <?php for ($i = 0; $i < MAX_SLOTS; $i++): $o = $offers[$i]; ?>
      <div class="card<?= !empty($o['active']) ? ' on' : '' ?>">
        <div class="card__head">
          <span class="card__num"><?= $i + 1 ?></span>
          <h2>Offre <?= $i + 1 ?></h2>
          <div class="toggles">
            <label class="toggle"><input type="checkbox" name="active[<?= $i ?>]" value="1" <?= !empty($o['active']) ? 'checked' : '' ?>> Afficher</label>
            <label class="toggle"><input type="checkbox" name="accent[<?= $i ?>]" value="1" <?= !empty($o['accent']) ? 'checked' : '' ?>> Mettre en avant</label>
          </div>
        </div>

        <div class="cols">
          <div class="row" style="flex:0 0 200px">
            <label>Étiquette</label>
            <input type="text" name="tag[<?= $i ?>]" value="<?= h((string) $o['tag']) ?>" maxlength="40" placeholder="Ex. Lubrifiants">
          </div>
          <div class="row">
            <label>Titre de l'offre</label>
            <input type="text" name="title[<?= $i ?>]" value="<?= h((string) $o['title']) ?>" maxlength="90" placeholder="Ex. IGOL — gamme haute performance">
          </div>
        </div>

        <div class="row">
          <label>Description</label>
          <textarea name="text[<?= $i ?>]" maxlength="320" placeholder="Quelques phrases sur l'offre…"><?= h((string) $o['text']) ?></textarea>
        </div>

        <div class="cols">
          <div class="row" style="flex:0 0 220px">
            <label>Texte du bouton</label>
            <input type="text" name="btn[<?= $i ?>]" value="<?= h((string) $o['btn']) ?>" maxlength="40" placeholder="Ex. Nous contacter">
          </div>
          <div class="row">
            <label>Message WhatsApp pré-rempli</label>
            <input type="text" name="wa[<?= $i ?>]" value="<?= h((string) $o['wa']) ?>" maxlength="400" placeholder="Bonjour ZOT AUTO, je suis intéressé(e) par…">
            <div class="hint">Le bouton ouvre WhatsApp (0693 05 70 12) avec ce message déjà écrit.</div>
          </div>
        </div>
      </div>
    <?php endfor; ?>

    <div class="actions">
      <button type="submit" class="btn">💾 Publier les offres</button>
      <p class="note">Astuce : laisse une offre vide (sans titre) pour ne pas l'afficher. Décoche « Afficher » pour la mettre en pause sans la supprimer.</p>
    </div>
  </form>
<?php admin_foot('offres'); ?>
