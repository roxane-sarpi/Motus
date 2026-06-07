<?php
require_once 'Security.php';
startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Database.php';
require_once 'User.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    exit('Erreur temporaire, merci de reessayer plus tard.');
}

$sqlStats = "SELECT games_played, games_won FROM users WHERE id_users = :id";
$stmtStats = $db->prepare($sqlStats);
$stmtStats->execute([':id' => $_SESSION['user_id']]);
$playerStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

$total = $playerStats['games_played'] ?? 0;
$victoires = $playerStats['games_won'] ?? 0;
$ratio = $total > 0 ? round(($victoires / $total) * 100) : 0;

$dictionary = require_once 'dictionary.php';
$maxAttempts = 6;

if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = [];
    $_SESSION['game_over'] = false;
    $_SESSION['won'] = false;
}

if (!isset($_SESSION['secret_word'])) {
    $randomIndex = array_rand($dictionary);
    $_SESSION['secret_word'] = $dictionary[$randomIndex];
    $_SESSION['attempts'] = [];
    $_SESSION['game_over'] = false;
    $_SESSION['won'] = false;
}

$secretWord = $_SESSION['secret_word'];
$wordLength = strlen($secretWord);
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = "Requete invalide, merci de reessayer.";
    } elseif (isset($_POST['reset'])) {
        $_SESSION['attempts'] = [];
        $_SESSION['game_over'] = false;
        $_SESSION['won'] = false;
        unset($_SESSION['secret_word']);

        header("Location: game.php");
        exit();
    } elseif (isset($_POST['guess']) && !$_SESSION['game_over']) {
        $guess = strtoupper(trim($_POST['guess']));

        if (strlen($guess) !== $wordLength || !preg_match('/^[A-Z]+$/', $guess)) {
            $message = "Le mot doit faire exactement $wordLength lettres.";
        } else {
            $analysis = array_fill(0, $wordLength, 'wrong');
            $secretFlags = array_fill(0, $wordLength, false);
            $guessFlags = array_fill(0, $wordLength, false);

            for ($i = 0; $i < $wordLength; $i++) {
                if ($guess[$i] === $secretWord[$i]) {
                    $analysis[$i] = 'correct';
                    $secretFlags[$i] = true;
                    $guessFlags[$i] = true;
                }
            }

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

            $_SESSION['attempts'][] = [
                'word' => $guess,
                'analysis' => $analysis
            ];

            if ($guess === $secretWord) {
                $_SESSION['won'] = true;
                $_SESSION['game_over'] = true;
                $message = "Bravo ! Tu as trouve le mot secret !";

                $userObj = new User($db);
                $userObj->updateStats($_SESSION['user_id'], true);

                $victoires++;
                $total++;
                $ratio = round(($victoires / $total) * 100);
            } elseif (count($_SESSION['attempts']) >= $maxAttempts) {
                $_SESSION['game_over'] = true;
                $message = "Dommage ! Le mot secret etait : $secretWord";

                $userObj = new User($db);
                $userObj->updateStats($_SESSION['user_id'], false);

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
        .header-game { width: 100%; max-width: 400px; display: flex; flex-direction: column; align-items: flex-start; gap: 5px; margin-bottom: 20px; }
        .header-top { display: flex; justify-content: space-between; width: 100%; }
        .stats-bar { font-size: 14px; color: #555; background: #eef2f3; padding: 8px 10px; border-radius: 4px; width: 100%; box-sizing: border-box; text-align: center; }
        .logout-btn { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .grid { display: flex; flex-direction: column; gap: 6px; margin: 20px 0; }
        .row { display: flex; gap: 6px; justify-content: center; }
        .letter {
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: bold; border: 2px solid #ccc; background: white; border-radius: 4px;
        }
        .correct { background-color: #e74c3c; color: white; border-color: #e74c3c; }
        .misplaced { background-color: #f1c40f; color: white; border-color: #f1c40f; }
        .wrong { background-color: #7f8c8d; color: white; border-color: #7f8c8d; }
    </style>
</head>
<body>

    <div class="header-game">
        <div class="header-top">
            <span>👤 <strong><?= e($_SESSION['pseudo']) ?></strong></span>
            <div>
                <a href="leaderboard.php" style="color: #f1c40f; text-decoration: none; margin-right: 15px; font-weight: bold;">🏆 Classement</a>
                <a href="logout.php" class="logout-btn">Quitter</a>
            </div>
        </div>
        <div class="stats-bar">
            📊 Parties : <strong><?= e($total) ?></strong> | Victoires : <strong><?= e($victoires) ?></strong> (<?= e($ratio) ?>%)
        </div>
    </div>

    <h1>MOTUS</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?= e($message) ?></strong></p>
    <?php endif; ?>

    <div class="grid">
        <?php foreach ($_SESSION['attempts'] as $attempt): ?>
            <div class="row">
                <?php foreach ($attempt['analysis'] as $index => $status): ?>
                    <?php $statusClass = in_array($status, ['correct', 'misplaced', 'wrong'], true) ? $status : 'wrong'; ?>
                    <span class="letter <?= e($statusClass) ?>"><?= e($attempt['word'][$index] ?? '') ?></span>
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
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if (!$_SESSION['game_over']): ?>
            <div>
                <label for="guess" style="text-align: center;">Ta proposition (<?= e($wordLength) ?> lettres) :</label>
                <input type="text" id="guess" name="guess" autofocus required maxlength="<?= e($wordLength) ?>" pattern="[A-Za-z]+" style="text-transform: uppercase; text-align: center; font-size: 18px; font-weight: bold; letter-spacing: 2px;">
            </div>
            <button type="submit">Valider</button>
        <?php else: ?>
            <button type="submit" name="reset" style="background-color: #2ecc71;">Recommencer une partie</button>
        <?php endif; ?>
    </form>

</body>
</html>
