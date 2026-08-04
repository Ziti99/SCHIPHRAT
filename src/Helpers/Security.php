<?php

namespace Clinique\Helpers;

class Security
{
    /**
     * Nettoie une entrée utilisateur
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Génère un token CSRF
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiting simple basé sur session + IP
     */
    public static function isRateLimited(string $key = 'login', int $maxAttempts = 5, int $lockoutMinutes = 15): bool
    {
        $attemptKey = $key . '_attempts';
        $timeKey = $key . '_last_attempt';

        $attempts = $_SESSION[$attemptKey] ?? 0;
        $lastAttempt = $_SESSION[$timeKey] ?? 0;

        if ($attempts >= $maxAttempts) {
            $elapsed = time() - $lastAttempt;
            if ($elapsed < ($lockoutMinutes * 60)) {
                return true; // Toujours bloqué
            } else {
                // Reset après le délai
                $_SESSION[$attemptKey] = 0;
                return false;
            }
        }
        return false;
    }

    public static function incrementAttempts(string $key = 'login'): void
    {
        $_SESSION[$key . '_attempts'] = ($_SESSION[$key . '_attempts'] ?? 0) + 1;
        $_SESSION[$key . '_last_attempt'] = time();
    }

    public static function resetAttempts(string $key = 'login'): void
    {
        $_SESSION[$key . '_attempts'] = 0;
        $_SESSION[$key . '_last_attempt'] = 0;
    }

    public static function getRemainingLockout(string $key = 'login', int $lockoutMinutes = 15): int
    {
        $last = $_SESSION[$key . '_last_attempt'] ?? 0;
        $elapsed = time() - $last;
        $remaining = ($lockoutMinutes * 60) - $elapsed;
        return max(0, (int) ceil($remaining / 60));
    }
}
