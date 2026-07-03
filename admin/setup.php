<?php
/**
 * setup.php — Première configuration du compte admin (identifiant + mot de passe).
 * Une fois .auth.php écrit, cette page refuse définitivement de se ré-exécuter.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/_shell.php';

// Si le compte existe déjà, on ne repasse plus jamais par ici.
if (file_exists(AUTH_FILE)) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expirée, merci de réessayer.';
    } else {
        $user    = trim((string)($_POST['user'] ?? ''));
        $pw      = (string)($_POST['password'] ?? '');
        $pw2     = (string)($_POST['password2'] ?? '');

        if ($user === '' || mb_strlen($user) < 3) {
            $error = "L'identifiant doit contenir au moins 3 caractères.";
        } elseif (strlen($pw) < 8) {
            $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($pw !== $pw2) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } elseif (file_exists(AUTH_FILE)) {
            // Vérification anti-course : un autre onglet a pu créer le fichier entre-temps.
            $error = "Le compte a déjà été configuré. Rendez-vous sur la page de connexion.";
        } else {
            $data = [
                'user' => $user,
                'hash' => password_hash($pw, PASSWORD_DEFAULT),
            ];
            $php = "<?php\nreturn " . var_export($data, true) . ";\n";

            $tmp = AUTH_FILE . '.tmp_' . bin2hex(random_bytes(4));
            file_put_contents($tmp, $php, LOCK_EX);
            chmod($tmp, 0600);
            rename($tmp, AUTH_FILE);

            // Le mot de passe ne doit jamais rester en mémoire de session.
            unset($pw, $pw2, $data, $php);

            session_regenerate_id(true);
            header('Location: login.php?welcome=1');
            exit;
        }
    }
}

render_page('Configuration initiale', function () {
    ?>
    <h1>Créer le compte admin</h1>
    <p class="lead">
      Cette page ne s'affichera qu'une seule fois. Choisissez un identifiant
      et un mot de passe robuste (8 caractères minimum) pour protéger
      l'éditeur de catalogue.
    </p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label for="user">Identifiant</label>
      <input type="text" id="user" name="user" required minlength="3" autocomplete="username">

      <label for="password">Mot de passe</label>
      <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">

      <label for="password2">Confirmer le mot de passe</label>
      <input type="password" id="password2" name="password2" required minlength="8" autocomplete="new-password">

      <button type="submit">Créer le compte</button>
    </form>
    <?php
}, $error);
