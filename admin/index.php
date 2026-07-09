<?php
/**
 * index.php — Garde d'accès à l'éditeur de catalogue.
 * Redirige vers setup/login selon l'état, vérifie l'expiration de session,
 * puis sert editor.html.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';

if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}

if (empty($_SESSION['admin_ok'])) {
    header('Location: login.php');
    exit;
}

// Expiration après 8h d'inactivité.
$since = (int)($_SESSION['admin_since'] ?? 0);
if ($since === 0 || (time() - $since) > ADMIN_SESSION_MAX_IDLE) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}
// Glisse la fenêtre d'inactivité à chaque accès.
$_SESSION['admin_since'] = time();

$editorPath = __DIR__ . '/editor.html';
if (!is_file($editorPath)) {
    http_response_code(500);
    echo 'Éditeur introuvable.';
    exit;
}

$html = file_get_contents($editorPath);

// Anti-cache : force le rechargement du VRAI catalogue en ligne à chaque ouverture
// de l'éditeur (sinon le navigateur peut servir une vieille copie en cache, et un
// "Publier" écraserait le catalogue réel avec des données périmées).
$html = preg_replace(
    '/<script\s+src=["\']\.\.\/data\/catalogue\.js["\']><\/script>/i',
    '<script src="../data/catalogue.js?t=' . time() . '"></script>',
    $html,
    1
);

// Injecte une barre de navigation admin homogène (grande, lisible) en haut de l'éditeur.
$lk = 'color:#eaf0ff;text-decoration:none;display:inline-flex;align-items:center;gap:7px;'
    . 'font-weight:600;font-size:.92rem;padding:8px 12px;border-radius:9px;transition:background .15s;white-space:nowrap;';
$logoutBar = '<style>'
    . '.zadminbar a:hover{background:rgba(255,255,255,.14);}'
    . '.zadminbar .em{font-size:1.15rem;line-height:1;}'
    . 'body{padding-top:56px !important;}'
    . '.top{top:56px !important;}'
    . '@media(max-width:760px){.zadminbar{overflow-x:auto;} .zadminbar__lbl{display:none;}}'
    . '</style>'
    . '<div class="zadminbar" style="position:fixed;top:0;left:0;right:0;z-index:99999;'
    . 'background:linear-gradient(90deg,#1753e0,#0f3fb5);box-shadow:0 2px 12px rgba(15,25,60,.25);'
    . 'font-family:-apple-system,Segoe UI,Roboto,sans-serif;'
    . 'height:56px;display:flex;align-items:center;gap:4px;padding:0 14px;">'
    . '<a href="dashboard.php" style="' . $lk . 'font-weight:800;font-size:1rem;color:#fff;"><span class="em">🛠️</span> ZOT AUTO</a>'
    . '<span style="flex:1"></span>'
    . '<a href="dashboard.php" style="' . $lk . '"><span class="em">📊</span> <span class="zadminbar__lbl">Tableau de bord</span></a>'
    . '<a href="axonaut.php" style="' . $lk . '"><span class="em">🔄</span> <span class="zadminbar__lbl">Axonaut</span></a>'
    . '<a href="axonaut_pricing.php" style="' . $lk . '"><span class="em">💶</span> <span class="zadminbar__lbl">Tarifs</span></a>'
    . '<a href="axonaut_photos.php" style="' . $lk . '"><span class="em">🖼️</span> <span class="zadminbar__lbl">Photos</span></a>'
    . '<a href="aide.php" style="' . $lk . '"><span class="em">❓</span> <span class="zadminbar__lbl">Aide</span></a>'
    . '<a href="logout.php" style="' . $lk . 'color:#ffd75e;font-weight:700;"><span class="em">🔒</span> <span class="zadminbar__lbl">Déconnexion</span></a>'
    . '</div>'
    . '<script>window.__ZOTADMIN = { csrf: "' . h(csrf_token()) . '", saveUrl: "save.php" };</script>';

if (preg_match('/<body[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
    $insertAt = $m[0][1] + strlen($m[0][0]);
    $html = substr($html, 0, $insertAt) . $logoutBar . substr($html, $insertAt);
} else {
    $html = $logoutBar . $html;
}

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
echo $html;
