<?php
// 1. On inclut nos classes pour pouvoir s'en servir
require_once 'Database.php';
require_once 'User.php';

// Variable pour stocker le message de succès ou d'erreur
$message = "";

// 2. On vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // On récupère les données tapées par l'utilisateur
    $pseudo = trim($_POST['pseudo']);
    $password = $_POST['password'];

    if (!empty($pseudo) && !empty($password)) {
        // 3. On initie la base de données et on récupère la connexion PDO
        $database = new Database();
        $db = $database->getConnection();

        if ($db) {
            // 4. On crée notre utilisateur et on tente l'inscription
            $user = new User($db);
            $isRegistered = $user->register($pseudo, $password);

            if ($isRegistered) {
                $message = "Inscription réussie ! Tu peux maintenant te connecter.";
            } else {
                $message = "Ce pseudo est déjà pris, choisis-en un autre.";
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
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
        <p><strong><?= $message ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
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