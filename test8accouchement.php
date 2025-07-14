<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();

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
        medecin.nom as medecin_nom,
        medecin.prenom as medecin_prenom,
        sage_femme.nom as sage_femme_nom,
        sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    ORDER BY a.date_accouchement DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Accouchements</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Test Affichage Accouchements</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Patiente</th>
                <th>Date</th>
                <th>Mode</th>
                <th>Bébé</th>
                <th>Médecin</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($accouchements as $accouchement): ?>
            <tr>
                <td><?php echo htmlspecialchars($accouchement['accouchement_id']); ?></td>
                <td><?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?></td>
                <td><?php echo htmlspecialchars($accouchement['date_accouchement']); ?></td>
                <td><?php echo htmlspecialchars($accouchement['mode_accouchement']); ?></td>
                <td><?php echo htmlspecialchars($accouchement['nom_bebe']); ?></td>
                <td><?php echo htmlspecialchars($accouchement['medecin_prenom'] . ' ' . $accouchement['medecin_nom']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html> 