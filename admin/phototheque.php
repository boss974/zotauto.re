<?php
/**
 * phototheque.php — Bibliothèque d'images de l'admin ZOT AUTO.
 *  - Range des images par catégorie dans ../assets/phototheque/<cat>/.
 *  - L'admin peut : uploader ses propres photos, remplir la biblio avec des
 *    photos d'ambiance (Pixabay), puis ASSOCIER manuellement une image à un
 *    produit (l'humain choisit → jamais de photo fausse).
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

const PH_DIR = __DIR__ . '/../assets/phototheque';
const PH_MAX = 6 * 1024 * 1024; // 6 Mo / upload

/** Catégories de la photothèque : clé => [libellé, requêtes Pixabay FR]. */
function ph_cats(): array
{
    return [
        'huiles'      => ['Huiles & lubrifiants', ['huile moteur', 'bidon huile moteur', 'lubrifiant automobile', 'vidange']],
        'detailing'   => ['Detailing & entretien', ['lavage automobile', 'nettoyage voiture', 'polish carrosserie', 'produit entretien voiture']],
        'outillage'   => ['Outillage', ['outils atelier', 'boîte à outils', 'clé mécanique', 'perceuse visseuse']],
        'pneus'       => ['Pneus', ['pneu voiture', 'pneus neufs', 'pneumatique']],
        'batteries'   => ['Batteries', ['batterie voiture', 'batterie automobile']],
        'accessoires' => ['Accessoires', ['accessoire voiture', 'pièces automobiles', 'garage automobile']],
        'autres'      => ['Autres', ['atelier mécanique auto', 'garage automobile']],
    ];
}

function ph_slug(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'img';
}

/** Scanne la bibliothèque → [catKey => [ ['file'=>rel, 'name'=>base], ... ]]. */
function ph_scan(): array
{
    $out = [];
    foreach (array_keys(ph_cats()) as $cat) {
        $dir = PH_DIR . '/' . $cat;
        $out[$cat] = [];
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [] as $f) {
                $out[$cat][] = ['file' => 'assets/phototheque/' . $cat . '/' . basename($f), 'name' => basename($f)];
            }
        }
    }
    return $out;
}

/** Recherche Pixabay (clé enregistrée dans photos_web → .gsearch.php). */
function ph_pixabay_key(): string
{
    $gs = __DIR__ . '/.gsearch.php';
    if (!is_file($gs)) { return ''; }
    $c = @include $gs;
    return is_array($c) ? (string) ($c['pxkey'] ?? '') : '';
}
function ph_pixabay_urls(string $key, string $q, int $page): array
{
    if (!function_exists('curl_init')) { return []; }
    $url = 'https://pixabay.com/api/?' . http_build_query([
        'key' => $key, 'q' => $q, 'image_type' => 'photo', 'per_page' => 20,
        'page' => max(1, $page), 'safesearch' => 'true', 'lang' => 'fr', 'order' => 'popular',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false]);
    $body = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($body === false || $code >= 400) { return []; }
    $j = json_decode($body, true);
    if (!is_array($j) || empty($j['hits'])) { return []; }
    $urls = [];
    foreach ($j['hits'] as $h) {
        $u = $h['largeImageURL'] ?? ($h['webformatURL'] ?? '');
        if ($u) { $urls[] = $u; }
    }
    return $urls;
}
function ph_download(string $url): ?array
{
    if (!function_exists('curl_init')) { return null; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'Mozilla/5.0']);
    $d = curl_exec($ch); $t = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($d === false || $code >= 400 || strlen($d) < 800) { return null; }
    $ext = 'jpg';
    if (stripos($t, 'png') !== false) { $ext = 'png'; }
    elseif (stripos($t, 'webp') !== false) { $ext = 'webp'; }
    elseif (stripos($t, 'jpeg') === false && stripos($t, 'jpg') === false) { return null; }
    return ['data' => $d, 'ext' => $ext];
}

$notice = ''; $error = '';

// --- Actions ------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de recharger la page.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $cats   = ph_cats();

        if ($action === 'upload') {
            $cat = (string) ($_POST['cat'] ?? 'autres');
            if (!isset($cats[$cat])) { $cat = 'autres'; }
            $dir = PH_DIR . '/' . $cat;
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                $error = 'Dossier photothèque non créable (permissions).';
            } else {
                $n = 0; $bad = 0;
                $files = $_FILES['photos'] ?? null;
                if ($files && is_array($files['name'])) {
                    $count = count($files['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if ((int) $files['error'][$i] !== UPLOAD_ERR_OK) { continue; }
                        if ((int) $files['size'][$i] > PH_MAX) { $bad++; continue; }
                        $tmp = $files['tmp_name'][$i];
                        $info = @getimagesize($tmp);
                        if ($info === false) { $bad++; continue; }
                        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? '';
                        if ($ext === '') { $bad++; continue; }
                        $base = ph_slug(pathinfo((string) $files['name'][$i], PATHINFO_FILENAME));
                        $fn = $base . '-' . substr(md5($base . microtime()), 0, 5) . '.' . $ext;
                        if (@move_uploaded_file($tmp, $dir . '/' . $fn)) { $n++; } else { $bad++; }
                    }
                }
                $notice = $n . ' photo(s) ajoutée(s) à la bibliothèque.' . ($bad ? ' (' . $bad . ' ignorée(s))' : '');
                if ($n === 0 && $bad === 0) { $error = 'Aucune photo reçue.'; $notice = ''; }
            }
        } elseif ($action === 'fetch') {
            $cat = (string) ($_POST['cat'] ?? '');
            if (!isset($cats[$cat])) { $error = 'Catégorie inconnue.'; }
            else {
                $key = ph_pixabay_key();
                if ($key === '') {
                    $error = 'Aucune clé Pixabay. Enregistre-la d\'abord dans « Photos web ».';
                } else {
                    @set_time_limit(0);
                    $dir = PH_DIR . '/' . $cat;
                    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                    $terms = $cats[$cat][1];
                    $want = 12; $got = 0; $seen = [];
                    foreach (glob($dir . '/*') ?: [] as $f) { $seen[md5_file($f)] = true; }
                    for ($p = 1; $p <= 3 && $got < $want; $p++) {
                        foreach ($terms as $t) {
                            if ($got >= $want) { break; }
                            foreach (ph_pixabay_urls($key, $t, $p) as $u) {
                                if ($got >= $want) { break; }
                                $dl = ph_download($u);
                                if (!$dl) { continue; }
                                $h = md5($dl['data']);
                                if (isset($seen[$h])) { continue; }
                                $seen[$h] = true;
                                $fn = ph_slug($t) . '-' . substr(md5($u), 0, 6) . '.' . $dl['ext'];
                                if (@file_put_contents($dir . '/' . $fn, $dl['data']) !== false) { $got++; }
                            }
                        }
                    }
                    $notice = $got . ' photo(s) d\'ambiance ajoutée(s) à « ' . h($cats[$cat][0]) . ' ». À toi de choisir les bonnes pour chaque produit.';
                }
            }
        } elseif ($action === 'del_img') {
            $rel = (string) ($_POST['file'] ?? '');
            if (strpos($rel, 'assets/phototheque/') === 0 && strpos($rel, '..') === false) {
                $abs = __DIR__ . '/../' . $rel;
                if (is_file($abs)) { @unlink($abs); $notice = 'Image supprimée de la bibliothèque.'; }
            }
        } elseif ($action === 'assign') {
            $pid = (string) ($_POST['product_id'] ?? '');
            $rel = (string) ($_POST['image'] ?? '');
            if ($pid === '' || strpos($rel, 'assets/phototheque/') !== 0) {
                $error = 'Sélection invalide.';
            } else {
                $cat = catalogue_read(); $prod = $cat['products']; $done = false;
                foreach ($prod as $i => $p) {
                    if ((string) ($p['id'] ?? '') === $pid) {
                        $prod[$i]['image'] = $rel; $prod[$i]['contain'] = false; $done = true; break;
                    }
                }
                if ($done) {
                    $cat['products'] = $prod; $w = catalogue_write($cat);
                    $notice = $w['ok'] ? '✅ Photo associée au produit et publiée.' : ($w['error'] ?? 'Écriture impossible.');
                    if (!$w['ok']) { $error = $notice; $notice = ''; }
                } else { $error = 'Produit introuvable.'; }
            }
        }
    }
}

$lib = ph_scan();
$libCount = 0; foreach ($lib as $a) { $libCount += count($a); }
$hasKey = ph_pixabay_key() !== '';
admin_head('Photothèque', 'phototheque');
?>
<style>
  .ph-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; margin-top:12px; }
  .ph-thumb { position:relative; border:1px solid var(--line); border-radius:10px; overflow:hidden; background:var(--paper); aspect-ratio:4/3; cursor:pointer; transition:box-shadow .15s, border-color .15s, transform .1s; }
  .ph-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
  .ph-thumb:hover { border-color:var(--blue); box-shadow:0 6px 16px -8px rgba(23,83,224,.5); }
  .ph-thumb.is-pick { outline:3px solid var(--blue); outline-offset:-1px; }
  .ph-thumb__del { position:absolute; top:4px; right:4px; background:rgba(180,35,24,.92); color:#fff; border:none; width:24px; height:24px; border-radius:7px; cursor:pointer; font-size:.8rem; line-height:1; }
  .ph-catwrap + .ph-catwrap { margin-top:8px; }
  .assignbar { position:sticky; bottom:0; z-index:20; background:var(--card); border:1px solid var(--blue-100); border-radius:12px; padding:12px 14px; margin-top:16px; box-shadow:var(--shadow); display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
  .assignbar .who { flex:1; min-width:200px; font-size:.9rem; }
  .assignbar .who b { color:var(--blue); }
  .miniprev { width:46px; height:46px; border-radius:8px; object-fit:cover; border:1px solid var(--line); }
</style>

<div class="page-head">
  <h1>🗂️ Photothèque</h1>
  <p class="lead">Ta bibliothèque d'images. Range des photos par catégorie, puis <strong>choisis toi-même</strong> la bonne image pour chaque produit — comme ça, jamais de photo fausse.</p>
</div>

<?php if ($notice): ?><div class="banner banner--ok"><?= $notice ?></div><?php endif; ?>
<?php if ($error):  ?><div class="banner banner--err"><?= h($error) ?></div><?php endif; ?>

<!-- 1) Remplir la bibliothèque -->
<div class="card">
  <div class="card__head"><span class="card__num">1</span><h2>Remplir la bibliothèque</h2></div>

  <h3>📤 Ajouter tes propres photos (recommandé)</h3>
  <p class="hint">Tes vraies photos boutique = les meilleures. JPG/PNG/WEBP, plusieurs à la fois.</p>
  <form method="post" enctype="multipart/form-data" class="cols" style="align-items:flex-end">
    <?= csrf_field() ?><input type="hidden" name="action" value="upload">
    <div class="row" style="flex:0 0 220px">
      <label>Catégorie</label>
      <select name="cat"><?php foreach (ph_cats() as $k => $c): ?><option value="<?= h($k) ?>"><?= h($c[0]) ?></option><?php endforeach; ?></select>
    </div>
    <div class="row"><label>Photos</label><input type="file" name="photos[]" accept="image/*" multiple></div>
    <div class="row" style="flex:0 0 auto"><button class="btn btn--primary" type="submit">Ajouter</button></div>
  </form>

  <div class="sep"></div>

  <h3>🌐 Photos d'ambiance automatiques (Pixabay)</h3>
  <p class="hint">Ajoute ~12 photos <strong>thématiques</strong> par catégorie (une huile, un outil…). Utile pour habiller vite, mais <strong>vérifie</strong> avant d'associer à un produit précis.<?= $hasKey ? '' : ' <strong>⚠️ Enregistre d\'abord ta clé Pixabay dans « Photos web ».</strong>' ?></p>
  <?php if ($hasKey): ?>
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <?php foreach (ph_cats() as $k => $c): ?>
      <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="fetch"><input type="hidden" name="cat" value="<?= h($k) ?>">
        <button class="btn btn--ghost" type="submit">+ <?= h($c[0]) ?></button>
      </form>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- 2) Associer -->
<div class="card">
  <div class="card__head"><span class="card__num">2</span><h2>Associer une image à un produit</h2></div>
  <p class="lead"><strong><?= (int) $libCount ?></strong> image(s) en bibliothèque. Étapes : ① cherche et clique un <strong>produit</strong>, ② clique la <strong>bonne image</strong> ci-dessous, ③ « Associer ».</p>

  <div class="row" style="max-width:520px">
    <label>① Produit</label>
    <input type="text" id="prodSearch" placeholder="Cherche par nom ou référence…" autocomplete="off">
    <div id="prodResults" style="border:1px solid var(--line); border-radius:10px; margin-top:6px; max-height:220px; overflow:auto; display:none;"></div>
  </div>

  <?php foreach (ph_cats() as $k => $c): if (empty($lib[$k])) continue; ?>
    <div class="ph-catwrap">
      <h3 style="margin:16px 0 0"><?= h($c[0]) ?> <span class="hint" style="font-weight:400">(<?= count($lib[$k]) ?>)</span></h3>
      <div class="ph-grid">
        <?php foreach ($lib[$k] as $img): ?>
          <div class="ph-thumb" data-img="<?= h($img['file']) ?>" title="Cliquer pour choisir">
            <img src="../<?= h($img['file']) ?>" loading="lazy" alt="">
            <form method="post" onsubmit="return confirm('Supprimer cette image de la bibliothèque ?')" style="margin:0">
              <?= csrf_field() ?><input type="hidden" name="action" value="del_img"><input type="hidden" name="file" value="<?= h($img['file']) ?>">
              <button class="ph-thumb__del" type="submit" title="Supprimer">✕</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($libCount === 0): ?>
    <p class="msg msg--warn" style="margin-top:14px">La bibliothèque est vide. Ajoute des photos ci-dessus (les tiennes, ou via Pixabay).</p>
  <?php endif; ?>
</div>

<!-- Barre d'association (sticky) -->
<form method="post" id="assignForm">
  <?= csrf_field() ?><input type="hidden" name="action" value="assign">
  <input type="hidden" name="product_id" id="aProduct">
  <input type="hidden" name="image" id="aImage">
  <div class="assignbar">
    <img id="aPrev" class="miniprev" alt="" style="display:none">
    <div class="who">
      Produit : <b id="aProdName">— aucun —</b><br>
      Image : <span id="aImgName">— aucune —</span>
    </div>
    <button class="btn btn--go" type="submit" id="aBtn" disabled>🔗 Associer &amp; publier</button>
  </div>
</form>

<script src="../data/catalogue.js?t=<?= time() ?>"></script>
<script>
(function(){
  var prods = ((window.ZOTAUTO && window.ZOTAUTO.products) || []);
  var search = document.getElementById('prodSearch'),
      results = document.getElementById('prodResults'),
      aProduct = document.getElementById('aProduct'),
      aImage = document.getElementById('aImage'),
      aProdName = document.getElementById('aProdName'),
      aImgName = document.getElementById('aImgName'),
      aPrev = document.getElementById('aPrev'),
      aBtn = document.getElementById('aBtn');

  function refresh(){ aBtn.disabled = !(aProduct.value && aImage.value); }

  search.addEventListener('input', function(){
    var q = search.value.trim().toLowerCase();
    if(q.length < 2){ results.style.display='none'; return; }
    var hits = prods.filter(function(p){
      return ((p.name||'')+' '+(p.reference||'')).toLowerCase().indexOf(q) !== -1;
    }).slice(0, 30);
    results.innerHTML = hits.map(function(p){
      return '<div class="prow" data-id="'+ (p.id||'') +'" data-name="'+ (p.name||'').replace(/"/g,'&quot;') +'" style="padding:9px 12px;border-bottom:1px solid var(--line-soft);cursor:pointer;font-size:.88rem">'
        + '<b>'+ (p.name||'?') +'</b> <span style="color:var(--muted-2)">'+ (p.reference||'') +'</span></div>';
    }).join('') || '<div style="padding:10px;color:var(--muted)">Aucun produit</div>';
    results.style.display='block';
  });
  results.addEventListener('click', function(e){
    var row = e.target.closest('.prow'); if(!row) return;
    aProduct.value = row.getAttribute('data-id');
    aProdName.textContent = row.getAttribute('data-name');
    search.value = row.getAttribute('data-name');
    results.style.display='none';
    refresh();
  });
  document.querySelectorAll('.ph-thumb').forEach(function(t){
    t.addEventListener('click', function(e){
      if(e.target.closest('form')) return; // clic sur ✕
      document.querySelectorAll('.ph-thumb.is-pick').forEach(function(x){x.classList.remove('is-pick');});
      t.classList.add('is-pick');
      var f = t.getAttribute('data-img');
      aImage.value = f;
      aImgName.textContent = f.split('/').pop();
      aPrev.src = '../'+f; aPrev.style.display='block';
      refresh();
    });
  });
})();
</script>
<?php
admin_foot('phototheque');
