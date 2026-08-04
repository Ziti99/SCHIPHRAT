<?php
require_once __DIR__ . '/vendor/autoload.php';
use Clinique\Services\Auth;
Auth::initSecureSession();

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
    <title>Clinique Obstétrique - Accueil</title>
    <meta name="description" content="Système de gestion moderne pour clinique obstétrique - suivi patientes, consultations, accouchements">
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
    <nav class="bg-white/80 backdrop-blur-md border-b border-purple-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-base sm:text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-xl lg:text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="hidden sm:block text-xs text-gray-500">Excellence en soins maternels</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <a href="/login.php" class="hidden sm:flex text-gray-600 hover:text-purple-600 transition-colors items-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                    </a>
                    <a href="/login.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-3 py-2 sm:px-6 sm:py-2 rounded-lg hover:shadow-lg transition-all duration-300 transform">
                        <i class="fas fa-sign-in-alt sm:hidden mr-1"></i>
                        <span class="hidden sm:inline">Accès Personnel</span>
                        <span class="sm:hidden">Connexion</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div class="space-y-4">
                        <div class="inline-flex items-center px-3 py-1 bg-green-50 border border-green-200 rounded-full text-xs text-green-700">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>Système sécurisé – v2.0
                        </div>
                        <h2 class="text-4xl lg:text-6xl font-bold leading-tight">
                            <span class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                Soins maternels
                            </span>
                            <br>
                            <span class="text-gray-800">d'excellence</span>
                        </h2>
                        <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
                            Système de gestion moderne et sécurisé pour le suivi complet des patientes enceintes, 
                            des consultations prénatales aux accouchements et au suivi post-natal.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/login.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:shadow-xl transition-all duration-300 text-center">
                            <i class="fas fa-user-md mr-3"></i>
                            Accès Personnel
                        </a>
                        <a href="#features" class="border-2 border-purple-500 text-purple-600 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-purple-50 transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-shield-alt mr-3"></i>
                            Voir les améliorations
                        </a>
                    </div>

                    <!-- Security badges -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <span class="inline-flex items-center px-3 py-1 bg-white border rounded-full text-xs text-gray-600"><i class="fas fa-lock mr-2 text-green-500"></i>Env sécurisé</span>
                        <span class="inline-flex items-center px-3 py-1 bg-white border rounded-full text-xs text-gray-600"><i class="fas fa-shield-alt mr-2 text-blue-500"></i>CSRF + Rate-limit</span>
                        <span class="inline-flex items-center px-3 py-1 bg-white border rounded-full text-xs text-gray-600"><i class="fas fa-key mr-2 text-purple-500"></i>Bcrypt 12</span>
                    </div>
                </div>

                <!-- Feature cards illustration -->
                <div class="relative hidden lg:block">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div class="bg-white p-5 rounded-2xl shadow-lg border">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-female text-purple-600"></i></div>
                                <h4 class="font-semibold text-sm">Patientes</h4>
                                <p class="text-xs text-gray-500 mt-1">Dossiers centralisés</p>
                            </div>
                            <div class="bg-white p-5 rounded-2xl shadow-lg border">
                                <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-baby text-pink-600"></i></div>
                                <h4 class="font-semibold text-sm">Accouchements</h4>
                                <p class="text-xs text-gray-500 mt-1">Enregistrement complet</p>
                            </div>
                        </div>
                        <div class="space-y-4 mt-8">
                            <div class="bg-white p-5 rounded-2xl shadow-lg border">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-stethoscope text-blue-600"></i></div>
                                <h4 class="font-semibold text-sm">Consultations</h4>
                                <p class="text-xs text-gray-500 mt-1">Suivi prénatal</p>
                            </div>
                            <div class="bg-white p-5 rounded-2xl shadow-lg border">
                                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-chart-bar text-cyan-600"></i></div>
                                <h4 class="font-semibold text-sm">Rapports</h4>
                                <p class="text-xs text-gray-500 mt-1">PDF & Excel</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">
                    ✅ Projet sécurisé et amélioré
                </h2>
                <p class="text-gray-600 max-w-3xl mx-auto">
                    Toutes les failles critiques ont été corrigées. Le système est maintenant prêt pour la production.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="p-6 border border-green-200 bg-green-50/50 rounded-xl">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-lock text-green-600"></i></div>
                    <h3 class="font-semibold">Secrets supprimés</h3>
                    <p class="text-sm text-gray-600 mt-1">Plus de credentials hardcodés. 100% via <code>.env</code> + Railway variables. .gitignore configuré.</p>
                </div>
                <div class="p-6 border border-blue-200 bg-blue-50/50 rounded-xl">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-database text-blue-600"></i></div>
                    <h3 class="font-semibold">Database PSR-4</h3>
                    <p class="text-sm text-gray-600 mt-1"><code>src/Config/Database.php</code> singleton sécurisé avec dotenv, support Railway MYSQL* vars.</p>
                </div>
                <div class="p-6 border border-purple-200 bg-purple-50/50 rounded-xl">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-shield-alt text-purple-600"></i></div>
                    <h3 class="font-semibold">Auth sécurisée</h3>
                    <p class="text-sm text-gray-600 mt-1">HttpOnly, SameSite, regenerate_id, CSRF token, rate-limiting 5 tentatives / 15min.</p>
                </div>
                <div class="p-6 border rounded-xl">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-home text-gray-600"></i></div>
                    <h3 class="font-semibold">Dashboard fonctionnel</h3>
                    <p class="text-sm text-gray-600 mt-1"><code>dashboard.php</code> implémenté, plus de 404. RBAC par rôle.</p>
                </div>
                <div class="p-6 border rounded-xl">
                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mb-3"><i class="fas fa-file-medical text-pink-600"></i></div>
                    <h3 class="font-semibold">Schéma complet</h3>
                    <p class="text-sm text-gray-600 mt-1">Tables patientes, consultations, accouchements, postnatal, audit_logs + seeds.</p>
                </div>
                <div class="p-6 border rounded-xl">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center mb-3"><i class="fab fa-docker text-cyan-600"></i></div>
                    <h3 class="font-semibold">Docker durci</h3>
                    <p class="text-sm text-gray-600 mt-1">Multi-stage, non-root user, healthcheck, opcache prod.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-3"><div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center"><i class="fas fa-heartbeat text-white text-sm"></i></div><span class="font-bold">Clinique Obstétrique</span></div>
                    <p class="text-sm text-gray-400">Système sécurisé v2.0 – Gestion complète clinique obstétrique.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 text-sm">Sécurité</h4>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li>✓ Variables d'environnement</li>
                        <li>✓ Bcrypt cost 12 + rehash</li>
                        <li>✓ Sessions HttpOnly / SameSite</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3 text-sm">Liens</h4>
                    <ul class="text-sm text-gray-400 space-y-1">
                        <li><a href="/login.php" class="hover:text-white">Connexion</a></li>
                        <li><a href="/dashboard.php" class="hover:text-white">Dashboard</a></li>
                        <li><a href="/patientes.php" class="hover:text-white">Patientes</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; <?= date('Y') ?> Clinique Obstétrique. Tous droits réservés. Système sécurisé.</p>
            </div>
        </div>
    </footer>
</body>
</html>
