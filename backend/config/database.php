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

        $result = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE 'must_change_password'");
        if ($result->rowCount() === 0) {
            $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER statut");
        }

        return $pdo;
    } catch (PDOException $e) {
        die('Erreur de connexion : ' . $e->getMessage());
    }
}
