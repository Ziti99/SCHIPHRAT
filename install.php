<?php
/**
 * Script d'installation automatique pour la Clinique Obstétrique
 * Ce script configure automatiquement la base de données et les fichiers nécessaires
 */

// Vérification des prérequis
function checkPrerequisites() {
    $errors = [];
    
    // Vérifier PHP
    if (version_compare(PHP_VERSION, '8.0.0', '<')) {
        $errors[] = "PHP 8.0 ou supérieur requis. Version actuelle : " . PHP_VERSION;
    }
    
    // Vérifier les extensions PHP
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extension PHP '$ext' manquante";
        }
    }
    
    // Vérifier Composer
    if (!file_exists('vendor/autoload.php')) {
        $errors[] = "Composer n'est pas installé. Exécutez 'composer install'";
    }
    
    // Vérifier les permissions d'écriture
    $writable_dirs = ['public', 'src'];
    foreach ($writable_dirs as $dir) {
        if (!is_writable($dir)) {
            $errors[] = "Le dossier '$dir' n'est pas accessible en écriture";
        }
    }
    
    return $errors;
}

// Configuration de la base de données
function setupDatabase($host, $dbname, $username, $password) {
    try {
        // Connexion à MySQL
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Sélectionner la base de données
        $pdo->exec("USE `$dbname`");
        
        // Supprimer les tables existantes dans l'ordre inverse des dépendances
        $drop_tables = [
            'sessions',
            'deces_neonataux', 
            'suivi_postnatal',
            'accouchements',
            'examens',
            'consultations_prenatales',
            'grossesses',
            'patientes',
            'users'
        ];
        
        foreach ($drop_tables as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            } catch (PDOException $e) {
                // Ignorer les erreurs si la table n'existe pas
            }
        }
        
        // Lire et exécuter le schéma SQL
        $sql = file_get_contents('database/schema.sql');
        
        // Supprimer les lignes de création de base de données et USE
        $sql = preg_replace('/CREATE DATABASE.*?;/', '', $sql);
        $sql = preg_replace('/USE.*?;/', '', $sql);
        
        // Exécuter les requêtes
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return ['success' => true, 'message' => 'Base de données configurée avec succès'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
    }
}

// Créer le fichier .env
function createEnvFile($config) {
    $env_content = "# Configuration de la base de données\n";
    $env_content .= "DB_HOST={$config['db_host']}\n";
    $env_content .= "DB_NAME={$config['db_name']}\n";
    $env_content .= "DB_USER={$config['db_user']}\n";
    $env_content .= "DB_PASS={$config['db_pass']}\n\n";
    
    $env_content .= "# Configuration de l'application\n";
    $env_content .= "APP_NAME=\"Clinique Obstétrique\"\n";
    $env_content .= "APP_URL={$config['app_url']}\n";
    $env_content .= "APP_ENV=development\n\n";
    
    $env_content .= "# Configuration email\n";
    $env_content .= "MAIL_HOST=smtp.gmail.com\n";
    $env_content .= "MAIL_PORT=587\n";
    $env_content .= "MAIL_USERNAME=\n";
    $env_content .= "MAIL_PASSWORD=\n";
    $env_content .= "MAIL_ENCRYPTION=tls\n\n";
    
    $env_content .= "# Sécurité\n";
    $env_content .= "JWT_SECRET=" . bin2hex(random_bytes(32)) . "\n";
    $env_content .= "SESSION_SECRET=" . bin2hex(random_bytes(32)) . "\n";
    
    if (file_put_contents('.env', $env_content)) {
        return ['success' => true, 'message' => 'Fichier .env créé avec succès'];
    } else {
        return ['success' => false, 'message' => 'Impossible de créer le fichier .env'];
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = [
        'db_host' => $_POST['db_host'] ?? 'localhost',
        'db_name' => $_POST['db_name'] ?? 'clinique_obstetrique',
        'db_user' => $_POST['db_user'] ?? 'root',
        'db_pass' => $_POST['db_pass'] ?? '',
        'app_url' => $_POST['app_url'] ?? 'http://localhost'
    ];
    
    $results = [];
    
    // Créer le fichier .env
    $env_result = createEnvFile($config);
    $results[] = $env_result;
    
    // Configurer la base de données
    $db_result = setupDatabase($config['db_host'], $config['db_name'], $config['db_user'], $config['db_pass']);
    $results[] = $db_result;
    
    $all_success = true;
    foreach ($results as $result) {
        if (!$result['success']) {
            $all_success = false;
        }
    }
    
    if ($all_success) {
        $success_message = "Installation terminée avec succès ! Vous pouvez maintenant accéder à votre clinique.";
    }
}

// Vérifier les prérequis
$prerequisites_errors = checkPrerequisites();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Clinique Obstétrique</title>
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
    <div class="max-w-4xl mx-auto p-8">
        <!-- En-tête -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl mb-6">
                <i class="fas fa-heartbeat text-white text-3xl"></i>
            </div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                Installation Clinique Obstétrique
            </h1>
            <p class="text-xl text-gray-600">Configurez votre système de gestion obstétrique</p>
        </div>

        <!-- Vérification des prérequis -->
        <?php if (!empty($prerequisites_errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-red-800 mb-4 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    Prérequis non satisfaits
                </h2>
                <ul class="space-y-2 text-red-700">
                    <?php foreach ($prerequisites_errors as $error): ?>
                        <li class="flex items-center">
                            <i class="fas fa-times-circle mr-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="mt-6 p-4 bg-red-100 rounded-lg">
                    <h3 class="font-semibold text-red-800 mb-2">Actions à effectuer :</h3>
                    <ul class="text-sm text-red-700 space-y-1">
                        <li>• Installez PHP 8.0 ou supérieur</li>
                        <li>• Activez les extensions PHP requises</li>
                        <li>• Exécutez <code class="bg-red-200 px-2 py-1 rounded">composer install</code></li>
                        <li>• Vérifiez les permissions des dossiers</li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-green-800 mb-4 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    Prérequis satisfaits
                </h2>
                <p class="text-green-700">Tous les prérequis sont satisfaits. Vous pouvez procéder à l'installation.</p>
            </div>
        <?php endif; ?>

        <!-- Message de succès -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-green-800 mb-4 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    Installation réussie !
                </h2>
                <p class="text-green-700 mb-4"><?php echo $success_message; ?></p>
                <div class="flex space-x-4">
                    <a href="/" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-home mr-2"></i>
                        Accéder à la clinique
                    </a>
                    <a href="/login.php" class="border-2 border-purple-500 text-purple-600 px-6 py-3 rounded-lg hover:bg-purple-50 transition-all duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'installation -->
        <?php if (empty($prerequisites_errors) && !isset($success_message)): ?>
            <div class="bg-white rounded-xl shadow-2xl p-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Configuration de la base de données</h2>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="db_host" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-server mr-2"></i>Hôte MySQL
                            </label>
                            <input 
                                type="text" 
                                id="db_host" 
                                name="db_host" 
                                value="localhost" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="db_name" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-database mr-2"></i>Nom de la base de données
                            </label>
                            <input 
                                type="text" 
                                id="db_name" 
                                name="db_name" 
                                value="clinique_obstetrique" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="db_user" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>Utilisateur MySQL
                            </label>
                            <input 
                                type="text" 
                                id="db_user" 
                                name="db_user" 
                                value="root" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lock mr-2"></i>Mot de passe MySQL
                            </label>
                            <input 
                                type="password" 
                                id="db_pass" 
                                name="db_pass" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                    </div>
                    
                    <div>
                        <label for="app_url" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-globe mr-2"></i>URL de l'application
                        </label>
                        <input 
                            type="url" 
                            id="app_url" 
                            name="app_url" 
                            value="http://localhost" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                        >
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>Informations importantes
                        </h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Assurez-vous que MySQL est installé et en cours d'exécution</li>
                            <li>• L'utilisateur MySQL doit avoir les droits de création de base de données</li>
                            <li>• L'URL doit pointer vers le dossier public/ de l'application</li>
                            <li>• Après l'installation, supprimez ce fichier install.php pour des raisons de sécurité</li>
                        </ul>
                    </div>
                    
                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                        >
                            <i class="fas fa-magic mr-2"></i>
                            Installer la clinique
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Résultats de l'installation -->
        <?php if (isset($results)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Résultats de l'installation</h3>
                <div class="space-y-3">
                    <?php foreach ($results as $result): ?>
                        <div class="flex items-center p-3 rounded-lg <?php echo $result['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                            <i class="fas <?php echo $result['success'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600'; ?> mr-3"></i>
                            <span class="<?php echo $result['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                                <?php echo htmlspecialchars($result['message']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 