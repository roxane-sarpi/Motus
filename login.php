<?php
// On démarre la session tout en haut pour pouvoir rediriger l'utilisateur
session_start();

require_once 'Database.php';
require_once 'User.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pseudo = trim($_POST['pseudo']);
    $password = $_POST['password'];

    if (!empty($pseudo) && !empty($password)) {
        $database = new Database();
        $db = $database->getConnection();

        if ($db) {
            $user = new User($db);
            
            // On utilise notre nouvelle méthode login()
            if ($user->login($pseudo, $password)) {
                // Redirection immédiate vers le jeu
                header("Location: game.php");
                exit();
            } else {
                $message = "Pseudo ou mot de passe incorrect.";
            }
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
        <p><strong><?= $message ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
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