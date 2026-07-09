<?php
/**
 * photos_web.php — Recherche de vraies photos produit sur le web (par référence)
 * et rangement dans une base d'images organisée par catégorie.
 *
 *  - Utilise l'API Google Custom Search (image) : clé + moteur (CX), 100 req/j gratuites.
 *  - Range les images dans ../assets/products/<catégorie>/<slug>.<ext>.
 *  - Construit un index réutilisable ../assets/products/index.json (ta base de données).
 *  - Met la photo trouvée en image par défaut du produit.
 *
 * ⚠️ Les images du web sont souvent protégées : en tant que revendeur, privilégie les
 *    visuels officiels des marques que tu vends et vérifie tes best-sellers.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/axonaut_lib.php';
require __DIR__ . '/ui.php';

// --- Garde d'accès ------------------------------------------------------
if (!file_exists(AUTH_FILE)) { header('Location: setup.php'); exit; }
if (empty($_SESSION['admin_ok'])) { header('Location: login.php'); exit; }
$since = (int) ($_SESSION['admin_since'] ?? 0);
if ($since === 0 || (time() - $since) > ADMIN_SESSION_MAX_IDLE) {
    session_unset(); session_destroy(); header('Location: login.php?timeout=1'); exit;
}
$_SESSION['admin_since'] = time();

const PRODUCTS_DIR = __DIR__ . '/../assets/products';
const GS_FILE      = __DIR__ . '/.gsearch.php';
const WEB_BATCH    = 20;   // photos web par lot (quota Google 100/j gratuit)
const PH_LOGO2     = 'assets/brands/logo-zotauto.png';

/** Le produit a-t-il déjà une vraie photo web rangée ? */
function has_web_photo(array $p): bool
{
    return strpos((string) ($p['image'] ?? ''), 'assets/products/') !== false;
}
/** Produit sans vraie photo (placeholder logo, visuel généré, ou vide) ? */
function needs_web_photo(array $p): bool
{
    $img = (string) ($p['image'] ?? '');
    return $img === ''
        || strpos($img, PH_LOGO2) !== false
        || strpos($img, 'assets/generated/') !== false; // visuel filigrané = remplaçable
}

/** Lit/écrit la config sources d'images (Pixabay gratuit + Google), privée 0600. */
function gs_read(): array
{
    $def = ['key' => '', 'cx' => '', 'pxkey' => ''];
    if (!is_file(GS_FILE)) { return $def; }
    $c = @include GS_FILE;
    return is_array($c) ? ($c + $def) : $def;
}
function gs_write(array $c): bool
{
    $php = "<?php\n// Sources images (Pixabay/Google) — PRIVÉ.\nreturn " . var_export($c, true) . ";\n";
    $tmp = @tempnam(__DIR__, 'gs_');
    if ($tmp === false || @file_put_contents($tmp, $php, LOCK_EX) === false) { return false; }
    @chmod($tmp, 0600);
    if (!@rename($tmp, GS_FILE)) { @unlink($tmp); return false; }
    @chmod(GS_FILE, 0600);
    return true;
}

/** Recherche image PIXABAY (gratuit, usage commercial autorisé, sans attribution).
 *  $page permet de varier les résultats (pagination) ; $pick choisit le rang du résultat. */
function pixabay_search_image(string $key, string $query, int $page = 1, int $pick = 0): array
{
    if (!function_exists('curl_init')) { return ['ok' => false, 'error' => 'cURL indisponible.']; }
    $url = 'https://pixabay.com/api/?' . http_build_query([
        'key' => $key, 'q' => $query, 'image_type' => 'photo', 'per_page' => 20,
        'page' => max(1, $page), 'safesearch' => 'true', 'lang' => 'fr', 'order' => 'popular',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) { return ['ok' => false, 'error' => 'Connexion Pixabay échouée.']; }
    if ($code === 400 || $code === 401) { return ['ok' => false, 'error' => 'Clé Pixabay invalide.', 'stop' => true]; }
    if ($code === 429) { return ['ok' => false, 'error' => 'Quota Pixabay atteint (réessayez plus tard).', 'stop' => true]; }
    $j = json_decode($body, true);
    if (!is_array($j) || empty($j['hits'])) { return ['ok' => false, 'error' => 'Aucune image Pixabay.']; }
    $hits = $j['hits'];
    $hit  = $hits[$pick % count($hits)];
    $u = $hit['largeImageURL'] ?? ($hit['webformatURL'] ?? '');
    if ($u === '') { return ['ok' => false, 'error' => 'URL image Pixabay absente.']; }
    return ['ok' => true, 'url' => $u];
}

/** Requête image Google Custom Search → URL de la meilleure image, ou ''. */
function gs_search_image(string $key, string $cx, string $query): array
{
    if (!function_exists('curl_init')) { return ['ok' => false, 'error' => 'cURL indisponible.']; }
    $url = 'https://www.googleapis.com/customsearch/v1?' . http_build_query([
        'key' => $key, 'cx' => $cx, 'q' => $query, 'searchType' => 'image',
        'num' => 1, 'safe' => 'active', 'imgSize' => 'medium',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) { return ['ok' => false, 'error' => 'Connexion Google échouée.']; }
    $j = json_decode($body, true);
    if ($code === 403) { return ['ok' => false, 'error' => 'Quota Google dépassé ou clé/API non activée (403).', 'stop' => true]; }
    if ($code >= 400 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? $j['error']['message'] : ('HTTP ' . $code);
        return ['ok' => false, 'error' => 'Google : ' . $msg, 'stop' => $code === 400];
    }
    if (empty($j['items'][0]['link'])) { return ['ok' => false, 'error' => 'Aucune image trouvée.']; }
    return ['ok' => true, 'url' => (string) $j['items'][0]['link']];
}

/** Télécharge une image, valide que c'est bien une image, renvoie [data, ext]. */
function download_image(string $url): array
{
    if (!function_exists('curl_init')) { return ['ok' => false]; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ZotAutoBot/1.0)',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($data === false || $code >= 400 || strlen($data) < 800) { return ['ok' => false]; }
    $ext = 'jpg';
    if (stripos($type, 'png') !== false) { $ext = 'png'; }
    elseif (stripos($type, 'webp') !== false) { $ext = 'webp'; }
    elseif (stripos($type, 'gif') !== false) { $ext = 'gif'; }
    elseif (stripos($type, 'jpeg') === false && stripos($type, 'jpg') === false) { return ['ok' => false]; }
    return ['ok' => true, 'data' => $data, 'ext' => $ext];
}

/** Met à jour l'index (base de données) des photos rangées. */
function products_index_add(string $rel, array $p): void
{
    $file = PRODUCTS_DIR . '/index.json';
    $idx  = is_file($file) ? (json_decode((string) @file_get_contents($file), true) ?: []) : [];
    $idx[(string) ($p['id'] ?? '')] = [
        'ref'   => (string) ($p['reference'] ?? ''),
        'name'  => (string) ($p['name'] ?? ''),
        'cat'   => (string) ($p['category'] ?? ''),
        'file'  => $rel,
        'date'  => date('c'),
    ];
    @file_put_contents($file, json_encode($idx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// --- État ---------------------------------------------------------------
$cat   = catalogue_read();
$prod  = $cat['products'];
$total = count($prod);
$done  = 0; $todo = 0;
foreach ($prod as $p) { if (has_web_photo($p)) { $done++; } elseif (needs_web_photo($p)) { $todo++; } }
$gs = gs_read();
$hasPixabay = ($gs['pxkey'] ?? '') !== '';
$hasGoogle  = ($gs['key'] ?? '') !== '' && ($gs['cx'] ?? '') !== '';
$hasKey = $hasPixabay || $hasGoogle;
$notice = ''; $error = '';

// --- Actions ------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée.';
    } elseif (($_POST['action'] ?? '') === 'save_gs') {
        $k  = trim((string) ($_POST['gkey'] ?? ''));  if ($k === '') { $k = (string) $gs['key']; }
        $cx = trim((string) ($_POST['gcx'] ?? ''));   if ($cx === '') { $cx = (string) $gs['cx']; }
        $px = trim((string) ($_POST['pxkey'] ?? ''));  if ($px === '') { $px = (string) $gs['pxkey']; }
        if ($k === '' && $cx === '' && $px === '') { $error = 'Renseignez au moins la clé Pixabay (gratuite) ou Google + CX.'; }
        elseif (gs_write(['key' => $k, 'cx' => $cx, 'pxkey' => $px])) { $notice = 'Configuration enregistrée.'; $gs = gs_read(); }
        else { $error = 'Enregistrement impossible (permissions).'; }
    } elseif (($_POST['action'] ?? '') === 'fetch') {
        if (!$hasKey) {
            $error = 'Configure d\'abord la clé Google + CX.';
        } else {
            @set_time_limit(0);
            $onlyPriced = !empty($_POST['only_priced']);
            $order = array_keys($prod);
            usort($order, function ($a, $b) use ($prod) {
                return (float) ($prod[$b]['price'] ?? 0) <=> (float) ($prod[$a]['price'] ?? 0);
            });
            $n = 0; $fail = 0; $lastErr = '';
            foreach ($order as $i) {
                if ($n >= WEB_BATCH) { break; }
                $p = $prod[$i];
                if (has_web_photo($p) || !needs_web_photo($p)) { continue; }
                if ($onlyPriced && (float) ($p['price'] ?? 0) <= 0) { continue; }
                // Pixabay (gratuit) : requête par CATÉGORIE en français (fiable, toujours
                // pertinente), avec variété via la pagination — le nom exact des produits est
                // trop ambigu ("servante" → tasse de thé). Google reste précis (marque+nom+réf).
                if ($hasPixabay) {
                    $catTerms = [
                        'huiles'      => ['huile moteur', 'bidon huile moteur', 'lubrifiant automobile', 'vidange moteur'],
                        'detailing'   => ['lavage automobile', 'nettoyage voiture', 'polish carrosserie', 'detailing voiture'],
                        'outillage'   => ['outils atelier', 'boîte à outils', 'outillage mécanique', 'clé à outils'],
                        'accessoires' => ['pièces automobiles', 'garage automobile', 'atelier mécanique auto', 'moteur voiture'],
                    ][$p['category'] ?? ''] ?? ['pièce automobile', 'garage automobile'];
                    $k    = $done + $n;                       // compteur global (continuité entre lots)
                    $q    = $catTerms[$k % count($catTerms)];
                    $page = 1 + intdiv($k, count($catTerms)) % 8;
                    $pick = $k % 20;
                    $s = pixabay_search_image((string) $gs['pxkey'], $q, $page, $pick);
                    if (!$s['ok'] && empty($s['stop'])) {
                        $s = pixabay_search_image((string) $gs['pxkey'], $catTerms[0], 1, $k % 10);
                    }
                } else {
                    $q = trim(($p['brand'] ?? '') . ' ' . ($p['name'] ?? '') . ' ' . ($p['reference'] ?? ''));
                    $s = gs_search_image((string) $gs['key'], (string) $gs['cx'], $q);
                }
                if (!$s['ok']) { $fail++; $lastErr = $s['error']; if (!empty($s['stop'])) { break; } continue; }
                $dl = download_image($s['url']);
                if (!$dl['ok']) { $fail++; $lastErr = 'Téléchargement image échoué.'; continue; }
                $catDir = PRODUCTS_DIR . '/' . ($p['category'] ?? 'divers');
                if (!is_dir($catDir) && !@mkdir($catDir, 0755, true)) { $error = 'Dossier non créable.'; break; }
                $slug = ax_slug((string) ($p['id'] ?? $p['reference'] ?? $p['name'] ?? 'p'));
                $rel  = 'assets/products/' . ($p['category'] ?? 'divers') . '/' . $slug . '.' . $dl['ext'];
                if (@file_put_contents(__DIR__ . '/../' . $rel, $dl['data']) !== false) {
                    $prod[$i]['image']   = $rel;
                    $prod[$i]['contain'] = false;
                    products_index_add($rel, $p);
                    $n++;
                } else { $fail++; }
            }
            $cat['products'] = $prod;
            $w = catalogue_write($cat);
            if (!$w['ok']) { $error = $w['error'] ?? 'Écriture catalogue impossible.'; }
            else { $notice = $n . ' photo(s) web rangée(s) et publiée(s).' . ($fail ? ' (' . $fail . ' non trouvée(s)/échec — dernier : ' . h($lastErr) . ')' : ''); }
        }
    }
    $cat = catalogue_read(); $prod = $cat['products']; $total = count($prod);
    $done = 0; $todo = 0;
    foreach ($prod as $p) { if (has_web_photo($p)) { $done++; } elseif (needs_web_photo($p)) { $todo++; } }
    $gs = gs_read(); $hasKey = $gs['key'] !== '' && $gs['cx'] !== '';
}
?>
<?php admin_head('Photos web', 'photosweb'); ?>
<style>
  .big { font-size:2rem; font-weight:800; }
  .bar { height:12px; background:#eef1fa; border-radius:99px; overflow:hidden; margin:10px 0; } .bar > i { display:block; height:100%; background:var(--teal); }
</style>
<div class="page-head">
  <h1>🌐 Photos web</h1>
  <p class="lead">Cherche de vraies photos par référence, rangées par catégorie, à associer à tes produits.</p>
</div>
  <p class="sub">Cherche une vraie photo pour chaque produit et la range dans une base
     organisée par catégorie (<code>assets/products/…</code>).</p>

  <?php if ($notice !== ''): ?><div class="msg msg--ok"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="msg msg--err"><?= h($error) ?></div><?php endif; ?>

  <div class="msg msg--warn">
    ℹ️ <strong>Pixabay = images gratuites &amp; légales</strong> (usage commercial, sans attribution), mais
    <strong>thématiques</strong> : elles illustrent le type de produit, pas la référence exacte.
    L'option Google trouve des photos plus précises mais souvent <strong>sous copyright</strong> (à réserver aux
    visuels officiels des marques que tu vends). Dans tous les cas, <strong>vérifie tes best-sellers</strong>.
  </div>

  <div class="card">
    <h2>1️⃣ Source d'images</h2>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_gs">

      <p style="margin:0 0 4px;font-weight:700;color:var(--teal)">🆓 Pixabay <?= $hasPixabay ? '✅ (active)' : '(recommandé, gratuit)' ?></p>
      <ol class="steps" style="margin-top:4px">
        <li>Crée un compte gratuit sur <strong>pixabay.com</strong> (sans carte bancaire).</li>
        <li>Va sur <strong>pixabay.com/api/docs</strong> → copie ta <strong>clé API</strong>.</li>
      </ol>
      <label for="pxkey">Clé API Pixabay <?= $hasPixabay ? '(enregistrée — laisser vide pour garder)' : '' ?></label>
      <input type="password" id="pxkey" name="pxkey" placeholder="00000000-xxxxxxxxxxxxxxxxxxxx" autocomplete="new-password">
      <p class="hint">Images <strong>gratuites pour usage commercial, sans attribution</strong> (thématiques, pas la pièce exacte).</p>

      <details style="margin:14px 0 6px">
        <summary style="cursor:pointer;font-weight:600;color:#5b6376">Option avancée : Google Custom Search (photos plus précises, ~5 $/1000)</summary>
        <label for="gkey" style="margin-top:10px">Clé API Google</label>
        <input type="password" id="gkey" name="gkey" placeholder="AIza..." autocomplete="new-password">
        <label for="gcx">ID moteur (CX)</label>
        <input type="text" id="gcx" name="gcx" placeholder="a1b2c3d4e5..." value="<?= h($gs['cx']) ?>">
        <p class="hint">console.cloud.google.com (Custom Search API) + programmablesearchengine.google.com (moteur images).</p>
      </details>

      <p style="margin-top:12px"><button class="btn btn--primary" type="submit">💾 Enregistrer</button></p>
    </form>
    <p class="hint">🔒 Clés stockées en privé (<code>.gsearch.php</code>, 0600). Si les deux sont renseignées, <strong>Pixabay (gratuit) est utilisé en priorité</strong>.</p>
  </div>

  <div class="card">
    <h2>2️⃣ Récupérer les photos</h2>
    <div><span class="big"><?= (int) $done ?></span> avec photo web · <?= (int) $todo ?> à traiter · <?= (int) $total ?> total</div>
    <div class="bar"><i style="width:<?= $total ? (int) round($done / $total * 100) : 0 ?>%"></i></div>
    <?php if ($hasKey): ?>
      <form method="post" id="webForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="fetch">
        <label style="display:flex;align-items:center;gap:8px;font-weight:500;margin:6px 0 12px"><input type="checkbox" name="only_priced" value="1" checked> Seulement les produits avec un prix (recommandé)</label>
        <button class="btn btn--go" id="webBtn" type="submit">🌐 Récupérer le prochain lot (<?= WEB_BATCH ?>)</button>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-weight:500"><input type="checkbox" id="webAuto"> Enchaîner auto</label>
      </form>
      <p class="hint" style="margin-top:10px">Les produits <strong>avec prix</strong> sont traités en premier. Range dans <code>assets/products/&lt;catégorie&gt;/</code> et met à jour l'index <code>index.json</code> (ta base de données).</p>
    <?php else: ?>
      <p class="hint">➡️ Configure la clé Google + CX ci-dessus pour activer.</p>
    <?php endif; ?>
  </div>
<script>
  (function(){var f=document.getElementById('webForm'),a=document.getElementById('webAuto');if(!f||!a)return;
   if(sessionStorage.getItem('zot_web_auto')==='1'){a.checked=true;setTimeout(function(){f.submit();},800);}
   f.addEventListener('submit',function(){sessionStorage.setItem('zot_web_auto',a.checked?'1':'0');var b=document.getElementById('webBtn');if(b){b.disabled=true;b.textContent='⏳ Recherche…';}});}());
</script>
<?php admin_foot('photosweb'); ?>
