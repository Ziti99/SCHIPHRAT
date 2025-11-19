<?php
// Ne pas redémarrer la session si elle est déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Améliorations Mobile CSS & JS -->
<link rel="stylesheet" href="/assets/css/mobile-improvements.css">
<script src="/assets/js/mobile-responsive.js" defer></script>
<nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- Bouton menu mobile amélioré -->
                <button id="mobileMenuButton" class="lg:hidden text-white bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 p-3 rounded-lg shadow-lg active:scale-95 transition-all -ml-2">
                    <i class="fas fa-bars text-xl"></i>
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
                <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'caissiere'])): ?>
                <!-- Icône de notification pour la caissière -->
                <div id="notification-bell-container" class="relative">
                    <button id="notification-bell" class="relative text-gray-600 hover:text-purple-600 transition-colors p-2 focus:outline-none" title="Notifications" aria-label="Notifications">
                        <i class="fas fa-bell text-base sm:text-lg md:text-xl"></i>
                        <span id="notification-badge" class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center hidden">
                            <span id="notification-count" class="text-[10px] sm:text-xs">0</span>
                        </span>
                    </button>
                    <!-- Dropdown des notifications -->
                    <div id="notification-dropdown" class="hidden fixed sm:absolute right-2 sm:right-0 top-16 sm:top-auto sm:mt-2 w-[calc(100vw-1rem)] sm:w-80 md:w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-[9999] max-h-[calc(100vh-5rem)] sm:max-h-96 overflow-y-auto">
                        <div class="p-3 sm:p-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-semibold text-gray-900 text-sm sm:text-base">Notifications</h3>
                            <button id="mark-all-read" class="text-xs sm:text-sm text-purple-600 hover:text-purple-800">Tout marquer lu</button>
                        </div>
                        <div id="notification-list" class="divide-y divide-gray-100">
                            <div class="p-4 text-center text-gray-500 text-sm">
                                <i class="fas fa-bell-slash text-2xl mb-2 text-gray-300"></i>
                                <p>Aucune notification</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors p-2" title="Déconnexion">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</nav> 
<?php if (in_array($_SESSION['user_role'] ?? '', ['secretaire', 'caissiere'])): ?>
<div id="networkStatusBanner" class="hidden text-white text-sm px-4 py-2 text-center" style="background:#991B1B;">
    Vous êtes hors‑ligne. Les actions seront envoyées dès le rétablissement de la connexion.
    <button id="retryNetworkBtn" class="ml-3 underline font-semibold">Réessayer</button>
    <span id="networkDot" class="ml-2 inline-block w-2 h-2 rounded-full align-middle" style="background:#EF4444;"></span>
</div>
<script>
(function() {
    const banner = document.getElementById('networkStatusBanner');
    const dot = document.getElementById('networkDot');
    const retry = document.getElementById('retryNetworkBtn');

    function update() {
        if (navigator.onLine) {
            if (banner) banner.classList.add('hidden');
        } else {
            if (banner) banner.classList.remove('hidden');
        }
        if (dot) dot.style.background = navigator.onLine ? '#10B981' : '#EF4444';
    }

    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    if (retry) retry.addEventListener('click', update);
    update();
})();
</script>
<?php endif; ?>