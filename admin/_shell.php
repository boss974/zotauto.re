<?php
/**
 * _shell.php — Petit helper pour rendre une page HTML sobre aux couleurs ZOT AUTO.
 * Pas de logique de sécurité ici : juste du rendu. Inclus par setup/login/verify.
 *
 * Usage :
 *   render_page('Titre', function () { ?> ... contenu ... <?php });
 */

declare(strict_types=1);

function render_page(string $title, callable $content, string $error = '', string $notice = ''): void
{
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> — ZOT AUTO Admin</title>
<style>
  :root{
    --bleu:#2a52e0;
    --rouge:#e01f2b;
    --jaune:#ffcb00;
    --bg:#f4f6fb;
    --card:#ffffff;
    --text:#1a2033;
    --muted:#6b7280;
    --border:#e2e6f0;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(160deg,var(--bg) 0%,#eef1fb 100%);
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    color:var(--text);
    padding:24px;
  }
  .card{
    width:100%;
    max-width:420px;
    background:var(--card);
    border-radius:16px;
    box-shadow:0 10px 40px rgba(20,30,70,.12);
    padding:32px 28px;
    border-top:6px solid var(--bleu);
  }
  .logo{
    display:flex;
    align-items:baseline;
    gap:2px;
    font-family:Sora,"Segoe UI",sans-serif;
    font-weight:800;
    font-size:1.5rem;
    margin-bottom:4px;
    letter-spacing:-.02em;
  }
  .logo span:first-child{color:var(--bleu);}
  .logo span:last-child{color:var(--rouge);}
  .logo .dot{color:var(--jaune);}
  .subtitle{color:var(--muted);font-size:.85rem;margin:0 0 24px;}
  h1{font-size:1.15rem;margin:0 0 6px;}
  p.lead{color:var(--muted);font-size:.9rem;margin:0 0 20px;line-height:1.5;}
  label{display:block;font-size:.85rem;font-weight:600;margin:14px 0 6px;}
  input[type=text],input[type=password],input[type=tel],input[type=email]{
    width:100%;
    min-height:44px;
    padding:10px 12px;
    border:1.5px solid var(--border);
    border-radius:10px;
    font-size:1rem;
    outline:none;
    transition:border-color .15s;
    background:#fbfcfe;
  }
  input:focus{border-color:var(--bleu);background:#fff;}
  input[name=otp]{
    letter-spacing:.4em;
    font-size:1.4rem;
    text-align:center;
    font-weight:700;
  }
  button, .btn{
    width:100%;
    min-height:44px;
    margin-top:20px;
    background:var(--bleu);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:1rem;
    font-weight:700;
    cursor:pointer;
    transition:background .15s, transform .05s;
  }
  button:hover{background:#1f3fb0;}
  button:active{transform:scale(.99);}
  button.secondary{
    background:#fff;
    color:var(--bleu);
    border:1.5px solid var(--bleu);
    margin-top:10px;
  }
  button.secondary:hover{background:#eef1fb;}
  button:disabled{opacity:.55;cursor:not-allowed;}
  .msg{
    border-radius:10px;
    padding:10px 14px;
    font-size:.85rem;
    margin-bottom:16px;
    line-height:1.4;
  }
  .msg.error{background:#fdeceb;color:#9b1c24;border:1px solid #f6c6c9;}
  .msg.notice{background:#eaf0ff;color:#1f3fb0;border:1px solid #cdd9fb;}
  .foot{margin-top:20px;text-align:center;font-size:.75rem;color:var(--muted);}
  .foot a{color:var(--bleu);text-decoration:none;}
  .accent-bar{height:4px;width:56px;background:var(--jaune);border-radius:4px;margin:2px 0 18px;}
</style>
</head>
<body>
  <div class="card">
    <div class="logo"><span>ZOT</span> <span>AUTO</span><span class="dot">.</span></div>
    <div class="accent-bar"></div>
    <?php if ($error !== ''): ?>
      <div class="msg error"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($notice !== ''): ?>
      <div class="msg notice"><?= h($notice) ?></div>
    <?php endif; ?>
    <?php $content(); ?>
    <div class="foot">Espace réservé — ZOT AUTO</div>
  </div>
</body>
</html>
<?php
}
