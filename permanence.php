<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();
$message = '';

// Traitement du formulaire
if ($_POST) {
    try {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $age = $_POST['age'];
        $nationalite = $_POST['nationalite'];
        $contact = $_POST['contact'];
        $acte_id = $_POST['acte_id'];
        $montant = $_POST['montant'];
        $observations = $_POST['observations'] ?? '';
        
        $secretaire_id = $_SESSION['user_id'];
        
        $sql = "INSERT INTO permanences (nom_patient, prenom_patient, age, nationalite, contact, acte_id, montant_paye, observations, secretaire_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->query($sql, [$nom, $prenom, $age, $nationalite, $contact, $acte_id, $montant, $observations, $secretaire_id]);
        
        $message = "Permanence enregistrée avec succès !";
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Gestion des filtres de date
$date_du = $_GET['date_du'] ?? date('Y-m-01');
$date_au = $_GET['date_au'] ?? date('Y-m-d');

// Récupérer les actes disponibles
$actes = $db->fetchAll("SELECT * FROM actes_poses WHERE is_active = 1 ORDER BY nom_acte");

// Récupérer les permanences filtrées par date
$permanences = $db->fetchAll("
    SELECT p.*, a.nom_acte, u.nom as secretaire_nom 
    FROM permanences p 
    JOIN actes_poses a ON p.acte_id = a.id 
    JOIN users u ON p.secretaire_id = u.id 
    WHERE DATE(p.created_at) >= ? AND DATE(p.created_at) <= ? 
    ORDER BY p.created_at DESC
", [$date_du, $date_au]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permanence du Jour - Clinique Obstétrique</title>
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
    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="flex">
    <!-- Sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <!-- Contenu principal -->
    <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
        <!-- Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Bouton pour ouvrir la modale -->
        <div class="flex justify-end mb-2"> <!-- mb-2 au lieu de mb-6 pour réduire l'espace -->
            <button id="openModalBtn" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg font-semibold shadow hover:shadow-lg transition-all duration-300 flex items-center">
                <i class="fas fa-plus mr-2"></i> Nouvelle permanence
            </button>
        </div>

        <!-- Modale de création -->
        <div id="modalPermanence" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 hidden">
            <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg relative">
                <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 text-2xl">&times;</button>
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                    Nouvelle Permanence
                </h2>
                <form method="POST" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                            <input type="text" name="nom" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                            <input type="text" name="prenom" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Âge *</label>
                            <input type="number" name="age" required min="1" max="120" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité *</label>
                            <select id="nationalite" name="nationalite" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                                <optgroup label="Afrique">
                                    <option value="Algérie">Algérie</option>
                                    <option value="Angola">Angola</option>
                                    <option value="Bénin">Bénin</option>
                                    <option value="Botswana">Botswana</option>
                                    <option value="Burkina Faso">Burkina Faso</option>
                                    <option value="Burundi">Burundi</option>
                                    <option value="Cameroun">Cameroun</option>
                                    <option value="Cap-Vert">Cap-Vert</option>
                                    <option value="Comores">Comores</option>
                                    <option value="Congo">Congo</option>
                                    <option value="Côte d'Ivoire">Côte d'Ivoire</option>
                                    <option value="Djibouti">Djibouti</option>
                                    <option value="Égypte">Égypte</option>
                                    <option value="Érythrée">Érythrée</option>
                                    <option value="Eswatini">Eswatini</option>
                                    <option value="Éthiopie">Éthiopie</option>
                                    <option value="Gabon">Gabon</option>
                                    <option value="Gambie">Gambie</option>
                                    <option value="Ghana">Ghana</option>
                                    <option value="Guinée">Guinée</option>
                                    <option value="Guinée-Bissau">Guinée-Bissau</option>
                                    <option value="Guinée équatoriale">Guinée équatoriale</option>
                                    <option value="Kenya">Kenya</option>
                                    <option value="Lesotho">Lesotho</option>
                                    <option value="Libéria">Libéria</option>
                                    <option value="Libye">Libye</option>
                                    <option value="Madagascar">Madagascar</option>
                                    <option value="Malawi">Malawi</option>
                                    <option value="Mali">Mali</option>
                                    <option value="Maroc">Maroc</option>
                                    <option value="Maurice">Maurice</option>
                                    <option value="Mauritanie">Mauritanie</option>
                                    <option value="Mozambique">Mozambique</option>
                                    <option value="Namibie">Namibie</option>
                                    <option value="Niger">Niger</option>
                                    <option value="Nigéria">Nigéria</option>
                                    <option value="Ouganda">Ouganda</option>
                                    <option value="Rwanda">Rwanda</option>
                                    <option value="Sao Tomé-et-Principe">Sao Tomé-et-Principe</option>
                                    <option value="Sénégal">Sénégal</option>
                                    <option value="Seychelles">Seychelles</option>
                                    <option value="Sierra Leone">Sierra Leone</option>
                                    <option value="Somalie">Somalie</option>
                                    <option value="Soudan">Soudan</option>
                                    <option value="Soudan du Sud">Soudan du Sud</option>
                                    <option value="Tanzanie">Tanzanie</option>
                                    <option value="Tchad">Tchad</option>
                                    <option value="Togo">Togo</option>
                                    <option value="Tunisie">Tunisie</option>
                                    <option value="Zambie">Zambie</option>
                                    <option value="Zimbabwe">Zimbabwe</option>
                                </optgroup>
                                <optgroup label="Autres pays">
                                    <option value="France">France</option>
                                    <option value="Belgique">Belgique</option>
                                    <option value="Canada">Canada</option>
                                    <option value="États-Unis">États-Unis</option>
                                    <option value="Chine">Chine</option>
                                    <option value="Inde">Inde</option>
                                    <option value="Brésil">Brésil</option>
                                    <!-- ... autres pays ... -->
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact *</label>
                            <input type="text" name="contact" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Acte posé *</label>
                            <select name="acte_id" id="acte_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Sélectionner un acte</option>
                                <?php foreach ($actes as $acte): ?>
                                    <option value="<?= $acte['id'] ?>" data-montant="<?= $acte['montant'] ?>">
                                        <?= $acte['nom_acte'] ?> - <?= number_format($acte['montant'], 0, ',', ' ') ?> FCFA
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Montant payé (FCFA) *</label>
                            <input type="number" name="montant" id="montant" required step="100" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                        <textarea name="observations" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-4 rounded-xl text-lg font-semibold hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-save mr-3"></i>
                        Enregistrer la permanence
                    </button>
                </form>
            </div>
        </div>

        <!-- Filtres de date -->
        <form method="get" class="flex flex-col md:flex-row md:items-center gap-4 mb-6">
            <div>
                <label for="date_du" class="block text-xs font-medium text-gray-600 mb-1">Du</label>
                <input type="date" id="date_du" name="date_du" value="<?= htmlspecialchars($date_du) ?>" class="px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label for="date_au" class="block text-xs font-medium text-gray-600 mb-1">Au</label>
                <input type="date" id="date_au" name="date_au" value="<?= htmlspecialchars($date_au) ?>" class="px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="self-end">
                <button type="submit" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg font-semibold shadow hover:shadow-lg transition-all duration-300">
                    Filtrer
                </button>
            </div>
        </form>

        <!-- Recherche et tableau paginé -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-list text-purple-600 mr-3"></i>
                    Permanences du Jour
                </h2>
                <input type="text" id="searchInput" placeholder="Rechercher par nom, prénom (ex: sarah toure), acte, contact..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent w-full md:w-80">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="permanenceTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prénom</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Âge</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nationalité</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acte</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="permanenceTableBody">
                        <?php foreach ($permanences as $permanence): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= htmlspecialchars($permanence['nom_patient']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= htmlspecialchars($permanence['prenom_patient']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['age']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['nationalite']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['nom_acte']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= number_format($permanence['montant_paye'], 0, ',', ' ') ?> FCFA</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['contact']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= date('H:i', strtotime($permanence['created_at'])) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-xs">
                                    <?php if ($permanence['statut_final'] === 'ok'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-green-100 text-green-800">Validé</span>
                                    <?php elseif ($permanence['statut_final'] === 'annule'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-red-100 text-red-800">Annulé</span>
                                    <?php else: ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-yellow-100 text-yellow-800">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm">
                                    <?php if ($permanence['statut_final'] === 'en_attente' || $permanence['statut_final'] === null): ?>
                                        <a href="permanence_edit.php?id=<?= $permanence['id'] ?>" class="text-blue-600 hover:text-blue-900 text-xs font-semibold"><i class="fas fa-edit mr-1"></i>Modifier</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-4">
                <nav id="paginationNav" class="inline-flex"></nav>
            </div>
        </div>
    </main>
</div>

    <script>
        // Modale ouverture/fermeture
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const modalPermanence = document.getElementById('modalPermanence');
        openModalBtn.addEventListener('click', () => modalPermanence.classList.remove('hidden'));
        closeModalBtn.addEventListener('click', () => modalPermanence.classList.add('hidden'));
        window.addEventListener('click', (e) => { if (e.target === modalPermanence) modalPermanence.classList.add('hidden'); });

        // Pagination et recherche JS
        const rows = Array.from(document.querySelectorAll('#permanenceTableBody tr'));
        const rowsPerPage = 10;
        let currentPage = 1;
        function renderTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            let filteredRows = rows.filter(row => {
                const rowText = row.textContent.toLowerCase();
                const searchTerms = search.split(' ').filter(term => term.length > 0);
                
                // Si la recherche contient plusieurs mots (ex: "sarah toure")
                if (searchTerms.length > 1) {
                    // Vérifier si tous les termes sont présents dans la ligne
                    return searchTerms.every(term => rowText.includes(term));
                } else {
                    // Recherche simple
                    return rowText.includes(search);
                }
            });
            rows.forEach(row => row.style.display = 'none');
            filteredRows.forEach((row, i) => {
                row.style.display = (i >= (currentPage-1)*rowsPerPage && i < currentPage*rowsPerPage) ? '' : 'none';
            });
            renderPagination(filteredRows.length);
        }
        function renderPagination(totalRows) {
            const nav = document.getElementById('paginationNav');
            nav.innerHTML = '';
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            for (let i=1; i<=totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = 'mx-1 px-3 py-1 rounded border ' + (i===currentPage ? 'bg-purple-500 text-white' : 'bg-white text-purple-600 border-purple-300');
                btn.onclick = () => { currentPage = i; renderTable(); };
                nav.appendChild(btn);
            }
        }
        document.getElementById('searchInput').addEventListener('input', () => { currentPage=1; renderTable(); });
        renderTable();
    </script>
    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect("#nationalite", {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                }
            });
        });
        // Auto-remplir le montant selon l'acte sélectionné
        const acteSelect = document.getElementById('acte_id');
        if (acteSelect) {
            acteSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const montant = selectedOption.getAttribute('data-montant');
                if (montant) {
                    document.getElementById('montant').value = montant;
                }
            });
        }
    </script>
</body>
</html> 