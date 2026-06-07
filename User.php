<?php
class User {
    // Déclaration de la propriété privée
    private $db;

    // Le constructeur qui reçoit la connexion
    public function __construct($db) {
        // Sauvegarde de la connexion dans la classe
        $this->db = $db;
    }

    // Préparation de la méthode pour inscrire un joueur
    public function register($pseudo, $password) {
        // Cryptage du mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // On prépare la requête SQL d'insertion
        $sql = "INSERT INTO users (pseudo, password) VALUES (:pseudo, :password)";
        $stmt = $this->db->prepare($sql);

        // On exécute la requête en liant nos variables de manière sécurisée
        try {
            $stmt->execute([
                ':pseudo' => $pseudo,
                ':password' => $hashedPassword
            ]);
            return true; // L'inscription a fonctionné
        } catch (PDOException $e) {
            // Si le pseudo existe déjà, ça déclenchera une erreur qu'on attrape ici
            return false; 
        }
    }

    // Méthode pour connecter un joueur
    public function login($pseudo, $password) {
        // On prépare la requête SQL pour récupérer l'utilisateur
        $sql = "SELECT id_users AS id, pseudo, password FROM users WHERE pseudo = :pseudo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pseudo' => $pseudo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si l'utilisateur existe et que le mot de passe est correct
        $passwordIsValid = false;

        if ($user) {
            $storedPassword = (string) $user['password'];
            $passwordIsValid = password_verify($password, $storedPassword);

            // Compatibilité avec les comptes créés avant le hashage des mots de passe.
            if (!$passwordIsValid && hash_equals($storedPassword, $password)) {
                $passwordIsValid = true;

                $updateSql = "UPDATE users SET password = :password WHERE id_users = :id";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':id' => $user['id']
                ]);
            }
        }

        if ($user && $passwordIsValid) {
            
            // Création du "bracelet VIP" (la session)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            
            return true; // Connexion réussie
        }
        return false; // Échec de la connexion
    }

    // Méthode pour mettre à jour les statistiques du joueur
    public function updateStats($userId, $isVictory) {
        // 1. On augmente toujours le nombre de parties jouées
        // 2. Si c'est une victoire, on augmente aussi les parties gagnées
        if ($isVictory) {
            $sql = "UPDATE users SET games_played = games_played + 1, games_won = games_won + 1 WHERE id_users = :id";
        } else {
            $sql = "UPDATE users SET games_played = games_played + 1 WHERE id_users = :id";
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }
    // Méthode pour récupérer les meilleurs joueurs (Wall of Fame)
    public function getLeaderboard($limit = 10) {
        // On récupère les joueurs qui ont au moins joué 1 partie, triés par victoires
        $sql = "SELECT pseudo, games_played, games_won FROM users WHERE games_played > 0 ORDER BY games_won DESC, games_played ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        // On sécurise la limite (PDO::PARAM_INT est obligatoire pour un LIMIT)
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}