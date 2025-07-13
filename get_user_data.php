<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    try {
        $user = $db->fetch("
            SELECT id, username, email, nom, prenom, role, telephone, specialite, is_active
            FROM users 
            WHERE id = ?
        ", [$user_id]);
        
        if ($user) {
            header('Content-Type: application/json');
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Utilisateur non trouvé']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID utilisateur manquant']);
}
?> 