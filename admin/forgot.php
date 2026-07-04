<?php
/**
 * forgot.php — Mot de passe oublié, étape 1 : saisie de l'identifiant.
 * Si l'identifiant correspond au compte, un code à 6 chiffres est envoyé
 * par email à l'administrateur (webmaster@zotauto.re), puis redirige vers reset.php.
 * Message générique pour ne pas révéler si l'identifiant existe.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/_shell.php';

// Pas de compte encore créé : rien à réinitialiser.
if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}

$error  = '';
$notice = '';

$_SESSION['reset_req_tries']     = $_SESSION['reset_req_tries']     ?? 0;
$_SESSION['reset_req_lock_until'] = $_SESSION['reset_req_lock_until'] ?? 0;

$locked = $_SESSION['reset_req_lock_until'] > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de réessayer.';
    } else {
        $user = trim((string)($_POST['user'] ?? ''));

        // Throttle d'envoi : 60s minimum entre deux demandes.
        $last = (int)($_SESSION['reset_last_sent'] ?? 0);
        if (time() - $last < 60 && $last > 0) {
            $wait = 60 - (time() - $last);
            $error = "Merci de patienter encore {$wait}s avant de redemander un code.";
        } else {
            $auth = include AUTH_FILE;
            $validUser = is_array($auth) && hash_equals((string)($auth['user'] ?? ''), $user);

            if ($validUser) {
                $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                $_SESSION['reset_hash']      = password_hash($code, PASSWORD_DEFAULT);
                $_SESSION['reset_exp']       = time() + OTP_TTL;
                $_SESSION['reset_tries']     = 0;
                $_SESSION['reset_user']      = $auth['user'];
                $_SESSION['reset_last_sent'] = time();

                $subject = 'Code de réinitialisation — Espace admin ZOT AUTO';
                $body = "Vous avez demandé la réinitialisation du mot de passe de l'espace admin ZOT AUTO.\n\n"
                      . "Votre code de réinitialisation est : {$code} (valable 10 minutes).\n\n"
                      . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : "
                      . "votre mot de passe actuel reste inchangé.\n";
                $headers = "From: " . MAIL_FROM . "\r\n"
                         . "Reply-To: " . MAIL_FROM . "\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n"
                         . "X-Mailer: PHP/" . phpversion();

                @mail(WEBMASTER_EMAIL, $subject, $body, $headers);
                unset($code);

                session_regenerate_id(false);
                header('Location: reset.php');
                exit;
            }

            // Identifiant inconnu : on compte l'échec (anti-bruteforce) mais on
            // affiche le même message générique pour ne pas révéler l'identifiant.
            $_SESSION['reset_req_tries']++;
            if ($_SESSION['reset_req_tries'] >= MAX_LOGIN_TRIES) {
                $_SESSION['reset_req_lock_until'] = time() + LOGIN_LOCK_SECONDS;
                $_SESSION['reset_req_tries'] = 0;
                $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
            } else {
                $notice = "Si cet identifiant existe, un code de réinitialisation vient d'être "
                        . "envoyé à l'adresse e-mail de l'administrateur.";
            }
        }
    }
} elseif ($locked) {
    $remaining = max(1, (int)ceil(($_SESSION['reset_req_lock_until'] - time()) / 60));
    $error = "Trop de tentatives. Réessayez dans environ {$remaining} min.";
}

render_page('Mot de passe oublié', function () use ($locked) {
    ?>
    <h1>Mot de passe oublié</h1>
    <p class="lead">
      Saisissez votre identifiant. Un code à 6 chiffres sera envoyé par e-mail
      à l'administrateur pour définir un nouveau mot de passe.
    </p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="user">Identifiant</label>
      <input type="text" id="user" name="user" required autocomplete="username" <?= $locked ? 'disabled' : '' ?>>
      <button type="submit" <?= $locked ? 'disabled' : '' ?>>Envoyer le code</button>
    </form>
    <div class="foot"><a href="login.php">← Retour à la connexion</a></div>
    <?php
}, $error, $notice);
