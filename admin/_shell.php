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
    --bleu:#1753e0;
    --bleu-deep:#0f3fb5;
    --rouge:#e01f2b;
    --jaune:#f4a900;
    --bg:#eef2fb;
    --bg2:#dde6f8;
    --card:#ffffff;
    --text:#151d33;
    --muted:#5c6479;
    --border:#e4e8f2;
    --field:#f6f8fc;
    --err-bg:#fdecec;--err-tx:#b42318;--err-ln:#f5b5b5;
    --ok-bg:#eef2ff;--ok-tx:#0f3fb5;--ok-ln:#dde6ff;
  }
  @media (prefers-color-scheme:dark){
    :root{
      --bg:#0b1020;--bg2:#0d1526;--card:#161d2c;--text:#eaeef7;--muted:#9aa3ba;
      --border:#2a3346;--field:#0f1626;
      --err-bg:#2a1414;--err-tx:#ff9b90;--err-ln:#4a2626;
      --ok-bg:#182238;--ok-tx:#93b0ff;--ok-ln:#1d2c4a;
    }
  }
  *{box-sizing:border-box;}
  body{
    margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
    background:radial-gradient(1200px 600px at 50% -10%, var(--bg2), var(--bg) 60%);
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    color:var(--text); padding:24px; -webkit-font-smoothing:antialiased;
  }
  .card{
    width:100%; max-width:420px; background:var(--card); border-radius:18px;
    box-shadow:0 18px 50px rgba(15,25,60,.18); padding:34px 30px;
    border:1px solid var(--border); position:relative; overflow:hidden;
  }
  .card::before{content:"";position:absolute;top:0;left:0;right:0;height:5px;
    background:linear-gradient(90deg,var(--bleu),var(--bleu-deep));}
  .logo{
    display:flex; align-items:baseline; gap:2px; font-weight:800; font-size:1.5rem;
    margin-bottom:4px; letter-spacing:-.02em;
  }
  .logo span:first-child{color:var(--bleu);}
  .logo span:last-child{color:var(--rouge);}
  .logo .dot{color:var(--jaune);}
  .subtitle{color:var(--muted);font-size:.85rem;margin:0 0 24px;}
  h1{font-size:1.2rem;margin:0 0 6px;letter-spacing:-.01em;}
  p.lead{color:var(--muted);font-size:.9rem;margin:0 0 20px;line-height:1.5;}
  label{display:block;font-size:.85rem;font-weight:600;margin:14px 0 6px;}
  input[type=text],input[type=password],input[type=tel],input[type=email]{
    width:100%; min-height:46px; padding:11px 13px; border:1.5px solid var(--border);
    border-radius:11px; font-size:1rem; outline:none; transition:border-color .15s, box-shadow .15s;
    background:var(--field); color:var(--text);
  }
  input:focus{border-color:var(--bleu);box-shadow:0 0 0 3px color-mix(in srgb,var(--bleu) 15%,transparent);background:var(--card);}
  input[name=otp]{letter-spacing:.4em;font-size:1.5rem;text-align:center;font-weight:700;}
  button, .btn{
    width:100%; min-height:46px; margin-top:22px; background:var(--bleu); color:#fff;
    border:none; border-radius:11px; font-size:1rem; font-weight:700; cursor:pointer;
    box-shadow:0 8px 20px -8px rgba(23,83,224,.6); transition:filter .15s, transform .08s;
  }
  button:hover{filter:brightness(1.07);}
  button:active{transform:scale(.99);}
  button.secondary{background:transparent;color:var(--bleu);border:1.5px solid var(--border);margin-top:10px;box-shadow:none;}
  button.secondary:hover{border-color:var(--bleu);filter:none;}
  button:disabled{opacity:.55;cursor:not-allowed;}
  .msg{border-radius:11px;padding:11px 15px;font-size:.85rem;margin-bottom:16px;line-height:1.45;border:1px solid transparent;}
  .msg.error{background:var(--err-bg);color:var(--err-tx);border-color:var(--err-ln);}
  .msg.notice{background:var(--ok-bg);color:var(--ok-tx);border-color:var(--ok-ln);}
  .foot{margin-top:20px;text-align:center;font-size:.78rem;color:var(--muted);}
  .foot a{color:var(--bleu);text-decoration:none;font-weight:600;}
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
