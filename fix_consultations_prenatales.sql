-- Script pour corriger la table consultations_prenatales
-- Suppression de la référence à grossesse_id et ajout de patiente_id

USE clinique_obstetrique;

-- 1. Ajouter la colonne patiente_id
ALTER TABLE consultations_prenatales ADD COLUMN patiente_id INT AFTER id;

-- 2. Supprimer la contrainte de clé étrangère grossesse_id
ALTER TABLE consultations_prenatales DROP FOREIGN KEY consultations_prenatales_ibfk_1;

-- 3. Supprimer la colonne grossesse_id
ALTER TABLE consultations_prenatales DROP COLUMN grossesse_id;

-- 4. Ajouter la contrainte de clé étrangère pour patiente_id
ALTER TABLE consultations_prenatales ADD CONSTRAINT fk_consultations_patiente 
FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE;

-- 5. Créer un index sur patiente_id pour optimiser les performances
CREATE INDEX idx_consultations_patiente_id ON consultations_prenatales(patiente_id);

-- 6. Supprimer l'index sur grossesse_id s'il existe
DROP INDEX IF EXISTS idx_consultations_grossesse_id ON consultations_prenatales;

-- 7. Mettre à jour les consultations existantes (si il y en a)
-- Note: Cette étape nécessite de mapper les grossesses vers les patientes
-- Pour l'instant, on va juste s'assurer que la structure est correcte

-- 8. Rendre patiente_id NOT NULL après avoir migré les données
-- ALTER TABLE consultations_prenatales MODIFY COLUMN patiente_id INT NOT NULL;

-- Vérification de la structure
DESCRIBE consultations_prenatales; 