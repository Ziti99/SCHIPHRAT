-- Ajouter la colonne sage_femme_id à la table suivi_postnatal
ALTER TABLE suivi_postnatal 
ADD COLUMN sage_femme_id INT NULL;

-- Ajouter la clé étrangère
ALTER TABLE suivi_postnatal 
ADD CONSTRAINT fk_suivi_postnatal_sage_femme 
FOREIGN KEY (sage_femme_id) REFERENCES users(id) ON DELETE SET NULL;

-- Ajouter un index pour améliorer les performances
CREATE INDEX idx_suivi_postnatal_sage_femme ON suivi_postnatal(sage_femme_id);

-- Modifier la contrainte existante pour permettre soit medecin_id soit sage_femme_id
-- D'abord supprimer l'ancienne contrainte si elle existe
ALTER TABLE suivi_postnatal 
DROP CONSTRAINT IF EXISTS chk_medecin_or_sage_femme;

-- Ajouter la nouvelle contrainte
ALTER TABLE suivi_postnatal 
ADD CONSTRAINT chk_medecin_or_sage_femme 
CHECK (medecin_id IS NOT NULL OR sage_femme_id IS NOT NULL); 