<?php
/**
 * Endpoint pour vérifier les nouvelles consultations
 * Utilise Server-Sent Events (SSE) pour envoyer des notifications en temps réel
 */
session_start();

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'caissiere'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Définir les headers pour SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Désactiver la mise en cache pour Nginx

// Empêcher la mise en cache
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

$db = new Database();

// Récupérer le timestamp de la dernière consultation vue (depuis le paramètre ou session)
$last_check = isset($_GET['last_check']) ? intval($_GET['last_check']) : time();
$last_consultation_id = isset($_GET['last_consultation_id']) ? intval($_GET['last_consultation_id']) : 0;

// Fonction pour envoyer un événement SSE
function sendSSE($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Envoyer un événement de connexion
sendSSE('connected', ['message' => 'Connexion établie', 'timestamp' => time()]);

// Boucle de vérification (pendant 60 secondes max, puis reconnexion)
$start_time = time();
$max_duration = 55; // 55 secondes pour éviter les timeouts

while ((time() - $start_time) < $max_duration) {
    try {
        // Vérifier s'il y a de nouvelles consultations avec paiements
        $new_consultations = $db->fetchAll("
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
                GROUP_CONCAT(ap.nom_acte SEPARATOR ', ') as actes_liste,
                UNIX_TIMESTAMP(cp.created_at) as created_timestamp
            FROM paiements p
            INNER JOIN patientes pat ON p.patiente_id = pat.id
            INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
            LEFT JOIN consultation_actes ca ON cp.id = ca.consultation_id
            LEFT JOIN actes_poses ap ON ca.acte_id = ap.id
            WHERE (UNIX_TIMESTAMP(cp.created_at) > ? OR cp.id > ?)
            GROUP BY p.id
            ORDER BY cp.date_consultation DESC
            LIMIT 10
        ", [$last_check, $last_consultation_id]);
        
        if (!empty($new_consultations)) {
            // Mettre à jour le dernier ID et timestamp
            $last_consultation_id = max(array_column($new_consultations, 'consultation_id'));
            $last_check = time();
            
            // Envoyer les nouvelles consultations
            sendSSE('new_consultations', [
                'consultations' => $new_consultations,
                'count' => count($new_consultations),
                'timestamp' => time()
            ]);
        }
        
        // Envoyer un heartbeat toutes les 10 secondes pour maintenir la connexion
        if ((time() - $start_time) % 10 == 0) {
            sendSSE('heartbeat', ['timestamp' => time()]);
        }
        
    } catch (Exception $e) {
        sendSSE('error', ['message' => 'Erreur: ' . $e->getMessage()]);
        break;
    }
    
    // Attendre 2 secondes avant la prochaine vérification
    sleep(2);
}

// Fermer la connexion
sendSSE('close', ['message' => 'Connexion fermée']);
?>

