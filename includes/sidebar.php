<?php
// Pas besoin d'autoloader, on utilise les sessions
// session_start(); // SUPPRIMÉ car déjà démarré dans dashboard.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
?>
<!-- Overlay pour fermer le menu sur mobile -->
<div id="mobileMenuOverlay" class="hidden lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40"></div>

<!-- Sidebar responsive -->
<aside id="sidebar" class="w-64 bg-white shadow-lg min-h-screen fixed lg:static transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 top-0 lg:top-auto">
    <!-- Bouton fermer (mobile uniquement) -->
    <button id="closeSidebarButton" class="lg:hidden absolute top-4 right-4 text-gray-600 hover:text-red-600">
        <i class="fas fa-times text-2xl"></i>
    </button>
    
    <nav class="mt-16 lg:mt-8">
        <div class="px-4 space-y-2">
            <a href="/dashboard.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-tachometer-alt mr-3"></i>
                Dashboard
            </a>
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
            <?php if ($_SESSION['user_role'] === 'secretaire'): ?>
            <a href="/permanence.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                <i class="fas fa-calendar-day mr-3"></i>
                Permanence du Jour
            </a>
            <?php endif; ?>
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'caissiere'): ?>
            <div class="border-t border-gray-200 pt-4 mt-4">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Permanences</p>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <a href="/permanences.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                            <i class="fas fa-check-circle mr-3"></i>
                            Validation permanences
                </a>
                <?php endif; ?>
                                        <a href="/permanences_vue.php" class="flex items-center px-4 py-3 text-gray-600 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors">
                            <i class="fas fa-eye mr-3"></i>
                            Vue des permanences
                </a>
            </div>
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
            <?php endif; ?>
        </div>
    </nav>
</aside>

<!-- Script pour le menu mobile responsive -->
<script>
    // Gestion du menu mobile
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const closeSidebarButton = document.getElementById('closeSidebarButton');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');
    
    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Empêcher le scroll
    }
    
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Réactiver le scroll
    }
    
    // Ouvrir le menu
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', openSidebar);
    }
    
    // Fermer le menu
    if (closeSidebarButton) {
        closeSidebarButton.addEventListener('click', closeSidebar);
    }
    
    // Fermer en cliquant sur l'overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Fermer le menu après un clic sur un lien (mobile uniquement)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) { // lg breakpoint
                closeSidebar();
            }
        });
    });
    
    console.log('📱 Menu mobile responsive initialisé');
</script> 