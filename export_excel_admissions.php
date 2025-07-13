<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();

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
    $where_conditions[] = "c.date_consultation >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_conditions[] = "c.date_consultation <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

if (!empty($medecin_id)) {
    $where_conditions[] = "c.medecin_id = ?";
    $params[] = $medecin_id;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Récupération des données
$admissions = $db->fetchAll("
    SELECT 
        c.id as consultation_id,
        c.date_consultation,
        c.observations,
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
    FROM consultations_prenatales c
    JOIN patientes p ON c.patiente_id = p.id
    JOIN users medecin ON c.medecin_id = medecin.id
    $where_clause
    ORDER BY c.date_consultation DESC
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
$sheet->setCellValue('A3', 'Exporté le ' . date('d/m/Y à H:i') . ' par ' . $auth->getCurrentUserName());
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
$sheet->setCellValue('B10', $auth->getCurrentUserName());

// En-têtes du tableau
$headers = [
    'A12' => 'ID Consultation',
    'B12' => 'Nom Patient',
    'C12' => 'Prénom Patient',
    'D12' => 'Téléphone',
    'E12' => 'Date Naissance',
    'F12' => 'Adresse',
    'G12' => 'Groupe Sanguin',
    'H12' => 'Date Consultation',
    'I12' => 'Médecin',
    'J12' => 'Spécialité',
    'K12' => 'Observations'
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
    $sheet->setCellValue('A' . $row, $admission['consultation_id']);
    $sheet->setCellValue('B' . $row, $admission['nom']);
    $sheet->setCellValue('C' . $row, $admission['prenom']);
    $sheet->setCellValue('D' . $row, $admission['telephone']);
    $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($admission['date_naissance'])));
    $sheet->setCellValue('F' . $row, $admission['adresse']);
    $sheet->setCellValue('G' . $row, $admission['groupe_sanguin'] ?? 'Non renseigné');
    $sheet->setCellValue('H' . $row, date('d/m/Y H:i', strtotime($admission['date_consultation'])));
    $sheet->setCellValue('I' . $row, 'Dr. ' . $admission['medecin_prenom'] . ' ' . $admission['medecin_nom']);
    $sheet->setCellValue('J' . $row, $admission['medecin_specialite'] ?? 'Non renseigné');
    $sheet->setCellValue('K' . $row, $admission['observations'] ?? '');
    
    // Style alterné pour les lignes
    if ($row % 2 == 0) {
        $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
    }
    
    // Bordures
    $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $row++;
}

// Ajustement automatique de la largeur des colonnes
foreach (range('A', 'K') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Style pour les cellules de données
$sheet->getStyle('A13:K' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A13:A' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // ID centré

// Pied de page avec informations
$footerRow = $row + 2;
$sheet->mergeCells('A' . $footerRow . ':K' . $footerRow);
$sheet->setCellValue('A' . $footerRow, 'Document généré automatiquement par le système de gestion de la Clinique Obstétrique');
$sheet->getStyle('A' . $footerRow)->getFont()->setItalic(true)->setSize(10);
$sheet->getStyle('A' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$footerRow++;
$sheet->mergeCells('A' . $footerRow . ':K' . $footerRow);
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