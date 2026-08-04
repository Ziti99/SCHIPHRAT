<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Helpers\Security;

Auth::initSecureSession();

// Si déjà connecté, rediriger
if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
$debug = filter_var($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Gestion du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérif CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!Security::verifyCsrfToken($csrf)) {
        $error = 'Jeton de sécurité invalide. Veuillez réessayer.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = Auth::attempt($username, $password);

        if ($result['success']) {
            $redirect = $_GET['redirect'] ?? '/dashboard.php';
            // Empêche open redirect
            if (!str_starts_with($redirect, '/')) {
                $redirect = '/dashboard.php';
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$csrfToken = Security::generateCsrfToken();
$isRateLimited = Security::isRateLimited('login', (int)($_ENV['LOGIN_MAX_ATTEMPTS'] ?? 5), (int)($_ENV['LOGIN_LOCKOUT_MINUTES'] ?? 15));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo et titre -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl mb-4 shadow-lg">
                <i class="fas fa-heartbeat text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                Clinique Obstétrique
            </h1>
            <p class="text-sm sm:text-base text-gray-600 mt-2">Connexion sécurisée</p>
        </div>

        <!-- Formulaire de connexion -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-purple-100">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start text-sm">
                    <i class="fas fa-exclamation-circle mr-3 mt-0.5"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <?php if ($isRateLimited): ?>
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg mb-6 text-sm">
                    <i class="fas fa-clock mr-2"></i>
                    Trop de tentatives. Veuillez patienter <?= Security::getRemainingLockout('login') ?> minute(s).
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Nom d'utilisateur
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        autocomplete="username"
                        autofocus
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                        placeholder="Entrez votre nom d'utilisateur"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"
                        <?= $isRateLimited ? 'disabled' : '' ?>
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                        placeholder="Entrez votre mot de passe"
                        <?= $isRateLimited ? 'disabled' : '' ?>
                    >
                </div>

                <button 
                    type="submit" 
                    <?= $isRateLimited ? 'disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-lg font-semibold cursor-not-allowed"' : 'class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-3 px-4 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 hover:from-purple-600 hover:to-pink-600"' ?>
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <?= $isRateLimited ? 'Compte bloqué' : 'Se connecter' ?>
                </button>
            </form>

            <!-- Informations de connexion de test - seulement en debug -->
            <?php if ($debug): ?>
            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Comptes de test (DEBUG)
                </h3>
                <div class="text-xs text-blue-700 space-y-1 font-mono">
                    <div><strong>Admin:</strong> admin / password</div>
                    <div><strong>Médecin:</strong> medecin1 / password</div>
                    <div><strong>Sage-femme:</strong> sagefemme1 / password</div>
                    <div><strong>Secrétaire:</strong> secretaire1 / password</div>
                </div>
                <p class="text-[10px] text-blue-600 mt-2">Désactivez APP_DEBUG=false en production</p>
            </div>
            <?php endif; ?>

            <div class="mt-6 text-center text-xs text-gray-400">
                <i class="fas fa-shield-alt mr-1"></i>Connexion chiffrée et sécurisée
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="/" class="text-sm text-gray-500 hover:text-purple-600"><i class="fas fa-arrow-left mr-2"></i>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
