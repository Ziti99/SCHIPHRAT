<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAuth();

// Vérifier que l'utilisateur est admin
if ($auth->getCurrentUserRole() !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

$db = Database::getInstance();
$message = '';

// Traitement des actions
if ($_POST) {
    try {
        $permanence_id = $_POST['permanence_id'];
        $action = $_POST['action']; // 'valider' ou 'rejeter'
        $admin_id = $auth->getCurrentUserId();
        
        $statut = ($action === 'valider') ? 'valide' : 'rejete';
        $statut_final = ($action === 'valider') ? 'ok' : 'annule';
        
        $sql = "UPDATE permanences SET statut = ?, statut_final = ?, admin_id = ?, date_validation = NOW() WHERE id = ?";
        $db->query($sql, [$statut, $statut_final, $admin_id, $permanence_id]);
        
        $message = "Permanence " . ($action === 'valider' ? 'validée' : 'rejetée') . " avec succès !";
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les permanences en attente
$permanences_attente = $db->fetchAll("
    SELECT p.*, a.nom_acte, u.nom as secretaire_nom, u.prenom as secretaire_prenom
    FROM permanences p 
    JOIN actes_poses a ON p.acte_id = a.id 
    JOIN users u ON p.secretaire_id = u.id 
    WHERE p.statut = 'en_attente'
    ORDER BY p.created_at DESC
");

// Supprimer la section affichant les permanences validées aujourd'hui

// Statistiques
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total_aujourd_hui,
        SUM(montant_paye) as total_montant,
        COUNT(CASE WHEN statut = 'valide' THEN 1 END) as validees,
        COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as en_attente
    FROM permanences 
    WHERE DATE(created_at) = CURDATE()
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation Permanences - Admin</title>
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
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
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
                        <p class="text-xs text-gray-500">Validation permanence</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($auth->getCurrentUserName()); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $auth->getCurrentUserRole()); ?></p>
                    </div>
                    <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="flex">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Validation permanence</h2>
            <!-- Message -->
            <?php if ($message): ?>
                <div class="mb-6 flex items-center gap-3 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-500"></i>
                    <span class="text-lg font-semibold"> <?= $message ?> </span>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Aujourd'hui</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['total_aujourd_hui'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Validées</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['validees'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">En Attente</p>
                            <p class="text-2xl font-bold text-gray-900"><?= $stats['en_attente'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Montant</p>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_montant'] ?? 0, 0, ',', ' ') ?> FCFA</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recherche et filtres -->
            <div class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
                <input type="text" id="searchInput" placeholder="Rechercher par nom, acte, contact..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent w-full md:w-80">
                <select id="statutFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tous statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="valide">Validées</option>
                    <option value="rejete">Rejetées</option>
                </select>
            </div>
            <!-- Grille de cartes paginée -->
            <div id="permanenceGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($permanences_attente as $permanence): ?>
                    <div class="border border-yellow-200 rounded-lg p-4 bg-yellow-50 flex flex-col justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 text-lg mb-1">
                                <?= htmlspecialchars($permanence['nom_patient']) ?> <?= htmlspecialchars($permanence['prenom_patient']) ?>
                            </h3>
                            <p class="text-xs text-gray-600 mb-1">
                                <?= htmlspecialchars($permanence['age']) ?> ans - <?= htmlspecialchars($permanence['nationalite']) ?><br>
                                Saisi par : <?= htmlspecialchars($permanence['secretaire_nom'] . ' ' . $permanence['secretaire_prenom']) ?>
                            </p>
                            <p class="text-sm text-gray-700 mb-1"><b>Acte :</b> <?= htmlspecialchars($permanence['nom_acte']) ?></p>
                            <p class="text-sm text-gray-700 mb-1"><b>Montant :</b> <?= number_format($permanence['montant_paye'], 0, ',', ' ') ?> FCFA</p>
                            <p class="text-sm text-gray-700 mb-1"><b>Contact :</b> <?= htmlspecialchars($permanence['contact']) ?></p>
                            <p class="text-sm text-gray-700 mb-1"><b>Heure :</b> <?= date('H:i', strtotime($permanence['created_at'])) ?></p>
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <form method="POST" class="flex gap-2 w-full">
                                <input type="hidden" name="permanence_id" value="<?= $permanence['id'] ?>">
                                <button name="action" value="valider" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg font-semibold flex items-center justify-center gap-2"><i class="fas fa-check"></i> Valider</button>
                                <button name="action" value="rejeter" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg font-semibold flex items-center justify-center gap-2"><i class="fas fa-times"></i> Rejeter</button>
                            </form>
                            <span class="inline-block px-3 py-1 rounded-full font-semibold bg-yellow-100 text-yellow-800 text-xs ml-2">En attente</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end mt-4">
                <nav id="paginationNav" class="inline-flex"></nav>
            </div>
            <script>
            // Pagination et recherche JS
            const cards = Array.from(document.querySelectorAll('#permanenceGrid > div'));
            const rowsPerPage = 20;
            let currentPage = 1;
            function renderGrid() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const statut = document.getElementById('statutFilter').value;
                let filtered = cards.filter(card => {
                    let match = card.textContent.toLowerCase().includes(search);
                    if (statut && !card.innerHTML.includes(statut)) return false;
                    return match;
                });
                cards.forEach(card => card.style.display = 'none');
                filtered.forEach((card, i) => {
                    card.style.display = (i >= (currentPage-1)*rowsPerPage && i < currentPage*rowsPerPage) ? '' : 'none';
                });
                renderPagination(filtered.length);
            }
            function renderPagination(totalRows) {
                const nav = document.getElementById('paginationNav');
                nav.innerHTML = '';
                const totalPages = Math.ceil(totalRows / rowsPerPage);
                for (let i=1; i<=totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.textContent = i;
                    btn.className = 'mx-1 px-3 py-1 rounded border ' + (i===currentPage ? 'bg-purple-500 text-white' : 'bg-white text-purple-600 border-purple-300');
                    btn.onclick = () => { currentPage = i; renderGrid(); };
                    nav.appendChild(btn);
                }
            }
            document.getElementById('searchInput').addEventListener('input', () => { currentPage=1; renderGrid(); });
            document.getElementById('statutFilter').addEventListener('change', () => { currentPage=1; renderGrid(); });
            renderGrid();
            </script>
        </main>
    </div>
</body>
</html> 