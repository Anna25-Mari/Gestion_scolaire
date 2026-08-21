-- ========================================
-- Migration : gestion des enseignants (directeur)
-- Ajoute le dernier diplôme, le statut et la date de suspension
-- ========================================

USE gestion_scolaire_test;

ALTER TABLE enseignants
    ADD COLUMN IF NOT EXISTS dernier_diplome VARCHAR(150) DEFAULT NULL AFTER specialite,
    ADD COLUMN IF NOT EXISTS statut ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif' AFTER dernier_diplome,
    ADD COLUMN IF NOT EXISTS date_suspension DATETIME DEFAULT NULL AFTER statut;
