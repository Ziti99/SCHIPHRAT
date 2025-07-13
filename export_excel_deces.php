<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Config\Database;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();

// Filtres
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$lieu = $_GET['lieu'] ?? '';
$cause = $_GET['cause'] ?? '';

$where = [];
$params = [];
if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
if (!empty($date_debut)) {
    $where[] = "DATE(d.date_deces) >= ?";
    $params[] = $date_debut;
}
if (!empty($date_fin)) {
    $where[] = "DATE(d.date_deces) <= ?";
    $params[] = $date_fin;
}
if (!empty($lieu)) {
    $where[] = "d.lieu_deces LIKE ?";
    $params[] = "%$lieu%";
}
if (!empty($cause)) {
    $where[] = "d.cause_deces LIKE ?";
    $params[] = "%$cause%";
}
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$deces = $db->fetchAll("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance, p.nationalite,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    JOIN users medecin ON d.medecin_id = medecin.id
    $whereClause
    ORDER BY d.date_deces DESC
", $params);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Décès');

// En-têtes
$headers = ['N°', 'Date', 'Patiente', 'Age (h)', 'Cause', 'Lieu', 'Médecin', 'Observations'];
$sheet->fromArray($headers, null, 'A1');

// Style en-têtes
$sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
$sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Données
$row = 2;
foreach ($deces as $index => $d) {
    $sheet->setCellValue('A' . $row, $index + 1);
    $sheet->setCellValue('B' . $row, date('d/m/Y H:i', strtotime($d['date_deces'])));
    $sheet->setCellValue('C' . $row, $d['prenom'] . ' ' . $d['nom']);
    $sheet->setCellValue('D' . $row, $d['age_deces'] !== null ? $d['age_deces'] : '-');
    $sheet->setCellValue('E' . $row, $d['cause_deces']);
    $sheet->setCellValue('F' . $row, $d['lieu_deces'] ?: '-');
    $sheet->setCellValue('G' . $row, 'Dr. ' . $d['medecin_prenom'] . ' ' . $d['medecin_nom']);
    $sheet->setCellValue('H' . $row, $d['observations'] ? $d['observations'] : '-');
    $row++;
}

// Style données
$sheet->getStyle('A2:H' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A2:H' . ($row-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('A2:A' . ($row-1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Largeur auto
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="registre_deces_' . date('Y-m-d_H-i-s') . '.xlsx"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;
?> 