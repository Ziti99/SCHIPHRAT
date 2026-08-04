<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Models\Patiente;
use Clinique\Helpers\Security;

Auth::requireAuth();
$user = Auth::user();
require_once __DIR__ . '/includes/layout.php';

$search = trim($_GET['search'] ?? '');
$message = '';
$error = '';

// Création
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Jeton CSRF invalide';
    } else {
        try {
            $data = [
                'nom' => strtoupper(trim($_POST['nom'] ?? '')),
                'prenom' => ucfirst(trim($_POST['prenom'] ?? '')),
                'date_naissance' => $_POST['date_naissance'] ?: null,
                'telephone' => trim($_POST['telephone'] ?? ''),
                'groupe_sanguin' => $_POST['groupe_sanguin'] ?: null,
                'adresse' => trim($_POST['adresse'] ?? ''),
                'antecedents' => trim($_POST['antecedents'] ?? ''),
                'created_by' => $user['id']
            ];

            if (empty($data['nom']) || empty($data['prenom'])) {
                throw new InvalidArgumentException("Nom et prénom obligatoires");
            }

            $id = Patiente::create($data);
            $message = "Patiente créée avec succès (ID: $id)";
        } catch (Throwable $e) {
            error_log("Patiente create error: " . $e->getMessage());
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

try {
    $patientes = Patiente::all(100, 0, $search);
    $total = Patiente::count();
} catch (Throwable $e) {
    $patientes = [];
    $total = 0;
    $error = "Base de données non initialisée. Importez database/schema.sql";
}

layout_header("Patientes", $user);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-female mr-3 text-purple-500"></i>Gestion des Patientes</h1>
            <p class="text-gray-500 text-sm mt-1"><?= $total ?> dossier(s) – Recherche: "<?= e($search) ?>"</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.toggle('hidden')" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-5 py-2.5 rounded-lg hover:shadow-lg transition">
            <i class="fas fa-plus mr-2"></i>Nouvelle patiente
        </button>
    </div>

    <?php if ($message): ?><div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4"><?= e($error) ?></div><?php endif; ?>

    <!-- Recherche -->
    <form method="GET" class="mb-6 flex gap-2">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Rechercher nom, prénom, téléphone, dossier..." class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
        <button type="submit" class="px-6 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-black"><i class="fas fa-search"></i></button>
        <?php if ($search): ?><a href="patientes.php" class="px-4 py-2.5 bg-gray-100 rounded-lg">Reset</a><?php endif; ?>
    </form>

    <!-- Liste -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Dossier</th>
                        <th class="px-6 py-3">Nom complet</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">GS</th>
                        <th class="px-6 py-3">Créé</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($patientes)): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">Aucune patiente – Importez database/schema.sql puis créez la première</td></tr>
                    <?php else: foreach ($patientes as $p): ?>
                        <tr class="hover:bg-purple-50/50">
                            <td class="px-6 py-4 font-mono text-xs font-medium text-purple-700"><?= e($p['dossier_number']) ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900"><?= e($p['nom'] . ' ' . $p['prenom']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($p['date_naissance'] ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm"><?= e($p['telephone'] ?? '-') ?><div class="text-xs text-gray-400 truncate max-w-[150px]"><?= e($p['adresse'] ?? '') ?></div></td>
                            <td class="px-6 py-4"><span class="px-2 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold"><?= e($p['groupe_sanguin'] ?? '-') ?></span></td>
                            <td class="px-6 py-4 text-xs text-gray-500"><?= e(date('d/m/Y', strtotime($p['created_at'] ?? 'now'))) ?></td>
                            <td class="px-6 py-4">
                                <a href="consultations.php?patiente_id=<?= $p['id'] ?>" class="text-purple-600 hover:text-purple-800 text-sm mr-2"><i class="fas fa-eye"></i></a>
                                <a href="#" class="text-gray-400 hover:text-gray-600 text-sm"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal creation -->
    <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/30" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">Nouvelle patiente</h3>
                    <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="csrf_token" value="<?= e(Security::generateCsrfToken()) ?>">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-1">Nom *</label><input required name="nom" class="w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500"></div>
                        <div><label class="block text-sm font-medium mb-1">Prénom *</label><input required name="prenom" class="w-full px-3 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-1">Date naissance</label><input type="date" name="date_naissance" class="w-full px-3 py-2.5 border rounded-lg"></div>
                        <div><label class="block text-sm font-medium mb-1">Téléphone</label><input name="telephone" placeholder="+241..." class="w-full px-3 py-2.5 border rounded-lg"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium mb-1">Groupe sanguin</label>
                            <select name="groupe_sanguin" class="w-full px-3 py-2.5 border rounded-lg"><option value="">--</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>AB+</option><option>AB-</option><option>O+</option><option>O-</option></select>
                        </div>
                        <div><label class="block text-sm font-medium mb-1">Adresse</label><input name="adresse" class="w-full px-3 py-2.5 border rounded-lg"></div>
                    </div>
                    <div><label class="block text-sm font-medium mb-1">Antécédents</label><textarea name="antecedents" rows="3" class="w-full px-3 py-2.5 border rounded-lg"></textarea></div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 rounded-lg">Annuler</button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg">Créer dossier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php layout_footer(); ?>
