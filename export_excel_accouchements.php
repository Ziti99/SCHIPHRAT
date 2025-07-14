<?php
// Démarrer la session en premier, avant tout autre code
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['user_role'] ?? '',
];
if (!in_array($user['role'], ['admin', 'medecin', 'sagefemme'])) {
    header('Location: dashboard.php');
    exit();
}

// Vérifier si PhpSpreadsheet est disponible
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    die('Erreur: PhpSpreadsheet n\'est pas installé. Veuillez installer les dépendances.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$db = new Database();

// Récupération des paramètres de filtrage
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$medecin_id = $_GET['medecin_id'] ?? '';
$sage_femme_id = $_GET['sage_femme_id'] ?? '';

// Construction des conditions WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_debut)) {
    $where_conditions[] = "a.date_accouchement >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_conditions[] = "a.date_accouchement <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

if (!empty($medecin_id)) {
    $where_conditions[] = "a.medecin_id = ?";
    $params[] = $medecin_id;
}

if (!empty($sage_femme_id)) {
    $where_conditions[] = "a.sage_femme_id = ?";
    $params[] = $sage_femme_id;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fonction pour générer l'ID d'accouchement
function generateAccouchementId($db, $date_accouchement, $accouchement_db_id) {
    $mois = date('m', strtotime($date_accouchement));
    $annee = date('Y', strtotime($date_accouchement));
    
    // Compter les accouchements du mois jusqu'à cet accouchement
    $accouchements_mois = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = ? AND YEAR(date_accouchement) = ? AND id <= ?
    ", [$mois, $annee, $accouchement_db_id])['count'];
    
    // Compter les accouchements de l'année jusqu'à cet accouchement
    $accouchements_annee = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE YEAR(date_accouchement) = ? AND id <= ?
    ", [$annee, $accouchement_db_id])['count'];
    
    // Générer l'ID : 5eme accouchement du 3eme mois et 12eme de l'année = 0503122025
    $numero_mois = str_pad($accouchements_mois, 2, '0', STR_PAD_LEFT);
    $numero_annee = str_pad($accouchements_annee, 2, '0', STR_PAD_LEFT);
    
    return $numero_mois . $mois . $numero_annee . $annee;
}

// Récupération des données
$accouchements = $db->fetchAll("
    SELECT 
        a.id as accouchement_id,
        a.date_accouchement,
        a.mode_accouchement,
        a.duree_travail,
        a.complications,
        a.nom_bebe,
        a.sexe_bebe,
        a.poids_bebe,
        a.taille_bebe,
        a.apgar_score,
        a.observations,
        p.id as patiente_id,
        p.nom, 
        p.prenom, 
        p.telephone,
        p.date_naissance,
        p.adresse,
        p.groupe_sanguin,
        medecin.nom as medecin_nom,
        medecin.prenom as medecin_prenom,
        sage_femme.nom as sage_femme_nom,
        sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    $where_clause
    ORDER BY a.date_accouchement DESC
", $params);

// Générer les IDs pour chaque accouchement
foreach ($accouchements as &$accouchement) {
    $accouchement['generated_id'] = generateAccouchementId($db, $accouchement['date_accouchement'], $accouchement['accouchement_id']);
}

// Création du fichier Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Titre du document
$sheet->setTitle('Registre Accouchements');

// En-tête avec informations de la clinique
$sheet->mergeCells('A1:L1');
$sheet->setCellValue('A1', 'CLINIQUE OBSTÉTRIQUE - REGISTRE DES ACCOUCHEMENTS');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:L2');
$sheet->setCellValue('A2', 'Accouchements');
$sheet->getStyle('A2')->getFont()->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A3:L3');
$sheet->setCellValue('A3', 'Exporté le ' . date('d/m/Y à H:i') . ' par ' . $user['username']);
$sheet->getStyle('A3')->getFont()->setSize(10);
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Informations sur les filtres appliqués
$filters_info = [];
if (!empty($search)) $filters_info[] = "Recherche: $search";
if (!empty($date_debut)) $filters_info[] = "Date début: $date_debut";
if (!empty($date_fin)) $filters_info[] = "Date fin: $date_fin";
if (!empty($medecin_id)) {
    $medecin = $db->fetch("SELECT nom, prenom FROM users WHERE id = ?", [$medecin_id]);
    if ($medecin) {
        $filters_info[] = "Médecin: Dr. " . $medecin['nom'] . " " . $medecin['prenom'];
    }
}
if (!empty($sage_femme_id)) {
    $sage_femme = $db->fetch("SELECT nom, prenom FROM users WHERE id = ?", [$sage_femme_id]);
    if ($sage_femme) {
        $filters_info[] = "Sage-femme: " . $sage_femme['nom'] . " " . $sage_femme['prenom'];
    }
}

if (!empty($filters_info)) {
    $sheet->mergeCells('A5:L5');
    $sheet->setCellValue('A5', 'Filtres appliqués: ' . implode(' | ', $filters_info));
    $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(10);
    $sheet->getStyle('A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FD');
}

// Statistiques
$sheet->setCellValue('A7', 'Statistiques');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(14);

$sheet->setCellValue('A8', 'Total accouchements:');
$sheet->setCellValue('B8', count($accouchements));
$sheet->setCellValue('A9', 'Date d\'export:');
$sheet->setCellValue('B9', date('d/m/Y'));
$sheet->setCellValue('A10', 'Exporté par:');
$sheet->setCellValue('B10', $user['username']);

// En-têtes du tableau
$headers = [
    'A12' => 'ID Généré',
    'B12' => 'ID DB',
    'C12' => 'Nom Patient',
    'D12' => 'Prénom Patient',
    'E12' => 'Téléphone',
    'F12' => 'Date Naissance',
    'G12' => 'Adresse',
    'H12' => 'Groupe Sanguin',
    'I12' => 'Date Accouchement',
    'J12' => 'Mode',
    'K12' => 'Durée Travail',
    'L12' => 'Médecin',
    'M12' => 'Sage-femme',
    'N12' => 'Nom Bébé',
    'O12' => 'Sexe Bébé',
    'P12' => 'Poids (kg)',
    'Q12' => 'Taille (cm)',
    'R12' => 'Score Apgar',
    'S12' => 'Complications',
    'T12' => 'Observations'
];

foreach ($headers as $cell => $header) {
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
    $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

// Données
$row = 13;
foreach ($accouchements as $accouchement) {
    $sheet->setCellValue('A' . $row, $accouchement['generated_id']);
    $sheet->setCellValue('B' . $row, $accouchement['accouchement_id']);
    $sheet->setCellValue('C' . $row, $accouchement['nom']);
    $sheet->setCellValue('D' . $row, $accouchement['prenom']);
    $sheet->setCellValue('E' . $row, $accouchement['telephone']);
    $sheet->setCellValue('F' . $row, date('d/m/Y', strtotime($accouchement['date_naissance'])));
    $sheet->setCellValue('G' . $row, $accouchement['adresse']);
    $sheet->setCellValue('H' . $row, $accouchement['groupe_sanguin'] ?? 'Non renseigné');
    $sheet->setCellValue('I' . $row, date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])));
    $sheet->setCellValue('J' . $row, $accouchement['mode_accouchement']);
    $sheet->setCellValue('K' . $row, $accouchement['duree_travail'] ? $accouchement['duree_travail'] . ' min' : '-');
    $sheet->setCellValue('L' . $row, $accouchement['medecin_nom'] ? 'Dr. ' . $accouchement['medecin_prenom'] . ' ' . $accouchement['medecin_nom'] : '-');
    $sheet->setCellValue('M' . $row, $accouchement['sage_femme_nom'] ? $accouchement['sage_femme_prenom'] . ' ' . $accouchement['sage_femme_nom'] : '-');
    $sheet->setCellValue('N' . $row, $accouchement['nom_bebe'] ?? '-');
    $sheet->setCellValue('O' . $row, $accouchement['sexe_bebe'] ? ($accouchement['sexe_bebe'] === 'M' ? 'Masculin' : 'Féminin') : '-');
    $sheet->setCellValue('P' . $row, $accouchement['poids_bebe'] ?? '-');
    $sheet->setCellValue('Q' . $row, $accouchement['taille_bebe'] ?? '-');
    $sheet->setCellValue('R' . $row, $accouchement['apgar_score'] ?? '-');
    $sheet->setCellValue('S' . $row, $accouchement['complications'] ?? '-');
    $sheet->setCellValue('T' . $row, $accouchement['observations'] ?? '');
    
    // Style alterné pour les lignes
    if ($row % 2 == 0) {
        $sheet->getStyle('A' . $row . ':T' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
    }
    
    // Bordures
    $sheet->getStyle('A' . $row . ':T' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $row++;
}

// Ajustement automatique de la largeur des colonnes
foreach (range('A', 'T') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Style pour les cellules de données
$sheet->getStyle('A13:T' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A13:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // ID généré centré
$sheet->getStyle('B13:B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // ID DB centré
$sheet->getStyle('O13:O' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Sexe bébé centré

// Pied de page avec informations
$footerRow = $row + 2;
$sheet->mergeCells('A' . $footerRow . ':T' . $footerRow);
$sheet->setCellValue('A' . $footerRow, 'Document généré automatiquement par le système de gestion de la Clinique Obstétrique');
$sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(10);
$sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$footerRow++;
$sheet->mergeCells('A' . $footerRow . ':T' . $footerRow);
$sheet->setCellValue('A' . $footerRow, 'Total: ' . count($accouchements) . ' accouchement(s) trouvé(s)');
$sheet->getStyle('A' . $footerRow)->getFont()->setBold(true)->setSize(10);
$sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Création du fichier Excel
$writer = new Xlsx($spreadsheet);

// En-têtes HTTP pour le téléchargement
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="registre_accouchements_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');

// Envoi du fichier
$writer->save('php://output');
exit;
?> 