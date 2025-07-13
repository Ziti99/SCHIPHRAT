<?php
// Démarrer la session en premier, avant tout autre code
session_start();

// Point d'entrée public pour la création de patientes
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Récupérer les infos utilisateur depuis la session
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? '',
];

$db = new Database();

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $date_naissance = $_POST['date_naissance'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $nationalite = $_POST['nationalite'] ?? '';
        $antecedents_medicaux = $_POST['antecedents_medicaux'] ?? '';
        
        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($telephone)) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } else {
            // Calculer l'âge
            $age = date_diff(date_create($date_naissance), date_create('today'))->y;
            
            // Insérer la patiente
            $sql = "INSERT INTO patientes (nom, prenom, date_naissance, age, adresse, telephone, nationalite, antecedents_medicaux) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $db->query($sql, [
                $nom,
                $prenom,
                $date_naissance,
                $age,
                $adresse,
                $telephone,
                $nationalite,
                $antecedents_medicaux
            ]);
            
            $message = "Patiente créée avec succès !";
            
            // Rediriger vers la liste des patientes
            header('Location: patientes.php?message=' . urlencode($message));
            exit;
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la création : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Patiente - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
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
                                <i class="fas fa-user-plus text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Nouvelle Patiente</span>
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

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages -->
                <?php if ($error): ?>
                    <div id="message-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        <button onclick="closeMessage('message-error')" class="absolute top-0 right-0 mt-2 mr-2 text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouvelle Patiente</h1>
                    <p class="text-gray-600">Ajoutez une nouvelle patiente au système</p>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Informations de base -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="nom" name="nom" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700 mb-2">
                                    Prénom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="prenom" name="prenom" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="date_naissance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de naissance <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="date_naissance" name="date_naissance" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Téléphone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="telephone" name="telephone" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nationalite" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nationalité
                                </label>
                                <select id="nationalite" name="nationalite"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner une nationalité</option>
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
                                        <option value="Allemagne">Allemagne</option>
                                        <option value="Italie">Italie</option>
                                        <option value="Espagne">Espagne</option>
                                        <option value="Portugal">Portugal</option>
                                        <option value="Suisse">Suisse</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Pays-Bas">Pays-Bas</option>
                                        <option value="Royaume-Uni">Royaume-Uni</option>
                                        <option value="Japon">Japon</option>
                                        <option value="Corée du Sud">Corée du Sud</option>
                                        <option value="Australie">Australie</option>
                                        <option value="Nouvelle-Zélande">Nouvelle-Zélande</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse
                            </label>
                            <textarea id="adresse" name="adresse" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                      placeholder="Adresse de la patiente (optionnel)"></textarea>
                        </div>

                        <!-- Informations médicales -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations médicales</h3>
                            
                            <div>
                                <label for="antecedents_medicaux" class="block text-sm font-medium text-gray-700 mb-2">
                                    Antécédents médicaux
                                </label>
                                <textarea id="antecedents_medicaux" name="antecedents_medicaux" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                          placeholder="Décrivez les antécédents médicaux de la patiente..."></textarea>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="patientes.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
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
        
        // Fonction pour fermer les messages
        function closeMessage(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html> 