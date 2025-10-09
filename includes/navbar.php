<?php
// Ne pas redémarrer la session si elle est déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Bouton menu mobile -->
                <button id="mobileMenuButton" class="lg:hidden text-purple-600 hover:text-purple-700 p-2 -ml-2">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                
                <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-base sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-base sm:text-xl lg:text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Clinique Obstétrique
                    </h1>
                    <?php if (isset($navbarSubtitle) && $navbarSubtitle): ?>
                        <p class="hidden sm:block text-gray-500 text-xs sm:text-sm font-medium mt-0.5"><?php echo htmlspecialchars($navbarSubtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <div class="text-right hidden sm:block">
                    <p class="text-xs sm:text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?></p>
                    <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $_SESSION['user_role'] ?? 'utilisateur'); ?></p>
                </div>
                <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors p-2" title="Déconnexion">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</nav> 