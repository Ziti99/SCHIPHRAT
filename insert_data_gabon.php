<?php
require_once 'config/database.php';

$db = new Database();

echo "Insertion des données fictives gabonaises...\n";

try {
    // 1. Insertion des patientes gabonaises
    echo "1. Insertion des patientes...\n";
    $patientes = [
        ['Mabika', 'Prisca', '1992-04-15', 'Libreville', '062345678', 'PT-001'],
        ['Moussavou', 'Jean', '1985-09-22', 'Port-Gentil', '077654321', 'PT-002'],
        ['Ngoma', 'Clarisse', '1998-12-03', 'Franceville', '065432198', 'PT-003'],
        ['Essone', 'Brice', '1979-06-10', 'Oyem', '066789123', 'PT-004'],
        ['Obiang', 'Sylvie', '1990-11-27', 'Lambaréné', '061234567', 'PT-005'],
        ['Bongo', 'Marie', '1988-03-14', 'Libreville', '064567890', 'PT-006'],
        ['Mba', 'Pierre', '1982-07-08', 'Port-Gentil', '078901234', 'PT-007'],
        ['Nguema', 'Aline', '1995-01-25', 'Franceville', '063456789', 'PT-008']
    ];

    foreach ($patientes as $patiente) {
        $db->query("
            INSERT INTO patientes (nom, prenom, date_naissance, adresse, telephone, numero_dossier)
            VALUES (?, ?, ?, ?, ?, ?)
        ", $patiente);
    }
    echo "✓ 8 patientes insérées\n";

    // 2. Insertion des grossesses
    echo "2. Insertion des grossesses...\n";
    $grossesses = [
        [1, '2024-01-15', '2024-10-15', 'Première grossesse', 'en_cours'],
        [2, '2024-02-20', '2024-11-20', 'Deuxième grossesse', 'en_cours'],
        [3, '2024-03-10', '2024-12-10', 'Première grossesse', 'en_cours'],
        [4, '2024-01-05', '2024-10-05', 'Troisième grossesse', 'en_cours'],
        [5, '2024-02-15', '2024-11-15', 'Deuxième grossesse', 'en_cours']
    ];

    foreach ($grossesses as $grossesse) {
        $db->query("
            INSERT INTO grossesses (patiente_id, date_debut_grossesse, date_terme_prevue, observations, statut)
            VALUES (?, ?, ?, ?, ?)
        ", $grossesse);
    }
    echo "✓ 5 grossesses insérées\n";

    // 3. Insertion des consultations prénatales
    echo "3. Insertion des consultations...\n";
    $consultations = [
        [1, 1, '2024-04-15 09:00:00', '12/8', 65.0, 20, 'Céphalique', 140, 'Tension normale, poids: 65kg', 'Contrôle dans 1 mois', '2024-05-15'],
        [2, 1, '2024-05-20 10:30:00', '13/8', 67.0, 22, 'Céphalique', 145, 'Bébé en bonne santé', 'Échographie dans 2 semaines', '2024-06-20'],
        [3, 1, '2024-06-10 14:00:00', '11/7', 58.0, 18, 'Céphalique', 150, 'Première visite', 'Contrôle dans 1 mois', '2024-07-10']
    ];

    foreach ($consultations as $consultation) {
        $db->query("
            INSERT INTO consultations_prenatales (grossesse_id, medecin_id, date_consultation, tension_arterielle, poids, hauteur_uterine, position_foetus, frequence_cardiaque_foetale, observations, recommandations, prochaine_consultation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", $consultation);
    }
    echo "✓ 3 consultations insérées\n";

    // 4. Insertion des accouchements
    echo "4. Insertion des accouchements...\n";
    $accouchements = [
        [1, '2024-10-15 14:30:00', 'voie_basse', 180, 'Aucune', 1, 2, 'Sarah', 'F', 3.2, 50, 'vivant', 9, 'Accouchement normal'],
        [2, '2024-11-20 16:45:00', 'cesarienne', 120, 'Dystocie', 1, 2, 'Thomas', 'M', 3.5, 52, 'vivant', 8, 'Césarienne programmée'],
        [3, '2024-12-10 12:15:00', 'voie_basse', 200, 'Aucune', 1, 2, 'Emma', 'F', 2.8, 48, 'vivant', 9, 'Accouchement normal']
    ];

    foreach ($accouchements as $accouchement) {
        $db->query("
            INSERT INTO accouchements (grossesse_id, date_accouchement, mode_accouchement, duree_travail, complications, medecin_id, sage_femme_id, nom_bebe, sexe_bebe, poids_bebe, taille_bebe, statut_bebe, apgar_score, observations)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", $accouchement);
    }
    echo "✓ 3 accouchements insérés\n";

    // 5. Insertion des suivis postnataux
    echo "5. Insertion des suivis postnataux...\n";
    $suivis = [
        [1, '2024-10-22', 'mere_et_bebe', 1, 'Récupération normale', 'Bébé en bonne santé', 'BCG, Hépatite B', '2024-11-22'],
        [2, '2024-11-27', 'mere_et_bebe', 1, 'Cicatrice césarienne propre', 'Bébé en bonne santé', 'BCG, Hépatite B', '2024-12-27'],
        [3, '2024-12-17', 'mere_et_bebe', 1, 'Allaitement bien établi', 'Bébé en bonne santé', 'BCG, Hépatite B', '2025-01-17']
    ];

    foreach ($suivis as $suivi) {
        $db->query("
            INSERT INTO suivi_postnatal (accouchement_id, date_visite, type_visite, medecin_id, observations_mere, observations_bebe, vaccinations, prochaine_visite)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", $suivi);
    }
    echo "✓ 3 suivis postnataux insérés\n";

    // 6. Insertion des registres
    echo "6. Insertion des registres...\n";
    $registres = [
        [1, 1, 'naissance', '2024-10-15', 'Libreville', 'Naissance de Sarah Mabika', 'REG-2024-001', 'valide', 'Naissance normale'],
        [2, 1, 'naissance', '2024-11-20', 'Port-Gentil', 'Naissance de Thomas Moussavou', 'REG-2024-002', 'valide', 'Naissance par césarienne'],
        [3, 1, 'naissance', '2024-12-10', 'Franceville', 'Naissance d\'Emma Ngoma', 'REG-2024-003', 'valide', 'Naissance normale']
    ];

    foreach ($registres as $registre) {
        $db->query("
            INSERT INTO registres (patiente_id, medecin_id, type_registre, date_evenement, lieu_evenement, details, numero_registre, statut, observations)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", $registre);
    }
    echo "✓ 3 registres insérés\n";

    echo "\n🎉 Toutes les données fictives gabonaises ont été insérées avec succès !\n";
    echo "Vous pouvez maintenant tester l'application avec ces données.\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de l'insertion : " . $e->getMessage() . "\n";
}
?> 