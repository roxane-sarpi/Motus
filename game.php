<?php
session_start();

// Protection : si le joueur n'est pas connecté, redirection immédiate
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// On inclut nos classes
require_once 'Database.php';
require_once 'User.php';

// --- 1. RÉCUPÉRATION DES STATISTIQUES ---
$database = new Database();
$db = $database->getConnection();

// On utilise bien "id_users" ici aussi pour correspondre à ta base !
$sqlStats = "SELECT games_played, games_won FROM users WHERE id_users = :id";
$stmtStats = $db->prepare($sqlStats);
$stmtStats->execute([':id' => $_SESSION['user_id']]);
$playerStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$total = $playerStats['games_played'] ?? 0;
$victoires = $playerStats['games_won'] ?? 0;
// Calcul du pourcentage de victoire sécurisé
$ratio = $total > 0 ? round(($victoires / $total) * 100) : 0;

// --- 2. CONFIGURATION DE LA PARTIE ---
$secretWord = "MOTUS"; 
$wordLength = strlen($secretWord);
$maxAttempts = 6;

// Initialisation de la session de jeu
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = [];
    $_SESSION['game_over'] = false;
    $_SESSION['won'] = false;
}

$message = "";

// --- 3. TRAITEMENT DE L'ACTION DU JOUEUR ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Cas du bouton Recommencer
    if (isset($_POST['reset'])) {
        $_SESSION['attempts'] = [];
        $_SESSION['game_over'] = false;
        $_SESSION['won'] = false;
        header("Location: game.php");
        exit();
    }

    // Traitement d'une proposition
    if (isset($_POST['guess']) && !$_SESSION['game_over']) {
        $guess = strtoupper(trim($_POST['guess']));

        if (strlen($guess) !== $wordLength) {
            $message = "⚠️ Le mot doit faire exactement $wordLength lettres !";
        } else {
            // --- ALGORITHME DE VALIDATION (Double Balayage) ---
            $analysis = array_fill(0, $wordLength, 'wrong');
            $secretFlags = array_fill(0, $wordLength, false);
            $guessFlags = array_fill(0, $wordLength, false);

            // 1er passage : Lettres bien placées (Rouge)
            for ($i = 0; $i < $wordLength; $i++) {
                if ($guess[$i] === $secretWord[$i]) {
                    $analysis[$i] = 'correct';
                    $secretFlags[$i] = true;
                    $guessFlags[$i] = true;
                }
            }

            // 2ème passage : Lettres mal placées (Jaune)
            for ($i = 0; $i < $wordLength; $i++) {
                if (!$guessFlags[$i]) {
                    for ($j = 0; $j < $wordLength; $j++) {
                        if (!$secretFlags[$j] && $guess[$i] === $secretWord[$j]) {
                            $analysis[$i] = 'misplaced';
                            $secretFlags[$j] = true;
                            break;
                        }
                    }
                }
            }

            // Sauvegarde de l'essai en session
            $_SESSION['attempts'][] = [
                'word' => $guess,
                'analysis' => $analysis
            ];

            // --- CONDITIONS DE FIN DE PARTIE ET SAUVEGARDE ---
            if ($guess === $secretWord) {
                $_SESSION['won'] = true;
                $_SESSION['game_over'] = true;
                $message = "🎉 Bravo ! Tu as trouvé le mot secret !";
                
                // SAUVEGARDE DE LA VICTOIRE EN BDD
                $userObj = new User($db);
                $userObj->updateStats($_SESSION['user_id'], true);

                // Mise à jour de l'affichage instantanée (pour ne pas avoir à rafraîchir)
                $victoires++;
                $total++;
                $ratio = round(($victoires / $total) * 100);

            } elseif (count($_SESSION['attempts']) >= $maxAttempts) {
                $_SESSION['game_over'] = true;
                $message = "💥 Dommage ! Le mot secret était : $secretWord";
                
                // SAUVEGARDE DE LA DÉFAITE EN BDD
                $userObj = new User($db);
                $userObj->updateStats($_SESSION['user_id'], false);

                // Mise à jour de l'affichage instantanée
                $total++;
                $ratio = $total > 0 ? round(($victoires / $total) * 100) : 0;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motus - Le Jeu</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Header and layout tweaks specific to game page */
        body.game-page { display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 56px 16px 32px; min-height: 100vh; box-sizing: border-box; }
        .header-game { width: 100%; max-width: 900px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; color: #2c3e50; }
        .logout-btn { color: #e74c3c; text-decoration: none; font-weight: bold; }

        /* Grid and letters */
        .grid { display: flex; flex-direction: column; gap: 6px; margin: 20px 0; }
        .row { display: flex; gap: 6px; justify-content: center; }
        .letter { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; border: 2px solid #ccc; background: white; border-radius: 4px; }
        .correct { background-color: #e74c3c; color: white; border-color: #e74c3c; }
        .misplaced { background-color: #f1c40f; color: white; border-color: #f1c40f; }
        .wrong { background-color: #7f8c8d; color: white; border-color: #7f8c8d; }

        /* Two-column main content */
        .main-content { display: grid; grid-template-columns: 260px 1fr; gap: 28px; width: 100%; max-width: 900px; align-items: start; box-sizing: border-box; }
        .rules { box-sizing: border-box; background: white; padding: 16px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.06); color: #2c3e50; }
        .rules h2 { margin-top: 0; font-size: 18px; }
        .rules ul { padding-left: 18px; margin: 8px 0 0 0; }
        .rules li { margin-bottom: 8px; line-height: 1.3; }
        .dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px; vertical-align: middle; }
        .dot.correct { background: #e74c3c; }
        .dot.misplaced { background: #f1c40f; }
        .dot.wrong { background: #7f8c8d; }
        .game-area { flex: 1 1 0; }
        .guess-form { margin-top: 14px; max-width: 420px; }

        @media (max-width: 600px) {
            .main-content { display: flex; flex-direction: column; align-items: center; }
            .rules { width: 100%; order: 2; }
            .game-area { order: 1; width: 100%; }
            .guess-form { width: 100%; }
            body.game-page { padding: 24px 12px; }
        }
    </style>
    <script>
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        window.addEventListener('load', function () { window.scrollTo(0,0); });
    </script>
</head>
<body class="game-page">

    <div class="header-game">
        <span>Joueur : <strong><?= htmlspecialchars($_SESSION['pseudo']) ?></strong></span>
        <a href="logout.php" class="logout-btn">Quitter</a>
    </div>

    <h1>MOTUS</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?= $message ?></strong></p>
    <?php endif; ?>

    <div class="main-content">

        <aside class="rules" aria-labelledby="rules-title">
            <h2 id="rules-title">Règles &amp; Stats</h2>
            <ul>
                <li>Devinez le mot en <strong><?= $maxAttempts ?></strong> essais.</li>
                <li>Le mot fait <strong><?= $wordLength ?></strong> lettres.</li>
                <li><span class="dot correct" aria-hidden="true"></span> Lettre bien placée (rouge).</li>
                <li><span class="dot misplaced" aria-hidden="true"></span> Lettre mal placée (jaune).</li>
                <li><span class="dot wrong" aria-hidden="true"></span> Lettre absente (gris).</li>
            </ul>

            <hr>
            <h3 style="margin-top:10px;">Your stats</h3>
            <p>Games played: <strong><?= htmlspecialchars($total) ?></strong></p>
            <p>Games won: <strong><?= htmlspecialchars($victoires) ?></strong></p>
            <p>Win rate: <strong><?= htmlspecialchars($ratio) ?>%</strong></p>
        </aside>

        <div class="game-area">

            <div class="grid">
                <?php foreach ($_SESSION['attempts'] as $attempt): ?>
                    <div class="row">
                        <?php foreach ($attempt['analysis'] as $index => $status): ?>
                            <span class="letter <?= $status ?>"><?= htmlspecialchars($attempt['word'][$index]) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php 
                $remaining = $maxAttempts - count($_SESSION['attempts']);
                for ($r = 0; $r < $remaining; $r++): 
                ?>
                    <div class="row">
                        <?php for ($l = 0; $l < $wordLength; $l++): ?>
                            <span class="letter"></span>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <form method="POST" action="game.php" class="guess-form">
                <?php if (!$_SESSION['game_over']): ?>
                    <div>
                        <label for="guess">Ta proposition (<?= $wordLength ?> lettres) :</label>
                        <input type="text" id="guess" name="guess" required maxlength="<?= $wordLength ?>" style="text-transform: uppercase;">
                    </div>
                    <button type="submit">Valider</button>
                <?php else: ?>
                    <button type="submit" name="reset" style="background-color: #2ecc71;">Recommencer une partie</button>
                <?php endif; ?>
            </form>

        </div>

    </div>

</body>
</html>