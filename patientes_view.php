<?php
// Démarrer la session en premier, avant tout autre code
session_start();

require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? '',
];

$db = new Database();

// Récupérer l'ID de la patiente
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: patientes.php');
    exit();
}

// Récupérer les informations de la patiente
try {
    $patiente = $db->fetchOne("SELECT * FROM patientes WHERE id = ?", [$id]);
    if (!$patiente) {
        header('Location: patientes.php');
        exit();
    }
    
    // Récupérer les consultations prénatales avec détails complets
    $consultations = $db->fetchAll("
        SELECT cp.*, 
               u.nom as medecin_nom, u.prenom as medecin_prenom, u.specialite
        FROM consultations_prenatales cp 
        LEFT JOIN users u ON cp.medecin_id = u.id
        WHERE cp.patiente_id = ? 
        ORDER BY cp.date_consultation DESC
    ", [$id]);
    
    // Pour chaque consultation, récupérer les actes médicaux
    foreach ($consultations as &$consultation) {
        $consultation['actes'] = $db->fetchAll("
            SELECT ca.*, ap.nom_acte, ap.description, 
                   COALESCE(ca.montant, ap.montant) as montant
            FROM consultation_actes ca
            INNER JOIN actes_poses ap ON ca.acte_id = ap.id
            WHERE ca.consultation_id = ?
        ", [$consultation['id']]);
    }
    unset($consultation); // Important : libérer la référence
    
    // Récupérer les accouchements (sans grossesses car table inexistante)
    $accouchements = $db->fetchAll("
        SELECT a.* 
        FROM accouchements a 
        WHERE a.patiente_id = ? 
        ORDER BY a.date_accouchement DESC
    ", [$id]);
    
    // Initialiser grossesses comme tableau vide car table inexistante
    $grossesses = [];
    
} catch (Exception $e) {
    header('Location: patientes.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Patiente - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="patientes.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-user text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Patiente</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </span>
                            <a href="logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails de la Patiente</h1>
                    <p class="text-gray-600">Informations complètes de <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?></p>
                </div>

                <!-- Informations principales -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Informations personnelles -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex items-center mb-6">
                                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold text-xl mr-4">
                                    <?php echo strtoupper(substr($patiente['prenom'], 0, 1) . substr($patiente['nom'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                    </h2>
                                    <p class="text-gray-600">ID: <?php echo htmlspecialchars($patiente['id']); ?></p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date de naissance:</span>
                                    <span class="font-medium"><?php echo date('d/m/Y', strtotime($patiente['date_naissance'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Âge:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($patiente['age']); ?> ans</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Téléphone:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($patiente['telephone']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nationalité:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($patiente['nationalite'] ?? 'Non renseigné'); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($patiente['adresse'])): ?>
                                <div class="mt-6 pt-4 border-t border-gray-200">
                                    <span class="text-sm font-medium text-gray-500">Adresse:</span>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($patiente['adresse']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($patiente['antecedents_medicaux'])): ?>
                                <div class="mt-6 pt-4 border-t border-gray-200">
                                    <span class="text-sm font-medium text-gray-500">Antécédents médicaux:</span>
                                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($patiente['antecedents_medicaux']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <div class="flex flex-wrap gap-2">
                                    <a href="patientes_edit.php?id=<?php echo $patiente['id']; ?>" 
                                       class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center">
                                        <i class="fas fa-edit mr-2"></i>Modifier
                                    </a>
                                    <a href="patientes_delete.php?id=<?php echo $patiente['id']; ?>" 
                                       class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-center"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette patiente ?')">
                                        <i class="fas fa-trash mr-2"></i>Supprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique médical -->
                    <div class="lg:col-span-2">
                        <!-- Consultations récentes (en premier) -->
                        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <i class="fas fa-stethoscope mr-2 text-blue-600"></i>Consultations récentes (<?php echo count($consultations); ?>)
                                </h3>
                                <a href="consultations/ajouter.php?patiente_id=<?php echo $patiente['id']; ?>" 
                                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Nouvelle Consultation
                                </a>
                            </div>
                            
                            <?php if (empty($consultations)): ?>
                                <p class="text-gray-500 text-center py-4">Aucune consultation enregistrée</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach (array_slice($consultations, 0, 5) as $index => $consultation): ?>
                                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                                            <!-- En-tête cliquable -->
                                            <button 
                                                type="button"
                                                onclick="toggleConsultationDetails(<?php echo $consultation['id']; ?>)"
                                                class="w-full text-left p-4 hover:bg-blue-50 hover:border-blue-300 transition-colors cursor-pointer flex justify-between items-start"
                                                id="consultation-header-<?php echo $consultation['id']; ?>">
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-gray-900 flex items-center">
                                                        <i class="fas fa-calendar-check text-blue-600 mr-2"></i>
                                                        Consultation du <?php echo date('d/m/Y', strtotime($consultation['date_consultation'])); ?>
                                                        <?php if (!empty($consultation['date_consultation'])): ?>
                                                            <span class="text-sm font-normal text-gray-500 ml-2">
                                                                à <?php echo date('H:i', strtotime($consultation['date_consultation'])); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <?php if ($consultation['observations']): ?>
                                                        <p class="text-sm text-gray-700 mt-2">
                                                            <?php echo htmlspecialchars(substr($consultation['observations'], 0, 100)); ?>
                                                            <?php if (strlen($consultation['observations']) > 100): ?>...<?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <i class="fas fa-chevron-down text-gray-400 ml-2 transition-transform" id="chevron-<?php echo $consultation['id']; ?>"></i>
                                            </button>
                                            
                                            <!-- Détails (masqués par défaut) -->
                                            <div 
                                                id="consultation-details-<?php echo $consultation['id']; ?>" 
                                                class="hidden border-t border-gray-200 bg-gray-50 p-4">
                                                <div class="space-y-4">
                                                    <!-- Informations générales -->
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-600">Date et heure:</span>
                                                            <p class="text-gray-900"><?php echo date('d/m/Y à H:i', strtotime($consultation['date_consultation'])); ?></p>
                                                        </div>
                                                        <?php if ($consultation['medecin_nom']): ?>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-600">Médecin:</span>
                                                                <p class="text-gray-900">Dr. <?php echo htmlspecialchars($consultation['medecin_prenom'] . ' ' . $consultation['medecin_nom']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($consultation['tension_arterielle']): ?>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-600">Tension artérielle:</span>
                                                                <p class="text-gray-900"><?php echo htmlspecialchars($consultation['tension_arterielle']); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($consultation['poids']): ?>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-600">Poids:</span>
                                                                <p class="text-gray-900"><?php echo htmlspecialchars($consultation['poids']); ?> kg</p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($consultation['hauteur_uterine']): ?>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-600">Hauteur utérine:</span>
                                                                <p class="text-gray-900"><?php echo htmlspecialchars($consultation['hauteur_uterine']); ?> cm</p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($consultation['frequence_cardiaque_foetale']): ?>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-600">Fréquence cardiaque fœtale:</span>
                                                                <p class="text-gray-900"><?php echo htmlspecialchars($consultation['frequence_cardiaque_foetale']); ?> bpm</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <!-- Actes médicaux -->
                                                    <?php if (!empty($consultation['actes'])): ?>
                                                        <div class="border-t border-gray-300 pt-4">
                                                            <h5 class="font-semibold text-gray-900 mb-3">
                                                                <i class="fas fa-stethoscope text-purple-600 mr-2"></i>Actes médicaux (<?php echo count($consultation['actes']); ?>)
                                                            </h5>
                                                            <div class="space-y-2">
                                                                <?php 
                                                                $total_actes = 0;
                                                                foreach ($consultation['actes'] as $acte): 
                                                                    $total_actes += $acte['montant'] * $acte['quantite'];
                                                                ?>
                                                                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                                                                        <div class="flex justify-between items-center">
                                                                            <div>
                                                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($acte['nom_acte']); ?></p>
                                                                                <?php if ($acte['description']): ?>
                                                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($acte['description']); ?></p>
                                                                                <?php endif; ?>
                                                                                <p class="text-xs text-gray-500 mt-1">Quantité: <?php echo $acte['quantite']; ?></p>
                                                                            </div>
                                                                            <p class="font-semibold text-purple-600">
                                                                                <?php echo number_format($acte['montant'] * $acte['quantite'], 0, ',', ' '); ?> FCFA
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                                <div class="bg-purple-50 rounded-lg p-3 border border-purple-200 mt-2">
                                                                    <div class="flex justify-between items-center">
                                                                        <span class="font-semibold text-gray-900">Total:</span>
                                                                        <span class="font-bold text-purple-600 text-lg">
                                                                            <?php echo number_format($total_actes, 0, ',', ' '); ?> FCFA
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Observations -->
                                                    <?php if ($consultation['observations']): ?>
                                                        <div class="border-t border-gray-300 pt-4">
                                                            <h5 class="font-semibold text-gray-900 mb-2">
                                                                <i class="fas fa-notes-medical text-green-600 mr-2"></i>Observations
                                                            </h5>
                                                            <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($consultation['observations']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Recommandations -->
                                                    <?php if ($consultation['recommandations']): ?>
                                                        <div class="border-t border-gray-300 pt-4">
                                                            <h5 class="font-semibold text-gray-900 mb-2">
                                                                <i class="fas fa-clipboard-check text-blue-600 mr-2"></i>Recommandations
                                                            </h5>
                                                            <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($consultation['recommandations']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Actions -->
                                                    <div class="border-t border-gray-300 pt-4 flex justify-end space-x-2">
                                                        <a href="consultations/voir.php?id=<?php echo $consultation['id']; ?>" 
                                                           class="text-sm text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-external-link-alt mr-1"></i>Voir la page complète
                                                        </a>
                                                        <a href="consultations/modifier.php?id=<?php echo $consultation['id']; ?>" 
                                                           class="text-sm text-green-600 hover:text-green-800">
                                                            <i class="fas fa-edit mr-1"></i>Modifier
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Grossesses -->
                        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <i class="fas fa-baby mr-2 text-purple-600"></i>Grossesses (<?php echo count($grossesses); ?>)
                                </h3>
                                <a href="ajouter.php?patiente_id=<?php echo $patiente['id']; ?>" 
                                   class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Nouvelle Grossesse
                                </a>
                            </div>
                            
                            <?php if (empty($grossesses)): ?>
                                <p class="text-gray-500 text-center py-4">Aucune grossesse enregistrée</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($grossesses as $grossesse): ?>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        Grossesse #<?php echo $grossesse['id']; ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-600">
                                                        Début: <?php echo date('d/m/Y', strtotime($grossesse['date_debut_grossesse'])); ?>
                                                        <?php if ($grossesse['date_fin_grossesse']): ?>
                                                            - Fin: <?php echo date('d/m/Y', strtotime($grossesse['date_fin_grossesse'])); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        Statut: 
                                                        <span class="font-medium 
                                                            <?php echo $grossesse['statut'] === 'en_cours' ? 'text-green-600' : 'text-gray-600'; ?>">
                                                            <?php echo $grossesse['statut'] === 'en_cours' ? 'En cours' : 'Terminée'; ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="consultations.php?grossesse_id=<?php echo $grossesse['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-800">
                                                        <i class="fas fa-stethoscope"></i>
                                                    </a>
                                                    <a href="accouchements.php?grossesse_id=<?php echo $grossesse['id']; ?>" 
                                                       class="text-green-600 hover:text-green-800">
                                                        <i class="fas fa-baby"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Accouchements -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900">
                                    <i class="fas fa-baby mr-2 text-green-600"></i>Accouchements (<?php echo count($accouchements); ?>)
                                </h3>
                                <a href="accouchements/ajouter.php?patiente_id=<?php echo $patiente['id']; ?>" 
                                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-plus mr-2"></i>Nouvel Accouchement
                                </a>
                            </div>
                            
                            <?php if (empty($accouchements)): ?>
                                <p class="text-gray-500 text-center py-4">Aucun accouchement enregistré</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($accouchements as $accouchement): ?>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-semibold text-gray-900">
                                                        Accouchement du <?php echo date('d/m/Y', strtotime($accouchement['date_accouchement'])); ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-600">
                                                        Grossesse #<?php echo $accouchement['grossesse_id']; ?>
                                                    </p>
                                                    <?php if ($accouchement['nom_bebe']): ?>
                                                        <p class="text-sm text-gray-700">
                                                            Bébé: <?php echo htmlspecialchars($accouchement['nom_bebe']); ?>
                                                            <?php if ($accouchement['sexe_bebe']): ?>
                                                                (<?php echo $accouchement['sexe_bebe'] === 'M' ? 'Garçon' : 'Fille'; ?>)
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    <?php if ($accouchement['poids_bebe']): ?>
                                                        <p class="text-sm text-gray-600">
                                                            Poids: <?php echo $accouchement['poids_bebe']; ?> kg
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleConsultationDetails(consultationId) {
            const detailsDiv = document.getElementById('consultation-details-' + consultationId);
            const chevron = document.getElementById('chevron-' + consultationId);
            
            if (!detailsDiv || !chevron) {
                console.error('Éléments non trouvés pour consultation ID:', consultationId);
                return;
            }
            
            if (detailsDiv.classList.contains('hidden')) {
                // Ouvrir
                detailsDiv.classList.remove('hidden');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
                chevron.style.transform = 'rotate(180deg)';
                console.log('Consultation', consultationId, 'ouverte');
            } else {
                // Fermer
                detailsDiv.classList.add('hidden');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
                chevron.style.transform = 'rotate(0deg)';
                console.log('Consultation', consultationId, 'fermée');
            }
        }
    </script>
</body>
</html> 