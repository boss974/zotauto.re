<?php
/**
 * axonaut_cron.php — Synchronisation Axonaut planifiée (CLI uniquement).
 * À appeler par une tâche cron LWS :  php /chemin/admin/axonaut_cron.php
 * Refuse tout accès web (pas d'authentification en ligne de commande).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande (cron).\n");
}

require __DIR__ . '/axonaut_lib.php';

$conf = axonaut_conf();
if (($conf['key'] ?? '') === '') {
    fwrite(STDERR, "Aucune clé API Axonaut enregistrée. Configurez d'abord admin/axonaut.php\n");
    exit(1);
}

$mode  = in_array($conf['mode'] ?? 'stock', ['stock', 'full'], true) ? $conf['mode'] : 'stock';
$fetch = axonaut_fetch_products((string) $conf['key']);
if (!$fetch['ok']) {
    fwrite(STDERR, 'Échec Axonaut : ' . ($fetch['error'] ?? '?') . "\n");
    exit(1);
}

$apply = axonaut_apply($fetch['products'], $mode);
if (!$apply['ok']) {
    fwrite(STDERR, 'Échec écriture catalogue : ' . ($apply['error'] ?? '?') . "\n");
    exit(1);
}

$conf['updated']     = time();
$conf['last_result'] = sprintf(
    '%s (cron) — %d reçus, %d MAJ%s, total %d',
    date('d/m/Y H:i'),
    count($fetch['products']),
    $apply['updated'],
    $mode === 'full' ? ', ' . $apply['added'] . ' ajoutés' : '',
    $apply['total']
);
axonaut_save_conf($conf);

echo $conf['last_result'] . "\n";
exit(0);
