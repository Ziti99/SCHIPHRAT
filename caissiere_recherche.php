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

$search = $_GET['search'] ?? '';
$resultats = [];

if ($search) {
    // Rechercher les patientes
    $resultats = $db->fetchAll("
        SELECT 
            pat.*,
            COUNT(DISTINCT p.id) as nb_consultations,
            COALESCE(SUM(p.montant_total), 0) as total_facture,
            COALESCE(SUM(p.montant_paye), 0) as total_paye,
            COALESCE(SUM(p.montant_restant), 0) as total_restant,
            SUM(CASE WHEN p.statut = 'en_attente' THEN 1 ELSE 0 END) as nb_en_attente,
            SUM(CASE WHEN p.statut = 'paye_partiel' THEN 1 ELSE 0 END) as nb_partiel
        FROM patientes pat
        LEFT JOIN paiements p ON pat.id = p.patiente_id
        WHERE pat.nom LIKE ? OR pat.prenom LIKE ? OR pat.telephone LIKE ?
        GROUP BY pat.id
        ORDER BY pat.nom, pat.prenom
    ", ["%$search%", "%$search%", "%$search%"]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher Patiente - Caissière</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include 'includes/navbar.php'; ?>
            
            <div class="p-8">
                <!-- En-tête -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-search text-green-600 mr-3"></i>
                        Rechercher une Patiente
                    </h1>
                    <p class="text-gray-600">Recherchez une patiente pour consulter son historique de paiements</p>
                </div>

                <!-- Formulaire de recherche -->
                <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                    <form method="GET" class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2"></i>Nom, Prénom ou Téléphone
                                </label>
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Ex: NKOGHE, Marie, 06 12 34 56..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg"
                                    autofocus>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-green-500 text-white px-8 py-3 rounded-lg hover:bg-green-600 transition-colors font-semibold text-lg">
                                    <i class="fas fa-search mr-2"></i>Rechercher
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($search): ?>
                            <div class="flex justify-between items-center pt-2">
                                <p class="text-sm text-gray-600">
                                    <?php echo count($resultats); ?> résultat(s) pour "<?php echo htmlspecialchars($search); ?>"
                                </p>
                                <a href="caissiere_recherche.php" class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-times mr-1"></i>Effacer la recherche
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Résultats de recherche -->
                <?php if ($search): ?>
                    <?php if (empty($resultats)): ?>
                        <!-- Aucun résultat -->
                        <div class="bg-white rounded-lg shadow-lg p-12 text-center">
                            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">Aucune patiente trouvée</h2>
                            <p class="text-gray-600 mb-6">
                                Aucune patiente ne correspond à "<?php echo htmlspecialchars($search); ?>"
                            </p>
                            <p class="text-sm text-gray-500">
                                Essayez avec d'autres termes de recherche (nom, prénom, téléphone)
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Résultats -->
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($resultats as $patiente): ?>
                                <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow overflow-hidden">
                                    <!-- En-tête carte -->
                                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 p-4 text-white">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center">
                                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-green-600 text-xl"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-lg">
                                                        <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                                    </h3>
                                                    <p class="text-xs opacity-90"><?php echo $patiente['age']; ?> ans</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm opacity-90">
                                            <i class="fas fa-phone mr-2"></i><?php echo htmlspecialchars($patiente['telephone']); ?>
                                        </div>
                                    </div>

                                    <!-- Corps carte -->
                                    <div class="p-4">
                                        <!-- Statistiques -->
                                        <div class="grid grid-cols-2 gap-3 mb-4">
                                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                                <p class="text-xs text-blue-600 mb-1">Consultations</p>
                                                <p class="text-2xl font-bold text-blue-900"><?php echo $patiente['nb_consultations']; ?></p>
                                            </div>
                                            <div class="bg-purple-50 rounded-lg p-3 text-center">
                                                <p class="text-xs text-purple-600 mb-1">Groupe Sang.</p>
                                                <p class="text-xl font-bold text-purple-900"><?php echo $patiente['groupe_sanguin'] ?? '-'; ?></p>
                                            </div>
                                        </div>

                                        <!-- Montants -->
                                        <div class="space-y-2 mb-4">
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Total facturé:</span>
                                                <span class="font-semibold"><?php echo number_format($patiente['total_facture'], 0, ',', ' '); ?> FCFA</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600">Total payé:</span>
                                                <span class="font-semibold text-green-600"><?php echo number_format($patiente['total_paye'], 0, ',', ' '); ?> FCFA</span>
                                            </div>
                                            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                                <span class="text-gray-600 font-semibold">Reste à payer:</span>
                                                <span class="font-bold text-red-600"><?php echo number_format($patiente['total_restant'], 0, ',', ' '); ?> FCFA</span>
                                            </div>
                                        </div>

                                        <!-- Statut paiements -->
                                        <?php if ($patiente['nb_en_attente'] > 0 || $patiente['nb_partiel'] > 0): ?>
                                            <div class="flex gap-2 mb-4">
                                                <?php if ($patiente['nb_en_attente'] > 0): ?>
                                                    <span class="flex-1 text-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                        <?php echo $patiente['nb_en_attente']; ?> en attente
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($patiente['nb_partiel'] > 0): ?>
                                                    <span class="flex-1 text-center px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                        <?php echo $patiente['nb_partiel']; ?> partiel
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-4 text-center">
                                                <span class="px-3 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    <i class="fas fa-check-circle mr-1"></i>Tous les paiements à jour
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Bouton d'action -->
                                        <a href="caissiere_patiente_detail.php?id=<?php echo $patiente['id']; ?>" 
                                           class="block w-full bg-green-500 text-white text-center py-3 rounded-lg hover:bg-green-600 transition-colors font-semibold">
                                            <i class="fas fa-eye mr-2"></i>Voir le Dossier Complet
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Écran d'accueil sans recherche -->
                    <div class="bg-white rounded-lg shadow-lg p-16 text-center">
                        <div class="max-w-lg mx-auto">
                            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-search text-5xl text-green-600"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Rechercher une Patiente</h2>
                            <p class="text-gray-600 mb-6">
                                Entrez le nom, prénom ou numéro de téléphone d'une patiente pour consulter son historique de consultations et de paiements.
                            </p>
                            
                            <!-- Conseils de recherche -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                                <h3 class="font-semibold text-blue-900 mb-2">
                                    <i class="fas fa-lightbulb mr-2"></i>Conseils de recherche :
                                </h3>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• Vous pouvez rechercher par <strong>nom de famille</strong> (ex: NKOGHE)</li>
                                    <li>• Ou par <strong>prénom</strong> (ex: Marie)</li>
                                    <li>• Ou par <strong>numéro de téléphone</strong> (ex: 06 12 34 56)</li>
                                    <li>• La recherche n'est pas sensible à la casse</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

