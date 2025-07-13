<?php
$users = [
    'admin' => 'password',
    'medecin1' => 'password',
    'sagefemme1' => 'password',
    'secretaire1' => 'password',
];
echo "<h2>Hashes générés sur ce serveur :</h2>";
echo "<table border='1' cellpadding='6'><tr><th>Utilisateur</th><th>Mot de passe</th><th>Hash</th></tr>";
foreach ($users as $user => $pass) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    echo "<tr><td>$user</td><td>$pass</td><td><code>$hash</code></td></tr>";
}
echo "</table>"; 