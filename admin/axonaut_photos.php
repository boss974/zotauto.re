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
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Photos produits — ZOT AUTO Admin</title>
<style>
  :root { --blue:#2a52e0; --ink:#1a2033; --line:#e3e7f0; --ok:#0a7d33; --err:#c0392b; --violet:#7c3aed; }
  * { box-sizing:border-box; }
  body { margin:0; font:15px/1.55 -apple-system,Segoe UI,Roboto,sans-serif; background:#f4f6fb; color:var(--ink); }
  .wrap { max-width:720px; margin:0 auto; padding:26px 18px 60px; }
  .top { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
  .top a { color:var(--blue); text-decoration:none; font-weight:600; font-size:.9rem; }
  h1 { font-size:1.5rem; margin:.2em 0 .1em; }
  .sub { color:#5b6376; margin:0 0 22px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:14px; padding:22px; margin-bottom:18px; box-shadow:0 3px 14px rgba(20,30,60,.04); }
  .card h2 { font-size:1.1rem; margin:0 0 6px; }
  label { display:block; font-weight:600; font-size:.9rem; margin:12px 0 5px; }
  input[type=text], input[type=password] { width:100%; padding:11px 13px; border:1px solid var(--line); border-radius:9px; font-size:.95rem; }
  .btn { display:inline-flex; align-items:center; gap:8px; border:0; border-radius:10px; padding:12px 20px; font-weight:700; font-size:.95rem; cursor:pointer; }
  .btn--primary { background:var(--blue); color:#fff; }
  .btn--go { background:var(--ok); color:#fff; }
  .btn--ai { background:var(--violet); color:#fff; }
  .btn:disabled { opacity:.6; cursor:progress; }
  .big { font-size:2rem; font-weight:800; }
  .msg { padding:11px 14px; border-radius:9px; margin:0 0 16px; font-size:.9rem; }
  .msg--ok { background:#e7f6ec; color:var(--ok); border:1px solid #b6e2c4; }
  .msg--err { background:#fdecea; color:var(--err); border:1px solid #f3c2bc; }
  .msg--warn { background:#fff7e6; border:1px solid #ffe0a3; color:#8a6d1a; }
  .hint { font-size:.83rem; color:#5b6376; }
  .bar { height:12px; background:#eef1fa; border-radius:99px; overflow:hidden; margin:10px 0; }
  .bar > i { display:block; height:100%; background:var(--blue); }
  .prev { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
  .prev img { width:120px; height:90px; border-radius:8px; border:1px solid var(--line); }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <a href="index.php">← Éditeur</a>
    <a href="axonaut.php">🔄 Axonaut</a>
    <a href="axonaut_pricing.php">💶 Tarifs</a>
    <a href="axonaut_schedule.php">🗓️ Planif</a>
    <a href="logout.php">Se déconnecter</a>
  </div>

  <h1>🖼️ Photos produits</h1>
  <p class="sub">Deux options : un <strong>visuel filigrané gratuit</strong> (immédiat) ou de
     <strong>vraies photos par IA</strong> (OpenAI, payant).</p>

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

  <!-- Niveau 2 : IA -->
  <div class="card">
    <h2>2️⃣ Vraies photos par IA (OpenAI) 🤖</h2>
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
  autoChain('aiForm', 'aiAuto', 'zot_ai_auto');
</script>
</body>
</html>
