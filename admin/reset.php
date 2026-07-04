<?php
/**
 * reset.php — Mot de passe oublié, étape 2 : saisie du code reçu par email
 * + nouveau mot de passe. Sur succès, réécrit .auth.php (écriture atomique).
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/_shell.php';

if (!file_exists(AUTH_FILE)) {
    header('Location: setup.php');
    exit;
}

// Il faut être passé par forgot.php (code généré) avant.
if (empty($_SESSION['reset_hash']) || empty($_SESSION['reset_user'])) {
    header('Location: forgot.php');
    exit;
}

$error  = '';
$notice = '';

function reset_clear(): void
{
    unset(
        $_SESSION['reset_hash'],
        $_SESSION['reset_exp'],
        $_SESSION['reset_tries'],
        $_SESSION['reset_user'],
        $_SESSION['reset_last_sent']
    );
}

// Expiration du code.
if (($_SESSION['reset_exp'] ?? 0) < time()) {
    reset_clear();
    header('Location: forgot.php?expired=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de réessayer.';
    } else {
        if (($_SESSION['reset_tries'] ?? 0) >= MAX_OTP_TRIES) {
            reset_clear();
            header('Location: forgot.php?locked=1');
            exit;
        }

        $code = trim((string)($_POST['otp'] ?? ''));
        $pw   = (string)($_POST['password'] ?? '');
        $pw2  = (string)($_POST['password2'] ?? '');

        $hash = (string)($_SESSION['reset_hash'] ?? '');
        $codeOk = $code !== '' && strlen($code) === 6 && ctype_digit($code)
            && $hash !== '' && password_verify($code, $hash);

        if (!$codeOk) {
            $_SESSION['reset_tries'] = ($_SESSION['reset_tries'] ?? 0) + 1;
            if ($_SESSION['reset_tries'] >= MAX_OTP_TRIES) {
                reset_clear();
                header('Location: forgot.php?locked=1');
                exit;
            }
            $remaining = MAX_OTP_TRIES - $_SESSION['reset_tries'];
            $error = "Code incorrect. Tentatives restantes : {$remaining}.";
        } elseif (strlen($pw) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($pw !== $pw2) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $data = [
                'user' => (string)$_SESSION['reset_user'],
                'hash' => password_hash($pw, PASSWORD_DEFAULT),
            ];
            $php = "<?php\nreturn " . var_export($data, true) . ";\n";

            $tmp = AUTH_FILE . '.tmp_' . bin2hex(random_bytes(4));
            if (file_put_contents($tmp, $php, LOCK_EX) === false) {
                $error = "Impossible d'enregistrer le nouveau mot de passe (droits du dossier). Contactez le support.";
            } else {
                @chmod($tmp, 0600);
                rename($tmp, AUTH_FILE);

                unset($pw, $pw2, $data, $php);
                reset_clear();
                // On repart d'une session propre : plus aucun état d'auth ne subsiste.
                $_SESSION['admin_ok'] = false;
                session_regenerate_id(true);
                header('Location: login.php?reset=1');
                exit;
            }
        }
        unset($pw, $pw2);
    }
}

render_page('Nouveau mot de passe', function () {
    ?>
    <h1>Nouveau mot de passe</h1>
    <p class="lead">
      Saisissez le code à 6 chiffres reçu par e-mail, puis choisissez
      votre nouveau mot de passe (8 caractères minimum).
    </p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="otp">Code reçu par email</label>
      <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
             autocomplete="one-time-code" required autofocus>

      <label for="password">Nouveau mot de passe</label>
      <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">

      <label for="password2">Confirmer le mot de passe</label>
      <input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password">

      <button type="submit">Réinitialiser le mot de passe</button>
    </form>
    <div class="foot"><a href="login.php">← Retour à la connexion</a></div>
    <?php
}, $error, $notice);
