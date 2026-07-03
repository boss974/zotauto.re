<?php
/**
 * verify.php — Étape 2 : saisie du code à 6 chiffres reçu par email (OTP).
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/_shell.php';

if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}

if (!empty($_SESSION['admin_ok'])) {
    header('Location: index.php');
    exit;
}

// Il faut être passé par login.php avant.
if (empty($_SESSION['otp_hash']) || empty($_SESSION['pending_user'])) {
    header('Location: login.php');
    exit;
}

$error  = '';
$notice = '';

function otp_reset(): void
{
    unset(
        $_SESSION['otp_hash'],
        $_SESSION['otp_exp'],
        $_SESSION['otp_tries'],
        $_SESSION['pending_user'],
        $_SESSION['otp_last_sent']
    );
}

// Expiration de l'OTP lui-même
if (($_SESSION['otp_exp'] ?? 0) < time()) {
    otp_reset();
    header('Location: login.php?expired=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de réessayer.';
    } elseif (isset($_POST['resend'])) {
        $last = (int)($_SESSION['otp_last_sent'] ?? 0);
        if (time() - $last < 60) {
            $wait = 60 - (time() - $last);
            $error = "Merci de patienter encore {$wait}s avant de redemander un code.";
        } else {
            $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['otp_hash']      = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['otp_exp']       = time() + OTP_TTL;
            $_SESSION['otp_tries']     = 0;
            $_SESSION['otp_last_sent'] = time();

            $subject = 'Nouveau code de connexion — Espace admin ZOT AUTO';
            $body = "Votre code de connexion à l'espace admin ZOT AUTO est : {$code} (valable 10 minutes).\n\n"
                  . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n";
            $headers = "From: " . MAIL_FROM . "\r\n"
                     . "Reply-To: " . MAIL_FROM . "\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n"
                     . "X-Mailer: PHP/" . phpversion();

            @mail(WEBMASTER_EMAIL, $subject, $body, $headers);
            unset($code);

            $notice = 'Un nouveau code vient d\'être envoyé.';
        }
    } else {
        $code = trim((string)($_POST['otp'] ?? ''));

        if (($_SESSION['otp_tries'] ?? 0) >= MAX_OTP_TRIES) {
            otp_reset();
            header('Location: login.php?locked=1');
            exit;
        }

        $hash = (string)($_SESSION['otp_hash'] ?? '');
        // password_verify() est déjà en temps constant (hash_equals en interne).
        $valid = $code !== '' && $hash !== '' && strlen($code) === 6 && ctype_digit($code)
            && password_verify($code, $hash);

        if ($valid) {
            $_SESSION['admin_ok']    = true;
            $_SESSION['admin_since'] = time();
            $_SESSION['admin_user']  = $_SESSION['pending_user'];
            otp_reset();
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['otp_tries'] = ($_SESSION['otp_tries'] ?? 0) + 1;
            if ($_SESSION['otp_tries'] >= MAX_OTP_TRIES) {
                otp_reset();
                header('Location: login.php?locked=1');
                exit;
            }
            $remaining = MAX_OTP_TRIES - $_SESSION['otp_tries'];
            $error = "Code incorrect. Tentatives restantes : {$remaining}.";
        }
    }
}

render_page('Vérification', function () {
    ?>
    <h1>Code de vérification</h1>
    <p class="lead">
      Un code à 6 chiffres a été envoyé par email à l'administrateur.
      Saisissez-le ci-dessous pour finaliser la connexion.
    </p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="otp">Code reçu par email</label>
      <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
             autocomplete="one-time-code" required autofocus>
      <button type="submit">Valider</button>
    </form>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="resend" value="1">
      <button type="submit" class="secondary">Renvoyer un code</button>
    </form>
    <?php
}, $error, $notice);
