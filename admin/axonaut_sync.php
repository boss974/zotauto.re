<?php
/**
 * axonaut_sync.php — Lance une synchronisation Axonaut → catalogue du site.
 * POST uniquement, session admin + CSRF obligatoires.
 * Écrit directement data/catalogue.js (= publication automatique).
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function ax_fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ax_fail(405, 'Méthode non autorisée.');
}
if (empty($_SESSION['admin_ok'])) {
    ax_fail(403, 'Non authentifié.');
}
if (!csrf_check()) {
    ax_fail(403, 'Jeton CSRF invalide.');
}

$conf = axonaut_conf();
if (($conf['key'] ?? '') === '') {
    ax_fail(400, 'Aucune clé API Axonaut enregistrée. Renseignez-la d\'abord.');
}

// Le mode peut être forcé par le formulaire, sinon celui enregistré.
$mode = $_POST['mode'] ?? ($conf['mode'] ?? 'stock');
$mode = in_array($mode, ['stock', 'full'], true) ? $mode : 'stock';

$fetch = axonaut_fetch_products((string) $conf['key']);
if (!$fetch['ok']) {
    ax_fail(502, $fetch['error'] ?? 'Échec de récupération Axonaut.');
}

$apply = axonaut_apply($fetch['products'], $mode);
if (!$apply['ok']) {
    ax_fail(500, $apply['error'] ?? 'Échec d\'écriture du catalogue.');
}

// Mémorise le résultat + l'horodatage.
$conf['updated']     = time();
$conf['mode']        = $mode;
$conf['last_result'] = sprintf(
    '%s — %d produits reçus, %d mis à jour%s (total site : %d)',
    date('d/m/Y H:i'),
    count($fetch['products']),
    $apply['updated'],
    $mode === 'full' ? ', ' . $apply['added'] . ' ajoutés' : '',
    $apply['total']
);
axonaut_save_conf($conf);

echo json_encode([
    'ok'       => true,
    'mode'     => $mode,
    'received' => count($fetch['products']),
    'updated'  => $apply['updated'],
    'added'    => $apply['added'],
    'total'    => $apply['total'],
    'message'  => $conf['last_result'],
], JSON_UNESCAPED_UNICODE);
