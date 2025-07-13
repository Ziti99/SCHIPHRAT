<?php
require_once __DIR__ . '/../config/database.php';
$db = new Database();
$users = [
    'admin' => 'password',
    'medecin1' => 'password',
    'sagefemme1' => 'password',
    'secretaire1' => 'password',
];
$updated = [];
foreach ($users as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->query('UPDATE users SET password = ? WHERE username = ?', [$hash, $user]);
    $updated[] = [$user, $pass, $hash];
}
echo "<h2>Mise à jour des mots de passe terminée !</h2>";
echo "<table border='1' cellpadding='6'><tr><th>Utilisateur</th><th>Mot de passe</th><th>Nouveau hash</th></tr>";
foreach ($updated as $row) {
    echo "<tr><td>{$row[0]}</td><td>{$row[1]}</td><td><code>{$row[2]}</code></td></tr>";
}
echo "</table>"; 