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

// Injecte un petit bandeau "Se déconnecter" juste après <body ...>.
$logoutBar = '<div style="position:fixed;top:0;right:0;z-index:99999;background:#1a2033;'
    . 'color:#fff;font:600 .75rem/1 -apple-system,Segoe UI,sans-serif;'
    . 'padding:8px 14px;border-bottom-left-radius:10px;display:flex;align-items:center;gap:10px;">'
    . '<span>Connecté</span>'
    . '<a href="axonaut.php" style="color:#7dffa1;text-decoration:none;">🔄 Axonaut</a>'
    . '<a href="logout.php" style="color:#ffcb00;text-decoration:none;">Se déconnecter</a>'
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
