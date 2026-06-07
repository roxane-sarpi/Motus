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
        // Crytage du mot de passe
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
}
    