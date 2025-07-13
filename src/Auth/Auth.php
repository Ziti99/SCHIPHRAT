<?php

namespace Clinique\Auth;

use Clinique\Config\Database;

class Auth
{
    private $db;
    private $user = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initSession();
        $this->checkSession();
    }

    private function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function checkSession()
    {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->db->fetch(
                "SELECT * FROM users WHERE id = ? AND is_active = 1",
                [$_SESSION['user_id']]
            );
        }
    }

    public function login($username, $password)
    {
        error_log("Tentative login: username='$username', password='$password'");
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE username = ? AND is_active = 1",
            [$username]
        );
        error_log("User trouvé: " . print_r($user, true));
        if ($user) {
            error_log("Password hash en base: " . $user['password']);
            $verify = password_verify($password, $user['password']);
            error_log("password_verify: " . ($verify ? 'OK' : 'ECHEC'));
        }
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $this->user = $user;
            // Générer un token de session
            $token = bin2hex(random_bytes(32));
            $this->db->query(
                "INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))",
                [$user['id'], $token]
            );
            return true;
        }
        return false;
    }

    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            $this->db->query(
                "DELETE FROM sessions WHERE user_id = ?",
                [$_SESSION['user_id']]
            );
        }
        
        session_destroy();
        $this->user = null;
    }

    public function isLoggedIn()
    {
        return $this->user !== null;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function hasRole($role)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $this->user['role'] === $role;
    }

    public function hasAnyRole($roles)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return in_array($this->user['role'], $roles);
    }

    public function requireAuth()
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function requireRole($role)
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            header('Location: /unauthorized.php');
            exit;
        }
    }

    public function requireAnyRole($roles)
    {
        $this->requireAuth();
        
        if (!$this->hasAnyRole($roles)) {
            header('Location: /unauthorized.php');
            exit;
        }
    }

    public function getCurrentUserId()
    {
        return $this->user ? $this->user['id'] : null;
    }

    public function getCurrentUserRole()
    {
        return $this->user ? $this->user['role'] : null;
    }

    public function getCurrentUserName()
    {
        return $this->user ? $this->user['nom'] . ' ' . $this->user['prenom'] : '';
    }
} 