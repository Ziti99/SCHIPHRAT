<?php
session_start();

// Test des exports
echo "<h1>Test des Exports</h1>";

// Vérifier les sessions
echo "<h2>Vérification des sessions</h2>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Non défini') . "<br>";
echo "Session username: " . ($_SESSION['username'] ?? 'Non défini') . "<br>";
echo "Session user_role: " . ($_SESSION['user_role'] ?? 'Non défini') . "<br>";

// Vérifier les fichiers d'export
echo "<h2>Vérification des fichiers d'export</h2>";
$export_files = [
    'export_pdf_accouchements.php',
    'export_excel_accouchements.php',
    'export_pdf_deces.php',
    'export_excel_deces.php',
    'export_pdf_admissions.php',
    'export_excel_admissions.php'
];

foreach ($export_files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe<br>";
    } else {
        echo "❌ $file n'existe pas<br>";
    }
}

// Vérifier les dépendances
echo "<h2>Vérification des dépendances</h2>";
if (class_exists('Dompdf\Dompdf')) {
    echo "✅ DOMPDF est installé<br>";
} else {
    echo "❌ DOMPDF n'est pas installé<br>";
}

if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo "✅ PhpSpreadsheet est installé<br>";
} else {
    echo "❌ PhpSpreadsheet n'est pas installé<br>";
}

// Test de la base de données
echo "<h2>Test de la base de données</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    echo "✅ Connexion à la base de données réussie<br>";
    
    // Test de récupération des données
    $accouchements = $db->fetchAll("SELECT COUNT(*) as count FROM accouchements");
    echo "✅ Nombre d'accouchements: " . $accouchements[0]['count'] . "<br>";
    
    $deces = $db->fetchAll("SELECT COUNT(*) as count FROM deces");
    echo "✅ Nombre de décès: " . $deces[0]['count'] . "<br>";
    
    $consultations = $db->fetchAll("SELECT COUNT(*) as count FROM consultations_prenatales");
    echo "✅ Nombre de consultations: " . $consultations[0]['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "<br>";
}

// Liens de test
echo "<h2>Liens de test</h2>";
echo "<a href='export_pdf_accouchements.php' target='_blank'>Test Export PDF Accouchements</a><br>";
echo "<a href='export_excel_accouchements.php' target='_blank'>Test Export Excel Accouchements</a><br>";
echo "<a href='export_pdf_deces.php' target='_blank'>Test Export PDF Décès</a><br>";
echo "<a href='export_excel_deces.php' target='_blank'>Test Export Excel Décès</a><br>";
echo "<a href='export_pdf_admissions.php' target='_blank'>Test Export PDF Admissions</a><br>";
echo "<a href='export_excel_admissions.php' target='_blank'>Test Export Excel Admissions</a><br>";
?> 