<?php
session_start();

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

$error = '';

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Vérifier les identifiants
        $stmt = $pdo->prepare("SELECT id, username, password, role, nom, prenom FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Clinique Obstétrique SHIPHRAT</title>
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
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl mb-4">
                <i class="fas fa-heartbeat text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                Clinique Obstétrique SHIPHRAT Shiprat
            </h1>
            <p class="text-sm sm:text-base text-gray-600 mt-2">Connexion au système</p>
        </div>

        <!-- Formulaire de connexion -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-purple-100">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Nom d'utilisateur
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                        placeholder="Entrez votre nom d'utilisateur"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Mot de passe
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 pr-12"
                            placeholder="Entrez votre mot de passe"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
                    </label>
                    <a href="#" class="text-sm text-purple-600 hover:text-purple-500">Mot de passe oublié ?</a>
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-3 px-4 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Se connecter
                </button>
            </form>

            <!-- Informations de connexion de test -->
            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Comptes de test
                </h3>
                <div class="text-xs text-blue-700 space-y-1">
                    <div><strong>Admin:</strong> admin / password</div>
                    <div><strong>Médecin:</strong> medecin1 / password</div>
                    <div><strong>Sage-femme:</strong> sagefemme1 / password</div>
                    <div><strong>Secrétaire:</strong> secretaire1 / password</div>
                </div>
            </div>
        </div>

        <!-- Lien retour -->
        <div class="text-center mt-6">
            <a href="/" class="text-purple-600 hover:text-purple-500 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à l'accueil
            </a>
        </div>
    </div>

    <script>
        console.log('Script JS chargé');
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Log des identifiants saisis lors de la soumission du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM entièrement chargé');
            const form = document.querySelector('form');
            if (!form) {
                console.error('Aucun formulaire trouvé sur la page !');
                return;
            } else {
                console.log('Formulaire détecté');
            }
            form.addEventListener('submit', function(e) {
                console.log('Soumission du formulaire détectée');
                const username = document.getElementById('username') ? document.getElementById('username').value : '[champ absent]';
                const password = document.getElementById('password') ? document.getElementById('password').value : '[champ absent]';
                console.log('Valeur username :', username);
                console.log('Valeur password :', password);
                console.log('Tentative de connexion avec :', username, password);
                // e.preventDefault(); // On laisse la soumission normale
            });
            // Animation d'entrée
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                form.style.transition = 'all 0.5s ease-out';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
<?php if (isset($error) && $error): ?>
    <script>console.log('Erreur PHP côté serveur : <?php echo addslashes($error); ?>');</script>
<?php endif; ?>
</body>
</html> 