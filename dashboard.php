<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Models\Patiente;
use Clinique\Models\User;

Auth::requireAuth();
$user = Auth::user();

require_once __DIR__ . '/includes/layout.php';

// Récupération stats (avec gestion d'erreur si tables non existantes)
try {
    $patienteStats = Patiente::stats();
    $userRoles = User::countByRole();
    $totalPatientes = $patienteStats['total'] ?? 0;
    $newThisMonth = $patienteStats['this_month'] ?? 0;
} catch (Throwable $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $totalPatientes = 0;
    $newThisMonth = 0;
    $userRoles = [];
    $patienteStats = ['total'=>0,'this_month'=>0,'by_blood'=>[]];
}

layout_header("Tableau de bord", $user);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Bonjour, <?= e($user['prenom']) ?> 👋</h1>
        <p class="text-gray-600 mt-1">Voici l'activité de votre clinique aujourd'hui – <span class="capitalize"><?= e($user['role']) ?></span></p>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Patientes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= $totalPatientes ?></p>
                    <p class="text-xs text-green-600 mt-2"><i class="fas fa-arrow-up mr-1"></i>+<?= $newThisMonth ?> ce mois</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-female text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Consultations</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">—</p>
                    <p class="text-xs text-gray-400 mt-2">Bientôt disponible</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-stethoscope text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Accouchements</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">—</p>
                    <p class="text-xs text-gray-400 mt-2">Bientôt disponible</p>
                </div>
                <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-baby text-pink-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Utilisateurs</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= array_sum(array_column($userRoles, 'total')) ?: '-' ?></p>
                    <p class="text-xs text-gray-400 mt-2"><?= count($userRoles) ?> rôles</p>
                </div>
                <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-cyan-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Actions rapides -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <a href="/patientes.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-purple-300 hover:bg-purple-50 transition group">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 mr-4">
                        <i class="fas fa-user-plus text-purple-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Nouvelle patiente</p>
                        <p class="text-xs text-gray-500">Créer un dossier</p>
                    </div>
                </a>
                <a href="/consultations.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition group">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 mr-4">
                        <i class="fas fa-notes-medical text-blue-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Consultation</p>
                        <p class="text-xs text-gray-500">Suivi prénatal</p>
                    </div>
                </a>
                <a href="/rapports.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-pink-300 hover:bg-pink-50 transition group">
                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center group-hover:bg-pink-200 mr-4">
                        <i class="fas fa-file-pdf text-pink-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Rapports</p>
                        <p class="text-xs text-gray-500">Exports PDF/Excel</p>
                    </div>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="/users.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-cyan-300 hover:bg-cyan-50 transition group">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center group-hover:bg-cyan-200 mr-4">
                        <i class="fas fa-user-shield text-cyan-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Gérer utilisateurs</p>
                        <p class="text-xs text-gray-500">Admin uniquement</p>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profil + Secu -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Mon profil</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Utilisateur</span><span class="font-medium"><?= e($user['username']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Rôle</span><span class="capitalize font-medium px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs"><?= e($user['role']) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Nom complet</span><span class="font-medium"><?= e($user['prenom'] . ' ' . $user['nom']) ?></span></div>
                    <div class="pt-3 border-t">
                        <a href="/logout.php" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-red-50 hover:text-red-600 rounded-lg transition text-sm">
                            <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                <h3 class="font-semibold mb-2"><i class="fas fa-shield-alt mr-2"></i>Système sécurisé</h3>
                <ul class="text-xs text-purple-100 space-y-1">
                    <li>✓ Sessions chiffrées HttpOnly</li>
                    <li>✓ Protection CSRF & Rate-limiting</li>
                    <li>✓ Mots de passe bcrypt cost 12</li>
                    <li>✓ Variables d'environnement</li>
                </ul>
                <div class="mt-4 text-[11px] text-purple-200">
                    Dernière activité: <?= date('H:i', $_SESSION['last_activity'] ?? time()) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Améliorations apportées -->
    <div class="mt-8 bg-amber-50 border border-amber-200 rounded-xl p-4">
        <h4 class="text-sm font-semibold text-amber-800"><i class="fas fa-tools mr-2"></i>Améliorations effectuées</h4>
        <ul class="text-xs text-amber-700 mt-2 grid sm:grid-cols-2 gap-1 list-disc list-inside">
            <li>Suppression credentials hardcodés</li>
            <li>Env via vlucas/phpdotenv</li>
            <li>Singleton Database corrigé + PSR-4</li>
            <li>Auth service avec regenerate_id + httponly</li>
            <li>CSRF tokens + Rate limiting</li>
            <li>Dashboard + Logout + RBAC</li>
            <li>Schéma SQL complet + seeds</li>
            <li>Dockerfile durci multi-stage</li>
            <li>.gitignore + .env.example complet</li>
        </ul>
    </div>
</div>

<?php layout_footer(); ?>
