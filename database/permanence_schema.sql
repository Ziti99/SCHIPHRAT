-- Table des types d'actes configurables par l'admin
CREATE TABLE actes_poses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_acte VARCHAR(100) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des permanences du jour
CREATE TABLE permanences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_patient VARCHAR(100) NOT NULL,
    prenom_patient VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    nationalite VARCHAR(50) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    acte_id INT NOT NULL,
    montant_paye DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
    statut_final VARCHAR(20) DEFAULT 'en_attente',
    observations TEXT,
    secretaire_id INT NOT NULL,
    admin_id INT,
    date_validation DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (acte_id) REFERENCES actes_poses(id),
    FOREIGN KEY (secretaire_id) REFERENCES users(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Insertion des actes fictifs par défaut
INSERT INTO actes_poses (nom_acte, montant, description) VALUES
('Consultation prénatale', 15000.00, 'Consultation de suivi de grossesse'),
('Échographie obstétricale', 25000.00, 'Échographie de contrôle'),
('Bilan sanguin complet', 12000.00, 'Analyses sanguines'),
('Vaccination', 8000.00, 'Vaccin tétanos'),
('Consultation post-natale', 10000.00, 'Suivi post-accouchement'),
('Échographie de datation', 20000.00, 'Échographie de datation'),
('Test de grossesse', 5000.00, 'Test urinaire'),
('Consultation gynécologique', 18000.00, 'Consultation gynécologique'),
('Échographie morphologique', 30000.00, 'Échographie morphologique'),
('Suivi de fertilité', 15000.00, 'Consultation fertilité');

-- Index pour optimiser les performances
CREATE INDEX idx_permanences_statut ON permanences(statut);
CREATE INDEX idx_permanences_date ON permanences(created_at);
CREATE INDEX idx_actes_poses_active ON actes_poses(is_active); 