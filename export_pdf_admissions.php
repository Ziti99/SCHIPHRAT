<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

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
        medecin.nom as medecin_nom,
        medecin.prenom as medecin_prenom,
        medecin.specialite as medecin_specialite
    FROM consultations_prenatales c
    JOIN patientes p ON c.patiente_id = p.id
    JOIN users medecin ON c.medecin_id = medecin.id
    $where_clause
    ORDER BY c.date_consultation DESC
", $params);

// Création du PDF avec DOMPDF
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// HTML du PDF avec design impeccable
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registre des Admissions - Clinique Obstétrique</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Inter", sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        
        .title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .stats-section {
            padding: 30px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-grid {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            flex: 1;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        
        .content {
            padding: 40px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            position: relative;
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.3s ease;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        td {
            padding: 12px;
            vertical-align: top;
        }
        
        .patient-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .patient-info {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        
        .date {
            font-weight: 500;
            color: #059669;
        }
        
        .medecin {
            font-weight: 500;
            color: #667eea;
        }
        
        .observations {
            max-width: 200px;
            word-wrap: break-word;
            font-size: 10px;
            color: #64748b;
        }
        
        .footer {
            padding: 30px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-text {
            color: #64748b;
            font-size: 14px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .no-data-icon {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .filters-info {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .filters-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .filter-item {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">🏥</div>
                <h1 class="title">Registre des Admissions</h1>
                <p class="subtitle">Clinique Obstétrique - Consultations Prénatales</p>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . count($admissions) . '</div>
                    <div class="stat-label">Total Admissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . date('d/m/Y') . '</div>
                    <div class="stat-label">Date d\'export</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $auth->getCurrentUserName() . '</div>
                    <div class="stat-label">Exporté par</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <h2 class="section-title">📋 Liste des Admissions</h2>';

// Affichage des filtres appliqués
$filters_applied = [];
if (!empty($search)) $filters_applied[] = "Recherche: $search";
if (!empty($date_debut)) $filters_applied[] = "Date début: $date_debut";
if (!empty($date_fin)) $filters_applied[] = "Date fin: $date_fin";
if (!empty($medecin_id)) {
    $medecin = $db->fetch("SELECT nom, prenom FROM users WHERE id = ?", [$medecin_id]);
    if ($medecin) {
        $filters_applied[] = "Médecin: Dr. " . $medecin['nom'] . " " . $medecin['prenom'];
    }
}

if (!empty($filters_applied)) {
    $html .= '
            <div class="filters-info">
                <div class="filters-title">🔍 Filtres appliqués:</div>';
    foreach ($filters_applied as $filter) {
        $html .= '<span class="filter-item">' . htmlspecialchars($filter) . '</span>';
    }
    $html .= '</div>';
}

if (empty($admissions)) {
    $html .= '
            <div class="no-data">
                <div class="no-data-icon">📭</div>
                <h3>Aucune admission trouvée</h3>
                <p>Aucune consultation prénatale ne correspond aux critères de recherche.</p>
            </div>';
} else {
    $html .= '
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>👤 Patient</th>
                            <th>📅 Date Consultation</th>
                            <th>👨‍⚕️ Médecin</th>
                            <th>📝 Observations</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($admissions as $admission) {
        $html .= '
                        <tr>
                            <td>
                                <div class="patient-name">' . htmlspecialchars($admission['prenom'] . ' ' . $admission['nom']) . '</div>
                                <div class="patient-info">📞 ' . htmlspecialchars($admission['telephone']) . '</div>
                                <div class="patient-info">🎂 ' . date('d/m/Y', strtotime($admission['date_naissance'])) . '</div>
                            </td>
                            <td class="date">' . date('d/m/Y H:i', strtotime($admission['date_consultation'])) . '</td>
                            <td class="medecin">Dr. ' . htmlspecialchars($admission['medecin_prenom'] . ' ' . $admission['medecin_nom']) . '</td>
                            <td class="observations">' . htmlspecialchars(substr($admission['observations'], 0, 100)) . (strlen($admission['observations']) > 100 ? '...' : '') . '</td>

                        </tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
            </div>';
}

$html .= '
        </div>
        
        <div class="footer">
            <div class="footer-text">
                <strong>Clinique Obstétrique</strong> - Registre des Admissions<br>
                Exporté le ' . date('d/m/Y à H:i') . ' par ' . htmlspecialchars($auth->getCurrentUserName()) . '<br>
                Document généré automatiquement - ' . count($admissions) . ' admission(s) trouvée(s)
            </div>
        </div>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Envoi du PDF
$dompdf->stream('registre_admissions_' . date('Y-m-d_H-i-s') . '.pdf', array('Attachment' => true));
?> 