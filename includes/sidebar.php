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

<!-- Script IMMÉDIAT pour forcer la fermeture du menu au chargement uniquement -->
<script>
(function() {
    'use strict';
    // Fermer le menu au chargement uniquement (pas de !important pour permettre l'ouverture)
    function forceCloseMenuOnLoad() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('mobileMenuOverlay');
        if (sidebar) {
            sidebar.classList.add('-translate-x-full');
            sidebar.setAttribute('data-menu-state', 'closed');
            // Pas de !important ici pour permettre l'ouverture
            if (window.innerWidth < 1024) {
                sidebar.style.transform = 'translateX(-100%)';
            }
        }
        if (overlay) {
            overlay.classList.add('hidden');
            if (window.innerWidth < 1024) {
                overlay.style.display = 'none';
            }
        }
        if (document.body) {
            document.body.style.overflow = 'auto';
        }
    }
    // Exécuter au chargement seulement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceCloseMenuOnLoad);
    } else {
        forceCloseMenuOnLoad();
    }
})();
</script>

<!-- Sidebar responsive avec scroll - Toujours fermé par défaut sur mobile -->
<aside id="sidebar" class="w-64 bg-white shadow-lg h-screen fixed lg:static transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 top-0 lg:top-auto overflow-y-auto" data-menu-state="closed">
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
    
    // Fonction pour fermer le sidebar
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (sidebar && window.innerWidth < 1024) {
            sidebar.classList.add('-translate-x-full');
            sidebar.setAttribute('data-menu-state', 'closed');
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.style.left = '';
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.style.display = 'none';
            }
            document.body.style.overflow = 'auto';
        }
    }
    
    // Fonction pour ouvrir le sidebar
    function openSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (sidebar && window.innerWidth < 1024) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.setAttribute('data-menu-state', 'open');
            sidebar.style.transform = 'translateX(0)';
            sidebar.style.left = '';
            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.style.display = 'block';
            }
            document.body.style.overflow = 'hidden';
        }
    }
    
    // S'assurer que le menu est fermé au chargement (uniquement sur mobile)
    function ensureMenuClosed() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (sidebar && window.innerWidth < 1024) {
            // Forcer la fermeture au chargement (sans !important pour permettre l'ouverture)
            sidebar.classList.add('-translate-x-full');
            sidebar.setAttribute('data-menu-state', 'closed');
            sidebar.style.transform = 'translateX(-100%)';
            sidebar.style.left = '';
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.style.display = 'none';
            }
            document.body.style.overflow = 'auto';
        }
    }
    
    // Attendre que le DOM soit chargé
    function initMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const closeSidebarButton = document.getElementById('closeSidebarButton');
        
        if (!sidebar) {
            console.warn('⚠️ Sidebar non trouvé');
            return;
        }
        
        // S'assurer que le menu est fermé au chargement IMMÉDIATEMENT
        ensureMenuClosed();
        
        // Double vérification après un court délai pour les appareils lents
        setTimeout(ensureMenuClosed, 50);
        
        // Ouvrir le menu
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            }, { passive: false });
        }
        
        // Fermer le menu avec le bouton croix
        if (closeSidebarButton) {
            closeSidebarButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            }, { passive: false });
        }
        
        // Fermer en cliquant sur l'overlay (click et touchstart pour mobile)
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            }, { passive: false });
            
            overlay.addEventListener('touchstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            }, { passive: false });
        }
        
        // Fermer le menu après un clic sur un lien (mobile uniquement)
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) { // lg breakpoint
                    setTimeout(closeSidebar, 150);
                }
            });
        });
        
        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && !sidebar.classList.contains('-translate-x-full')) {
                closeSidebar();
            }
        });
        
        // Fermer le menu lors du redimensionnement si on passe en mode desktop
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 1024) { // lg breakpoint
                    closeSidebar();
                }
            }, 100);
        });
        
        // Fermer le menu lors du changement d'orientation
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                closeSidebar();
            }, 100);
        });
        
        console.log('📱 Menu mobile responsive initialisé');
    }
    
    // Fermer le menu IMMÉDIATEMENT avant même le chargement du DOM
    ensureMenuClosed();
    
    // Puis initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ensureMenuClosed();
            setTimeout(function() {
                ensureMenuClosed();
                initMobileMenu();
            }, 10);
        });
    } else {
        ensureMenuClosed();
        setTimeout(function() {
            ensureMenuClosed();
            initMobileMenu();
        }, 10);
    }
    
    // Vérification supplémentaire après chargement complet
    window.addEventListener('load', function() {
        ensureMenuClosed();
    });
    
    // Exposer les fonctions globalement pour éviter les conflits
    window.closeMobileMenu = closeSidebar;
    window.openMobileMenu = openSidebar;
})();
</script> 