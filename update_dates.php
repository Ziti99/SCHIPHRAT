<?php
require_once 'config/database.php';

$db = new Database();

try {
    // Mettre à jour les dates des consultations pour ce mois
    $db->query("
        UPDATE consultations_prenatales 
        SET date_consultation = DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY)
        WHERE id = 1
    ");
    
    $db->query("
        UPDATE consultations_prenatales 
        SET date_consultation = DATE_SUB(CURRENT_DATE(), INTERVAL 10 DAY)
        WHERE id = 2
    ");
    
    $db->query("
        UPDATE consultations_prenatales 
        SET date_consultation = DATE_SUB(CURRENT_DATE(), INTERVAL 15 DAY)
        WHERE id = 3
    ");
    
    // Mettre à jour les dates des accouchements pour ce mois
    $db->query("
        UPDATE accouchements 
        SET date_accouchement = DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY)
        WHERE id = 1
    ");
    
    $db->query("
        UPDATE accouchements 
        SET date_accouchement = DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        WHERE id = 2
    ");
    
    $db->query("
        UPDATE accouchements 
        SET date_accouchement = DATE_SUB(CURRENT_DATE(), INTERVAL 12 DAY)
        WHERE id = 3
    ");
    
    // Mettre à jour les dates des suivis postnataux pour ce mois
    $db->query("
        UPDATE suivi_postnatal 
        SET date_visite = DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY)
        WHERE id = 1
    ");
    
    $db->query("
        UPDATE suivi_postnatal 
        SET date_visite = DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)
        WHERE id = 2
    ");
    
    $db->query("
        UPDATE suivi_postnatal 
        SET date_visite = DATE_SUB(CURRENT_DATE(), INTERVAL 11 DAY)
        WHERE id = 3
    ");
    
    echo "Dates mises à jour avec succès !<br>";
    echo "Les données d'exemple correspondent maintenant au mois actuel.<br>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?> 