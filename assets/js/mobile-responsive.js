/**
 * Améliorations JavaScript pour le responsive mobile
 * Clinique SHIPHRAT
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Détection mobile
    const isMobile = window.innerWidth < 768;
    
    // Amélioration du scroll du sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        // Smooth scroll
        sidebar.style.scrollBehavior = 'smooth';
        
        // Sur mobile, fermer le menu en touchant en dehors
        const overlay = document.getElementById('mobileMenuOverlay');
        if (overlay) {
            overlay.addEventListener('touchstart', function(e) {
                e.preventDefault();
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = 'auto';
            });
        }
    }
    
    // Amélioration du scroll des tableaux sur mobile
    if (isMobile) {
        const tables = document.querySelectorAll('.overflow-x-auto');
        tables.forEach(table => {
            // Ajouter un indicateur de scroll
            const indicator = document.createElement('div');
            indicator.className = 'text-xs text-gray-500 text-center py-2 bg-blue-50 border-t border-blue-200';
            indicator.innerHTML = '<i class="fas fa-arrows-alt-h mr-2"></i>Glissez horizontalement pour voir plus';
            table.parentNode.insertBefore(indicator, table);
            
            // Cacher l'indicateur après le premier scroll
            table.addEventListener('scroll', function() {
                indicator.style.display = 'none';
            }, { once: true });
        });
    }
    
    // Fermeture automatique du menu mobile après clic
    const sidebarLinks = document.querySelectorAll('#sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobileMenuOverlay');
                if (sidebar && overlay) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }
            }
        });
    });
    
    // Amélioration des formulaires sur mobile
    if (isMobile) {
        // Ajouter une classe pour les inputs sur mobile
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.add('mobile-input');
        });
    }
    
    // Gestion du clavier virtuel sur iOS
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        const inputs = document.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Scroll vers l'input pour éviter qu'il soit caché par le clavier
                setTimeout(() => {
                    this.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            });
        });
    }
    
    console.log('📱 Améliorations mobile chargées - Device: ' + (isMobile ? 'Mobile' : 'Desktop'));
});

// Fonction pour détecter l'orientation
window.addEventListener('orientationchange', function() {
    console.log('📱 Orientation changée');
    // Fermer le sidebar si ouvert
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileMenuOverlay');
    if (sidebar && overlay) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
});

