<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Models\User;
use Clinique\Helpers\Security;

Auth::requireRole(['admin']);
$user = Auth::user();
require_once __DIR__ . '/includes/layout.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $err = 'CSRF invalide';
    } else {
        try {
            $data = [
                'username' => trim($_POST['username']),
                'password' => $_POST['password'],
                'role' => $_POST['role'],
                'nom' => strtoupper(trim($_POST['nom'])),
                'prenom' => ucfirst(trim($_POST['prenom'])),
                'email' => trim($_POST['email']) ?: null
            ];
            if (strlen($data['password']) < 8) throw new Exception("Mot de passe min 8 caractères");
            User::create($data);
            $msg = "Utilisateur {$data['username']} créé";
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }
    }
}

try {
    $users = User::all(100);
} catch (Throwable $e) {
    $users = [];
    $err = "DB non initialisée";
}

layout_header("Utilisateurs", $user);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold"><i class="fas fa-users mr-2 text-cyan-500"></i>Gestion utilisateurs (Admin)</h1>
        <button onclick="document.getElementById('m').classList.toggle('hidden')" class="bg-gray-900 text-white px-5 py-2.5 rounded-lg"><i class="fas fa-plus mr-2"></i>Nouvel utilisateur</button>
    </div>

    <?php if($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-lg mb-4"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg mb-4"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-6 py-3 text-left">Username</th><th class="px-6 py-3">Nom</th><th class="px-6 py-3">Rôle</th><th class="px-6 py-3">Email</th><th class="px-6 py-3">Créé</th></tr></thead>
            <tbody class="divide-y">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-gray-50"><td class="px-6 py-3 font-mono text-sm"><?= e($u['username']) ?></td><td class="px-6 py-3"><?= e($u['prenom'].' '.$u['nom']) ?></td><td class="px-6 py-3"><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs capitalize"><?= e($u['role']) ?></span></td><td class="px-6 py-3 text-sm"><?= e($u['email'] ?? '-') ?></td><td class="px-6 py-3 text-xs text-gray-500"><?= e($u['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="m" class="hidden fixed inset-0 z-50 overflow-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/30" onclick="document.getElementById('m').classList.add('hidden')"></div>
            <div class="bg-white rounded-2xl p-6 w-full max-w-lg relative z-10">
                <h3 class="font-bold mb-4">Créer utilisateur</h3>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(Security::generateCsrfToken()) ?>">
                    <div class="grid grid-cols-2 gap-3"><input name="nom" placeholder="Nom" required class="border p-2.5 rounded-lg"><input name="prenom" placeholder="Prénom" required class="border p-2.5 rounded-lg"></div>
                    <input name="username" placeholder="Username" required class="w-full border p-2.5 rounded-lg">
                    <input type="email" name="email" placeholder="Email (optionnel)" class="w-full border p-2.5 rounded-lg">
                    <select name="role" class="w-full border p-2.5 rounded-lg"><option value="secretaire">Secrétaire</option><option value="medecin">Médecin</option><option value="sagefemme">Sage-femme</option><option value="caissier">Caissier</option><option value="admin">Admin</option></select>
                    <input type="password" name="password" placeholder="Mot de passe min 8" required class="w-full border p-2.5 rounded-lg">
                    <div class="flex justify-end gap-2"><button type="button" onclick="document.getElementById('m').classList.add('hidden')" class="px-4 py-2 bg-gray-100 rounded-lg">Annuler</button><button class="px-5 py-2 bg-gray-900 text-white rounded-lg">Créer</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php layout_footer(); ?>
