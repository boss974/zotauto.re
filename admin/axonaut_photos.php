<?php
/**
 * axonaut_photos.php — Photos produits (2 niveaux).
 *  1) Visuel filigrané généré (SVG) — gratuit, instantané, marche sans clé.
 *  2) Vraies photos par IA (OpenAI Images) — nécessite une clé + budget.
 * Les images sont de vrais fichiers dans ../assets/generated/ et le champ `image`
 * du produit est mis à jour → visibles sur le site ET dans l'éditeur admin.
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

const GEN_DIR      = __DIR__ . '/../assets/generated';
const PH_LOGO      = 'assets/brands/logo-zotauto.png';
const SVG_BATCH    = 250;   // visuels SVG par lot
const AI_BATCH     = 6;     // photos IA par lot (coût + temps maîtrisés)
const FREE_BATCH   = 8;     // photos IA GRATUITES (Pollinations) par lot
const OPENAI_FILE  = __DIR__ . '/.openai.php';
const AI_COST_UNIT = 0.04;  // estimation $ par image (gpt-image-1, indicatif)

/** Un produit a-t-il besoin d'un visuel ? (pas de vraie photo importée) */
function needs_photo(array $p): bool
{
    $img = (string) ($p['image'] ?? '');
    return $img === '' || strpos($img, PH_LOGO) !== false;
}

/** Un produit a-t-il déjà une vraie photo IA (.png générée) ? */
function has_ai_photo(array $p): bool
{
    $img = (string) ($p['image'] ?? '');
    return strpos($img, 'assets/generated/') !== false && substr($img, -4) === '.png';
}

function svg_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/** SVG filigrané ZOT AUTO d'un produit. */
function photo_svg(array $p): string
{
    $cats = [
        'detailing'   => ['#7c3aed', '✨'],
        'outillage'   => ['#ea580c', '🔧'],
        'huiles'      => ['#16a34a', '🛢️'],
        'accessoires' => ['#2a52e0', '🚗'],
    ];
    $cv   = $cats[$p['category'] ?? ''] ?? ['#2a52e0', '📦'];
    $name = svg_esc(mb_substr((string) ($p['name'] ?? ''), 0, 46));
    return '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
        . '<rect width="400" height="300" fill="' . $cv[0] . '"/>'
        . '<rect width="400" height="300" fill="#000" opacity="0.06"/>'
        . '<text x="200" y="165" font-size="46" fill="#fff" opacity="0.13" font-weight="bold" font-family="Arial,sans-serif" text-anchor="middle" transform="rotate(-20 200 165)">ZOT AUTO</text>'
        . '<text x="200" y="138" font-size="92" text-anchor="middle" dominant-baseline="central">' . $cv[1] . '</text>'
        . '<text x="200" y="236" font-size="19" fill="#fff" font-family="Arial,sans-serif" text-anchor="middle">' . $name . '</text>'
        . '<text x="390" y="290" font-size="13" fill="#fff" opacity="0.8" font-weight="bold" font-family="Arial,sans-serif" text-anchor="end">zotauto.re</text>'
        . '</svg>';
}

/** Lit / écrit la clé OpenAI (0600, privée). */
function openai_key_read(): string
{
    if (!is_file(OPENAI_FILE)) { return ''; }
    $c = @include OPENAI_FILE;
    return (is_array($c) && !empty($c['key'])) ? (string) $c['key'] : '';
}
function openai_key_write(string $key): bool
{
    $php = "<?php\n// Clé OpenAI — PRIVÉE (ne jamais exposer).\nreturn " . var_export(['key' => $key], true) . ";\n";
    $tmp = @tempnam(__DIR__, 'oai_');
    if ($tmp === false || @file_put_contents($tmp, $php, LOCK_EX) === false) { return false; }
    @chmod($tmp, 0600);
    if (!@rename($tmp, OPENAI_FILE)) { @unlink($tmp); return false; }
    @chmod(OPENAI_FILE, 0600);
    return true;
}

/** Applique un filigrane texte « ZOT AUTO » sur une image PNG (si GD dispo). */
function watermark_png(string $pngData): string
{
    if (!function_exists('imagecreatefromstring')) { return $pngData; }
    $im = @imagecreatefromstring($pngData);
    if ($im === false) { return $pngData; }
    $w = imagesx($im); $h = imagesy($im);
    imagealphablending($im, true);
    $white = imagecolorallocatealpha($im, 255, 255, 255, 90);
    $txt = 'ZOT AUTO';
    // Filigrane en bas à droite (police GD interne 5).
    $fw = imagefontwidth(5) * strlen($txt);
    imagestring($im, 5, $w - $fw - 12, $h - 22, $txt, $white);
    ob_start();
    imagepng($im);
    $out = ob_get_clean();
    imagedestroy($im);
    return $out !== false ? $out : $pngData;
}

/**
 * Génère une vraie photo produit via l'API OpenAI Images.
 * @return array{ok:bool, png?:string, error?:string}
 */
function ai_generate_photo(string $key, array $p): array
{
    $name = trim((string) ($p['name'] ?? 'pièce auto'));
    $cat  = (string) ($p['category'] ?? '');
    $catFr = ['detailing' => 'produit de detailing / nettoyage auto', 'outillage' => 'outil / équipement d\'atelier automobile', 'huiles' => 'huile / lubrifiant automobile', 'accessoires' => 'accessoire / pièce automobile'][$cat] ?? 'pièce automobile';
    $prompt = "Photo produit professionnelle e-commerce d'un(e) " . $catFr . " : « " . $name
        . " ». Objet centré sur fond blanc studio épuré, éclairage doux, haute définition, réaliste, sans texte ni logo.";

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL indisponible sur le serveur.'];
    }
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['model' => 'gpt-image-1', 'prompt' => $prompt, 'size' => '1024x1024', 'n' => 1]),
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) { return ['ok' => false, 'error' => 'Connexion OpenAI échouée : ' . $err]; }
    $j = json_decode($body, true);
    if ($code === 401) { return ['ok' => false, 'error' => 'Clé OpenAI refusée (401).']; }
    if ($code >= 400 || !is_array($j)) {
        $msg = is_array($j) && isset($j['error']['message']) ? $j['error']['message'] : ('HTTP ' . $code);
        return ['ok' => false, 'error' => 'OpenAI : ' . $msg];
    }
    $png = null;
    if (!empty($j['data'][0]['b64_json'])) {
        $png = base64_decode($j['data'][0]['b64_json']);
    } elseif (!empty($j['data'][0]['url'])) {
        $png = @file_get_contents($j['data'][0]['url']);
    }
    if (!$png) { return ['ok' => false, 'error' => 'Image OpenAI illisible.']; }
    return ['ok' => true, 'png' => watermark_png($png)];
}

/**
 * Génère une vraie photo produit via Pollinations.ai — 100% GRATUIT, sans clé API.
 * @return array{ok:bool, png?:string, error?:string}
 */
function ai_generate_photo_free(array $p): array
{
    $name = trim((string) ($p['name'] ?? 'pièce auto'));
    $cat  = (string) ($p['category'] ?? '');
    $catEn = [
        'detailing'   => 'car detailing / cleaning product',
        'outillage'   => 'automotive workshop tool / equipment',
        'huiles'      => 'automotive motor oil / lubricant bottle',
        'accessoires' => 'automotive accessory / car part',
    ][$cat] ?? 'automotive car part';
    // Prompt en anglais (meilleurs résultats sur Pollinations/Flux).
    $prompt = 'Professional e-commerce product photo of a ' . $catEn . ': ' . $name
        . '. Centered object on clean white studio background, soft lighting, high detail, photorealistic, no text, no watermark, no logo.';
    $url = 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt)
        . '?width=1024&height=1024&nologo=true&model=flux&seed=' . abs(crc32((string) ($p['id'] ?? $name)) % 100000);

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL indisponible sur le serveur.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'ZotAutoBot/1.0',
    ]);
    $img  = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($img === false || $img === '') { return ['ok' => false, 'error' => 'Connexion Pollinations échouée : ' . $err]; }
    if ($code >= 400) { return ['ok' => false, 'error' => 'Pollinations : HTTP ' . $code]; }
    if (strpos($type, 'image/') !== 0 && strlen($img) < 2000) {
        return ['ok' => false, 'error' => 'Réponse Pollinations non-image (réessayez).'];
    }
    return ['ok' => true, 'png' => watermark_png($img)];
}

// --- État ---------------------------------------------------------------
$cat      = catalogue_read();
$products = $cat['products'];
$total    = count($products);
$todoSvg  = 0; $withAi = 0;
foreach ($products as $p) {
    if (needs_photo($p)) { $todoSvg++; }
    if (has_ai_photo($p)) { $withAi++; }
}
$hasKey = openai_key_read() !== '';
$notice = ''; $error = '';

// --- Actions ------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, réessayez.';
    } elseif (($_POST['action'] ?? '') === 'save_key') {
        $k = trim((string) ($_POST['openai_key'] ?? ''));
        if ($k === '' && $hasKey) { $k = openai_key_read(); }
        if ($k === '') { $error = 'Veuillez saisir votre clé OpenAI.'; }
        elseif (openai_key_write($k)) { $notice = 'Clé OpenAI enregistrée.'; $hasKey = true; }
        else { $error = 'Impossible d\'enregistrer la clé (permissions).'; }
    } elseif (($_POST['action'] ?? '') === 'gen_svg') {
        if (!is_dir(GEN_DIR) && !@mkdir(GEN_DIR, 0755, true)) {
            $error = 'Dossier assets/generated/ non créable.';
        } else {
            @set_time_limit(120);
            $n = 0;
            foreach ($products as $i => $p) {
                if ($n >= SVG_BATCH) { break; }
                if (!needs_photo($p)) { continue; }
                $fn  = ax_slug((string) ($p['id'] ?? $p['name'] ?? 'p'));
                if (@file_put_contents(GEN_DIR . '/' . $fn . '.svg', photo_svg($p)) !== false) {
                    $products[$i]['image'] = 'assets/generated/' . $fn . '.svg';
                    $products[$i]['contain'] = false;
                    $n++;
                }
            }
            $cat['products'] = $products;
            $w = catalogue_write($cat);
            $notice = $w['ok'] ? ($n . ' visuel(s) filigrané(s) générés.') : ($w['error'] ?? 'Écriture impossible.');
            if (!$w['ok']) { $error = $notice; $notice = ''; }
        }
    } elseif (($_POST['action'] ?? '') === 'gen_free') {
        // IA GRATUITE (Pollinations) — pas de clé requise.
        if (!is_dir(GEN_DIR) && !@mkdir(GEN_DIR, 0755, true)) {
            $error = 'Dossier assets/generated/ non créable.';
        } else {
            @set_time_limit(0);
            $n = 0; $fails = 0; $lastErr = '';
            // Produits AVEC prix d'abord (les plus vendables).
            $order = array_keys($products);
            usort($order, function ($a, $b) use ($products) {
                return (float) ($products[$b]['price'] ?? 0) <=> (float) ($products[$a]['price'] ?? 0);
            });
            foreach ($order as $i) {
                if ($n >= FREE_BATCH) { break; }
                $p = $products[$i];
                if (has_ai_photo($p)) { continue; }                      // déjà une photo IA
                if (!needs_photo($p) && !has_ai_photo($p)) { continue; }  // vraie photo importée : on respecte
                $res = ai_generate_photo_free($p);
                if ($res['ok']) {
                    $fn = ax_slug((string) ($p['id'] ?? $p['name'] ?? 'p'));
                    if (@file_put_contents(GEN_DIR . '/' . $fn . '.png', $res['png']) !== false) {
                        $products[$i]['image'] = 'assets/generated/' . $fn . '.png';
                        $products[$i]['contain'] = false;
                        $n++;
                    }
                } else {
                    $fails++; $lastErr = $res['error'];
                    if ($fails >= 3) { break; }
                }
            }
            $cat['products'] = $products;
            $w = catalogue_write($cat);
            if (!$w['ok']) { $error = $w['error'] ?? 'Écriture impossible.'; }
            else {
                $notice = $n . ' photo(s) IA gratuite(s) générée(s) et publiée(s).'
                    . ($fails ? ' (' . $fails . ' échec(s) : ' . h($lastErr) . ')' : '');
            }
        }
    } elseif (($_POST['action'] ?? '') === 'gen_ai') {
        $key = openai_key_read();
        if ($key === '') {
            $error = 'Enregistrez d\'abord votre clé OpenAI.';
        } elseif (!is_dir(GEN_DIR) && !@mkdir(GEN_DIR, 0755, true)) {
            $error = 'Dossier assets/generated/ non créable.';
        } else {
            @set_time_limit(0);
            $n = 0; $fails = 0; $lastErr = '';
            // On priorise les produits AVEC un prix (les plus vendables), puis le reste.
            $order = array_keys($products);
            usort($order, function ($a, $b) use ($products) {
                return (float) ($products[$b]['price'] ?? 0) <=> (float) ($products[$a]['price'] ?? 0);
            });
            foreach ($order as $i) {
                if ($n >= AI_BATCH) { break; }
                $p = $products[$i];
                if (has_ai_photo($p)) { continue; }              // déjà une vraie photo IA
                if (!needs_photo($p) && !has_ai_photo($p)) { continue; } // vraie photo importée : on respecte
                $res = ai_generate_photo($key, $p);
                if ($res['ok']) {
                    $fn = ax_slug((string) ($p['id'] ?? $p['name'] ?? 'p'));
                    if (@file_put_contents(GEN_DIR . '/' . $fn . '.png', $res['png']) !== false) {
                        $products[$i]['image'] = 'assets/generated/' . $fn . '.png';
                        $products[$i]['contain'] = false;
                        $n++;
                    }
                } else {
                    $fails++; $lastErr = $res['error'];
                    if ($fails >= 2) { break; } // stoppe vite si la clé/quotas posent problème
                }
            }
            $cat['products'] = $products;
            $w = catalogue_write($cat);
            if (!$w['ok']) { $error = $w['error'] ?? 'Écriture impossible.'; }
            else {
                $notice = $n . ' vraie(s) photo(s) IA générée(s) et publiée(s).'
                    . ($fails ? ' (' . $fails . ' échec(s) : ' . h($lastErr) . ')' : '');
            }
        }
    }
    // recompte
    $cat = catalogue_read(); $products = $cat['products']; $total = count($products);
    $todoSvg = 0; $withAi = 0;
    foreach ($products as $p) { if (needs_photo($p)) { $todoSvg++; } if (has_ai_photo($p)) { $withAi++; } }
    $hasKey = openai_key_read() !== '';
}

$aiTodo   = $total - $withAi;
$estCost  = number_format($aiTodo * AI_COST_UNIT, 2, ',', ' ');
$gdOk     = function_exists('imagecreatefromstring');
?>
<?php admin_head('Photos produits', 'photos'); ?>
<style>
  .bar { height:12px; background:#eef1fa; border-radius:99px; overflow:hidden; margin:10px 0; }
  .bar > i { display:block; height:100%; background:var(--blue); }
  .prev { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
  .prev img { width:120px; height:90px; border-radius:8px; border:1px solid var(--line); }
</style>
<div class="page-head">
  <h1>🖼️ Photos produits</h1>
  <p class="lead">Visuel généré gratuit par catégorie, ou vraies photos par IA pour les produits importants.</p>
</div>

  <?php if ($notice !== ''): ?><div class="msg msg--ok"><?= $notice /* déjà échappé au besoin */ ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="msg msg--err"><?= h($error) ?></div><?php endif; ?>

  <!-- Niveau 1 : SVG gratuit -->
  <div class="card">
    <h2>1️⃣ Visuel filigrané (gratuit, immédiat)</h2>
    <div><span class="big"><?= (int) $todoSvg ?></span> produit(s) sans photo · <?= (int) $total ?> au total</div>
    <div class="bar"><i style="width:<?= $total ? (int) round(($total - $todoSvg) / $total * 100) : 100 ?>%"></i></div>
    <?php if ($todoSvg > 0): ?>
      <form method="post" id="svgForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="gen_svg">
        <button class="btn btn--go" id="svgBtn" type="submit">✨ Générer le prochain lot (<?= min(SVG_BATCH, (int) $todoSvg) ?>)</button>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-weight:500"><input type="checkbox" id="svgAuto"> Enchaîner auto</label>
      </form>
    <?php else: ?>
      <p class="msg msg--ok" style="margin-top:14px">✅ Tous les produits ont au moins un visuel.</p>
    <?php endif; ?>
    <div class="prev">
      <?php foreach (['detailing' => 1, 'outillage' => 1, 'huiles' => 1] as $k => $x): ?>
        <img alt="<?= h($k) ?>" src="data:image/svg+xml,<?= rawurlencode(photo_svg(['category' => $k, 'name' => ucfirst($k)])) ?>">
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Niveau 2 : IA GRATUITE (Pollinations) -->
  <div class="card">
    <h2>2️⃣ Photos par IA — 100% GRATUIT ✨🤖</h2>
    <p class="hint">Génère une vraie image par produit via <strong>Pollinations.ai</strong> — <strong>gratuit, sans clé, sans budget</strong>. Filigrane ZOT AUTO ajouté automatiquement<?= $gdOk ? '' : ' (GD absente → image brute)' ?>.</p>
    <div class="msg msg--warn" style="margin-top:10px">
      ⚠️ Ce sont des <strong>illustrations générées par IA</strong> (rendu réaliste, pas la photo exacte de l'article). Idéal pour habiller le catalogue&nbsp;; pour une pièce précise, une vraie photo reste préférable.
    </div>
    <div style="margin-top:10px"><span class="big"><?= (int) $withAi ?></span> produit(s) avec vraie photo · <strong><?= (int) $aiTodo ?></strong> à faire</div>
    <div class="bar"><i style="width:<?= $total ? (int) round($withAi / $total * 100) : 0 ?>%"></i></div>
    <?php if ($aiTodo > 0): ?>
      <form method="post" id="freeForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="gen_free">
        <button class="btn btn--go" id="freeBtn" type="submit">✨ Générer <?= min(FREE_BATCH, (int) $aiTodo) ?> photos gratuites</button>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-weight:500"><input type="checkbox" id="freeAuto"> Enchaîner auto (tout le catalogue)</label>
      </form>
      <p class="hint" style="margin-top:8px">Chaque lot prend ~1 à 2 min (les produits <strong>avec prix</strong> d'abord). Coche « Enchaîner » et laisse tourner pour tout faire.</p>
    <?php else: ?>
      <p class="msg msg--ok" style="margin-top:14px">✅ Tous les produits ont une vraie photo.</p>
    <?php endif; ?>
  </div>

  <!-- Niveau 3 : IA OpenAI (payant, qualité premium) -->
  <div class="card">
    <h2>3️⃣ Vraies photos par IA — OpenAI (payant, premium) 🤖</h2>
    <p class="hint">Génère une photo réaliste par produit via OpenAI (modèle <code>gpt-image-1</code>).
       Filigrane ZOT AUTO ajouté automatiquement<?= $gdOk ? '' : ' (indisponible : extension GD absente sur ce serveur → photo brute)' ?>.</p>

    <form method="post" autocomplete="off" style="margin-bottom:14px">
      <?= csrf_field() ?><input type="hidden" name="action" value="save_key">
      <label for="openai_key">Clé API OpenAI <?= $hasKey ? '(enregistrée — laisser vide pour garder)' : '' ?></label>
      <input type="password" id="openai_key" name="openai_key" placeholder="sk-..." autocomplete="new-password">
      <p class="hint">Obtenez-la sur <strong>platform.openai.com → API keys</strong>. Stockée en privé sur le serveur, jamais affichée.</p>
      <p style="margin-top:10px"><button class="btn btn--primary" type="submit">💾 Enregistrer la clé</button></p>
    </form>

    <div class="msg msg--warn">
      💰 <strong>Coût</strong> : ~<?= h(number_format(AI_COST_UNIT, 2, ',', ' ')) ?> $ par image.
      Il reste <strong><?= (int) $aiTodo ?></strong> produit(s) sans vraie photo IA → estimation <strong>~<?= h($estCost) ?> $</strong> pour tout faire.
      On procède par petits lots de <?= AI_BATCH ?> (les produits <strong>avec prix</strong> d'abord).
    </div>

    <?php if ($hasKey): ?>
      <div><span class="big"><?= (int) $withAi ?></span> produit(s) avec vraie photo IA</div>
      <form method="post" id="aiForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="gen_ai">
        <button class="btn btn--ai" id="aiBtn" type="submit">🤖 Générer <?= AI_BATCH ?> photos IA</button>
        <label style="display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-weight:500"><input type="checkbox" id="aiAuto"> Enchaîner auto (attention au coût)</label>
      </form>
      <p class="hint" style="margin-top:8px">Chaque lot prend ~1 min. Clique à nouveau (ou coche « Enchaîner ») pour continuer.</p>
    <?php else: ?>
      <p class="hint">➡️ Enregistre ta clé OpenAI ci-dessus pour activer la génération IA.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <p class="hint">🔒 Clé OpenAI et coûts d'achat stockés en privé (fichiers <code>.openai.php</code> / <code>.axonaut_costs.php</code>, 0600).
       💡 Tu peux aussi remplacer la photo d'un produit à la main dans l'éditeur.</p>
  </div>
<script>
  function autoChain(formId, autoId, storeKey) {
    var form = document.getElementById(formId), auto = document.getElementById(autoId);
    if (!form || !auto) return;
    if (sessionStorage.getItem(storeKey) === '1') {
      auto.checked = true;
      setTimeout(function () { form.submit(); }, 700);
    }
    form.addEventListener('submit', function () {
      sessionStorage.setItem(storeKey, auto.checked ? '1' : '0');
      var b = form.querySelector('button'); if (b) { b.disabled = true; b.textContent = '⏳ En cours…'; }
    });
  }
  autoChain('svgForm', 'svgAuto', 'zot_svg_auto');
  autoChain('freeForm', 'freeAuto', 'zot_free_auto');
  autoChain('aiForm', 'aiAuto', 'zot_ai_auto');
</script>
<?php admin_foot('photos'); ?>
