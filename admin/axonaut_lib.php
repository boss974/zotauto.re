<?php
/**
 * axonaut_lib.php — Fonctions partagées pour la synchronisation Axonaut.
 *
 * Axonaut = logiciel de gestion (facturation + stock). On l'utilise comme
 * SOURCE DE VÉRITÉ du stock : le site zotauto.re reflète le stock d'Axonaut.
 *
 * Sécurité : la clé API est stockée côté serveur uniquement (fichier .axonaut.php,
 * permissions 0600), jamais renvoyée au navigateur en clair.
 */

declare(strict_types=1);

const AXONAUT_CONF_FILE = __DIR__ . '/.axonaut.php';
const AXONAUT_API_BASE  = 'https://axonaut.com/api/v2';
const CATALOGUE_FILE    = __DIR__ . '/../data/catalogue.js';

/** Lit la configuration Axonaut (clé + options). Retourne un tableau. */
function axonaut_conf(): array
{
    if (!is_file(AXONAUT_CONF_FILE)) {
        return ['key' => '', 'mode' => 'stock', 'updated' => 0, 'last_result' => ''];
    }
    $c = @include AXONAUT_CONF_FILE;
    if (!is_array($c)) {
        return ['key' => '', 'mode' => 'stock', 'updated' => 0, 'last_result' => ''];
    }
    return $c + ['key' => '', 'mode' => 'stock', 'updated' => 0, 'last_result' => ''];
}

/** Enregistre la configuration Axonaut de façon atomique (0600). */
function axonaut_save_conf(array $conf): bool
{
    $php = "<?php\n// Généré automatiquement — NE PAS partager (contient la clé API Axonaut).\nreturn "
         . var_export($conf, true) . ";\n";
    $tmp = @tempnam(__DIR__, 'axc_');
    if ($tmp === false) {
        return false;
    }
    if (@file_put_contents($tmp, $php, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, AXONAUT_CONF_FILE)) {
        @unlink($tmp);
        return false;
    }
    @chmod(AXONAUT_CONF_FILE, 0600);
    return true;
}

/**
 * Appelle l'API Axonaut et récupère TOUS les produits (pagination gérée).
 * @return array{ok:bool, products?:array, error?:string, sample?:array}
 */
function axonaut_fetch_products(string $key): array
{
    if ($key === '') {
        return ['ok' => false, 'error' => 'Clé API manquante.'];
    }

    $all  = [];
    $page = 1;
    $guard = 0; // sécurité anti-boucle infinie

    do {
        $guard++;
        $url = AXONAUT_API_BASE . '/products?page=' . $page;
        $res = axonaut_http_get($url, $key);
        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error']];
        }

        $decoded = json_decode($res['body'], true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Réponse Axonaut illisible (JSON invalide).'];
        }

        // Axonaut renvoie soit un tableau direct, soit {data:[...]}.
        $batch = isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : $decoded;
        if (!is_array($batch) || $batch === []) {
            break;
        }

        foreach ($batch as $p) {
            if (is_array($p)) {
                $all[] = $p;
            }
        }

        // On continue tant que la page renvoie un lot "plein" (heuristique).
        $page++;
    } while (count($batch) >= 50 && $guard < 40);

    return [
        'ok'       => true,
        'products' => $all,
        'sample'   => $all[0] ?? [], // 1er produit brut (aide au diagnostic du mapping)
    ];
}

/** GET HTTP vers Axonaut avec la clé, via cURL si dispo sinon file_get_contents. */
function axonaut_http_get(string $url, string $key): array
{
    // --- Voie 1 : cURL ---
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['userApiKey: ' . $key, 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => 'Connexion Axonaut échouée : ' . $err];
        }
        if ($code === 401 || $code === 403) {
            return ['ok' => false, 'error' => 'Clé API refusée par Axonaut (HTTP ' . $code . '). Vérifiez la clé.'];
        }
        if ($code >= 400) {
            return ['ok' => false, 'error' => 'Axonaut a répondu HTTP ' . $code . '.'];
        }
        return ['ok' => true, 'body' => (string) $body];
    }

    // --- Voie 2 : file_get_contents (si allow_url_fopen) ---
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'header'        => "userApiKey: {$key}\r\nAccept: application/json\r\n",
            'timeout'       => 25,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return ['ok' => false, 'error' => 'Connexion Axonaut impossible (cURL et allow_url_fopen indisponibles).'];
    }
    return ['ok' => true, 'body' => $body];
}

/** Premier champ non vide parmi une liste de clés possibles. */
function ax_pick(array $row, array $keys, $default = '')
{
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) {
            return $row[$k];
        }
    }
    return $default;
}

/** Normalise un identifiant (slug) sûr pour le catalogue. */
function ax_slug(string $s): string
{
    $s = strtolower(trim($s));
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($t !== false) {
            $s = $t;
        }
    }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
    $s = trim($s, '-');
    return $s !== '' ? $s : ('p-' . substr(md5($s . microtime()), 0, 8));
}

/** Convertit une quantité Axonaut en statut de stock du site. */
function ax_stock_status($row): string
{
    $qty = ax_pick((array) $row, ['stock', 'quantity', 'stock_quantity', 'real_stock', 'available_stock'], null);
    if ($qty === null) {
        return 'in'; // pas de suivi de stock côté Axonaut → considéré disponible
    }
    return ((float) $qty) > 0 ? 'in' : 'soon';
}

/**
 * Mappe un produit Axonaut brut vers le schéma produit du site.
 * $existing = produit du site déjà présent (même référence) pour préserver
 * les champs éditoriaux (image, description, featured, catégorie…).
 */
function ax_map_product(array $row, ?array $existing): array
{
    $ref   = (string) ax_pick($row, ['product_code', 'reference', 'sku', 'code'], '');
    $name  = (string) ax_pick($row, ['name', 'label', 'title'], 'Produit');
    $price = (float)  ax_pick($row, ['price', 'unit_price', 'price_ht', 'pu_ht'], 0);
    $desc  = (string) ax_pick($row, ['description', 'comments', 'comment', 'details'], '');

    // Base = produit existant si trouvé, sinon valeurs par défaut prudentes.
    $base = $existing ?? [
        'id'          => ax_slug($ref !== '' ? $ref : $name),
        'name'        => $name,
        'brand'       => (string) ax_pick($row, ['brand', 'supplier_name', 'manufacturer'], ''),
        'category'    => 'accessoires',
        'reference'   => $ref,
        'price'       => $price,
        'oldPrice'    => null,
        'stock'       => 'in',
        'featured'    => false,
        'nouveau'     => false,
        'badge'       => '',
        'description' => $desc,
        'image'       => 'assets/brands/logo-zotauto.png',
        'contain'     => true,
        'rating'      => 5,
        'reviews'     => 0,
    ];

    // Champs TOUJOURS rafraîchis depuis Axonaut (prix + stock + réf).
    $base['price']     = $price;
    $base['reference'] = $ref !== '' ? $ref : ($base['reference'] ?? '');
    $base['stock']     = ax_stock_status($row);

    // Nom/description : on remplit si Axonaut a une valeur, sinon on garde l'existant.
    if ($name !== '' && $name !== 'Produit') {
        $base['name'] = $name;
    }
    if ($desc !== '') {
        $base['description'] = $desc;
    }

    return $base;
}

/** Lit le catalogue actuel du site → tableau ['products'=>[], 'services'=>[]]. */
function catalogue_read(): array
{
    $default = ['products' => [], 'services' => []];
    if (!is_file(CATALOGUE_FILE)) {
        return $default;
    }
    $content = (string) @file_get_contents(CATALOGUE_FILE);
    $a = strpos($content, '{');
    $b = strrpos($content, '}');
    if ($a === false || $b === false || $b <= $a) {
        return $default;
    }
    $json = substr($content, $a, $b - $a + 1);
    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['products'])) {
        return $default;
    }
    return $data + $default;
}

/** Écrit le catalogue (window.ZOTAUTO = {...}) de façon atomique. */
function catalogue_write(array $data): array
{
    $dir = dirname(CATALOGUE_FILE);
    if (!is_dir($dir) || !is_writable($dir)) {
        return ['ok' => false, 'error' => 'Dossier data non inscriptible.'];
    }
    $json = json_encode(
        ['products' => array_values($data['products']), 'services' => array_values($data['services'])],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        return ['ok' => false, 'error' => 'Encodage JSON du catalogue échoué.'];
    }
    $header = "/* =========================================================\n"
        . "   ZOT AUTO — CATALOGUE (généré par la synchro Axonaut)\n"
        . "   Dernière synchro : " . date('d/m/Y H:i') . "\n"
        . "   ========================================================= */\n\n"
        . "window.ZOTAUTO = ";
    $content = $header . $json . ";\n";

    $tmp = @tempnam($dir, 'cat_');
    if ($tmp === false) {
        return ['ok' => false, 'error' => 'tempnam impossible.'];
    }
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        @unlink($tmp);
        return ['ok' => false, 'error' => 'Écriture temporaire impossible.'];
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, CATALOGUE_FILE)) {
        @unlink($tmp);
        return ['ok' => false, 'error' => 'Renommage impossible (permissions catalogue.js).'];
    }
    return ['ok' => true];
}

/**
 * Fusionne les produits Axonaut dans le catalogue selon le mode.
 * - mode 'stock' : met à jour prix + stock des produits EXISTANTS (par référence).
 *                  N'ajoute rien, ne supprime rien. (Sûr, non destructif.)
 * - mode 'full'  : le catalogue produit = reflet d'Axonaut (les produits sans
 *                  référence correspondante sont AJOUTÉS ; les services sont gardés).
 *
 * @return array{ok:bool, updated:int, added:int, total:int, error?:string}
 */
function axonaut_apply(array $axProducts, string $mode): array
{
    $cat = catalogue_read();
    $site = $cat['products'];

    // Index des produits du site par référence (normalisée).
    $byRef = [];
    foreach ($site as $i => $p) {
        $ref = strtolower(trim((string) ($p['reference'] ?? '')));
        if ($ref !== '') {
            $byRef[$ref] = $i;
        }
    }

    $updated = 0;
    $added   = 0;

    if ($mode === 'stock') {
        foreach ($axProducts as $row) {
            $ref = strtolower(trim((string) ax_pick($row, ['product_code', 'reference', 'sku', 'code'], '')));
            if ($ref === '' || !isset($byRef[$ref])) {
                continue; // on ne touche qu'aux produits déjà sur le site
            }
            $i = $byRef[$ref];
            $before = $site[$i];
            $site[$i]['price'] = (float) ax_pick($row, ['price', 'unit_price', 'price_ht', 'pu_ht'], $before['price'] ?? 0);
            $site[$i]['stock'] = ax_stock_status($row);
            $updated++;
        }
    } else { // full
        foreach ($axProducts as $row) {
            $ref = strtolower(trim((string) ax_pick($row, ['product_code', 'reference', 'sku', 'code'], '')));
            if ($ref !== '' && isset($byRef[$ref])) {
                $i = $byRef[$ref];
                $site[$i] = ax_map_product($row, $site[$i]);
                $updated++;
            } else {
                $site[] = ax_map_product($row, null);
                $added++;
            }
        }
    }

    $cat['products'] = $site;
    $w = catalogue_write($cat);
    if (!$w['ok']) {
        return ['ok' => false, 'updated' => 0, 'added' => 0, 'total' => 0, 'error' => $w['error']];
    }

    return ['ok' => true, 'updated' => $updated, 'added' => $added, 'total' => count($site)];
}
