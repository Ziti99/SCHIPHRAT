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
    'role' => $_SESSION['role'] ?? '',
];
if (!in_array($user['role'], ['admin', 'medecin', 'sage_femme'])) {
    header('Location: dashboard.php');
    exit();
}

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

// Création du PDF avec DOMPDF
require_once __DIR__ . '/vendor/autoload.php';
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
    <title>Registre des Accouchements - Clinique Obstétrique</title>
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
            font-size: 11px;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }
        
        th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: 1px solid #4c51bf !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
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
            padding: 10px 8px;
            vertical-align: top;
        }
        
        .patient-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .patient-info {
            font-size: 9px;
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
        
        .sage-femme {
            font-weight: 500;
            color: #dc2626;
        }
        
        .type-accouchement {
            font-weight: 500;
            color: #7c3aed;
        }
        
        .mode-accouchement {
            font-weight: 500;
            color: #ea580c;
        }
        
        .enfant-info {
            font-size: 9px;
            color: #64748b;
        }
        
        .observations {
            max-width: 150px;
            word-wrap: break-word;
            font-size: 9px;
            color: #64748b;
        }
        
        .id-accouchement {
            text-align: center;
        }
        
        .id-number {
            font-weight: 700;
            color: #059669;
            font-size: 12px;
        }
        
        .id-detail {
            font-size: 8px;
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
        th {
            background: #4F46E5 !important;
            color: #fff !important;
            font-size: 13px;
            font-weight: bold;
            border: 1px solid #4F46E5 !important;
            text-align: center;
            padding: 10px 6px;
        }
        td {
            border: 1px solid #e2e8f0;
            text-align: center;
            padding: 8px 4px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">👶</div>
                <h1 class="title">Registre des Accouchements</h1>
                <p class="subtitle">Clinique Obstétrique - Accouchements</p>
            </div>
        </div>
        
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">' . count($accouchements) . '</div>
                    <div class="stat-label">Total Accouchements</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . date('d/m/Y') . '</div>
                    <div class="stat-label">Date d\'export</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">' . $user['username'] . '</div>
                    <div class="stat-label">Exporté par</div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <h2 class="section-title">Liste des Accouchements</h2>';

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
if (!empty($sage_femme_id)) {
    $sage_femme = $db->fetch("SELECT nom, prenom FROM users WHERE id = ?", [$sage_femme_id]);
    if ($sage_femme) {
        $filters_applied[] = "Sage-femme: " . $sage_femme['nom'] . " " . $sage_femme['prenom'];
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

if (empty($accouchements)) {
    $html .= '
            <div class="no-data">
                <div class="no-data-icon">📭</div>
                <h3>Aucun accouchement trouvé</h3>
                <p>Aucun accouchement ne correspond aux critères de recherche.</p>
            </div>';
} else {
    $html .= '
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Date/Heure</th>
                        <th>Médecin</th>
                        <th>Sage-femme</th>
                        <th>Mode</th>
                        <th>Enfant</th>
                        <th>Observations</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($accouchements as $accouchement) {
        // Préparer l'affichage enfant
        $enfant = '-';
        if ($accouchement['nom_bebe'] && $accouchement['sexe_bebe']) {
            $enfant = htmlspecialchars($accouchement['nom_bebe']) . ' (' . ($accouchement['sexe_bebe'] === 'M' ? 'Garçon' : 'Fille') . ')';
        } elseif ($accouchement['nom_bebe']) {
            $enfant = htmlspecialchars($accouchement['nom_bebe']);
        } elseif ($accouchement['sexe_bebe']) {
            $enfant = $accouchement['sexe_bebe'] === 'M' ? 'Garçon' : 'Fille';
        }
        $html .= '
                    <tr>
                        <td>' . htmlspecialchars($accouchement['generated_id']) . '</td>
                        <td>' . htmlspecialchars($accouchement['nom']) . '</td>
                        <td>' . htmlspecialchars($accouchement['prenom']) . '</td>
                        <td>' . date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])) . '</td>
                        <td>' . ($accouchement['medecin_nom'] ? 'Dr. ' . htmlspecialchars($accouchement['medecin_prenom'] . ' ' . $accouchement['medecin_nom']) : '-') . '</td>
                        <td>' . ($accouchement['sage_femme_nom'] ? htmlspecialchars($accouchement['sage_femme_prenom'] . ' ' . $accouchement['sage_femme_nom']) : '-') . '</td>
                        <td>' . ($accouchement['mode_accouchement'] ? htmlspecialchars($accouchement['mode_accouchement']) : '-') . '</td>
                        <td>' . $enfant . '</td>
                        <td>' . ($accouchement['observations'] ? htmlspecialchars($accouchement['observations']) : '-') . '</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>';
}

$html .= '
        </div>
        
        <div class="footer">
            <div class="footer-text">
                <strong>Clinique Obstétrique</strong> - Registre des Accouchements<br>
                Exporté le ' . date('d/m/Y à H:i') . ' par ' . htmlspecialchars($user['username']) . '<br>
                Document généré automatiquement - ' . count($accouchements) . ' accouchement(s) trouvé(s)
            </div>
        </div>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Envoi du PDF
$dompdf->stream('registre_accouchements_' . date('Y-m-d_H-i-s') . '.pdf', array('Attachment' => true));
?> 