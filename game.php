<?php
session_start();

// Protection : si le joueur n'est pas connecté, redirection immédiate
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Configuration de la partie (Mot fixe pour le MVP, à rendre dynamique plus tard)
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

// Traitement de l'action du joueur
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

            // Vérification des conditions de fin de partie
            if ($guess === $secretWord) {
                $_SESSION['won'] = true;
                $_SESSION['game_over'] = true;
                $message = "Bravo ! Tu as trouvé le mot secret !";
            } elseif (count($_SESSION['attempts']) >= $maxAttempts) {
                $_SESSION['game_over'] = true;
                $message = "Dommage ! Le mot secret était : $secretWord";
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
        body.game-page {
            justify-content: flex-start;
            align-items: center;
            padding: 96px 16px 32px;
            height: auto;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .header-game { width: 100%; max-width: 400px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .logout-btn { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .grid { display: flex; flex-direction: column; gap: 6px; margin: 20px 0; }
        .row { display: flex; gap: 6px; justify-content: center; }
        .letter {
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: bold; border: 2px solid #ccc; background: white; border-radius: 4px;
        }
        /* Couleurs sémantiques Motus */
        .correct { background-color: #e74c3c; color: white; border-color: #e74c3c; }   /* Rouge bien placé */
        .misplaced { background-color: #f1c40f; color: white; border-color: #f1c40f; } /* Jaune mal placé */
        .wrong { background-color: #7f8c8d; color: white; border-color: #7f8c8d; }     /* Gris absent */
    </style>
    <script>
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.addEventListener('load', function () {
            window.scrollTo(0, 0);
        });
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

    <div class="grid">
        <?php foreach ($_SESSION['attempts'] as $attempt): ?>
            <div class="row">
                <?php foreach ($attempt['analysis'] as $index => $status): ?>
                    <span class="letter <?= $status ?>"><?= $attempt['word'][$index] ?></span>
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

    <form method="POST" action="game.php" style="max-width: 400px;">
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

</body>
</html>