<?php
// Démarrer la session en premier, avant tout autre code
session_start();

// Point d'entrée public pour la modification de patientes
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

$message = '';
$error = '';

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
} catch (Exception $e) {
    header('Location: patientes.php');
    exit();
}

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
            
            // Mettre à jour la patiente
            $sql = "UPDATE patientes SET nom = ?, prenom = ?, date_naissance = ?, age = ?, adresse = ?, telephone = ?, nationalite = ?, antecedents_medicaux = ? WHERE id = ?";
            
            $db->query($sql, [
                $nom,
                $prenom,
                $date_naissance,
                $age,
                $adresse,
                $telephone,
                $nationalite,
                $antecedents_medicaux,
                $id
            ]);
            
            $message = "Patiente modifiée avec succès !";
            
            // Rediriger vers la liste des patientes
            header('Location: patientes.php?message=' . urlencode($message));
            exit();
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Patiente - Clinique Obstétrique</title>
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
                                <i class="fas fa-user-edit text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Modifier Patiente</span>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier Patiente</h1>
                    <p class="text-gray-600">Modifiez les informations de la patiente</p>
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
                                       value="<?php echo htmlspecialchars($patiente['nom']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="prenom" class="block text-sm font-medium text-gray-700 mb-2">
                                    Prénom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="prenom" name="prenom" required
                                       value="<?php echo htmlspecialchars($patiente['prenom']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="date_naissance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de naissance <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="date_naissance" name="date_naissance" required
                                       value="<?php echo htmlspecialchars($patiente['date_naissance']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Téléphone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="telephone" name="telephone" required
                                       value="<?php echo htmlspecialchars($patiente['telephone']); ?>"
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
                                        <option value="Algérie" <?php echo ($patiente['nationalite'] == 'Algérie') ? 'selected' : ''; ?>>Algérie</option>
                                        <option value="Angola" <?php echo ($patiente['nationalite'] == 'Angola') ? 'selected' : ''; ?>>Angola</option>
                                        <option value="Bénin" <?php echo ($patiente['nationalite'] == 'Bénin') ? 'selected' : ''; ?>>Bénin</option>
                                        <option value="Botswana" <?php echo ($patiente['nationalite'] == 'Botswana') ? 'selected' : ''; ?>>Botswana</option>
                                        <option value="Burkina Faso" <?php echo ($patiente['nationalite'] == 'Burkina Faso') ? 'selected' : ''; ?>>Burkina Faso</option>
                                        <option value="Burundi" <?php echo ($patiente['nationalite'] == 'Burundi') ? 'selected' : ''; ?>>Burundi</option>
                                        <option value="Cameroun" <?php echo ($patiente['nationalite'] == 'Cameroun') ? 'selected' : ''; ?>>Cameroun</option>
                                        <option value="Cap-Vert" <?php echo ($patiente['nationalite'] == 'Cap-Vert') ? 'selected' : ''; ?>>Cap-Vert</option>
                                        <option value="Comores" <?php echo ($patiente['nationalite'] == 'Comores') ? 'selected' : ''; ?>>Comores</option>
                                        <option value="Congo" <?php echo ($patiente['nationalite'] == 'Congo') ? 'selected' : ''; ?>>Congo</option>
                                        <option value="Côte d'Ivoire" <?php echo ($patiente['nationalite'] == 'Côte d\'Ivoire') ? 'selected' : ''; ?>>Côte d'Ivoire</option>
                                        <option value="Djibouti" <?php echo ($patiente['nationalite'] == 'Djibouti') ? 'selected' : ''; ?>>Djibouti</option>
                                        <option value="Égypte" <?php echo ($patiente['nationalite'] == 'Égypte') ? 'selected' : ''; ?>>Égypte</option>
                                        <option value="Érythrée" <?php echo ($patiente['nationalite'] == 'Érythrée') ? 'selected' : ''; ?>>Érythrée</option>
                                        <option value="Eswatini" <?php echo ($patiente['nationalite'] == 'Eswatini') ? 'selected' : ''; ?>>Eswatini</option>
                                        <option value="Éthiopie" <?php echo ($patiente['nationalite'] == 'Éthiopie') ? 'selected' : ''; ?>>Éthiopie</option>
                                        <option value="Gabon" <?php echo ($patiente['nationalite'] == 'Gabon') ? 'selected' : ''; ?>>Gabon</option>
                                        <option value="Gambie" <?php echo ($patiente['nationalite'] == 'Gambie') ? 'selected' : ''; ?>>Gambie</option>
                                        <option value="Ghana" <?php echo ($patiente['nationalite'] == 'Ghana') ? 'selected' : ''; ?>>Ghana</option>
                                        <option value="Guinée" <?php echo ($patiente['nationalite'] == 'Guinée') ? 'selected' : ''; ?>>Guinée</option>
                                        <option value="Guinée-Bissau" <?php echo ($patiente['nationalite'] == 'Guinée-Bissau') ? 'selected' : ''; ?>>Guinée-Bissau</option>
                                        <option value="Guinée équatoriale" <?php echo ($patiente['nationalite'] == 'Guinée équatoriale') ? 'selected' : ''; ?>>Guinée équatoriale</option>
                                        <option value="Kenya" <?php echo ($patiente['nationalite'] == 'Kenya') ? 'selected' : ''; ?>>Kenya</option>
                                        <option value="Lesotho" <?php echo ($patiente['nationalite'] == 'Lesotho') ? 'selected' : ''; ?>>Lesotho</option>
                                        <option value="Libéria" <?php echo ($patiente['nationalite'] == 'Libéria') ? 'selected' : ''; ?>>Libéria</option>
                                        <option value="Libye" <?php echo ($patiente['nationalite'] == 'Libye') ? 'selected' : ''; ?>>Libye</option>
                                        <option value="Madagascar" <?php echo ($patiente['nationalite'] == 'Madagascar') ? 'selected' : ''; ?>>Madagascar</option>
                                        <option value="Malawi" <?php echo ($patiente['nationalite'] == 'Malawi') ? 'selected' : ''; ?>>Malawi</option>
                                        <option value="Mali" <?php echo ($patiente['nationalite'] == 'Mali') ? 'selected' : ''; ?>>Mali</option>
                                        <option value="Maroc" <?php echo ($patiente['nationalite'] == 'Maroc') ? 'selected' : ''; ?>>Maroc</option>
                                        <option value="Maurice" <?php echo ($patiente['nationalite'] == 'Maurice') ? 'selected' : ''; ?>>Maurice</option>
                                        <option value="Mauritanie" <?php echo ($patiente['nationalite'] == 'Mauritanie') ? 'selected' : ''; ?>>Mauritanie</option>
                                        <option value="Mozambique" <?php echo ($patiente['nationalite'] == 'Mozambique') ? 'selected' : ''; ?>>Mozambique</option>
                                        <option value="Namibie" <?php echo ($patiente['nationalite'] == 'Namibie') ? 'selected' : ''; ?>>Namibie</option>
                                        <option value="Niger" <?php echo ($patiente['nationalite'] == 'Niger') ? 'selected' : ''; ?>>Niger</option>
                                        <option value="Nigéria" <?php echo ($patiente['nationalite'] == 'Nigéria') ? 'selected' : ''; ?>>Nigéria</option>
                                        <option value="Ouganda" <?php echo ($patiente['nationalite'] == 'Ouganda') ? 'selected' : ''; ?>>Ouganda</option>
                                        <option value="Rwanda" <?php echo ($patiente['nationalite'] == 'Rwanda') ? 'selected' : ''; ?>>Rwanda</option>
                                        <option value="Sao Tomé-et-Principe" <?php echo ($patiente['nationalite'] == 'Sao Tomé-et-Principe') ? 'selected' : ''; ?>>Sao Tomé-et-Principe</option>
                                        <option value="Sénégal" <?php echo ($patiente['nationalite'] == 'Sénégal') ? 'selected' : ''; ?>>Sénégal</option>
                                        <option value="Seychelles" <?php echo ($patiente['nationalite'] == 'Seychelles') ? 'selected' : ''; ?>>Seychelles</option>
                                        <option value="Sierra Leone" <?php echo ($patiente['nationalite'] == 'Sierra Leone') ? 'selected' : ''; ?>>Sierra Leone</option>
                                        <option value="Somalie" <?php echo ($patiente['nationalite'] == 'Somalie') ? 'selected' : ''; ?>>Somalie</option>
                                        <option value="Soudan" <?php echo ($patiente['nationalite'] == 'Soudan') ? 'selected' : ''; ?>>Soudan</option>
                                        <option value="Soudan du Sud" <?php echo ($patiente['nationalite'] == 'Soudan du Sud') ? 'selected' : ''; ?>>Soudan du Sud</option>
                                        <option value="Tanzanie" <?php echo ($patiente['nationalite'] == 'Tanzanie') ? 'selected' : ''; ?>>Tanzanie</option>
                                        <option value="Tchad" <?php echo ($patiente['nationalite'] == 'Tchad') ? 'selected' : ''; ?>>Tchad</option>
                                        <option value="Togo" <?php echo ($patiente['nationalite'] == 'Togo') ? 'selected' : ''; ?>>Togo</option>
                                        <option value="Tunisie" <?php echo ($patiente['nationalite'] == 'Tunisie') ? 'selected' : ''; ?>>Tunisie</option>
                                        <option value="Zambie" <?php echo ($patiente['nationalite'] == 'Zambie') ? 'selected' : ''; ?>>Zambie</option>
                                        <option value="Zimbabwe" <?php echo ($patiente['nationalite'] == 'Zimbabwe') ? 'selected' : ''; ?>>Zimbabwe</option>
                                    </optgroup>
                                    <optgroup label="Autres pays">
                                        <option value="France" <?php echo ($patiente['nationalite'] == 'France') ? 'selected' : ''; ?>>France</option>
                                        <option value="Belgique" <?php echo ($patiente['nationalite'] == 'Belgique') ? 'selected' : ''; ?>>Belgique</option>
                                        <option value="Canada" <?php echo ($patiente['nationalite'] == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                        <option value="États-Unis" <?php echo ($patiente['nationalite'] == 'États-Unis') ? 'selected' : ''; ?>>États-Unis</option>
                                        <option value="Chine" <?php echo ($patiente['nationalite'] == 'Chine') ? 'selected' : ''; ?>>Chine</option>
                                        <option value="Inde" <?php echo ($patiente['nationalite'] == 'Inde') ? 'selected' : ''; ?>>Inde</option>
                                        <option value="Brésil" <?php echo ($patiente['nationalite'] == 'Brésil') ? 'selected' : ''; ?>>Brésil</option>
                                        <option value="Allemagne" <?php echo ($patiente['nationalite'] == 'Allemagne') ? 'selected' : ''; ?>>Allemagne</option>
                                        <option value="Italie" <?php echo ($patiente['nationalite'] == 'Italie') ? 'selected' : ''; ?>>Italie</option>
                                        <option value="Espagne" <?php echo ($patiente['nationalite'] == 'Espagne') ? 'selected' : ''; ?>>Espagne</option>
                                        <option value="Portugal" <?php echo ($patiente['nationalite'] == 'Portugal') ? 'selected' : ''; ?>>Portugal</option>
                                        <option value="Suisse" <?php echo ($patiente['nationalite'] == 'Suisse') ? 'selected' : ''; ?>>Suisse</option>
                                        <option value="Luxembourg" <?php echo ($patiente['nationalite'] == 'Luxembourg') ? 'selected' : ''; ?>>Luxembourg</option>
                                        <option value="Pays-Bas" <?php echo ($patiente['nationalite'] == 'Pays-Bas') ? 'selected' : ''; ?>>Pays-Bas</option>
                                        <option value="Royaume-Uni" <?php echo ($patiente['nationalite'] == 'Royaume-Uni') ? 'selected' : ''; ?>>Royaume-Uni</option>
                                        <option value="Japon" <?php echo ($patiente['nationalite'] == 'Japon') ? 'selected' : ''; ?>>Japon</option>
                                        <option value="Corée du Sud" <?php echo ($patiente['nationalite'] == 'Corée du Sud') ? 'selected' : ''; ?>>Corée du Sud</option>
                                        <option value="Australie" <?php echo ($patiente['nationalite'] == 'Australie') ? 'selected' : ''; ?>>Australie</option>
                                        <option value="Nouvelle-Zélande" <?php echo ($patiente['nationalite'] == 'Nouvelle-Zélande') ? 'selected' : ''; ?>>Nouvelle-Zélande</option>
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
                                      placeholder="Adresse de la patiente (optionnel)"><?php echo htmlspecialchars($patiente['adresse']); ?></textarea>
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
                                          placeholder="Décrivez les antécédents médicaux de la patiente..."><?php echo htmlspecialchars($patiente['antecedents_medicaux']); ?></textarea>
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