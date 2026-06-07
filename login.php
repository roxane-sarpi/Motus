<?php
require_once 'Security.php';
require_once 'Database.php';
require_once 'User.php';

startSecureSession();

$message = "";
$maxLoginAttempts = 5;
$lockoutSeconds = 300;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = $_POST['password'] ?? '';
    $now = time();

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = "Requete invalide, merci de reessayer.";
    } elseif (isset($_SESSION['login_locked_until']) && $_SESSION['login_locked_until'] > $now) {
        $remaining = $_SESSION['login_locked_until'] - $now;
        $message = "Trop de tentatives. Merci de reessayer dans " . ceil($remaining / 60) . " minute(s).";
    } elseif (!empty($pseudo) && !empty($password)) {
        usleep(250000);

        $database = new Database();
        $db = $database->getConnection();

        if ($db) {
            $user = new User($db);

            if ($user->login($pseudo, $password)) {
                unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
                header("Location: game.php");
                exit();
            }

            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

            if ($_SESSION['login_attempts'] >= $maxLoginAttempts) {
                $_SESSION['login_locked_until'] = $now + $lockoutSeconds;
            }

            $message = "Pseudo ou mot de passe incorrect.";
        } else {
            $message = "Erreur temporaire, merci de reessayer plus tard.";
        }
    } else {
        $message = "Merci de remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motus - Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>Connexion à Motus</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?= e($message) ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div>
            <label for="pseudo">Pseudo :</label>
            <input type="text" id="pseudo" name="pseudo" required>
        </div>
        <br>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <button type="submit">Se connecter</button>
        <p><a href="register.php" style="color: #3498db; text-decoration: none;">Pas encore de compte ? S'inscrire</a></p>
    </form>

</body>
</html>
