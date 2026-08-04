<?php
// Layout helpers
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function layout_header(string $title = "Clinique Obstétrique", array $user = null): void {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#EC4899',
                        accent: '#06B6D4'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php if ($user): ?>
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
                        <a href="/dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-home mr-2"></i>Dashboard</a>
                        <a href="/patientes.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-female mr-2"></i>Patientes</a>
                        <a href="/consultations.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-stethoscope mr-2"></i>Consultations</a>
                        <a href="/rapports.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-chart-bar mr-2"></i>Rapports</a>
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <a href="/users.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50 hover:text-purple-700"><i class="fas fa-users mr-2"></i>Utilisateurs</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden sm:block text-right">
                        <p class="text-sm font-medium text-gray-900"><?= e($user['prenom'] . ' ' . $user['nom']) ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= e($user['role']) ?></p>
                    </div>
                    <div class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        <?= e(strtoupper(substr($user['prenom'] ?? 'U', 0, 1))) ?>
                    </div>
                    <a href="/logout.php" class="text-gray-400 hover:text-red-500 p-2"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main>
<?php
}

function layout_footer(): void {
?>
    </main>
    <footer class="bg-white border-t border-gray-100 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">
            &copy; <?= date('Y') ?> Clinique Obstétrique. Système sécurisé.
        </div>
    </footer>
</body>
</html>
<?php
}
