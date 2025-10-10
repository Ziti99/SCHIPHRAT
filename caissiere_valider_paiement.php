<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'caissiere'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
$db = new Database();

// Vérifier si les tables de paiements existent
try {
    $db->query("SELECT 1 FROM paiements LIMIT 1");
} catch (PDOException $e) {
    header('Location: /setup_caisse_system.php');
    exit;
}

$paiement_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Récupérer les informations du paiement
$paiement = $db->fetch("
    SELECT p.*, pat.nom, pat.prenom, pat.telephone, cp.date_consultation
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    WHERE p.id = ?
", [$paiement_id]);

if (!$paiement) {
    header('Location: caissiere_consultations.php');
    exit;
}

// Récupérer les actes de cette consultation
$actes = $db->fetchAll("
    SELECT ca.*, ap.nom_acte
    FROM consultation_actes ca
    INNER JOIN actes_poses ap ON ca.acte_id = ap.id
    WHERE ca.consultation_id = ?
", [$paiement['consultation_id']]);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $montant_verse = floatval($_POST['montant_verse']);
        $mode_paiement = $_POST['mode_paiement'];
        $reference = $_POST['reference'] ?? '';
        $observations = $_POST['observations'] ?? '';
        
        if ($montant_verse <= 0) {
            throw new Exception('Le montant doit être supérieur à 0');
        }
        
        if ($montant_verse > $paiement['montant_restant']) {
            throw new Exception('Le montant versé ne peut pas dépasser le montant restant');
        }
        
        // Mise à jour du paiement
        $nouveau_montant_paye = $paiement['montant_paye'] + $montant_verse;
        $nouveau_montant_restant = $paiement['montant_total'] - $nouveau_montant_paye;
        
        // S'assurer que le montant restant ne soit jamais négatif
        if ($nouveau_montant_restant < 0) {
            $nouveau_montant_restant = 0;
        }
        
        // Déterminer le statut : paye_total si montant restant <= 0, sinon paye_partiel
        $nouveau_statut = ($nouveau_montant_restant <= 0) ? 'paye_total' : 'paye_partiel';
        
        $db->query("
            UPDATE paiements SET
                montant_paye = ?,
                montant_restant = ?,
                statut = ?,
                mode_paiement = ?,
                reference_paiement = ?,
                observations = ?,
                caissiere_id = ?,
                date_paiement = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ", [
            $nouveau_montant_paye,
            $nouveau_montant_restant,
            $nouveau_statut,
            $mode_paiement,
            $reference,
            $observations,
            $_SESSION['user_id'],
            $paiement_id
        ]);
        
        // Enregistrer dans l'historique
        $db->query("
            INSERT INTO historique_paiements (paiement_id, montant, mode_paiement, reference, observations, caissiere_id, date_versement)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $paiement_id,
            $montant_verse,
            $mode_paiement,
            $reference,
            $observations,
            $_SESSION['user_id']
        ]);
        
        if ($nouveau_statut === 'paye_total') {
            header('Location: caissiere_recu.php?id=' . $paiement_id . '&success=1');
        } else {
            header('Location: caissiere_valider_paiement.php?id=' . $paiement_id . '&success=partial');
        }
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Message de succès
if (isset($_GET['success'])) {
    $message = $_GET['success'] === 'partial' ? 'Paiement partiel enregistré avec succès !' : 'Paiement enregistré !';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation Paiement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include 'includes/navbar.php'; ?>
            <div class="p-8">
                <a href="caissiere_consultations.php" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>

                <h1 class="text-3xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-cash-register text-green-600 mr-3"></i>
                    Validation de Paiement
                </h1>

                <?php if ($message): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Info consultation -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Informations Consultation</h2>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-600">Patiente:</dt>
                                <dd class="font-bold text-lg"><?php echo htmlspecialchars($paiement['prenom'] . ' ' . $paiement['nom']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Téléphone:</dt>
                                <dd class="font-semibold"><?php echo htmlspecialchars($paiement['telephone'] ?? '-'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-gray-600">Date consultation:</dt>
                                <dd class="font-semibold"><?php echo date('d/m/Y à H:i', strtotime($paiement['date_consultation'])); ?></dd>
                            </div>
                        </dl>

                        <div class="mt-6 border-t pt-4">
                            <h3 class="font-semibold text-gray-900 mb-2">Actes effectués:</h3>
                            <div class="space-y-2">
                                <?php foreach ($actes as $acte): ?>
                                    <div class="flex justify-between text-sm bg-gray-50 p-2 rounded">
                                        <span><?php echo htmlspecialchars($acte['nom_acte']); ?></span>
                                        <span class="font-semibold"><?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-6 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Montant total:</span>
                                    <span class="text-xl font-bold text-gray-900"><?php echo number_format($paiement['montant_total'], 0, ',', ' '); ?> FCFA</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-700">Déjà payé:</span>
                                    <span class="text-lg font-bold text-green-600"><?php echo number_format($paiement['montant_paye'], 0, ',', ' '); ?> FCFA</span>
                                </div>
                                <div class="flex justify-between pt-2 border-t-2 border-blue-200">
                                    <span class="text-gray-700 font-semibold">Reste à payer:</span>
                                    <span class="text-2xl font-bold text-red-600"><?php echo number_format($paiement['montant_restant'], 0, ',', ' '); ?> FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire de paiement -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Enregistrer le Paiement</h2>
                        
                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Montant versé <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="montant_verse" id="montant_verse" step="0.01" 
                                       max="<?php echo $paiement['montant_restant']; ?>"
                                       value="<?php echo $paiement['montant_restant']; ?>"
                                       required
                                       oninput="updatePaiementStatus()"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg text-lg font-bold">
                                <div class="flex justify-between items-center mt-2">
                                    <p class="text-xs text-gray-500">Maximum: <?php echo number_format($paiement['montant_restant'], 0, ',', ' '); ?> FCFA</p>
                                    <p id="reste_apres" class="text-sm font-semibold"></p>
                                </div>
                                <div id="statut_paiement" class="mt-2 p-3 rounded-lg hidden">
                                    <p class="text-sm font-semibold"></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Mode de paiement <span class="text-red-500">*</span>
                                </label>
                                <select name="mode_paiement" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                                    <option value="">Sélectionner...</option>
                                    <option value="especes">Espèces</option>
                                    <option value="carte">Carte bancaire</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                    <option value="mixte">Mixte</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Référence (optionnel)
                                </label>
                                <input type="text" name="reference" 
                                       placeholder="N° transaction, chèque..." 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Observations (optionnel)
                                </label>
                                <textarea name="observations" rows="3" 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg"></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button type="submit" class="flex-1 bg-green-500 text-white py-4 rounded-lg font-bold hover:bg-green-600 transition-colors">
                                    <i class="fas fa-check mr-2"></i>Valider le Paiement
                                </button>
                                <a href="caissiere_consultations.php" 
                                   class="px-6 py-4 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                    Annuler
                                </a>
                            </div>
                        </form>

                        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Paiement partiel :</strong> Vous pouvez encaisser une partie du montant aujourd'hui. 
                                Le reste pourra être payé ultérieurement.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const montantRestant = <?php echo $paiement['montant_restant']; ?>;
        const montantTotal = <?php echo $paiement['montant_total']; ?>;
        const dejaPaye = <?php echo $paiement['montant_paye']; ?>;

        function updatePaiementStatus() {
            const montantVerse = parseFloat(document.getElementById('montant_verse').value) || 0;
            const nouveauMontantPaye = dejaPaye + montantVerse;
            const nouveauMontantRestant = Math.max(0, montantTotal - nouveauMontantPaye);
            
            const resteApresEl = document.getElementById('reste_apres');
            const statutPaiementEl = document.getElementById('statut_paiement');
            
            // Afficher le reste après paiement
            if (montantVerse > 0) {
                resteApresEl.textContent = 'Reste après: ' + nouveauMontantRestant.toLocaleString('fr-FR') + ' FCFA';
                resteApresEl.className = nouveauMontantRestant > 0 ? 'text-sm font-semibold text-orange-600' : 'text-sm font-semibold text-green-600';
                
                // Afficher le statut du paiement
                statutPaiementEl.classList.remove('hidden');
                
                if (nouveauMontantRestant <= 0) {
                    statutPaiementEl.className = 'mt-2 p-3 rounded-lg bg-green-50 border border-green-200';
                    statutPaiementEl.querySelector('p').innerHTML = '<i class="fas fa-check-circle mr-2"></i>✓ Ce paiement sera marqué comme <strong>PAYÉ INTÉGRALEMENT</strong>';
                    statutPaiementEl.querySelector('p').className = 'text-sm font-semibold text-green-700';
                } else {
                    statutPaiementEl.className = 'mt-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200';
                    statutPaiementEl.querySelector('p').innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>⚠ Paiement partiel - Il restera ' + nouveauMontantRestant.toLocaleString('fr-FR') + ' FCFA à payer';
                    statutPaiementEl.querySelector('p').className = 'text-sm font-semibold text-yellow-700';
                }
            } else {
                resteApresEl.textContent = '';
                statutPaiementEl.classList.add('hidden');
            }
        }

        // Initialiser l'affichage au chargement
        document.addEventListener('DOMContentLoaded', function() {
            updatePaiementStatus();
        });
    </script>
</body>
</html>

