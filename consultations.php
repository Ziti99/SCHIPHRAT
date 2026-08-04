<?php
require_once __DIR__ . '/vendor/autoload.php';
use Clinique\Services\Auth;
Auth::requireAuth();
$user = Auth::user();
require_once __DIR__ . '/includes/layout.php';

layout_header("Consultations", $user);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-stethoscope text-blue-600 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Module Consultations</h1>
        <p class="text-gray-500 mt-2 max-w-xl mx-auto">Le module de suivi prénatal / postnatal est en cours de construction. La structure DB est prête (<code>consultations</code>, <code>accouchements</code>, <code>suivi_postnatal</code>).</p>
        <div class="mt-6 flex justify-center gap-3">
            <a href="/patientes.php" class="px-5 py-2.5 bg-purple-600 text-white rounded-lg">Voir patientes</a>
            <a href="/dashboard.php" class="px-5 py-2.5 bg-gray-100 rounded-lg">Dashboard</a>
        </div>
        <div class="mt-8 text-left bg-gray-50 rounded-lg p-4 max-w-2xl mx-auto">
            <h4 class="font-semibold text-sm mb-2">Prochaines étapes dev:</h4>
            <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                <li>CRUD consultations avec patiente_id</li>
                <li>Calcul automatique semaine grossesse</li>
                <li>Graphique évolution poids / tension</li>
                <li>Export PDF ordonnance via dompdf</li>
            </ul>
        </div>
    </div>
</div>
<?php layout_footer(); ?>
