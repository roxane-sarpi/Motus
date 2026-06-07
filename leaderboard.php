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

$userObj = new User($db);
$leaders = $userObj->getLeaderboard(10);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motus - Wall of Fame</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .leaderboard-container {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            color: #ecc94b;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }
        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .rank-1 { font-weight: bold; color: #f1c40f; font-size: 1.2rem; }
        .rank-2 { font-weight: bold; color: #bdc3c7; font-size: 1.1rem; }
        .rank-3 { font-weight: bold; color: #cd7f32; font-size: 1.1rem; }
        .back-btn {
            display: inline-block;
            margin-top: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>

    <div class="leaderboard-container">
        <h1 style="font-size: 2rem; margin-top: 0;">🏆 Wall of Fame</h1>
        <p>Les meilleurs joueurs de Motus</p>

        <table>
            <thead>
                <tr>
                    <th>Rang</th>
                    <th>Joueur</th>
                    <th>Victoires</th>
                    <th>Parties</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($leaders) > 0): ?>
                    <?php foreach ($leaders as $index => $player):
                        $rank = $index + 1;
                        $rankClass = $rank <= 3 ? "rank-$rank" : "";
                    ?>
                        <tr>
                            <td class="<?= e($rankClass) ?>">#<?= e($rank) ?></td>
                            <td style="font-weight: bold;"><?= e($player['pseudo']) ?></td>
                            <td style="color: #2ecc71; font-weight: bold;"><?= e($player['games_won']) ?></td>
                            <td><?= e($player['games_played']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Aucun joueur classé pour le moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <a href="game.php" class="back-btn">⬅ Retour au jeu</a>
    </div>

</body>
</html>
