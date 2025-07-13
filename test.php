<?php
echo "<h1>🔍 DIAGNOSTIC COMPLET - CLINIQUE OBSTÉTRIQUE</h1>";
echo "<hr>";

// 1. Informations PHP de base
echo "<h2>📋 INFORMATIONS PHP</h2>";
echo "<p><strong>Version PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

// 2. Test des extensions PHP
echo "<h2>🔧 EXTENSIONS PHP</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'zip'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ Extension $ext: CHARGÉE</p>";
    } else {
        echo "<p style='color: red;'>❌ Extension $ext: MANQUANTE</p>";
    }
}

// 3. Test des permissions et fichiers
echo "<h2>📁 PERMISSIONS ET FICHIERS</h2>";
$test_paths = [
    '/var/www/html',
    '/var/www/html/index.php',
    '/var/www/html/vendor',
    '/var/www/html/vendor/autoload.php',
    '/var/www/html/Database.php',
    '/var/www/html/login.php',
    '/var/www/html/dashboard.php'
];

foreach ($test_paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $readable = is_readable($path) ? 'LISIBLE' : 'NON LISIBLE';
        $writable = is_writable($path) ? 'ÉCRITABLE' : 'NON ÉCRITABLE';
        echo "<p style='color: green;'>✅ $path - Permissions: $perms - $readable - $writable</p>";
    } else {
        echo "<p style='color: red;'>❌ $path - FICHIER MANQUANT</p>";
    }
}

// 4. Test de l'autoloader
echo "<h2>🔄 TEST AUTOLOADER</h2>";
$autoload_paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/var/www/html/vendor/autoload.php'
];

foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        echo "<p style='color: green;'>✅ Autoloader trouvé: $path</p>";
        try {
            require_once $path;
            echo "<p style='color: green;'>✅ Autoloader chargé avec succès</p>";
            
            // Test des classes
            if (class_exists('Clinique\Auth\Auth')) {
                echo "<p style='color: green;'>✅ Classe Clinique\Auth\Auth trouvée</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Classe Clinique\Auth\Auth non trouvée</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erreur autoloader: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Autoloader non trouvé: $path</p>";
    }
}

// 5. Variables d'environnement
echo "<h2>🌍 VARIABLES D'ENVIRONNEMENT</h2>";
$env_vars = ['PORT', 'PWD', 'HOME', 'USER', 'PATH'];
foreach ($env_vars as $var) {
    $value = $_ENV[$var] ?? getenv($var) ?? 'Non défini';
    echo "<p><strong>$var:</strong> $value</p>";
}

// 6. Test de base de données
echo "<h2>🗄️ TEST BASE DE DONNÉES</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    echo "<p style='color: green;'>✅ Connexion MySQL réussie</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur MySQL: " . $e->getMessage() . "</p>";
}

// 7. Test des erreurs PHP
echo "<h2>⚠️ CONFIGURATION ERREURS PHP</h2>";
echo "<p><strong>display_errors:</strong> " . (ini_get('display_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p><strong>error_reporting:</strong> " . ini_get('error_reporting') . "</p>";
echo "<p><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'ON' : 'OFF') . "</p>";
echo "<p><strong>error_log:</strong> " . ini_get('error_log') . "</p>";

// 8. Test des sessions
echo "<h2>🔐 TEST SESSIONS</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Session active</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Session non active</p>";
}

// 9. Test des modules Apache
echo "<h2>🌐 MODULES APACHE</h2>";
$apache_modules = ['mod_rewrite', 'mod_headers', 'mod_php'];
foreach ($apache_modules as $module) {
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        if (in_array($module, $modules)) {
            echo "<p style='color: green;'>✅ Module $module: CHARGÉ</p>";
        } else {
            echo "<p style='color: red;'>❌ Module $module: NON CHARGÉ</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Impossible de vérifier les modules Apache</p>";
        break;
    }
}

// 10. Test des fonctions critiques
echo "<h2>⚙️ FONCTIONS CRITIQUES</h2>";
$critical_functions = ['header', 'session_start', 'file_exists', 'is_readable', 'chmod'];
foreach ($critical_functions as $func) {
    if (function_exists($func)) {
        echo "<p style='color: green;'>✅ Fonction $func: DISPONIBLE</p>";
    } else {
        echo "<p style='color: red;'>❌ Fonction $func: MANQUANTE</p>";
    }
}

// 11. Test de la mémoire
echo "<h2>💾 MÉMOIRE</h2>";
echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
echo "<p><strong>Memory Usage:</strong> " . memory_get_usage(true) . " bytes</p>";
echo "<p><strong>Peak Memory:</strong> " . memory_get_peak_usage(true) . " bytes</p>";

// 12. Test des timeouts
echo "<h2>⏱️ TIMEOUTS</h2>";
echo "<p><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . "s</p>";
echo "<p><strong>max_input_time:</strong> " . ini_get('max_input_time') . "s</p>";

echo "<hr>";
echo "<h2>🎯 RÉSUMÉ</h2>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";

echo "<h3>🚀 DIAGNOSTIC TERMINÉ</h3>";
?> 