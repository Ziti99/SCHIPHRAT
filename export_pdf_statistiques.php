<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
require_once __DIR__ . '/config/database.php';

// Récupération des images de graphiques envoyées en POST (base64)
$chart1 = $_POST['chart1'] ?? null;
$chart2 = $_POST['chart2'] ?? null;

// Récupération des statistiques mensuelles
$db = new Database();
$mois_stats = $db->fetchAll("
    SELECT 
        DATE_FORMAT(date_accouchement, '%Y-%m') as mois,
        COUNT(*) as accouchements,
        SUM(CASE WHEN mode_accouchement = 'cesarienne' THEN 1 ELSE 0 END) as cesariennes,
        SUM(CASE WHEN mode_accouchement = 'voie_basse' THEN 1 ELSE 0 END) as voie_basse
    FROM accouchements 
    WHERE date_accouchement >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_accouchement, '%Y-%m')
    ORDER BY mois DESC
");

// Tableau de traduction des mois en français
$mois_fr = [
    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
    'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
    'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
    'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
];

$html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Export PDF</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; color: #222; }
        h1 { color: #2563eb; text-align: center; margin-bottom: 0.5em; }
        h2 { color: #059669; margin-top: 2em; }
        .stat-block { background: linear-gradient(90deg, #a7f3d0 0%, #f0abfc 100%); border-radius: 1em; padding: 1em 2em; margin: 1em 0; box-shadow: 0 2px 8px #0001; }
        .chart-img { display: block; margin: 2em auto; max-width: 600px; border-radius: 1em; box-shadow: 0 2px 8px #0002; border: 2px solid #a5b4fc; }
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 3em; }
        .table-block { background: #fff; border-radius: 1em; padding: 1em 2em; margin: 2em 0; box-shadow: 0 2px 8px #0001; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #a5b4fc; padding: 8px; text-align: center; }
        th { background: #a7f3d0; color: #222; }
        tr:nth-child(even) { background: #f0fdfa; }
    </style>
</head>
<body>
    <h1>Statistiques de la Clinique</h1>
    <div class="stat-block">
        <h2>Graphique 1 : Accouchements par mois</h2>';
if ($chart1) {
    $html .= '<img src="' . htmlspecialchars($chart1) . '" class="chart-img">';
} else {
    $html .= '<p style="color:#f43f5e">Graphique non transmis</p>';
}
$html .= '</div><div class="stat-block">
        <h2>Graphique 2 : Répartition par âge</h2>';
if ($chart2) {
    $html .= '<img src="' . htmlspecialchars($chart2) . '" class="chart-img">';
} else {
    $html .= '<p style="color:#f43f5e">Graphique non transmis</p>';
}
$html .= '</div>';

// Ajout du tableau des statistiques mensuelles
$html .= '<div class="table-block">
    <h2>Tableau : Statistiques mensuelles (6 derniers mois)</h2>
    <table>
        <thead>
            <tr>
                <th>Mois</th>
                <th>Accouchements</th>
                <th>Césariennes</th>
                <th>Voie basse</th>
                <th>Taux césariennes</th>
            </tr>
        </thead>
        <tbody>';
foreach ($mois_stats as $stat) {
    $mois_en = date('F', strtotime($stat['mois'] . '-01'));
    $mois_annee = $mois_fr[$mois_en] . ' ' . date('Y', strtotime($stat['mois'] . '-01'));
    $taux_cesariennes = $stat['accouchements'] > 0 ? round(($stat['cesariennes'] / $stat['accouchements']) * 100, 1) : 0;
    $html .= '<tr>';
    $html .= '<td>' . $mois_annee . '</td>';
    $html .= '<td>' . $stat['accouchements'] . '</td>';
    $html .= '<td>' . $stat['cesariennes'] . '</td>';
    $html .= '<td>' . $stat['voie_basse'] . '</td>';
    $html .= '<td>' . $taux_cesariennes . '%</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table></div>';

$html .= '<div class="footer">Export PDF généré le ' . date('d/m/Y H:i') . '.</div></body></html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('statistiques_clinique.pdf', ["Attachment" => true]);
exit; 