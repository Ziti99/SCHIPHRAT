<?php
require_once 'config/database.php';

$db = new Database();

echo "<h1>Test des données et statistiques</h1>";

try {
    // Test des patientes
    echo "<h2>Patientes</h2>";
    $patientes = $db->fetchAll("SELECT COUNT(*) as total FROM patientes");
    echo "Total patientes: " . $patientes[0]['total'] . "<br>";
    
    $patientes_list = $db->fetchAll("SELECT nom, prenom FROM patientes LIMIT 5");
    echo "Premières patientes:<br>";
    foreach ($patientes_list as $p) {
        echo "- " . $p['nom'] . " " . $p['prenom'] . "<br>";
    }
    
    // Test des consultations
    echo "<h2>Consultations</h2>";
    $consultations = $db->fetchAll("SELECT COUNT(*) as total FROM consultations_prenatales");
    echo "Total consultations: " . $consultations[0]['total'] . "<br>";
    
    $consultations_list = $db->fetchAll("
        SELECT cp.date_consultation, p.nom, p.prenom 
        FROM consultations_prenatales cp
        JOIN grossesses g ON cp.grossesse_id = g.id
        JOIN patientes p ON g.patiente_id = p.id
        ORDER BY cp.date_consultation DESC LIMIT 5
    ");
    echo "Dernières consultations:<br>";
    foreach ($consultations_list as $c) {
        echo "- " . $c['date_consultation'] . " - " . $c['nom'] . " " . $c['prenom'] . "<br>";
    }
    
    // Test des accouchements
    echo "<h2>Accouchements</h2>";
    $accouchements = $db->fetchAll("SELECT COUNT(*) as total FROM accouchements");
    echo "Total accouchements: " . $accouchements[0]['total'] . "<br>";
    
    $accouchements_list = $db->fetchAll("
        SELECT a.date_accouchement, a.mode_accouchement, p.nom, p.prenom
        FROM accouchements a
        JOIN grossesses g ON a.grossesse_id = g.id
        JOIN patientes p ON g.patiente_id = p.id
        ORDER BY a.date_accouchement DESC LIMIT 5
    ");
    echo "Derniers accouchements:<br>";
    foreach ($accouchements_list as $a) {
        echo "- " . $a['date_accouchement'] . " - " . $a['mode_accouchement'] . " - " . $a['nom'] . " " . $a['prenom'] . "<br>";
    }
    
    // Test des suivis postnataux
    echo "<h2>Suivis postnataux</h2>";
    $suivis = $db->fetchAll("SELECT COUNT(*) as total FROM suivi_postnatal");
    echo "Total suivis: " . $suivis[0]['total'] . "<br>";
    
    // Test des statistiques du mois
    echo "<h2>Statistiques du mois</h2>";
    $consultations_mois = $db->fetchAll("
        SELECT COUNT(*) as total 
        FROM consultations_prenatales 
        WHERE MONTH(date_consultation) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_consultation) = YEAR(CURRENT_DATE())
    ");
    echo "Consultations ce mois: " . $consultations_mois[0]['total'] . "<br>";
    
    $accouchements_mois = $db->fetchAll("
        SELECT COUNT(*) as total 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_accouchement) = YEAR(CURRENT_DATE())
    ");
    echo "Accouchements ce mois: " . $accouchements_mois[0]['total'] . "<br>";
    
    // Test des types d'accouchements
    echo "<h2>Types d'accouchements</h2>";
    $types_accouchements = $db->fetchAll("
        SELECT mode_accouchement, COUNT(*) as total
        FROM accouchements
        GROUP BY mode_accouchement
    ");
    foreach ($types_accouchements as $type) {
        echo "- " . $type['mode_accouchement'] . ": " . $type['total'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?> 