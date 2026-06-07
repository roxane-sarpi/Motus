<?php
class Database {
    // 1. On déclare notre propriété tout en haut
    private $connexion; 

    public function __construct() {
        $host = "localhost";
        $dbname = "motus";
        $username = "root";
        $password = "";

        try {
            // 2. On sauvegarde la connexion PDO dans notre propriété avec $this->
            $this->connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            
        } catch (Exception $e) {
            echo "Erreur de connexion : " . $e->getMessage();
        }
    }
}