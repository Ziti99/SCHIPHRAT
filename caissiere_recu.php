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

// Historique des versements - Non utilisé dans le PDF
// $versements = $db->fetchAll("
//     SELECT hp.*, u.nom, u.prenom
//     FROM historique_paiements hp
//     LEFT JOIN users u ON hp.caissiere_id = u.id
//     WHERE hp.paiement_id = ?
//     ORDER BY hp.date_versement
// ", [$paiement_id]);

// Générer le HTML du reçu
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 15px; font-size: 12px; }
        .header { text-align: center; border-bottom: 3px solid #8B5CF6; padding-bottom: 15px; margin-bottom: 15px; }
        .header h1 { color: #8B5CF6; margin: 0; font-size: 24px; font-weight: bold; }
        .header p { margin: 3px 0; color: #666; font-size: 11px; }
        .recu-numero { background: #8B5CF6; color: white; padding: 8px; text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        .section { margin-bottom: 12px; }
        .section-title { background: #F3E8FF; padding: 6px; font-weight: bold; color: #8B5CF6; border-left: 4px solid #8B5CF6; margin-bottom: 8px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 11px; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #F3E8FF; font-weight: bold; color: #8B5CF6; }
        .total-row { background: #F3E8FF; font-weight: bold; font-size: 13px; }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 40%; padding: 3px 0; color: #666; font-size: 11px; }
        .info-value { display: table-cell; padding: 3px 0; font-weight: bold; font-size: 11px; }
        .footer { margin-top: 20px; text-align: center; padding-top: 15px; border-top: 2px solid #ddd; font-size: 10px; color: #666; }
        .signature { margin-top: 20px; }
        .signature-line { border-top: 1px solid #000; width: 200px; margin: 20px auto 5px; }
        .statut-paye { background: #8B5CF6; color: white; padding: 4px 8px; border-radius: 4px; display: inline-block; font-size: 10px; }
        .statut-partiel { background: #F59E0B; color: white; padding: 4px 8px; border-radius: 4px; display: inline-block; font-size: 10px; }
        .amount-badges { display: flex; gap: 8px; margin: 8px 0 0 0; }
        .amount-badge { padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .amount-paid { background: #F3E8FF; color: #6B21A8; border: 1px solid #C084FC; }
        .amount-remaining { background: #FCE7F3; color: #BE185D; border: 1px solid #F9A8D4; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 SCHIPHRAT</h1>
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
                <div class="info-value">' . number_format($paiement['montant_paye'], 0, ',', ' ') . ' FCFA</div>
            </div>
            <div class="info-row">
                <div class="info-label">Montant restant:</div>
                <div class="info-value">' . number_format($paiement['montant_restant'], 0, ',', ' ') . ' FCFA</div>
            </div>
            <div class="amount-badges">
                <div class="amount-badge amount-paid">Payé: ' . number_format($paiement['montant_paye'], 0, ',', ' ') . ' FCFA</div>
                <div class="amount-badge amount-remaining">Reste: ' . number_format($paiement['montant_restant'], 0, ',', ' ') . ' FCFA</div>
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
        <p style="margin-top: 10px; font-size: 10px;">Schiphrat - Libreville, Gabon</p>
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

