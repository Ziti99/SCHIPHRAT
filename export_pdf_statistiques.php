<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Récupération des images de graphiques envoyées en POST (base64)
$chart1 = $_POST['chart1'] ?? null;
$chart2 = $_POST['chart2'] ?? null;
$chart3 = $_POST['chart3'] ?? null;

// Récupération d'autres données statistiques si besoin
// $stat1 = $_POST['stat1'] ?? '';
// ...

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
    </style>
</head>
<body>
    <h1>Statistiques de la Clinique</h1>
    <div class="stat-block">
        <h2>Graphique 1 : Répartition des accouchements</h2>';
if ($chart1) {
    $html .= '<img src="' . htmlspecialchars($chart1) . '" class="chart-img">';
} else {
    $html .= '<p style="color:#f43f5e">Graphique non transmis</p>';
}
$html .= '</div><div class="stat-block">
        <h2>Graphique 2 : Évolution mensuelle</h2>';
if ($chart2) {
    $html .= '<img src="' . htmlspecialchars($chart2) . '" class="chart-img">';
} else {
    $html .= '<p style="color:#f43f5e">Graphique non transmis</p>';
}
$html .= '</div><div class="stat-block">
        <h2>Graphique 3 : Autre statistique</h2>';
if ($chart3) {
    $html .= '<img src="' . htmlspecialchars($chart3) . '" class="chart-img">';
} else {
    $html .= '<p style="color:#f43f5e">Graphique non transmis</p>';
}
$html .= '</div><div class="footer">Export PDF généré le ' . date('d/m/Y H:i') . '.</div></body></html>';

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