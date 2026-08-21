<?php
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gestion_scolaire_test');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'sql207.infinityfree.com');
    define('DB_NAME', 'if0_42695792_gestion');
    define('DB_USER', 'if0_42695792');
    define('DB_PASS', 'Joanespe');
}

function getConnection() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        ensureSchema($pdo);

        return $pdo;
    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}

function ensureSchema($pdo) {
    // Colonne must_change_password (migration historique)
    if (!columnExists($pdo, 'utilisateurs', 'must_change_password')) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER statut");
    }

    // Colonne telephone pour l'envoi du mot de passe par SMS
    if (!columnExists($pdo, 'utilisateurs', 'telephone')) {
        $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN telephone VARCHAR(20) DEFAULT NULL AFTER email");
    }

    // Table cycles
    $pdo->exec("CREATE TABLE IF NOT EXISTS cycles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL UNIQUE,
        code VARCHAR(20) DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Table classes + colonne cycle_id
    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        niveau VARCHAR(50) DEFAULT NULL,
        cycle_id INT DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        capacite INT NOT NULL DEFAULT 40
    ) ENGINE=InnoDB");
    if (!columnExists($pdo, 'classes', 'cycle_id')) {
        $pdo->exec("ALTER TABLE classes ADD COLUMN cycle_id INT DEFAULT NULL AFTER niveau");
    }

    // Table eleves
    $pdo->exec("CREATE TABLE IF NOT EXISTS eleves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        date_naissance DATE DEFAULT NULL,
        sexe ENUM('M', 'F') NOT NULL DEFAULT 'M',
        parent_nom VARCHAR(150) DEFAULT NULL,
        parent_tel VARCHAR(20) DEFAULT NULL,
        adresse VARCHAR(255) DEFAULT NULL,
        classe_id INT DEFAULT NULL,
        date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Table enseignants
    $pdo->exec("CREATE TABLE IF NOT EXISTS enseignants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        telephone VARCHAR(20) DEFAULT NULL,
        specialite VARCHAR(100) DEFAULT NULL,
        date_embauche DATE DEFAULT NULL
    ) ENGINE=InnoDB");

    // Table de liaison enseignants <-> classes
    $pdo->exec("CREATE TABLE IF NOT EXISTS enseignants_classes (
        enseignant_id INT NOT NULL,
        classe_id INT NOT NULL,
        PRIMARY KEY (enseignant_id, classe_id)
    ) ENGINE=InnoDB");
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
