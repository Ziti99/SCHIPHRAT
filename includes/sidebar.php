<?php
// Pas besoin d'autoloader, on utilise les sessions
// session_start(); // SUPPRIMÉ car déjà démarré dans dashboard.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
?>
<aside class="w-64 bg-white shadow-lg min-h-screen">
    <nav class="mt-8">
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

<!-- Script de debug pour la sidebar -->
<script>
    console.log('🔍 DEBUG: Sidebar chargée');
    console.log('👤 User role:', '<?php echo $_SESSION['user_role']; ?>');
    console.log('🔐 User logged in:', <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);
    
    // Log spécifique pour le lien Patientes
    const patientesLink = document.getElementById('sidebar-patientes-link');
    if (patientesLink) {
        console.log('🔗 Lien Patientes trouvé:', patientesLink.href);
        console.log('📝 Texte du lien:', patientesLink.textContent.trim());
        
        // Intercepter le clic sur le lien Patientes
        patientesLink.addEventListener('click', function(e) {
            console.log('🖱️ CLIC sur lien Patientes dans sidebar');
            console.log('📍 URL de destination:', this.href);
            console.log('⏰ Timestamp:', new Date().toISOString());
            console.log('🎯 Élément cliqué:', e.target);
            console.log('📱 Événement complet:', e);
            
            // Vérifier si le lien est valide
            if (!this.href || this.href === '#' || this.href === window.location.href) {
                console.error('❌ Lien Patientes invalide:', this.href);
                e.preventDefault();
                return false;
            }
            
            console.log('✅ Lien Patientes valide, navigation autorisée');
        });
    } else {
        console.error('❌ Lien Patientes non trouvé dans la sidebar');
    }
    
    // Log tous les liens de la sidebar
    const sidebarLinks = document.querySelectorAll('aside a');
    console.log('🔗 Total liens sidebar:', sidebarLinks.length);
    sidebarLinks.forEach((link, index) => {
        console.log(`🔗 Sidebar lien ${index + 1}:`, link.href, link.textContent.trim());
    });
</script> 