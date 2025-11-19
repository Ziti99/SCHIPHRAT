<?php
// Pas besoin d'autoloader, on utilise les sessions
// session_start(); // SUPPRIMÉ car déjà démarré dans dashboard.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
?>
<!-- Overlay pour fermer le menu sur mobile - Plus visible -->
<div id="mobileMenuOverlay" class="hidden lg:hidden fixed inset-0 bg-black bg-opacity-60 z-40 backdrop-blur-sm transition-opacity duration-300"></div>

<!-- Sidebar responsive avec scroll -->
<aside id="sidebar" class="w-64 bg-white shadow-lg h-screen fixed lg:static transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 top-0 lg:top-auto overflow-y-auto">
    <!-- Bouton fermer (mobile uniquement) - Design amélioré -->
    <button id="closeSidebarButton" class="lg:hidden absolute top-4 right-4 bg-red-500 text-white hover:bg-red-600 w-10 h-10 rounded-full shadow-lg flex items-center justify-center active:scale-90 transition-all z-50">
        <i class="fas fa-times text-lg"></i>
    </button>
    
    <nav class="mt-16 lg:mt-8 pb-8">
        <div class="px-4 space-y-2">
            <a href="/dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-tachometer-alt mr-3"></i>
                Dashboard
            </a>
            <?php if (($_SESSION['user_role'] ?? '') !== 'caissiere'): ?>
            <a href="/patientes.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" id="sidebar-patientes-link">
                <i class="fas fa-user-injured mr-3"></i>
                Patientes
            </a>
            <a href="/consultations.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-calendar-check mr-3"></i>
                Consultations
            </a>
            <a href="/accouchements.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-baby mr-3"></i>
                Accouchements
            </a>
            <a href="/deces.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-cross mr-3"></i>
                Décès
            </a>
            <a href="/suivi-postnatal.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-heartbeat mr-3"></i>
                Suivi Post-natal
            </a>
            <a href="/registres.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-book-medical mr-3"></i>
                Registres
            </a>
            <a href="/statistiques.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-chart-line mr-3"></i>
                Statistiques
            </a>
            <?php else: ?>
            <!-- Pour la caissière: n'afficher que Patientes et Consultations (généraux) -->
            <a href="/patientes.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" id="sidebar-patientes-link">
                <i class="fas fa-user-injured mr-3"></i>
                Patientes
            </a>
            <a href="/consultations.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-calendar-check mr-3"></i>
                Consultations
            </a>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div class="border-t border-gray-200 pt-4 mt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
                <a href="/utilisateurs.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                    <i class="fas fa-users-cog mr-3"></i>
                    Utilisateurs
                </a>
                <a href="/actes.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                    <i class="fas fa-cogs mr-3"></i>
                    Gestion des actes
                </a>
            </div>
            <?php endif; ?>            <?php if (in_array($_SESSION['user_role'], ['admin', 'caissiere'])): ?>
            <div class="border-t border-gray-200 pt-4 mt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Gestion de Caisse</p>
                <a href="/caissiere_dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                    <i class="fas fa-cash-register mr-3"></i>
                    Dashboard Caisse
                </a>
                <a href="/caissiere_consultations.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                    <i class="fas fa-list-alt mr-3"></i>
                    Consultations & Paiements
                </a>
                <a href="/caissiere_recherche.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                    <i class="fas fa-search mr-3"></i>
                    Rechercher Patiente
                </a>
                <a href="/caissiere_statistiques.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Statistiques Caisse
                </a>
            </div>
            <?php endif; ?>
        </div>
    </nav>
</aside>

<!-- Script pour le menu mobile responsive -->
<script>
(function() {
    'use strict';
    
    // Attendre que le DOM soit chargé
    function initMobileMenu() {
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const closeSidebarButton = document.getElementById('closeSidebarButton');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        
        if (!sidebar) return;
        
        function openSidebar() {
            if (sidebar) {
                sidebar.classList.remove('-translate-x-full');
                if (overlay) overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Empêcher le scroll
            }
        }
        
        function closeSidebar() {
            if (sidebar) {
                sidebar.classList.add('-translate-x-full');
                if (overlay) overlay.classList.add('hidden');
                document.body.style.overflow = 'auto'; // Réactiver le scroll
            }
        }
        
        // Ouvrir le menu
        if (mobileMenuButton) {
            mobileMenuButton.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            };
        }
        
        // Fermer le menu avec le bouton croix
        if (closeSidebarButton) {
            closeSidebarButton.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            };
        }
        
        // Fermer en cliquant sur l'overlay
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            });
        }
        
        // Fermer le menu après un clic sur un lien (mobile uniquement)
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) { // lg breakpoint
                    setTimeout(closeSidebar, 100); // Petit délai pour laisser la navigation se faire
                }
            });
        });
        
        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && !sidebar.classList.contains('-translate-x-full')) {
                closeSidebar();
            }
        });
        
        console.log('📱 Menu mobile responsive initialisé');
    }
    
    // Initialiser immédiatement si le DOM est déjà chargé
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileMenu);
    } else {
        initMobileMenu();
    }
})();
</script> 