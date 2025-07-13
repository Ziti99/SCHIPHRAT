<?php
require_once 'vendor/autoload.php';
use Clinique\Config\Database;

$db = Database::getInstance();

echo "=== DÉBOGAGE SUIVI POST-NATAL ===\n\n";

// 1. Vérifier les données dans suivi_postnatal
echo "1. Données dans suivi_postnatal:\n";
$suivi = $db->fetchAll("SELECT * FROM suivi_postnatal");
foreach($suivi as $s) {
    echo "ID: {$s['id']}, Accouchement: {$s['accouchement_id']}, Date: {$s['date_visite']}, Type: {$s['type_visite']}, Médecin: {$s['medecin_id']}\n";
}

echo "\n2. Vérifier les accouchements:\n";
$accouchements = $db->fetchAll("SELECT id, patiente_id FROM accouchements");
foreach($accouchements as $a) {
    echo "ID: {$a['id']}, Patiente: {$a['patiente_id']}\n";
}

echo "\n3. Vérifier les patientes:\n";
$patientes = $db->fetchAll("SELECT id, nom, prenom FROM patientes");
foreach($patientes as $p) {
    echo "ID: {$p['id']}, Nom: {$p['nom']} {$p['prenom']}\n";
}

echo "\n4. Vérifier les utilisateurs:\n";
$users = $db->fetchAll("SELECT id, nom, prenom, role FROM users");
foreach($users as $u) {
    echo "ID: {$u['id']}, Nom: {$u['nom']} {$u['prenom']}, Role: {$u['role']}\n";
}

echo "\n5. Test de la requête complète:\n";
try {
    $test = $db->fetchAll("
        SELECT sp.*, 
               p.nom, p.prenom,
               a.nom_bebe, a.sexe_bebe, a.poids_bebe,
               u.nom as medecin_nom, u.prenom as medecin_prenom
        FROM suivi_postnatal sp
        JOIN accouchements a ON sp.accouchement_id = a.id
        JOIN patientes p ON a.patiente_id = p.id
        LEFT JOIN users u ON sp.medecin_id = u.id
        ORDER BY sp.date_visite DESC
    ");
    
    echo "Résultats trouvés: " . count($test) . "\n";
    foreach($test as $t) {
        echo "Visite ID: {$t['id']}, Mère: {$t['prenom']} {$t['nom']}, Médecin: {$t['medecin_nom']} {$t['medecin_prenom']}\n";
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

echo "\nDébogage terminé.\n";
?> 