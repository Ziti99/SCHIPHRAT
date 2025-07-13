<?php
// Ne pas redémarrer la session si elle est déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="bg-white shadow-lg border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                        Clinique Obstétrique
                    </h1>
                    <?php if (isset($navbarSubtitle) && $navbarSubtitle): ?>
                        <p class="text-gray-500 text-sm font-medium mt-0.5"><?php echo htmlspecialchars($navbarSubtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?></p>
                    <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $_SESSION['user_role'] ?? 'utilisateur'); ?></p>
                </div>
                <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav> 