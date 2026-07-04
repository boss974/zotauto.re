<?php
/**
 * login.php — Étape 1 : identifiant + mot de passe. Rate-limité.
 * Sur succès : génère un OTP à 6 chiffres, l'envoie par email, redirige vers verify.php.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/_shell.php';

if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}

// Déjà pleinement authentifié ? direct vers l'éditeur.
if (!empty($_SESSION['admin_ok'])) {
    header('Location: index.php');
    exit;
}

$error  = '';
$notice = '';
if (isset($_GET['welcome'])) {
    $notice = 'Compte créé. Vous pouvez vous connecter.';
} elseif (isset($_GET['reset'])) {
    $notice = 'Mot de passe réinitialisé. Connectez-vous avec le nouveau.';
} elseif (isset($_GET['expired'])) {
    $notice = 'Le code a expiré. Merci de recommencer.';
} elseif (isset($_GET['locked'])) {
    $notice = 'Trop de tentatives sur le code. Merci de recommencer.';
}

$_SESSION['login_tries']    = $_SESSION['login_tries']    ?? 0;
$_SESSION['login_lock_until'] = $_SESSION['login_lock_until'] ?? 0;

$locked = $_SESSION['login_lock_until'] > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de réessayer.';
    } else {
        $user = trim((string)($_POST['user'] ?? ''));
        $pw   = (string)($_POST['password'] ?? '');

        $auth = include AUTH_FILE;
        $validUser = is_array($auth) && hash_equals((string)($auth['user'] ?? ''), $user);
        $validPw   = is_array($auth) && isset($auth['hash']) && password_verify($pw, (string)$auth['hash']);

        if ($validUser && $validPw) {
            // Reset anti-bruteforce
            $_SESSION['login_tries'] = 0;
            $_SESSION['login_lock_until'] = 0;

            $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $_SESSION['otp_hash']     = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['otp_exp']      = time() + OTP_TTL;
            $_SESSION['otp_tries']    = 0;
            $_SESSION['pending_user'] = $auth['user'];
            $_SESSION['otp_last_sent'] = time();

            $subject = 'Code de connexion — Espace admin ZOT AUTO';
            $body = "Votre code de connexion à l'espace admin ZOT AUTO est : {$code} (valable 10 minutes).\n\n"
                  . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n";
            $headers = "From: " . MAIL_FROM . "\r\n"
                     . "Reply-To: " . MAIL_FROM . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n"
                     . "X-Mailer: PHP/" . phpversion();

            @mail(WEBMASTER_EMAIL, $subject, $body, $headers);

            unset($code, $pw);

            session_regenerate_id(true);
            header('Location: verify.php');
            exit;
        } else {
            $_SESSION['login_tries']++;
            if ($_SESSION['login_tries'] >= MAX_LOGIN_TRIES) {
                $_SESSION['login_lock_until'] = time() + LOGIN_LOCK_SECONDS;
                $_SESSION['login_tries'] = 0;
                $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
            } else {
                $error = 'Identifiant ou mot de passe incorrect.';
            }
        }
        unset($pw);
    }
} elseif ($locked) {
    $remaining = max(1, (int)ceil(($_SESSION['login_lock_until'] - time()) / 60));
    $error = "Trop de tentatives. Réessayez dans environ {$remaining} min.";
}

render_page('Connexion', function () use ($locked) {
    ?>
    <h1>Connexion</h1>
    <p class="lead">Accès réservé à l'administration du catalogue ZOT AUTO.</p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="user">Identifiant</label>
      <input type="text" id="user" name="user" required autocomplete="username" <?= $locked ? 'disabled' : '' ?>>

      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required autocomplete="current-password" <?= $locked ? 'disabled' : '' ?>>

      <button type="submit" <?= $locked ? 'disabled' : '' ?>>Se connecter</button>
    </form>
    <div class="foot"><a href="forgot.php">Mot de passe oublié ?</a></div>
    <?php
}, $error, $notice);
