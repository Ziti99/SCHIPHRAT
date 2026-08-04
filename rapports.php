<?php
require_once __DIR__ . '/vendor/autoload.php';
use Clinique\Services\Auth;
use Clinique\Config\Database;

Auth::requireAuth();
$user = Auth::user();
require_once __DIR__ . '/includes/layout.php';

// Stats rapides
try {
    $db = Database::getInstance();
    $stats = [
        'patientes' => $db->fetchColumn("SELECT COUNT(*) FROM patientes"),
        'consultations' => $db->fetchColumn("SELECT COUNT(*) FROM consultations"),
        'accouchements' => $db->fetchColumn("SELECT COUNT(*) FROM accouchements"),
    ];
} catch (Throwable $e) {
    $stats = ['patientes'=>0,'consultations'=>0,'accouchements'=>0];
}

layout_header("Rapports", $user);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-6"><i class="fas fa-chart-bar mr-2 text-pink-500"></i>Rapports & Exports</h1>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold mb-2">Registre Patientes</h3>
            <p class="text-sm text-gray-500 mb-4"><?= $stats['patientes'] ?> dossiers</p>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm hover:bg-red-100"><i class="fas fa-file-pdf mr-1"></i> PDF (dompdf)</button>
                <button class="px-4 py-2 bg-green-50 text-green-700 rounded-lg text-sm hover:bg-green-100"><i class="fas fa-file-excel mr-1"></i> Excel</button>
            </div>
            <p class="text-[11px] text-gray-400 mt-3">Prêt – implémentation via Dompdf / PhpSpreadsheet dans <code>src/Services/ExportService.php</code></p>
        </div>
        <div class="bg-white p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold mb-2">Consultations mensuelles</h3>
            <p class="text-sm text-gray-500 mb-4"><?= $stats['consultations'] ?> consultations</p>
            <div class="h-24 bg-gray-50 rounded flex items-center justify-center text-xs text-gray-400">Graphique à venir (Chart.js)</div>
        </div>
        <div class="bg-white p-6 rounded-xl border shadow-sm">
            <h3 class="font-semibold mb-2">Accouchements</h3>
            <p class="text-sm text-gray-500 mb-4"><?= $stats['accouchements'] ?> accouchements</p>
            <div class="h-24 bg-gray-50 rounded flex items-center justify-center text-xs text-gray-400">Stats voie basse / césarienne</div>
        </div>
    </div>

    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
        <i class="fas fa-info-circle mr-2"></i>Les librairies <code>dompdf/dompdf</code> et <code>phpoffice/phpspreadsheet</code> sont déjà dans composer.json. Il suffit de créer <code>src/Services/ExportService.php</code> pour générer les exports.
    </div>
</div>
<?php layout_footer(); ?>
