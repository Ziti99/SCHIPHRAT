<?php
/**
 * Endpoint API pour récupérer les consultations en JSON
 * Utilisé pour actualiser la liste sans recharger toute la page
 */
session_start();

// Mode debug (activer avec ?debug=1 dans l'URL)
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_logs = [];

function debug_log($message, $data = null) {
    global $debug_mode, $debug_logs;
    $log_entry = [
        'time' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data
    ];
    $debug_logs[] = $log_entry;
    
    // Log dans error_log aussi pour le serveur
    $log_message = "[REALTIME API] " . $message;
    if ($data !== null) {
        $log_message .= " | Data: " . json_encode($data);
    }
    error_log($log_message);
}

debug_log('Début de la requête API', [
    'user_id' => $_SESSION['user_id'] ?? 'non défini',
    'user_role' => $_SESSION['user_role'] ?? 'non défini',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'query_string' => $_SERVER['QUERY_STRING'] ?? ''
]);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'caissiere'])) {
    debug_log('Accès refusé - Non authentifié ou rôle invalide', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null
    ]);
    http_response_code(401);
    echo json_encode([
        'error' => 'Non authentifié',
        'debug' => $debug_mode ? $debug_logs : null
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = new Database();
debug_log('Connexion à la base de données établie');

// Récupérer les mêmes filtres que la page principale
$statut_filtre = $_GET['statut'] ?? 'tous';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search = $_GET['search'] ?? '';
$last_check = isset($_GET['last_check']) ? intval($_GET['last_check']) : null;
$last_consultation_id = isset($_GET['last_consultation_id']) ? intval($_GET['last_consultation_id']) : 0;

debug_log('Paramètres reçus', [
    'statut_filtre' => $statut_filtre,
    'date_debut' => $date_debut,
    'date_fin' => $date_fin,
    'search' => $search,
    'last_check' => $last_check,
    'last_consultation_id' => $last_consultation_id
]);

// Construction de la requête (identique à caissiere_consultations.php)
$where_clauses = [];
$params = [];

if ($statut_filtre !== 'tous') {
    $where_clauses[] = "p.statut = ?";
    $params[] = $statut_filtre;
}

if ($date_debut) {
    $where_clauses[] = "DATE(cp.date_consultation) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $where_clauses[] = "DATE(cp.date_consultation) <= ?";
    $params[] = $date_fin;
}

if ($search) {
    $where_clauses[] = "(pat.nom LIKE ? OR pat.prenom LIKE ? OR pat.telephone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

debug_log('Requête SQL construite', [
    'where_clauses' => $where_clauses,
    'params_count' => count($params),
    'where_sql' => $where_sql
]);

// Récupérer les consultations
$start_time = microtime(true);
$consultations = $db->fetchAll("
    SELECT 
        p.id as paiement_id,
        p.montant_total,
        p.montant_paye,
        p.montant_restant,
        p.statut,
        p.mode_paiement,
        p.date_paiement,
        cp.id as consultation_id,
        cp.date_consultation,
        pat.id as patiente_id,
        pat.nom,
        pat.prenom,
        pat.telephone,
        COUNT(ca.id) as nb_actes,
        GROUP_CONCAT(ap.nom_acte SEPARATOR ', ') as actes_liste
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    LEFT JOIN consultation_actes ca ON cp.id = ca.consultation_id
    LEFT JOIN actes_poses ap ON ca.acte_id = ap.id
    $where_sql
    GROUP BY p.id
    ORDER BY cp.date_consultation DESC
", $params);

$query_time = round((microtime(true) - $start_time) * 1000, 2);
debug_log('Consultations récupérées', [
    'count' => count($consultations),
    'query_time_ms' => $query_time,
    'first_consultation_id' => !empty($consultations) ? $consultations[0]['consultation_id'] : null,
    'last_consultation_id' => !empty($consultations) ? end($consultations)['consultation_id'] : null
]);

// Statistiques
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'paye_partiel' THEN 1 ELSE 0 END) as partiel,
        SUM(CASE WHEN statut = 'paye_total' THEN 1 ELSE 0 END) as complet,
        SUM(montant_restant) as montant_restant_total
    FROM paiements p
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    $where_sql
", $params);

debug_log('Statistiques récupérées', $stats);

// Identifier les nouvelles consultations
$new_consultations_count = 0;
if ($last_consultation_id > 0 && !empty($consultations)) {
    $new_consultations_count = count(array_filter($consultations, function($c) use ($last_consultation_id) {
        return $c['consultation_id'] > $last_consultation_id;
    }));
    debug_log('Nouvelles consultations détectées', [
        'new_count' => $new_consultations_count,
        'last_consultation_id_received' => $last_consultation_id,
        'max_consultation_id' => max(array_column($consultations, 'consultation_id'))
    ]);
}

$response_data = [
    'success' => true,
    'consultations' => $consultations,
    'stats' => $stats,
    'timestamp' => time(),
    'new_consultations_count' => $new_consultations_count
];

if ($debug_mode) {
    $response_data['debug'] = [
        'logs' => $debug_logs,
        'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true)
    ];
}

debug_log('Réponse envoyée', [
    'consultations_count' => count($consultations),
    'new_consultations_count' => $new_consultations_count,
    'debug_mode' => $debug_mode
]);

header('Content-Type: application/json');
echo json_encode($response_data);
?>

