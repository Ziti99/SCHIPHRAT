<?php
/**
 * Middleware d'authentification – à inclure en haut de chaque page protégée
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Services\Auth;

Auth::initSecureSession();

// Vérifie si l'utilisateur est connecté pour les pages protégées
function require_login() {
    \Clinique\Services\Auth::requireAuth();
}

function require_roles(array $roles) {
    \Clinique\Services\Auth::requireRole($roles);
}

function current_user() {
    return \Clinique\Services\Auth::user();
}

function is_logged_in(): bool {
    return \Clinique\Services\Auth::check();
}

function has_role(string $role): bool {
    return \Clinique\Services\Auth::hasRole($role);
}
