-- Script pour supprimer le champ numero_dossier de la table patientes
-- Exécuter ce script pour supprimer le champ dossier

USE clinique_obstetrique;

-- Supprimer l'index sur numero_dossier
DROP INDEX IF EXISTS idx_patientes_numero_dossier ON patientes;

-- Supprimer la colonne numero_dossier de la table patientes
ALTER TABLE patientes DROP COLUMN numero_dossier;

-- Vérifier que la colonne a été supprimée
DESCRIBE patientes; 