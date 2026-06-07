<?php
// Fichier : dictionary.php

// 1. On lit le fichier texte
// FILE_IGNORE_NEW_LINES : supprime les retours à la ligne invisibles à la fin des mots
// FILE_SKIP_EMPTY_LINES : ignore les lignes vides pour éviter les bugs
$words = file(__DIR__ . '/words.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// 2. Par sécurité, on s'assure que tous les mots sont bien en majuscules et sans espaces parasites
$cleanWords = array_map(function($word) {
    return strtoupper(trim($word));
}, $words);

// 3. On retourne le tableau propre
return $cleanWords;