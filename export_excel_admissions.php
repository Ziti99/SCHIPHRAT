<?php
// Démarrer la session en premier, avant tout autre code
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

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
    $where_conditions[] = "a.date_admission >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_conditions[] = "a.date_admission <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

if (!empty($medecin_id)) {
    $where_conditions[] = "a.medecin_id = ?";
    $params[] = $medecin_id;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Récupération des données
$admissions = $db->fetchAll("
    SELECT 
        a.id as admission_id,
        a.date_admission,
        a.date_sortie,
        a.motif_admission,
        a.diagnostic,
        a.traitement,
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
        medecin.specialite as medecin_specialite
    FROM admissions a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    $where_clause
    ORDER BY a.date_admission DESC
", $params);

// Création du fichier Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Titre du document
$sheet->setTitle('Registre Admissions');

// En-tête avec informations de la clinique
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'CLINIQUE OBSTÉTRIQUE - REGISTRE DES ADMISSIONS');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:H2');
$sheet->setCellValue('A2', 'Consultations Prénatales');
$sheet->getStyle('A2')->getFont()->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A3:H3');
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

if (!empty($filters_info)) {
    $sheet->mergeCells('A5:H5');
    $sheet->setCellValue('A5', 'Filtres appliqués: ' . implode(' | ', $filters_info));
    $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(10);
    $sheet->getStyle('A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F4FD');
}

// Statistiques
$sheet->setCellValue('A7', 'Statistiques');
$sheet->getStyle('A7')->getFont()->setBold(true)->setSize(14);

$sheet->setCellValue('A8', 'Total admissions:');
$sheet->setCellValue('B8', count($admissions));
$sheet->setCellValue('A9', 'Date d\'export:');
$sheet->setCellValue('B9', date('d/m/Y'));
$sheet->setCellValue('A10', 'Exporté par:');
$sheet->setCellValue('B10', $user['username']);

// En-têtes du tableau
$headers = [
    'A12' => 'ID Admission',
    'B12' => 'Nom Patient',
    'C12' => 'Prénom Patient',
    'D12' => 'Téléphone',
    'E12' => 'Date Naissance',
    'F12' => 'Adresse',
    'G12' => 'Groupe Sanguin',
    'H12' => 'Date Admission',
    'I12' => 'Date Sortie',
    'J12' => 'Motif Admission',
    'K12' => 'Diagnostic',
    'L12' => 'Traitement',
    'M12' => 'Médecin',
    'N12' => 'Spécialité',
    'O12' => 'Observations'
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
foreach ($admissions as $admission) {
    $sheet->setCellValue('A' . $row, $admission['admission_id']);
    $sheet->setCellValue('B' . $row, $admission['nom']);
    $sheet->setCellValue('C' . $row, $admission['prenom']);
    $sheet->setCellValue('D' . $row, $admission['telephone']);
    $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($admission['date_naissance'])));
    $sheet->setCellValue('F' . $row, $admission['adresse']);
    $sheet->setCellValue('G' . $row, $admission['groupe_sanguin'] ?? 'Non défini');
    $sheet->setCellValue('H' . $row, date('d/m/Y H:i', strtotime($admission['date_admission'])));
    $sheet->setCellValue('I' . $row, $admission['date_sortie'] ? date('d/m/Y H:i', strtotime($admission['date_sortie'])) : 'En cours');
    $sheet->setCellValue('J' . $row, $admission['motif_admission'] ?? '');
    $sheet->setCellValue('K' . $row, $admission['diagnostic'] ?? '');
    $sheet->setCellValue('L' . $row, $admission['traitement'] ?? '');
    $sheet->setCellValue('M' . $row, 'Dr. ' . $admission['medecin_prenom'] . ' ' . $admission['medecin_nom']);
    $sheet->setCellValue('N' . $row, $admission['medecin_specialite'] ?? 'Non défini');
    $sheet->setCellValue('O' . $row, $admission['observations'] ?? '');
    
    // Style alterné pour les lignes
    if ($row % 2 == 0) {
        $sheet->getStyle('A' . $row . ':O' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
    }
    
    // Bordures
    $sheet->getStyle('A' . $row . ':O' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $row++;
}

// Ajustement automatique de la largeur des colonnes
foreach (range('A', 'O') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Style pour les cellules de données
$sheet->getStyle('A13:O' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A13:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // ID centré

// Pied de page avec informations
$footerRow = $row + 2;
$sheet->mergeCells('A' . $footerRow . ':O' . $footerRow);
$sheet->setCellValue('A' . $footerRow, 'Document généré automatiquement par le système de gestion de la Clinique Obstétrique');
$sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(10);
$sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$footerRow++;
$sheet->mergeCells('A' . $footerRow . ':O' . $footerRow);
$sheet->setCellValue('A' . $footerRow, 'Total: ' . count($admissions) . ' admission(s) trouvée(s)');
$sheet->getStyle('A' . $footerRow)->getFont()->setBold(true)->setSize(10);
$sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Création du fichier Excel
$writer = new Xlsx($spreadsheet);

// En-têtes HTTP pour le téléchargement
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="registre_admissions_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');

// Envoi du fichier
$writer->save('php://output');
exit;
?> 