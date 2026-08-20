<?php
/**
 * Script de migration automatique.
 * Vérifie et ajoute la colonne must_change_password si elle n'existe pas.
 * A appeler une fois au démarrage ou via le navigateur.
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getConnection();

    // Vérifier si la colonne existe
    $result = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE 'must_change_password'");
    if ($result->rowCount() === 0) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER statut");
        echo "Colonne 'must_change_password' ajoutee avec succes.<br>";
    } else {
        echo "Colonne 'must_change_password' deja presente.<br>";
    }

    echo "Migration terminee.";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
