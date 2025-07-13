-- Modification de la table suivi_postnatal pour ajouter les colonnes medecin_id et sage_femme_id
-- avec une contrainte pour qu'au moins une des deux soit obligatoire

-- Ajouter les colonnes medecin_id et sage_femme_id
ALTER TABLE suivi_postnatal 
ADD COLUMN medecin_id INT NULL,
ADD COLUMN sage_femme_id INT NULL;

-- Ajouter les clés étrangères
ALTER TABLE suivi_postnatal 
ADD CONSTRAINT fk_suivi_postnatal_medecin 
FOREIGN KEY (medecin_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE suivi_postnatal 
ADD CONSTRAINT fk_suivi_postnatal_sage_femme 
FOREIGN KEY (sage_femme_id) REFERENCES users(id) ON DELETE SET NULL;

-- Ajouter une contrainte pour qu'au moins une des deux colonnes soit remplie
ALTER TABLE suivi_postnatal 
ADD CONSTRAINT chk_medecin_or_sage_femme 
CHECK (medecin_id IS NOT NULL OR sage_femme_id IS NOT NULL);

-- Ajouter un index pour améliorer les performances
CREATE INDEX idx_suivi_postnatal_medecin ON suivi_postnatal(medecin_id);
CREATE INDEX idx_suivi_postnatal_sage_femme ON suivi_postnatal(sage_femme_id); 