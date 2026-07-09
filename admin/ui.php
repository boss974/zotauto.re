<?php
/**
 * ui.php — Design system partagé de l'espace admin ZOT AUTO.
 * Fournit : admin_css(), admin_head($title,$active), admin_foot(), admin_help_data().
 * Toutes les pages outils incluent ce fichier pour une interface homogène + aide intégrée.
 *
 * Usage :
 *   require __DIR__ . '/ui.php';
 *   admin_head('Titre de la page', 'cle_active');   // ouvre <body> + nav + <main class="wrap">
 *   ... contenu (cards, stats, forms) ...
 *   admin_foot();                                    // ferme + tiroir d'aide
 */

declare(strict_types=1);

if (!function_exists('h')) {
    function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

/** Menu principal : clé => [libellé, fichier, emoji]. */
function admin_menu(): array
{
    return [
        'dashboard' => ['Tableau de bord', 'dashboard.php', '&#128202;'],
        'offres'    => ['Offres',          'offres.php',    '&#128227;'],
        'axonaut'   => ['Synchro Axonaut',  'axonaut.php',   '&#128260;'],
        'pricing'   => ['Tarifs &amp; promos', 'axonaut_pricing.php', '&#128181;'],
        'photos'    => ['Photos produits',  'axonaut_photos.php', '&#128444;'],
        'photosweb' => ['Photos web',       'photos_web.php', '&#127760;'],
        'schedule'  => ['Planification',    'axonaut_schedule.php', '&#128197;'],
        'editor'    => ['Éditeur catalogue', 'index.php',    '&#128221;'],
        'aide'      => ['Aide',             'aide.php',      '&#10067;'],
    ];
}

/** Aide contextuelle par page : titre + étapes (HTML autorisé, échappé côté rédaction). */
function admin_help_data(): array
{
    return [
        'dashboard' => [
            'Le tableau de bord',
            [
                'Les <strong>chiffres du haut</strong> résument votre catalogue : nombre de produits, combien ont un prix, une photo, sont en stock.',
                'Les <strong>tuiles</strong> ouvrent chaque outil. Commencez par <strong>Synchro Axonaut</strong> si vos produits ne sont pas encore chargés.',
                'La bannière indique si Axonaut est connecté et la date de la dernière synchronisation.',
            ],
        ],
        'offres' => [
            'Gérer les offres du moment',
            [
                'Ces cartes s\'affichent sur la page d\'accueil, section « En ce moment chez ZOT AUTO ».',
                'Remplissez un emplacement : <strong>étiquette</strong>, <strong>titre</strong>, <strong>description</strong>, <strong>texte du bouton</strong> et le <strong>message WhatsApp</strong> pré-rempli.',
                'Cochez <strong>« Afficher »</strong> pour publier, ou décochez pour mettre en pause sans supprimer.',
                'Cochez <strong>« Mettre en avant »</strong> pour l\'offre vedette (fond bleu).',
                'Cliquez sur <strong>Publier</strong> : c\'est en ligne immédiatement. Un emplacement sans titre n\'apparaît pas.',
            ],
        ],
        'axonaut' => [
            'Synchroniser avec Axonaut',
            [
                'Collez votre <strong>clé API Axonaut</strong> puis enregistrez-la (une seule fois).',
                'Choisissez le mode <strong>« Catalogue complet »</strong> pour importer tous vos produits, prix et stocks.',
                'Cliquez sur <strong>Synchroniser</strong> et patientez. Le résultat s\'affiche en bas.',
                'Répétez la synchro quand votre stock ou vos prix changent dans Axonaut.',
            ],
        ],
        'pricing' => [
            'Tarifs & promos',
            [
                'Réglez la <strong>marge de vente</strong> appliquée aux prix d\'achat Axonaut (ex. 40&nbsp;%).',
                'Activez une <strong>promo</strong> en pourcentage pour baisser tous les prix affichés d\'un coup.',
                'Enregistrez : les prix du site se recalculent à la prochaine synchro.',
            ],
        ],
        'photos' => [
            'Photos produits',
            [
                'Chaque produit sans photo reçoit un <strong>visuel généré</strong> aux couleurs de sa catégorie.',
                'Vous pouvez générer de <strong>vraies photos par IA</strong> (nécessite une clé) pour les produits importants.',
                'Les visuels se régénèrent automatiquement à l\'affichage : rien à refaire en cas de doute.',
            ],
        ],
        'photosweb' => [
            'Photos web',
            [
                'Recherchez de vraies photos par <strong>référence</strong> ou mot-clé.',
                'Les résultats sont rangés par <strong>catégorie</strong> pour retrouver facilement.',
                'Associez la photo qui correspond le mieux à chaque produit.',
            ],
        ],
        'schedule' => [
            'Planification automatique',
            [
                'Définissez la <strong>fréquence</strong> de synchronisation Axonaut (ex. tous les jours).',
                'Le système génère une <strong>tâche planifiée</strong> à copier dans votre hébergement LWS.',
                'Une fois en place, votre stock se met à jour tout seul.',
            ],
        ],
        'editor' => [
            'Éditeur du catalogue',
            [
                'Modifiez un produit <strong>à la main</strong> : nom, prix, description, photo.',
                'Utile pour un ajustement ponctuel entre deux synchros Axonaut.',
                'Cliquez sur <strong>Publier</strong> pour envoyer les changements en ligne.',
                '⚠️ La synchro Axonaut « Catalogue complet » réécrit le catalogue : privilégiez-la pour les mises à jour de masse.',
            ],
        ],
        'aide' => [
            'Centre d\'aide',
            [
                'Retrouvez ici le mode d\'emploi complet de chaque outil.',
                'Le bouton <strong>« Comment faire ? »</strong> est disponible sur toutes les pages, en haut à droite.',
            ],
        ],
    ];
}

/** CSS partagé de tout l'espace admin (light + dark). */
function admin_css(): string
{
    return <<<CSS
:root{
  --blue:#1753e0; --blue-deep:#0f3fb5; --blue-050:#eef2ff; --blue-100:#dde6ff;
  --yellow:#f4a900; --wa:#25d366;
  --ink:#151d33; --muted:#5c6479; --muted-2:#8a92a6;
  --paper:#f4f6fc; --card:#ffffff; --line:#e4e8f2; --line-soft:#eef1f8;
  --ok:#0a7d33; --ok-bg:#e7f6ec; --ok-line:#b6e2c4;
  --warn:#8a6d1a; --warn-bg:#fff7e6; --warn-line:#ffe0a3;
  --err:#b42318; --err-bg:#fdecec; --err-line:#f5b5b5;
  --shadow-sm:0 2px 10px rgba(21,29,51,.05);
  --shadow:0 6px 24px rgba(21,29,51,.08);
  --radius:16px; --radius-sm:11px;
}
@media (prefers-color-scheme:dark){
  :root{
    --ink:#eaeef7; --muted:#9aa3ba; --muted-2:#727c93;
    --paper:#0d1220; --card:#161d2c; --line:#2a3346; --line-soft:#212a3c;
    --blue-050:#182238; --blue-100:#1d2c4a;
    --ok-bg:#10241a; --ok-line:#1f4a34;
    --warn-bg:#241f10; --warn-line:#4a3d1f;
    --err-bg:#2a1414; --err-line:#4a2626;
    --shadow-sm:0 2px 10px rgba(0,0,0,.35); --shadow:0 6px 24px rgba(0,0,0,.45);
  }
}
*{box-sizing:border-box;}
html{-webkit-text-size-adjust:100%;}
body{
  margin:0; background:var(--paper); color:var(--ink);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  line-height:1.55; -webkit-font-smoothing:antialiased;
}
a{color:var(--blue);}
:focus-visible{outline:3px solid color-mix(in srgb,var(--blue) 45%,transparent); outline-offset:2px; border-radius:6px;}

/* ---- Topbar ---- */
.adminbar{position:sticky; top:0; z-index:50; background:var(--card); border-bottom:1px solid var(--line); box-shadow:var(--shadow-sm);}
.adminbar__in{max-width:1000px; margin:0 auto; padding:12px 20px; display:flex; align-items:center; gap:14px;}
.brand{display:flex; align-items:baseline; gap:1px; font-weight:800; font-size:1.15rem; letter-spacing:-.02em; text-decoration:none;}
.brand .b1{color:var(--blue);} .brand .b2{color:#e01f2b;} .brand .dot{color:var(--yellow);}
.adminbar__sp{flex:1;}
.adminbar__act{display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;}
.tbtn{display:inline-flex; align-items:center; gap:6px; text-decoration:none; font-weight:600; font-size:.85rem; color:var(--muted); padding:7px 12px; border-radius:999px; border:1px solid transparent; transition:background .15s,color .15s,border-color .15s; cursor:pointer; background:none;}
.tbtn:hover{background:var(--blue-050); color:var(--blue);}
.tbtn--help{color:var(--blue); border-color:var(--blue-100); background:var(--blue-050);}
.tbtn--help:hover{background:var(--blue-100);}
.tbtn--danger:hover{background:var(--err-bg); color:var(--err);}

/* ---- Nav tabs ---- */
.adminnav{background:var(--card); border-bottom:1px solid var(--line); position:sticky; top:57px; z-index:40;}
.adminnav__in{max-width:1000px; margin:0 auto; padding:0 12px; display:flex; gap:2px; overflow-x:auto; scrollbar-width:none;}
.adminnav__in::-webkit-scrollbar{display:none;}
.navtab{flex:0 0 auto; display:inline-flex; align-items:center; gap:6px; text-decoration:none; white-space:nowrap; color:var(--muted); font-weight:600; font-size:.86rem; padding:13px 14px; border-bottom:2.5px solid transparent; transition:color .15s,border-color .15s;}
.navtab:hover{color:var(--ink);}
.navtab.is-active{color:var(--blue); border-bottom-color:var(--blue);}
.navtab .em{font-size:1rem; line-height:1;}

/* ---- Layout ---- */
.wrap{max-width:1000px; margin:0 auto; padding:26px 20px 72px;}
.page-head{margin:0 0 22px;}
.page-head h1{margin:0; font-size:1.6rem; letter-spacing:-.015em; text-wrap:balance;}
.page-head .lead{margin:6px 0 0; color:var(--muted); max-width:60ch;}

/* ---- Cards ---- */
.card{background:var(--card); border:1px solid var(--line); border-radius:var(--radius); padding:22px; box-shadow:var(--shadow-sm);}
.card + .card{margin-top:16px;}
.card__head{display:flex; align-items:center; gap:12px; margin-bottom:14px;}
.card__num{width:28px; height:28px; border-radius:8px; background:var(--blue); color:#fff; display:grid; place-items:center; font-weight:800; font-size:.9rem; flex:0 0 auto;}
.card__head h2{margin:0; font-size:1.08rem; flex:1;}
h2.section-title{font-size:1.15rem; margin:26px 0 12px;}

/* ---- Stats grid ---- */
.grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:14px; margin-bottom:24px;}
.stat{background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px 18px; box-shadow:var(--shadow-sm);}
.stat b, .stat .big{display:block; font-size:2rem; font-weight:800; line-height:1.1; font-variant-numeric:tabular-nums;}
.stat span, .stat .sub{font-size:.82rem; color:var(--muted);}
.stat .mini{height:6px; background:var(--line-soft); border-radius:99px; overflow:hidden; margin-top:8px;}
.stat .mini i{display:block; height:100%; background:var(--blue); border-radius:99px;}

/* ---- Tiles ---- */
.tiles{display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:14px;}
.tile{display:block; text-decoration:none; color:inherit; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; box-shadow:var(--shadow-sm); transition:transform .14s, box-shadow .14s, border-color .14s;}
.tile:hover{transform:translateY(-3px); box-shadow:var(--shadow); border-color:var(--blue-100);}
.tile .ic{font-size:1.7rem;}
.tile h3{margin:8px 0 4px; font-size:1.05rem;}
.tile p{margin:0; font-size:.85rem; color:var(--muted);}

/* ---- Buttons ---- */
.btn{display:inline-flex; align-items:center; justify-content:center; gap:8px; background:var(--blue); color:#fff; border:none; padding:12px 22px; border-radius:var(--radius-sm); font:inherit; font-weight:700; cursor:pointer; text-decoration:none; box-shadow:0 6px 16px -6px rgba(23,83,224,.45); transition:filter .15s, transform .12s;}
.btn:hover{filter:brightness(1.06); transform:translateY(-1px);}
.btn:active{transform:translateY(0);}
.btn svg{width:18px; height:18px;}
.btn--primary{background:var(--blue);}
.btn--go{background:var(--ok); box-shadow:0 6px 16px -6px rgba(10,125,51,.45);}
.btn--ai{background:linear-gradient(135deg,#7c3aed,#4f46e5); box-shadow:0 6px 16px -6px rgba(99,60,220,.5);}
.btn--ghost{background:transparent; color:var(--blue); border:1.5px solid var(--line); box-shadow:none;}
.btn--ghost:hover{border-color:var(--blue); filter:none;}
.btn--block{width:100%;}
.btn:disabled{opacity:.55; cursor:not-allowed; transform:none;}

/* ---- Forms ---- */
label{display:block; font-weight:600; font-size:.85rem; margin:0 0 6px; color:var(--ink);}
input[type=text],input[type=password],input[type=tel],input[type=email],input[type=number],input[type=search],select,textarea{
  width:100%; min-height:44px; padding:10px 13px; border:1.5px solid var(--line); border-radius:10px; font:inherit; background:var(--paper); color:var(--ink); outline:none; transition:border-color .15s, background .15s;
}
textarea{min-height:70px; resize:vertical; line-height:1.5;}
input:focus,select:focus,textarea:focus{border-color:var(--blue); background:var(--card);}
.row{margin-bottom:14px;}
.cols{display:flex; gap:14px; flex-wrap:wrap;}
.cols .row{flex:1; min-width:180px;}
.hint,.sub,.note{font-size:.8rem; color:var(--muted-2); margin-top:4px;}
.lead{color:var(--muted);}
.toggle{display:inline-flex; align-items:center; gap:8px; font-weight:600; font-size:.9rem; cursor:pointer;}
.toggle input{width:18px; height:18px; accent-color:var(--blue);}
.toggles{display:flex; gap:18px; flex-wrap:wrap;}

/* ---- Messages / banners ---- */
.msg,.banner{border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:.9rem; line-height:1.45; border:1px solid var(--line);}
.msg--ok,.banner--ok{background:var(--ok-bg); border-color:var(--ok-line); color:var(--ok);}
.msg--warn,.banner--warn{background:var(--warn-bg); border-color:var(--warn-line); color:var(--warn);}
.msg--err,.msg.error,.banner--err{background:var(--err-bg); border-color:var(--err-line); color:var(--err);}
.notice{background:var(--blue-050); border-color:var(--blue-100); color:var(--blue-deep);}

/* ---- Bits ---- */
.pill{display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:700; padding:4px 11px; border-radius:999px; background:var(--line-soft); color:var(--muted);}
.pill--on{background:var(--ok-bg); color:var(--ok);}
.pill--off{background:var(--err-bg); color:var(--err);}
.keytag,.k{font-family:ui-monospace,Menlo,Consolas,monospace; background:var(--line-soft); color:var(--blue); padding:2px 8px; border-radius:6px; font-size:.85em;}
.status-row{display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid var(--line-soft);}
.status-row:first-child{border-top:none;}
.status-row .k{flex:0 0 auto;} .status-row .v{color:var(--muted);}
.sep{height:1px; background:var(--line); margin:20px 0;}
.sp{flex:1;}
.cronbox{background:#0d1220; color:#d7e0f5; font-family:ui-monospace,Menlo,Consolas,monospace; font-size:.82rem; padding:14px 16px; border-radius:10px; overflow-x:auto; white-space:pre; margin:10px 0;}
.copybtn{cursor:pointer;}

/* ---- Steps list ---- */
ol.steps,.steps{margin:0; padding:0; list-style:none; counter-reset:s; display:flex; flex-direction:column; gap:11px;}
ol.steps li,.steps li{position:relative; padding-left:36px;}
ol.steps li::before,.steps li::before{counter-increment:s; content:counter(s); position:absolute; left:0; top:-1px; width:25px; height:25px; border-radius:50%; background:var(--blue-050); color:var(--blue); font-weight:700; font-size:.8rem; display:grid; place-items:center; font-variant-numeric:tabular-nums;}

/* ---- Help drawer ---- */
.help-scrim{position:fixed; inset:0; background:rgba(15,20,35,.5); opacity:0; visibility:hidden; transition:opacity .25s; z-index:100;}
.help-scrim.open{opacity:1; visibility:visible;}
.help-panel{position:fixed; top:0; right:0; height:100%; width:min(420px,92vw); background:var(--card); box-shadow:-12px 0 40px rgba(15,20,35,.25); transform:translateX(100%); transition:transform .28s cubic-bezier(.4,0,.2,1); z-index:101; display:flex; flex-direction:column;}
.help-panel.open{transform:translateX(0);}
.help-panel__head{display:flex; align-items:center; gap:12px; padding:20px; border-bottom:1px solid var(--line);}
.help-panel__head h2{margin:0; font-size:1.1rem; flex:1;}
.help-panel__close{background:var(--line-soft); border:none; width:34px; height:34px; border-radius:9px; font-size:1.1rem; cursor:pointer; color:var(--ink);}
.help-panel__body{padding:20px; overflow-y:auto;}
.help-panel__body .intro{margin:0 0 16px; color:var(--muted); font-size:.9rem;}
.help-panel__foot{margin-top:auto; padding:16px 20px; border-top:1px solid var(--line); font-size:.8rem; color:var(--muted);}
.help-panel__foot a{font-weight:600; text-decoration:none;}

/* ---- Compat : classes héritées des anciennes pages ---- */
.ex{font-size:.8rem; color:var(--muted-2);}
.check{color:var(--ok); font-weight:700;}
.off-note{font-size:.8rem; color:var(--muted); margin-top:6px;}
.card h2{font-size:1.08rem; margin:0 0 12px;}
.card h3{font-size:1rem; margin:0 0 8px;}
.freq{display:flex; gap:10px; flex-wrap:wrap; margin:6px 0 4px;}
.radio{display:inline-flex; align-items:center; gap:7px; font-weight:600; font-size:.9rem; cursor:pointer; padding:8px 12px; border:1.5px solid var(--line); border-radius:10px;}
.radio input{accent-color:var(--blue);}
.bar{height:6px; background:var(--line-soft); border-radius:99px; overflow:hidden;}
.bar > i{display:block; height:100%; background:var(--blue); border-radius:99px;}
.prev{border:1px dashed var(--line); border-radius:12px; padding:14px; background:var(--paper);}
.big{font-size:2rem; font-weight:800; line-height:1.1;}
.v{color:var(--muted);}

@media (max-width:600px){
  .adminbar__in{padding:10px 14px;}
  .wrap{padding:20px 14px 64px;}
  .tbtn span.lbl{display:none;}
  .page-head h1{font-size:1.35rem;}
}
@media (prefers-reduced-motion:reduce){*{transition:none !important;}}
CSS;
}

/**
 * Ouvre la page : <head> + topbar + nav + <main class="wrap">.
 * $active = clé du menu (voir admin_menu) pour surligner l'onglet courant.
 */
function admin_head(string $title, string $active = ''): void
{
    $menu = admin_menu();
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#1753e0">
<title><?= h($title) ?> — ZOT AUTO Admin</title>
<style><?= admin_css() ?></style>
</head>
<body>
<header class="adminbar">
  <div class="adminbar__in">
    <a class="brand" href="dashboard.php"><span class="b1">ZOT</span>&nbsp;<span class="b2">AUTO</span><span class="dot">.</span></a>
    <span class="adminbar__sp"></span>
    <div class="adminbar__act">
      <button type="button" class="tbtn tbtn--help" id="helpOpen" aria-haspopup="dialog">&#10067; <span class="lbl">Comment faire&nbsp;?</span></button>
      <a class="tbtn" href="../index.html" target="_blank" rel="noopener">&#127760; <span class="lbl">Voir le site</span></a>
      <a class="tbtn tbtn--danger" href="logout.php">&#128274; <span class="lbl">Déconnexion</span></a>
    </div>
  </div>
</header>
<nav class="adminnav" aria-label="Navigation admin">
  <div class="adminnav__in">
    <?php foreach ($menu as $key => [$label, $file, $emoji]): ?>
      <a class="navtab<?= $key === $active ? ' is-active' : '' ?>" href="<?= h($file) ?>"><span class="em"><?= $emoji ?></span> <?= $label ?></a>
    <?php endforeach; ?>
  </div>
</nav>
<main class="wrap">
<?php
}

/** Ferme la page + injecte le tiroir d'aide de la page $active. */
function admin_foot(string $active = ''): void
{
    $help = admin_help_data();
    $entry = $help[$active] ?? ['Aide', ['Utilisez le menu du haut pour naviguer entre les outils.']];
    [$htitle, $steps] = $entry;
    ?>
</main>

<div class="help-scrim" id="helpScrim"></div>
<aside class="help-panel" id="helpPanel" role="dialog" aria-modal="true" aria-label="Aide de la page">
  <div class="help-panel__head">
    <h2>&#10067; <?= h($htitle) ?></h2>
    <button type="button" class="help-panel__close" id="helpClose" aria-label="Fermer l'aide">&times;</button>
  </div>
  <div class="help-panel__body">
    <p class="intro">Voici comment procéder, étape par étape&nbsp;:</p>
    <ol class="steps">
      <?php foreach ($steps as $s): ?><li><?= $s ?></li><?php endforeach; ?>
    </ol>
  </div>
  <div class="help-panel__foot">
    Besoin de plus&nbsp;? Voir le <a href="aide.php">centre d'aide complet</a>.
  </div>
</aside>

<script>
(function(){
  var scrim=document.getElementById('helpScrim'),
      panel=document.getElementById('helpPanel'),
      open=document.getElementById('helpOpen'),
      close=document.getElementById('helpClose');
  function show(){scrim.classList.add('open');panel.classList.add('open');}
  function hide(){scrim.classList.remove('open');panel.classList.remove('open');}
  if(open)open.addEventListener('click',show);
  if(close)close.addEventListener('click',hide);
  if(scrim)scrim.addEventListener('click',hide);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')hide();});
})();
</script>
</body>
</html>
<?php
}
