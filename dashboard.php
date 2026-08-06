<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Models\Patiente;
use Clinique\Models\User;

Auth::initSecureSession();

// ⚠️ TEMPORAIRE: Simuler une session utilisateur sans login
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_nom'] = 'Admin';
    $_SESSION['user_prenom'] = 'Test';
}

$user = [
    'id' => $_SESSION['user_id'] ?? null,
    'username' => $_SESSION['username'] ?? 'Utilisateur',
    'role' => $_SESSION['user_role'] ?? 'user',
    'nom' => $_SESSION['user_nom'] ?? 'Test',
    'prenom' => $_SESSION['user_prenom'] ?? 'Admin'
];

try {
    $stats = Patiente::stats();
    $usersByRole = User::countByRole();
} catch (Exception $e) {
    $stats = ['total' => 0, 'new_this_month' => 0];
    $usersByRole = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/dashboard.php" class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <span class="font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Clinique</span>
                    </a>
                    <div class="hidden md:flex space-x-1">
                        <a href="/dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700 bg-purple-50 text-purple-700"><i class="fas fa-home mr-2"></i>Dashboard</a>
                        <a href="/patientes.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50"><i class="fas fa-female mr-2"></i>Patientes</a>
                        <a href="/consultations.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50"><i class="fas fa-stethoscope mr-2"></i>Consultations</a>
                    </div>
                </div>
                <a href="/logout.php" class="text-gray-400 hover:text-red-500 p-2"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </nav>
    <main>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Bonjour, <?= htmlspecialchars($user['prenom']) ?> 👋</h1>
                <p class="text-gray-600 mt-1">Bienvenue sur votre tableau de bord</p>
                <div class="mt-4 inline-flex items-center px-4 py-2 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    <i class="fas fa-lock-open mr-2"></i>
                    <strong>Mode test activé</strong> – Authentification contournée pour tester
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <p class="text-sm text-gray-500">Total Patientes</p>
                    <p class="text-3xl font-bold mt-1"><?= $stats['total'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <p class="text-sm text-gray-500">Consultations</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <p class="text-sm text-gray-500">Accouchements</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border">
                    <p class="text-sm text-gray-500">Utilisateurs</p>
                    <p class="text-3xl font-bold mt-1">0</p>
                </div>
            </div>
            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border p-6">
                    <h2 class="text-lg font-semibold mb-4">Actions rapides</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <a href="/patientes.php" class="flex items-center p-4 border rounded-lg hover:border-purple-300 hover:bg-purple-50">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-plus text-purple-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Ajouter Patiente</p>
                            </div>
                        </a>
                        <a href="/consultations.php" class="flex items-center p-4 border rounded-lg hover:border-blue-300 hover:bg-blue-50">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-plus text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-sm">Enregistrer Consultation</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-white border-t border-gray-100 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
            &copy; <?= date('Y') ?> Clinique Obstétrique – Mode test
        </div>
    </footer>
</body>
</html>