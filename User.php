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
            
            // ---> LIGNES AJOUTÉES : Création du "bracelet VIP" (la session) <---
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            
            return true; // Connexion réussie
        }
        return false; // Échec de la connexion
    }
} 