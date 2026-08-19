-- ========================================
-- Base de données : gestion_scolaire
-- ========================================

CREATE DATABASE IF NOT EXISTS gestion_scolaire
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE gestion_scolaire;

-- ========================================
-- Table : utilisateurs
-- ========================================

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'directeur') NOT NULL DEFAULT 'directeur',
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Compte admin par défaut
-- Email : admin@anna.com
-- Mot de passe : admin123
-- ========================================

INSERT INTO utilisateurs (nom, prenom, email, password, role, statut)
VALUES ('Admin', 'Super', 'admin@anna.com', '$2y$10$VWoowZCMICr4Zhml4w2St.YUfj/h4BXsHvYu.CIif5iETeF.P3v3m', 'admin', 'actif');
