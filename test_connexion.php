<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = new Database();
    $result = $db->query('SELECT 1');
    echo '<h2 style="color:green">Connexion à la base de données : OK</h2>';
} catch (Exception $e) {
    echo '<h2 style="color:red">Erreur de connexion à la base de données :</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
} 