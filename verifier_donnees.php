<?php
require_once 'config/database.php';

$db = new Database();

echo "Vérification des données dans la base...\n\n";

try {
    // Vérifier les patientes
    $patientes = $db->fetchAll("SELECT COUNT(*) as total FROM patientes");
    echo "📊 Patientes : " . $patientes[0]['total'] . "\n";
    
    $patientes_list = $db->fetchAll("SELECT nom, prenom, adresse FROM patientes LIMIT 5");
    foreach ($patientes_list as $p) {
        echo "  - " . $p['nom'] . " " . $p['prenom'] . " (" . $p['adresse'] . ")\n";
    }
    
    // Vérifier les grossesses
    $grossesses = $db->fetchAll("SELECT COUNT(*) as total FROM grossesses");
    echo "\n📊 Grossesses : " . $grossesses[0]['total'] . "\n";
    
    // Vérifier les consultations
    $consultations = $db->fetchAll("SELECT COUNT(*) as total FROM consultations");
    echo "📊 Consultations : " . $consultations[0]['total'] . "\n";
    
    // Vérifier les accouchements
    $accouchements = $db->fetchAll("SELECT COUNT(*) as total FROM accouchements");
    echo "📊 Accouchements : " . $accouchements[0]['total'] . "\n";
    
    // Vérifier les suivis postnataux
    $suivis = $db->fetchAll("SELECT COUNT(*) as total FROM suivi_postnatal");
    echo "📊 Suivis postnataux : " . $suivis[0]['total'] . "\n";
    
    // Vérifier les registres
    $registres = $db->fetchAll("SELECT COUNT(*) as total FROM registres");
    echo "📊 Registres : " . $registres[0]['total'] . "\n";
    
    echo "\n✅ Vérification terminée !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la vérification : " . $e->getMessage() . "\n";
}
?> 