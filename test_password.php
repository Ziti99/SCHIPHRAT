<?php
$hash = '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy';
$test = 'password';
$result = password_verify($test, $hash);
echo "<h2>Test password_verify('password', \$hash) :</h2>";
echo $result ? '<span style="color:green">OK : le mot de passe est valide !</span>' : '<span style="color:red">ECHEC : le mot de passe est refusé !</span>';
echo "<br><br>Version PHP : " . phpversion(); 