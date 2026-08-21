-- ========================================
-- Base de données : gestion_scolaire_test
-- ========================================

CREATE DATABASE IF NOT EXISTS gestion_scolaire_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE gestion_scolaire_test;

-- ========================================
-- Table : utilisateurs
-- ========================================

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'directeur') NOT NULL DEFAULT 'directeur',
    statut ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Table : cycles (Maternelle, Primaire, Secondaire...)
-- ========================================

CREATE TABLE IF NOT EXISTS cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- Table : classes
-- ========================================

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    niveau VARCHAR(50) DEFAULT NULL,
    cycle_id INT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    capacite INT NOT NULL DEFAULT 40,
    CONSTRAINT fk_classes_cycle FOREIGN KEY (cycle_id) REFERENCES cycles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- Table : eleves
-- ========================================

CREATE TABLE IF NOT EXISTS eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE DEFAULT NULL,
    sexe ENUM('M', 'F') NOT NULL DEFAULT 'M',
    parent_nom VARCHAR(150) DEFAULT NULL,
    parent_tel VARCHAR(20) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    classe_id INT DEFAULT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_eleves_classe FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- Table : enseignants
-- ========================================

CREATE TABLE IF NOT EXISTS enseignants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    specialite VARCHAR(100) DEFAULT NULL,
    date_embauche DATE DEFAULT NULL
) ENGINE=InnoDB;

-- ========================================
-- Table de liaison : enseignants <-> classes
-- ========================================

CREATE TABLE IF NOT EXISTS enseignants_classes (
    enseignant_id INT NOT NULL,
    classe_id INT NOT NULL,
    PRIMARY KEY (enseignant_id, classe_id),
    CONSTRAINT fk_ec_enseignant FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE CASCADE,
    CONSTRAINT fk_ec_classe FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- Compte admin par défaut
-- Email : admin@anna.com
-- Mot de passe : admin123
-- ========================================

INSERT INTO utilisateurs (nom, prenom, email, telephone, password, role, statut)
VALUES ('Admin', 'Super', 'admin@anna.com', NULL, '$2y$10$VWoowZCMICr4Zhml4w2St.YUfj/h4BXsHvYu.CIif5iETeF.P3v3m', 'admin', 'actif');
