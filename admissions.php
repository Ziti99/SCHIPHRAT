<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Paramètres de filtrage
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$medecin_id = $_GET['medecin_id'] ?? '';
$statut_grossesse = $_GET['statut_grossesse'] ?? '';

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_debut)) {
    $where_conditions[] = "c.date_consultation >= ?";
    $params[] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_conditions[] = "c.date_consultation <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

if (!empty($medecin_id)) {
    $where_conditions[] = "c.medecin_id = ?";
    $params[] = $medecin_id;
}

// Suppression du filtre statut_grossesse car la table grossesses n'existe pas

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Récupération des admissions (consultations prénatales)
$admissions = $db->fetchAll("
    SELECT 
        c.id as consultation_id,
        c.date_consultation,
        c.observations,
        p.id as patiente_id,
        p.nom, 
        p.prenom, 
        p.telephone,
        p.date_naissance,
        medecin.nom as medecin_nom,
        medecin.prenom as medecin_prenom,
        medecin.specialite as medecin_specialite
    FROM consultations_prenatales c
    JOIN patientes p ON c.patiente_id = p.id
    JOIN users medecin ON c.medecin_id = medecin.id
    $where_clause
    ORDER BY c.date_consultation DESC
", $params);

// Récupération des médecins pour le filtre
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role IN ('medecin', 'sage_femme') AND is_active = 1
    ORDER BY nom, prenom
");

// Statistiques
$total_admissions = count($admissions);
$admissions_ce_mois = $db->fetch("
    SELECT COUNT(*) as count 
    FROM consultations_prenatales 
    WHERE MONTH(date_consultation) = MONTH(CURDATE()) 
    AND YEAR(date_consultation) = YEAR(CURDATE())
")['count'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre des Admissions - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 min-h-screen">
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
                            <a href="../registres.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-user-plus text-2xl text-blue-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Registre des Admissions</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="../logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100">Total Admissions</p>
                                <p class="text-3xl font-bold"><?php echo $total_admissions; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100">Ce Mois</p>
                                <p class="text-3xl font-bold"><?php echo $admissions_ce_mois; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100">Actions</p>
                                <p class="text-lg font-bold">Export & Filtres</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-download text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-filter mr-2 text-purple-600"></i>Filtres de recherche
                    </h3>
                    
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nom, prénom ou dossier..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                            <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                            <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Médecin</label>
                            <select name="medecin_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Tous les médecins</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?php echo $medecin['id']; ?>" <?php echo $medecin_id == $medecin['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end space-x-3">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="admissions.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Actions d'export -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Liste des Admissions</h2>
                    <div class="flex space-x-3">
                        <button onclick="exportToPDF()" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-md hover:from-red-600 hover:to-red-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </button>
                        <button onclick="exportToExcel()" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-md hover:from-green-600 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                    </div>
                </div>

                <!-- Tableau des admissions -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date Consultation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($admissions)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg">Aucune admission trouvée</p>
                                            <p class="text-sm">Essayez de modifier vos filtres de recherche</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($admissions as $admission): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($admission['prenom'] . ' ' . $admission['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
            
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        Tél: <?php echo htmlspecialchars($admission['telephone']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('d/m/Y', strtotime($admission['date_consultation'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('H:i', strtotime($admission['date_consultation'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    Dr. <?php echo htmlspecialchars($admission['medecin_prenom'] . ' ' . $admission['medecin_nom']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($admission['medecin_specialite']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    Consultation prénatale
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: <?php echo $admission['consultation_id']; ?>
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewDetails(<?php echo $admission['consultation_id']; ?>)" 
                                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="../consultations/modifier.php?id=<?php echo $admission['consultation_id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportToPDF() {
            // Récupération des paramètres de filtrage
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search') || '';
            const date_debut = urlParams.get('date_debut') || '';
            const date_fin = urlParams.get('date_fin') || '';
            const medecin_id = urlParams.get('medecin_id') || '';
            
            // Construction de l'URL d'export
            const exportUrl = `export_pdf_admissions.php?search=${encodeURIComponent(search)}&date_debut=${encodeURIComponent(date_debut)}&date_fin=${encodeURIComponent(date_fin)}&medecin_id=${encodeURIComponent(medecin_id)}`;
            
            // Affichage d'un message de chargement
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Génération PDF...';
            button.disabled = true;
            
            // Téléchargement du PDF
            const link = document.createElement('a');
            link.href = exportUrl;
            link.download = `registre_admissions_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Restauration du bouton
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }

        function exportToExcel() {
            // Récupération des paramètres de filtrage
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search') || '';
            const date_debut = urlParams.get('date_debut') || '';
            const date_fin = urlParams.get('date_fin') || '';
            const medecin_id = urlParams.get('medecin_id') || '';
            
            // Construction de l'URL d'export
            const exportUrl = `export_excel_admissions.php?search=${encodeURIComponent(search)}&date_debut=${encodeURIComponent(date_debut)}&date_fin=${encodeURIComponent(date_fin)}&medecin_id=${encodeURIComponent(medecin_id)}`;
            
            // Affichage d'un message de chargement
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Génération Excel...';
            button.disabled = true;
            
            // Téléchargement du fichier Excel
            const link = document.createElement('a');
            link.href = exportUrl;
            link.download = `registre_admissions_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.xlsx`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Restauration du bouton
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }

        function viewDetails(consultationId) {
            // Redirection vers la page de détails de la consultation
            window.location.href = `../consultations/voir.php?id=${consultationId}`;
        }
    </script>
</body>
</html> 