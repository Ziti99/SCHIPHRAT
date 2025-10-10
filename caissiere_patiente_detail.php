<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caissiere') {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
$db = new Database();

// Vérifier si les tables de paiements existent
try {
    $db->query("SELECT 1 FROM paiements LIMIT 1");
} catch (Exception $e) {
    // Tables non créées, rediriger vers l'installation
    header('Location: /setup_caisse_system.php');
    exit;
}

$patiente_id = $_GET['id'] ?? 0;

// Récupérer les informations de la patiente
$patiente = $db->fetch("SELECT * FROM patientes WHERE id = ?", [$patiente_id]);

if (!$patiente) {
    header('Location: caissiere_consultations.php');
    exit;
}

// Historique des consultations avec actes et paiements
$consultations = $db->fetchAll("
    SELECT 
        cp.id as consultation_id,
        cp.date_consultation,
        cp.observations,
        p.id as paiement_id,
        p.montant_total,
        p.montant_paye,
        p.montant_restant,
        p.statut,
        p.mode_paiement,
        p.date_paiement,
        u.nom as medecin_nom,
        u.prenom as medecin_prenom
    FROM consultations_prenatales cp
    LEFT JOIN paiements p ON cp.id = p.consultation_id
    LEFT JOIN users u ON cp.medecin_id = u.id
    WHERE cp.patiente_id = ?
    ORDER BY cp.date_consultation DESC
", [$patiente_id]);

// Pour chaque consultation, récupérer les actes
foreach ($consultations as &$consultation) {
    $consultation['actes'] = $db->fetchAll("
        SELECT ca.*, ap.nom_acte, ap.description
        FROM consultation_actes ca
        INNER JOIN actes_poses ap ON ca.acte_id = ap.id
        WHERE ca.consultation_id = ?
    ", [$consultation['consultation_id']]);
    
    // Historique des paiements partiels
    if ($consultation['paiement_id']) {
        $consultation['historique_paiements'] = $db->fetchAll("
            SELECT hp.*, u.nom, u.prenom
            FROM historique_paiements hp
            LEFT JOIN users u ON hp.caissiere_id = u.id
            WHERE hp.paiement_id = ?
            ORDER BY hp.date_versement DESC
        ", [$consultation['paiement_id']]);
    }
}

// Totaux
$totaux = $db->fetch("
    SELECT 
        COUNT(DISTINCT cp.id) as nb_consultations,
        COALESCE(SUM(p.montant_total), 0) as total_facture,
        COALESCE(SUM(p.montant_paye), 0) as total_paye,
        COALESCE(SUM(p.montant_restant), 0) as total_restant
    FROM consultations_prenatales cp
    LEFT JOIN paiements p ON cp.id = p.consultation_id
    WHERE cp.patiente_id = ?
", [$patiente_id]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail Patiente - <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include 'includes/navbar.php'; ?>
            <div class="p-8">
                <div class="mb-6">
                    <a href="caissiere_consultations.php" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Dossier : <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                    </h1>
                </div>

                <!-- Info patiente et totaux -->
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Informations Patiente</h2>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Nom complet:</dt>
                                <dd class="font-semibold"><?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Âge:</dt>
                                <dd class="font-semibold"><?php echo $patiente['age'] ?? '-'; ?> ans</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Téléphone:</dt>
                                <dd class="font-semibold"><?php echo htmlspecialchars($patiente['telephone'] ?? '-'); ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-600">Groupe sanguin:</dt>
                                <dd class="font-semibold"><?php echo htmlspecialchars($patiente['groupe_sanguin'] ?? '-'); ?></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg shadow-lg p-6 border-l-4 border-green-500">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Résumé Financier</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Consultations:</span>
                                <span class="text-2xl font-bold text-blue-600"><?php echo $totaux['nb_consultations']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Total facturé:</span>
                                <span class="text-lg font-bold text-gray-900"><?php echo number_format($totaux['total_facture'], 0, ',', ' '); ?> FCFA</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700">Total payé:</span>
                                <span class="text-lg font-bold text-green-600"><?php echo number_format($totaux['total_paye'], 0, ',', ' '); ?> FCFA</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t-2 border-green-200">
                                <span class="text-gray-700 font-semibold">Reste à payer:</span>
                                <span class="text-2xl font-bold text-red-600"><?php echo number_format($totaux['total_restant'], 0, ',', ' '); ?> FCFA</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique consultations -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Historique des Consultations et Paiements</h2>
                    
                    <?php if (empty($consultations)): ?>
                        <p class="text-center text-gray-500 py-8">Aucune consultation enregistrée</p>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($consultations as $c): ?>
                                <div class="border-l-4 <?php echo $c['statut'] === 'paye_total' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50'; ?> p-4 rounded">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <p class="font-semibold text-lg text-gray-900">
                                                <i class="fas fa-calendar mr-2"></i>
                                                <?php echo date('d/m/Y à H:i', strtotime($c['date_consultation'])); ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                Par Dr. <?php echo htmlspecialchars(($c['medecin_prenom'] ?? '') . ' ' . ($c['medecin_nom'] ?? 'Non spécifié')); ?>
                                            </p>
                                        </div>
                                        <?php
                                        $statut_colors = [
                                            'en_attente' => 'bg-red-500',
                                            'paye_partiel' => 'bg-yellow-500',
                                            'paye_total' => 'bg-green-500'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-white text-xs <?php echo $statut_colors[$c['statut']] ?? 'bg-gray-500'; ?>">
                                            <?php echo str_replace('_', ' ', ucfirst($c['statut'])); ?>
                                        </span>
                                    </div>

                                    <!-- Actes posés -->
                                    <?php if (!empty($c['actes'])): ?>
                                        <div class="bg-white rounded p-3 mb-3">
                                            <p class="font-semibold text-sm text-gray-700 mb-2">Actes posés:</p>
                                            <div class="space-y-1">
                                                <?php foreach ($c['actes'] as $acte): ?>
                                                    <div class="flex justify-between text-sm">
                                                        <span>• <?php echo htmlspecialchars($acte['nom_acte']); ?></span>
                                                        <span class="font-semibold"><?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Paiement -->
                                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600">Total:</span>
                                            <span class="font-bold text-gray-900 ml-2"><?php echo number_format($c['montant_total'], 0, ',', ' '); ?> FCFA</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Payé:</span>
                                            <span class="font-bold text-green-600 ml-2"><?php echo number_format($c['montant_paye'], 0, ',', ' '); ?> FCFA</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Reste:</span>
                                            <span class="font-bold text-red-600 ml-2"><?php echo number_format($c['montant_restant'], 0, ',', ' '); ?> FCFA</span>
                                        </div>
                                    </div>

                                    <!-- Historique paiements partiels -->
                                    <?php if (!empty($c['historique_paiements'])): ?>
                                        <div class="mt-3 bg-blue-50 rounded p-3">
                                            <p class="font-semibold text-sm text-blue-900 mb-2">Historique des versements:</p>
                                            <?php foreach ($c['historique_paiements'] as $hp): ?>
                                                <div class="flex justify-between text-xs text-blue-800">
                                                    <span>
                                                        <?php echo date('d/m/Y H:i', strtotime($hp['date_versement'])); ?> 
                                                        - <?php echo htmlspecialchars($hp['prenom'] . ' ' . $hp['nom']); ?>
                                                    </span>
                                                    <span class="font-bold"><?php echo number_format($hp['montant'], 0, ',', ' '); ?> FCFA</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="mt-3 flex gap-2">
                                        <?php if ($c['statut'] !== 'paye_total'): ?>
                                            <a href="caissiere_valider_paiement.php?id=<?php echo $c['paiement_id']; ?>" 
                                               class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 text-sm">
                                                <i class="fas fa-check mr-1"></i>Valider paiement
                                            </a>
                                        <?php else: ?>
                                            <a href="caissiere_recu.php?id=<?php echo $c['paiement_id']; ?>" 
                                               class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 text-sm">
                                                <i class="fas fa-file-pdf mr-1"></i>Imprimer reçu
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

