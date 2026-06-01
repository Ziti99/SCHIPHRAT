<?php
// Redirection simple vers login si pas connecté
session_start();
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8">
                    <div class="space-y-4">
                        <h2 class="text-5xl lg:text-6xl font-bold leading-tight">
                            <span class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                Soins maternels
                            </span>
                            <br>
                            <span class="text-gray-800">d'excellence</span>
                        </h2>
                        <p class="text-xl text-gray-600 leading-relaxed">
                            Système de gestion moderne pour le suivi complet des patientes enceintes, 
                            des consultations prénatales aux accouchements et au suivi post-natal.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/login.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:shadow-xl transition-all duration-300">
                            <i class="fas fa-user-md mr-3"></i>
                            Accès Personnel
                        </a>
                        <a href="#features" class="border-2 border-purple-500 text-purple-600 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-purple-50 transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-info-circle mr-3"></i>
                            En savoir plus
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">
                    Fonctionnalités principales
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Un système complet pour la gestion moderne de votre clinique obstétrique
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Clinique Obstétrique. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>