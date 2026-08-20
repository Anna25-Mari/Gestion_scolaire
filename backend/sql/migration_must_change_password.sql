-- ========================================
-- Migration : Ajout colonne must_change_password
-- A executer sur la base de donnees en ligne
-- via phpMyAdmin (InfinityFree)
-- ========================================

-- 1. Ajouter la colonne
ALTER TABLE utilisateurs 
ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 
AFTER statut;
