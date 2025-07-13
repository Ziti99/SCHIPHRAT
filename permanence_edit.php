<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAuth();
if ($auth->getCurrentUserRole() !== 'secretaire') {
    header('Location: /dashboard.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: permanence.php');
    exit;
}

// Récupérer la permanence
$permanence = $db->fetch('SELECT * FROM permanences WHERE id = ? AND statut = "en_attente" AND DATE(created_at) = CURDATE()', [$id]);
if (!$permanence) {
    $message = "Modification impossible : cette permanence n'est pas modifiable.";
}

// Récupérer les actes
$actes = $db->fetchAll("SELECT * FROM actes_poses WHERE is_active = 1 ORDER BY nom_acte");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $permanence) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $age = $_POST['age'];
    $nationalite = $_POST['nationalite'];
    $contact = $_POST['contact'];
    $acte_id = $_POST['acte_id'];
    $montant = $_POST['montant'];
    $observations = $_POST['observations'] ?? '';
    $db->query('UPDATE permanences SET nom_patient=?, prenom_patient=?, age=?, nationalite=?, contact=?, acte_id=?, montant_paye=?, observations=? WHERE id=?',
        [$nom, $prenom, $age, $nationalite, $contact, $acte_id, $montant, $observations, $id]);
    header('Location: permanence.php?message=Modification+réussie');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la permanence - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-heartbeat text-2xl text-purple-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Clinique Obstétrique</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($auth->getUser()['nom'] . ' ' . $auth->getUser()['prenom']); ?>
                        <span class="text-sm text-gray-500">(<?php echo htmlspecialchars($auth->getUser()['role']); ?>)</span>
                    </span>
                    <a href="logout.php" class="text-red-600 hover:text-red-800 transition-colors duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="max-w-xl mx-auto mt-12 bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-2xl font-bold mb-6 text-purple-700 flex items-center"><i class="fas fa-edit mr-3"></i>Modifier la permanence</h1>
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?= $message ?></div>
            <a href="permanence.php" class="text-purple-600 hover:underline">Retour</a>
        <?php elseif ($permanence): ?>
        <form method="POST" class="space-y-6">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($permanence['nom_patient']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($permanence['prenom_patient']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Âge *</label>
                    <input type="number" name="age" value="<?= htmlspecialchars($permanence['age']) ?>" required min="1" max="120" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nationalité *</label>
                    <select id="nationalite" name="nationalite" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <optgroup label="Afrique">
                            <option value="Algérie" <?= $permanence['nationalite']==='Algérie'?'selected':'' ?>>Algérie</option>
                            <option value="Angola" <?= $permanence['nationalite']==='Angola'?'selected':'' ?>>Angola</option>
                            <option value="Bénin" <?= $permanence['nationalite']==='Bénin'?'selected':'' ?>>Bénin</option>
                            <option value="Botswana" <?= $permanence['nationalite']==='Botswana'?'selected':'' ?>>Botswana</option>
                            <option value="Burkina Faso" <?= $permanence['nationalite']==='Burkina Faso'?'selected':'' ?>>Burkina Faso</option>
                            <option value="Burundi" <?= $permanence['nationalite']==='Burundi'?'selected':'' ?>>Burundi</option>
                            <option value="Cameroun" <?= $permanence['nationalite']==='Cameroun'?'selected':'' ?>>Cameroun</option>
                            <option value="Cap-Vert" <?= $permanence['nationalite']==='Cap-Vert'?'selected':'' ?>>Cap-Vert</option>
                            <option value="Comores" <?= $permanence['nationalite']==='Comores'?'selected':'' ?>>Comores</option>
                            <option value="Congo" <?= $permanence['nationalite']==='Congo'?'selected':'' ?>>Congo</option>
                            <option value="Côte d'Ivoire" <?= $permanence['nationalite']==="Côte d'Ivoire"?'selected':'' ?>>Côte d'Ivoire</option>
                            <option value="Djibouti" <?= $permanence['nationalite']==='Djibouti'?'selected':'' ?>>Djibouti</option>
                            <option value="Égypte" <?= $permanence['nationalite']==='Égypte'?'selected':'' ?>>Égypte</option>
                            <option value="Érythrée" <?= $permanence['nationalite']==='Érythrée'?'selected':'' ?>>Érythrée</option>
                            <option value="Eswatini" <?= $permanence['nationalite']==='Eswatini'?'selected':'' ?>>Eswatini</option>
                            <option value="Éthiopie" <?= $permanence['nationalite']==='Éthiopie'?'selected':'' ?>>Éthiopie</option>
                            <option value="Gabon" <?= $permanence['nationalite']==='Gabon'?'selected':'' ?>>Gabon</option>
                            <option value="Gambie" <?= $permanence['nationalite']==='Gambie'?'selected':'' ?>>Gambie</option>
                            <option value="Ghana" <?= $permanence['nationalite']==='Ghana'?'selected':'' ?>>Ghana</option>
                            <option value="Guinée" <?= $permanence['nationalite']==='Guinée'?'selected':'' ?>>Guinée</option>
                            <option value="Guinée-Bissau" <?= $permanence['nationalite']==='Guinée-Bissau'?'selected':'' ?>>Guinée-Bissau</option>
                            <option value="Guinée équatoriale" <?= $permanence['nationalite']==='Guinée équatoriale'?'selected':'' ?>>Guinée équatoriale</option>
                            <option value="Kenya" <?= $permanence['nationalite']==='Kenya'?'selected':'' ?>>Kenya</option>
                            <option value="Lesotho" <?= $permanence['nationalite']==='Lesotho'?'selected':'' ?>>Lesotho</option>
                            <option value="Libéria" <?= $permanence['nationalite']==='Libéria'?'selected':'' ?>>Libéria</option>
                            <option value="Libye" <?= $permanence['nationalite']==='Libye'?'selected':'' ?>>Libye</option>
                            <option value="Madagascar" <?= $permanence['nationalite']==='Madagascar'?'selected':'' ?>>Madagascar</option>
                            <option value="Malawi" <?= $permanence['nationalite']==='Malawi'?'selected':'' ?>>Malawi</option>
                            <option value="Mali" <?= $permanence['nationalite']==='Mali'?'selected':'' ?>>Mali</option>
                            <option value="Maroc" <?= $permanence['nationalite']==='Maroc'?'selected':'' ?>>Maroc</option>
                            <option value="Maurice" <?= $permanence['nationalite']==='Maurice'?'selected':'' ?>>Maurice</option>
                            <option value="Mauritanie" <?= $permanence['nationalite']==='Mauritanie'?'selected':'' ?>>Mauritanie</option>
                            <option value="Mozambique" <?= $permanence['nationalite']==='Mozambique'?'selected':'' ?>>Mozambique</option>
                            <option value="Namibie" <?= $permanence['nationalite']==='Namibie'?'selected':'' ?>>Namibie</option>
                            <option value="Niger" <?= $permanence['nationalite']==='Niger'?'selected':'' ?>>Niger</option>
                            <option value="Nigéria" <?= $permanence['nationalite']==='Nigéria'?'selected':'' ?>>Nigéria</option>
                            <option value="Ouganda" <?= $permanence['nationalite']==='Ouganda'?'selected':'' ?>>Ouganda</option>
                            <option value="Rwanda" <?= $permanence['nationalite']==='Rwanda'?'selected':'' ?>>Rwanda</option>
                            <option value="Sao Tomé-et-Principe" <?= $permanence['nationalite']==='Sao Tomé-et-Principe'?'selected':'' ?>>Sao Tomé-et-Principe</option>
                            <option value="Sénégal" <?= $permanence['nationalite']==='Sénégal'?'selected':'' ?>>Sénégal</option>
                            <option value="Seychelles" <?= $permanence['nationalite']==='Seychelles'?'selected':'' ?>>Seychelles</option>
                            <option value="Sierra Leone" <?= $permanence['nationalite']==='Sierra Leone'?'selected':'' ?>>Sierra Leone</option>
                            <option value="Somalie" <?= $permanence['nationalite']==='Somalie'?'selected':'' ?>>Somalie</option>
                            <option value="Soudan" <?= $permanence['nationalite']==='Soudan'?'selected':'' ?>>Soudan</option>
                            <option value="Soudan du Sud" <?= $permanence['nationalite']==='Soudan du Sud'?'selected':'' ?>>Soudan du Sud</option>
                            <option value="Tanzanie" <?= $permanence['nationalite']==='Tanzanie'?'selected':'' ?>>Tanzanie</option>
                            <option value="Tchad" <?= $permanence['nationalite']==='Tchad'?'selected':'' ?>>Tchad</option>
                            <option value="Togo" <?= $permanence['nationalite']==='Togo'?'selected':'' ?>>Togo</option>
                            <option value="Tunisie" <?= $permanence['nationalite']==='Tunisie'?'selected':'' ?>>Tunisie</option>
                            <option value="Zambie" <?= $permanence['nationalite']==='Zambie'?'selected':'' ?>>Zambie</option>
                            <option value="Zimbabwe" <?= $permanence['nationalite']==='Zimbabwe'?'selected':'' ?>>Zimbabwe</option>
                        </optgroup>
                        <optgroup label="Autres pays">
                            <option value="France" <?= $permanence['nationalite']==='France'?'selected':'' ?>>France</option>
                            <option value="Belgique" <?= $permanence['nationalite']==='Belgique'?'selected':'' ?>>Belgique</option>
                            <option value="Canada" <?= $permanence['nationalite']==='Canada'?'selected':'' ?>>Canada</option>
                            <option value="États-Unis" <?= $permanence['nationalite']==='États-Unis'?'selected':'' ?>>États-Unis</option>
                            <option value="Chine" <?= $permanence['nationalite']==='Chine'?'selected':'' ?>>Chine</option>
                            <option value="Inde" <?= $permanence['nationalite']==='Inde'?'selected':'' ?>>Inde</option>
                            <option value="Brésil" <?= $permanence['nationalite']==='Brésil'?'selected':'' ?>>Brésil</option>
                            <!-- ... autres pays ... -->
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact *</label>
                    <input type="text" name="contact" value="<?= htmlspecialchars($permanence['contact']) ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Acte posé *</label>
                    <select name="acte_id" id="acte_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="">Sélectionner un acte</option>
                        <?php foreach ($actes as $acte): ?>
                            <option value="<?= $acte['id'] ?>" data-montant="<?= $acte['montant'] ?>" <?= $permanence['acte_id']==$acte['id']?'selected':'' ?>>
                                <?= $acte['nom_acte'] ?> - <?= number_format($acte['montant'], 0, ',', ' ') ?> FCFA
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Montant payé (FCFA) *</label>
                    <input type="number" name="montant" id="montant" value="<?= htmlspecialchars($permanence['montant_paye']) ?>" required step="100" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                <textarea name="observations" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg"><?= htmlspecialchars($permanence['observations']) ?></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-4 rounded-xl text-lg font-semibold hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-save mr-3"></i>
                Enregistrer les modifications
            </button>
            <a href="permanence.php" class="block text-center mt-4 text-purple-600 hover:underline">Annuler</a>
        </form>
        <?php endif; ?>
    </div>
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