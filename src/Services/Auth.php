<?php

namespace Clinique\Services;

use Clinique\Config\Database;
use Clinique\Helpers\Security;
use Clinique\Models\User;

class Auth
{
    public static function initSecureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? null) == 443;
            $secure = filter_var($_ENV['SESSION_SECURE'] ?? $_SERVER['SESSION_SECURE'] ?? $isHttps, FILTER_VALIDATE_BOOLEAN);
            $samesite = $_ENV['SESSION_SAMESITE'] ?? 'Lax';
            $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 0);

            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => $samesite
            ]);

            session_start();
        }
    }

    public static function attempt(string $username, string $password): array
    {
        self::initSecureSession();

        // Rate limiting
        $maxAttempts = (int) ($_ENV['LOGIN_MAX_ATTEMPTS'] ?? 5);
        $lockoutMinutes = (int) ($_ENV['LOGIN_LOCKOUT_MINUTES'] ?? 15);

        if (Security::isRateLimited('login', $maxAttempts, $lockoutMinutes)) {
            $remaining = Security::getRemainingLockout('login', $lockoutMinutes);
            return [
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans $remaining minute(s)."
            ];
        }

        if (empty($username) || empty($password)) {
            Security::incrementAttempts('login');
            return ['success' => false, 'message' => 'Veuillez remplir tous les champs.'];
        }

        try {
            $user = User::findByUsername($username);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                Security::incrementAttempts('login');
                // Délai anti brute-force
                sleep(1);
                return ['success' => false, 'message' => 'Identifiants incorrects.'];
            }

            // Succès – réinitialiser tentatives et régénérer session (anti fixation)
            Security::resetAttempts('login');
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email'] = $user['email'] ?? null;
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Rehash si nécessaire (cost augmenté)
            if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $db = Database::getInstance();
                $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $user['id']]);
            }

            return ['success' => true, 'user' => $user];
        } catch (\Throwable $e) {
            error_log("Auth error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.'];
        }
    }

    public static function check(): bool
    {
        self::initSecureSession();

        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Vérification timeout inactivité (2h)
        $maxInactivity = 2 * 3600;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxInactivity)) {
            self::logout();
            return false;
        }

        // Vérification vol de session basique (IP + User-Agent)
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // On tolère changement IP (mobile) mais on log
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUa) {
            error_log("Possible session hijack: UA mismatch for user {$_SESSION['user_id']}");
            // Optionnel: self::logout(); return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['user_role'],
            'nom' => $_SESSION['user_nom'],
            'prenom' => $_SESSION['user_prenom'],
            'email' => $_SESSION['user_email'] ?? null
        ];
    }

    public static function logout(): void
    {
        self::initSecureSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard.php'));
            exit;
        }
    }

    public static function requireRole(array $roles): void
    {
        self::requireAuth();
        $userRole = $_SESSION['user_role'] ?? '';
        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            die("Accès refusé. Rôle requis: " . implode(', ', $roles));
        }
    }

    public static function hasRole(string $role): bool
    {
        return ($_SESSION['user_role'] ?? '') === $role;
    }

    public static function hasAnyRole(array $roles): bool
    {
        return in_array($_SESSION['user_role'] ?? '', $roles, true);
    }
}
