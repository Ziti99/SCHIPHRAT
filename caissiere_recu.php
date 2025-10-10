<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'caissiere'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$db = new Database();

// Vérifier si les tables de paiements existent
try {
    $db->query("SELECT 1 FROM paiements LIMIT 1");
} catch (PDOException $e) {
    header('Location: /setup_caisse_system.php');
    exit;
}

$paiement_id = $_GET['id'] ?? 0;

// Récupérer les informations complètes
$paiement = $db->fetch("
    SELECT p.*, pat.*, cp.date_consultation,
           u.nom as caissiere_nom, u.prenom as caissiere_prenom
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    LEFT JOIN users u ON p.caissiere_id = u.id
    WHERE p.id = ?
", [$paiement_id]);

if (!$paiement) {
    die('Paiement introuvable');
}

// Récupérer les actes
$actes = $db->fetchAll("
    SELECT ca.*, ap.nom_acte, ap.description
    FROM consultation_actes ca
    INNER JOIN actes_poses ap ON ca.acte_id = ap.id
    WHERE ca.consultation_id = ?
", [$paiement['consultation_id']]);

// Historique des versements
$versements = $db->fetchAll("
    SELECT hp.*, u.nom, u.prenom
    FROM historique_paiements hp
    LEFT JOIN users u ON hp.caissiere_id = u.id
    WHERE hp.paiement_id = ?
    ORDER BY hp.date_versement
", [$paiement_id]);

// Générer le HTML du reçu
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #10B981; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #10B981; margin: 0; font-size: 28px; }
        .header p { margin: 5px 0; color: #666; }
        .recu-numero { background: #10B981; color: white; padding: 10px; text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section-title { background: #f3f4f6; padding: 8px; font-weight: bold; color: #333; border-left: 4px solid #10B981; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9fafb; font-weight: bold; }
        .total-row { background: #f0fdf4; font-weight: bold; font-size: 16px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 40%; padding: 5px 0; color: #666; }
        .info-value { display: table-cell; padding: 5px 0; font-weight: bold; }
        .footer { margin-top: 40px; text-align: center; padding-top: 20px; border-top: 2px solid #ddd; font-size: 12px; color: #666; }
        .signature { margin-top: 40px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 30px auto 5px; }
        .statut-paye { background: #10B981; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
        .statut-partiel { background: #F59E0B; color: white; padding: 5px 10px; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 CLINIQUE OBSTÉTRIQUE SHIPHRAT</h1>
        <p>Excellence en soins maternels</p>
        <p>Libreville, Gabon | Tél: +241 XX XX XX XX</p>
    </div>

    <div class="recu-numero">
        REÇU DE PAIEMENT N° ' . str_pad($paiement_id, 6, '0', STR_PAD_LEFT) . '
    </div>

    <div class="section">
        <div class="section-title">INFORMATIONS PATIENTE</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet:</div>
                <div class="info-value">' . htmlspecialchars($paiement['prenom'] . ' ' . $paiement['nom']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de naissance:</div>
                <div class="info-value">' . date('d/m/Y', strtotime($paiement['date_naissance'])) . ' (' . $paiement['age'] . ' ans)</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone:</div>
                <div class="info-value">' . htmlspecialchars($paiement['telephone']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de consultation:</div>
                <div class="info-value">' . date('d/m/Y à H:i', strtotime($paiement['date_consultation'])) . '</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">DÉTAIL DES ACTES MÉDICAUX</div>
        <table>
            <thead>
                <tr>
                    <th>Acte médical</th>
                    <th style="text-align: center;">Quantité</th>
                    <th style="text-align: right;">Prix unitaire</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>';

foreach ($actes as $acte) {
    $total_acte = $acte['montant'] * $acte['quantite'];
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($acte['nom_acte']) . '</td>
                    <td style="text-align: center;">' . $acte['quantite'] . '</td>
                    <td style="text-align: right;">' . number_format($acte['montant'], 0, ',', ' ') . ' FCFA</td>
                    <td style="text-align: right;">' . number_format($total_acte, 0, ',', ' ') . ' FCFA</td>
                </tr>';
}

$html .= '
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">TOTAL:</td>
                    <td style="text-align: right;">' . number_format($paiement['montant_total'], 0, ',', ' ') . ' FCFA</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">DÉTAIL DU PAIEMENT</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Montant total:</div>
                <div class="info-value">' . number_format($paiement['montant_total'], 0, ',', ' ') . ' FCFA</div>
            </div>
            <div class="info-row">
                <div class="info-label">Montant payé:</div>
                <div class="info-value" style="color: #10B981;">' . number_format($paiement['montant_paye'], 0, ',', ' ') . ' FCFA</div>
            </div>
            <div class="info-row">
                <div class="info-label">Montant restant:</div>
                <div class="info-value" style="color: #EF4444;">' . number_format($paiement['montant_restant'], 0, ',', ' ') . ' FCFA</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mode de paiement:</div>
                <div class="info-value">' . ucfirst(str_replace('_', ' ', $paiement['mode_paiement'] ?? 'Non spécifié')) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de paiement:</div>
                <div class="info-value">' . ($paiement['date_paiement'] ? date('d/m/Y à H:i', strtotime($paiement['date_paiement'])) : 'Non spécifié') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut:</div>
                <div class="info-value">
                    ' . ($paiement['statut'] === 'paye_total' ? '<span class="statut-paye">✓ PAYÉ INTÉGRALEMENT</span>' : '<span class="statut-partiel">⚠ PAIEMENT PARTIEL</span>') . '
                </div>
            </div>
        </div>
    </div>';

if (!empty($versements)) {
    $html .= '
    <div class="section">
        <div class="section-title">HISTORIQUE DES VERSEMENTS</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Mode</th>
                    <th>Caissière</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($versements as $v) {
        $html .= '
                <tr>
                    <td>' . date('d/m/Y H:i', strtotime($v['date_versement'])) . '</td>
                    <td>' . number_format($v['montant'], 0, ',', ' ') . ' FCFA</td>
                    <td>' . ucfirst(str_replace('_', ' ', $v['mode_paiement'])) . '</td>
                    <td>' . htmlspecialchars($v['prenom'] . ' ' . $v['nom']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </div>';
}

$html .= '
    <div class="signature">
        <div style="text-align: right; margin-right: 50px;">
            <p>Caissière: ' . htmlspecialchars(($paiement['caissiere_prenom'] ?? '') . ' ' . ($paiement['caissiere_nom'] ?? '')) . '</p>
            <div class="signature-line"></div>
            <p style="margin: 0;">Signature</p>
        </div>
    </div>

    <div class="footer">
        <p>Merci de votre confiance</p>
        <p>Ce reçu est généré électroniquement le ' . date('d/m/Y à H:i') . '</p>
        <p style="margin-top: 10px; font-size: 10px;">Clinique Obstétrique SHIPHRAT - Libreville, Gabon</p>
    </div>
</body>
</html>';

// Configuration DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Télécharger ou afficher
$filename = 'Recu_' . str_pad($paiement_id, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';

if (isset($_GET['download'])) {
    $dompdf->stream($filename, ['Attachment' => true]);
} else {
    $dompdf->stream($filename, ['Attachment' => false]);
}

