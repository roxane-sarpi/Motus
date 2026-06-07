<?php
require_once 'Security.php';
require_once 'Database.php';
require_once 'User.php';

startSecureSession();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = "Requete invalide, merci de reessayer.";
    } elseif (!empty($pseudo) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        if ($db) {
            $user = new User($db);
            $isRegistered = $user->register($pseudo, $password);

            if ($isRegistered) {
                $message = "Inscription reussie ! Tu peux maintenant te connecter.";
            } else {
                $message = "Ce pseudo est deja pris, choisis-en un autre.";
            }
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
    <title>Motus - Inscription</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>Créer un compte Motus</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?= e($message) ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div>
            <label for="pseudo">Pseudo :</label>
            <input type="text" id="pseudo" name="pseudo" required maxlength="30">
        </div>
        <br>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <button type="submit">S'inscrire</button>
        <p><a href="login.php" style="color: #3498db; text-decoration: none;">Déjà un compte ? Se connecter</a></p>
    </form>

</body>
</html>
