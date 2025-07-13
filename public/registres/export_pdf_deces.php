<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

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

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;

$html = '<html><head><style>
body { font-family: Arial, sans-serif; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #4F46E5; color: #fff; font-weight: bold; }
.title { font-size: 18px; font-weight: bold; margin-bottom: 10px; text-align:center; }
</style></head><body>';
$html .= '<div class="title">REGISTRE DES DÉCÈS</div>';
$html .= '<table><thead><tr>
<th>N°</th><th>Date</th><th>Patiente</th><th>Age (h)</th><th>Cause</th><th>Lieu</th><th>Médecin</th><th>Observations</th>
</tr></thead><tbody>';
foreach ($deces as $index => $d) {
    $html .= '<tr>';
    $html .= '<td>' . ($index + 1) . '</td>';
    $html .= '<td>' . date('d/m/Y H:i', strtotime($d['date_deces'])) . '</td>';
    $html .= '<td>' . htmlspecialchars($d['prenom'] . ' ' . $d['nom']) . '</td>';
    $html .= '<td>' . ($d['age_deces'] !== null ? $d['age_deces'] : '-') . '</td>';
    $html .= '<td>' . htmlspecialchars($d['cause_deces']) . '</td>';
    $html .= '<td>' . htmlspecialchars($d['lieu_deces'] ?: '-') . '</td>';
    $html .= '<td>Dr. ' . htmlspecialchars($d['medecin_prenom'] . ' ' . $d['medecin_nom']) . '</td>';
    $html .= '<td>' . ($d['observations'] ? htmlspecialchars($d['observations']) : '-') . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table></body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('registre_deces_' . date('Y-m-d_H-i-s') . '.pdf', array('Attachment' => true));
?> 