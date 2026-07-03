<?php
/**
 * save.php — Publication automatique du catalogue.
 * Reçoit le contenu texte de data/catalogue.js (window.ZOTAUTO = {...};),
 * le valide (JSON + clés products/services), puis l'écrit de façon atomique
 * dans ../data/catalogue.js. Session + CSRF obligatoires.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Méthode ------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail(405, 'Méthode non autorisée.');
}

// --- Authentification ---------------------------------------------------
if (empty($_SESSION['admin_ok'])) {
    fail(403, 'Non authentifié.');
}

// --- CSRF ---------------------------------------------------------------
if (!csrf_check()) {
    fail(403, 'Jeton CSRF invalide.');
}

// --- Récupération + limite de taille -----------------------------------
$content = $_POST['catalogue'] ?? '';
if (!is_string($content) || $content === '') {
    fail(400, 'Contenu manquant.');
}
if (strlen($content) > 5 * 1024 * 1024) {
    fail(413, 'Fichier trop volumineux (max 5 Mo).');
}

// --- Validation : extraction du JSON -----------------------------------
$anchor = strpos($content, 'window.ZOTAUTO');
if ($anchor === false) {
    fail(400, 'En-tête window.ZOTAUTO introuvable.');
}
$start = strpos($content, '{', $anchor);
$end   = strrpos($content, '}');
if ($start === false || $end === false || $end <= $start) {
    fail(400, 'Bloc JSON introuvable.');
}
$json = substr($content, $start, $end - $start + 1);

$data = json_decode($json, true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    fail(400, 'JSON invalide : ' . json_last_error_msg());
}
if (!isset($data['products']) || !is_array($data['products'])
    || !isset($data['services']) || !is_array($data['services'])) {
    fail(400, 'Structure invalide : clés products et services (tableaux) requises.');
}

// --- Écriture atomique --------------------------------------------------
$dataDir = __DIR__ . '/../data';
$target  = $dataDir . '/catalogue.js';

if (!is_dir($dataDir)) {
    fail(500, 'Dossier data introuvable sur le serveur.');
}
if (!is_writable($dataDir)) {
    fail(500, 'Dossier data non inscriptible (permissions serveur).');
}

$tmp = @tempnam($dataDir, 'cat_');
if ($tmp === false) {
    fail(500, 'Impossible de créer le fichier temporaire.');
}

if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
    @unlink($tmp);
    fail(500, 'Échec de l\'écriture temporaire.');
}
@chmod($tmp, 0644);

if (!@rename($tmp, $target)) {
    @unlink($tmp);
    fail(500, 'Échec du renommage (permissions sur catalogue.js).');
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
