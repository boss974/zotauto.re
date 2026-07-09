<?php
/**
 * offres.php — Gestion des « Offres du moment » affichées sur la page d'accueil.
 * Écrit data/offres.js (window.ZOTAUTO_OFFERS = [...]). Session + CSRF obligatoires.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';

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
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Offres du moment — ZOT AUTO Admin</title>
<style>
  :root { --blue:#1753e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:820px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  .top .sp { flex:1; }
  h1 { font-size:1.55rem; margin:.1em 0 .2em; }
  .lead { color:#5b6376; margin:0 0 22px; font-size:.95rem; }
  .banner { border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:.92rem; }
  .banner--ok { background:#e7f6ec; border:1px solid #b6e2c4; color:var(--ok); }
  .banner--err { background:#fdecec; border:1px solid #f5b5b5; color:#b42318; }
  .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px; margin-bottom:18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .card.on { border-color:#bcd0ff; }
  .card__head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
  .card__num { width:28px; height:28px; border-radius:8px; background:var(--blue); color:#fff; display:grid; place-items:center; font-weight:800; font-size:.9rem; }
  .card__head h2 { font-size:1.05rem; margin:0; flex:1; }
  .row { margin-bottom:12px; }
  .row label { display:block; font-weight:600; font-size:.82rem; margin-bottom:4px; color:#3a4256; }
  .row input[type=text], .row textarea { width:100%; padding:9px 11px; border:1px solid var(--line); border-radius:9px; font:inherit; background:#fff; }
  .row textarea { resize:vertical; min-height:60px; }
  .row .hint { font-size:.76rem; color:#8a93a6; margin-top:3px; }
  .cols { display:flex; gap:12px; flex-wrap:wrap; }
  .cols .row { flex:1; min-width:180px; }
  .toggles { display:flex; gap:18px; flex-wrap:wrap; }
  .toggle { display:inline-flex; align-items:center; gap:7px; font-size:.88rem; font-weight:600; cursor:pointer; }
  .toggle input { width:18px; height:18px; accent-color:var(--blue); }
  .actions { position:sticky; bottom:0; background:#f4f6fb; padding:16px 0; margin-top:6px; }
  .btn { display:inline-flex; align-items:center; gap:8px; background:var(--blue); color:#fff; border:none; padding:13px 26px; border-radius:11px; font:inherit; font-weight:700; cursor:pointer; box-shadow:0 6px 18px rgba(23,83,224,.28); }
  .btn:hover { filter:brightness(1.05); }
  .note { font-size:.82rem; color:#5b6376; margin-top:10px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <strong style="font-size:1.05rem">🛠️ ZOT AUTO — Admin</strong>
    <span class="sp"></span>
    <a href="dashboard.php">← Tableau de bord</a>
    <a href="../index.html#offres" target="_blank">🌐 Voir sur le site</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>📣 Offres du moment</h1>
  <p class="lead">Ces cartes s'affichent sur la page d'accueil, dans la section « En ce moment chez ZOT AUTO ». Remplis jusqu'à <?= MAX_SLOTS ?> offres, coche « Afficher » pour les publier, puis clique sur <strong>Publier</strong>.</p>

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
</div>
</body>
</html>
