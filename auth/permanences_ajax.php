<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAuth();

// Vérifier que l'utilisateur est admin ou caissière
if (!in_array($auth->getCurrentUserRole(), ['admin', 'caissiere'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

$db = Database::getInstance();

// Récupérer les paramètres de filtrage
$date_du = $_GET['date_du'] ?? date('Y-m-d');  // Jour actuel par défaut
$date_au = $_GET['date_au'] ?? date('Y-m-d');  // Jour actuel par défaut
$statut_filter = $_GET['statut'] ?? '';

// Construire la requête avec filtres
$where_conditions = ["DATE(p.created_at) >= ?", "DATE(p.created_at) <= ?"];
$params = [$date_du, $date_au];

if (!empty($statut_filter)) {
    $where_conditions[] = "p.statut_final = ?";
    $params[] = $statut_filter;
}

$where_clause = implode(" AND ", $where_conditions);

// Récupérer les permanences filtrées
$permanences = $db->fetchAll("
    SELECT p.*, a.nom_acte, u.nom as secretaire_nom, u.prenom as secretaire_prenom
    FROM permanences p
    JOIN actes_poses a ON p.acte_id = a.id
    JOIN users u ON p.secretaire_id = u.id
    WHERE $where_clause
    ORDER BY p.created_at DESC
", $params);

// Statistiques pour le dashboard
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total_permanences,
        SUM(montant_paye) as total_montant,
        COUNT(CASE WHEN statut_final = 'ok' THEN 1 END) as validees,
        COUNT(CASE WHEN statut_final = 'annule' THEN 1 END) as annulees,
        COUNT(CASE WHEN statut_final = 'en_attente' THEN 1 END) as en_attente,
        AVG(montant_paye) as moyenne_montant,
        COUNT(CASE WHEN statut_final = 'en_attente' AND DATE(created_at) = CURDATE() THEN 1 END) as en_attente_aujourd_hui
    FROM permanences 
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
", [$date_du, $date_au]);

// Total par acte posé (uniquement les validées)
$total_par_acte = $db->fetchAll("
    SELECT a.nom_acte, COUNT(*) as nb_fois, SUM(p.montant_paye) as total_montant
    FROM permanences p
    JOIN actes_poses a ON p.acte_id = a.id
    WHERE DATE(p.created_at) >= ? AND DATE(p.created_at) <= ? 
    AND p.statut_final = 'ok'
    GROUP BY a.id, a.nom_acte
    ORDER BY nb_fois DESC
", [$date_du, $date_au]);

// Déterminer la période affichée
$periode_affichee = '';
if ($date_du === $date_au) {
    if ($date_du === date('Y-m-d')) {
        $periode_affichee = "Aujourd'hui (" . date('d/m/Y') . ")";
    } else {
        $periode_affichee = "Le " . date('d/m/Y', strtotime($date_du));
    }
} else {
    $periode_affichee = "Du " . date('d/m/Y', strtotime($date_du)) . " au " . date('d/m/Y', strtotime($date_au));
}

// Préparer les données pour la réponse
$response = [];
foreach ($permanences as $permanence) {
    $statut_html = '';
    if ($permanence['statut_final'] === 'ok') {
        $statut_html = '<span class="inline-block px-3 py-1 rounded-full font-semibold bg-green-100 text-green-800">OK</span>';
    } elseif ($permanence['statut_final'] === 'annule') {
        $statut_html = '<span class="inline-block px-3 py-1 rounded-full font-semibold bg-red-100 text-red-800">Annulé</span>';
    } else {
        $statut_html = '<span class="inline-block px-3 py-1 rounded-full font-semibold bg-yellow-100 text-yellow-800">En attente</span>';
    }
    
    $response[] = [
        'nom' => htmlspecialchars($permanence['nom_patient']),
        'prenom' => htmlspecialchars($permanence['prenom_patient']),
        'age' => htmlspecialchars($permanence['age']),
        'nationalite' => htmlspecialchars($permanence['nationalite']),
        'acte' => htmlspecialchars($permanence['nom_acte']),
        'montant' => number_format($permanence['montant_paye'], 0, ',', ' ') . ' FCFA',
        'contact' => htmlspecialchars($permanence['contact']),
        'heure' => date('H:i', strtotime($permanence['created_at'])),
        'secretaire' => htmlspecialchars($permanence['secretaire_nom']) . ' ' . htmlspecialchars($permanence['secretaire_prenom']),
        'statut' => $statut_html
    ];
}

// Retourner la réponse en JSON avec toutes les données
$final_response = [
    'permanences' => $response,
    'stats' => $stats,
    'total_par_acte' => $total_par_acte,
    'periode_affichee' => $periode_affichee
];

// Retourner la réponse en JSON
header('Content-Type: application/json');
echo json_encode($final_response); 