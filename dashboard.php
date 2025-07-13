<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Configuration de la base de données
$host = 'metro.proxy.rlwy.net';
$port = '29698';
$dbname = 'railway';
$username = 'root';
$password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des statistiques
$stats = [
    'total_patientes' => $pdo->query("SELECT COUNT(*) FROM patientes")->fetchColumn(),
    'consultations_ce_mois' => $pdo->query("SELECT COUNT(*) FROM consultations_prenatales WHERE MONTH(date_consultation) = MONTH(CURDATE()) AND YEAR(date_consultation) = YEAR(CURDATE())")->fetchColumn(),
    'accouchements_ce_mois' => $pdo->query("SELECT COUNT(*) FROM accouchements WHERE MONTH(date_accouchement) = MONTH(CURDATE()) AND YEAR(date_accouchement) = YEAR(CURDATE())")->fetchColumn()
];

// Récupération des dernières activités
$recent_consultations = $pdo->query("
    SELECT cp.*, p.nom, p.prenom, u.nom as medecin_nom, u.prenom as medecin_prenom
    FROM consultations_prenatales cp
    JOIN patientes p ON cp.patiente_id = p.id
    JOIN users u ON cp.medecin_id = u.id
    ORDER BY cp.date_consultation DESC
    LIMIT 5
")->fetchAll();

$recent_accouchements = $pdo->query("
    SELECT a.*, p.nom, p.prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    ORDER BY a.date_accouchement DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#EC4899',
                        accent: '#06B6D4'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="text-xs text-gray-500">Dashboard</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $_SESSION['user_role']); ?></p>
                    </div>
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user text-sm"></i>
                        </button>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                            <a href="/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i>Profil
                            </a>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Tableau de bord</h2>
                <p class="text-gray-600">Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Total Patientes</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_patientes']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-injured text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-100">Consultations ce mois</p>
                            <p class="text-3xl font-bold"><?php echo $stats['consultations_ce_mois']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Accouchements ce mois</p>
                            <p class="text-3xl font-bold"><?php echo $stats['accouchements_ce_mois']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-plus-circle text-purple-500 mr-2"></i>
                        Actions rapides
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="/patientes/ajouter.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4 rounded-lg text-center hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-user-plus text-2xl mb-2"></i>
                            <p class="font-semibold">Nouvelle patiente</p>
                        </a>
                        <a href="/consultations/ajouter.php" class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white p-4 rounded-lg text-center hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-calendar-plus text-2xl mb-2"></i>
                            <p class="font-semibold">Nouvelle consultation</p>
                        </a>
                        <a href="/accouchements/ajouter.php" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-4 rounded-lg text-center hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-baby text-2xl mb-2"></i>
                            <p class="font-semibold">Nouvel accouchement</p>
                        </a>
                        <a href="/statistiques.php" class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-4 rounded-lg text-center hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <p class="font-semibold">Voir statistiques</p>
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-clock text-purple-500 mr-2"></i>
                        Activités récentes
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar-check text-purple-600 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Consultations récentes</p>
                                <p class="text-xs text-gray-500"><?php echo count($recent_consultations); ?> consultations ce mois</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-baby text-green-600 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Accouchements récents</p>
                                <p class="text-xs text-gray-500"><?php echo count($recent_accouchements); ?> accouchements ce mois</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières activités -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Dernières consultations -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-calendar-check text-purple-500 mr-2"></i>
                        Dernières consultations
                    </h3>
                    <div class="space-y-4">
                        <?php if (empty($recent_consultations)): ?>
                            <p class="text-gray-500 text-center py-4">Aucune consultation récente</p>
                        <?php else: ?>
                            <?php foreach ($recent_consultations as $consultation): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-md text-purple-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($consultation['nom'] . ' ' . $consultation['prenom']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Dr. <?php echo htmlspecialchars($consultation['medecin_nom'] . ' ' . $consultation['medecin_prenom']); ?> - 
                                            <?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Derniers accouchements -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-baby text-green-500 mr-2"></i>
                        Derniers accouchements
                    </h3>
                    <div class="space-y-4">
                        <?php if (empty($recent_accouchements)): ?>
                            <p class="text-gray-500 text-center py-4">Aucun accouchement récent</p>
                        <?php else: ?>
                            <?php foreach ($recent_accouchements as $accouchement): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-baby text-green-600"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($accouchement['nom'] . ' ' . $accouchement['prenom']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo ucfirst(str_replace('_', ' ', $accouchement['mode_accouchement'])); ?> - 
                                            <?php echo date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }

        // Fermer le menu en cliquant ailleurs
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('userMenu');
            const button = event.target.closest('button');
            
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 