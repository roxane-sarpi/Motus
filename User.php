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
        
    }
}