<?php

namespace Clinique\Models;

use Clinique\Config\Database;
use PDO;

class User
{
    public int $id;
    public string $username;
    public string $role;
    public string $nom;
    public string $prenom;
    public ?string $email;
    public string $created_at;

    private const ALLOWED_ROLES = ['admin', 'medecin', 'sagefemme', 'secretaire', 'caissier'];

    public static function findByUsername(string $username): ?array
    {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT id, username, password_hash, role, nom, prenom, email, created_at FROM users WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT id, username, role, nom, prenom, email, created_at FROM users WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public static function all(int $limit = 100, int $offset = 0): array
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT id, username, role, nom, prenom, email, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public static function create(array $data): int
    {
        if (!in_array($data['role'], self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException("Rôle invalide");
        }

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO users (username, password_hash, role, nom, prenom, email) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['username'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['role'],
                $data['nom'],
                $data['prenom'],
                $data['email'] ?? null
            ]
        );
        return (int) $db->lastInsertId();
    }

    public static function countByRole(): array
    {
        try {
            $db = Database::getInstance();
            return $db->fetchAll("SELECT role, COUNT(*) as total FROM users GROUP BY role");
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ALLOWED_ROLES, true);
    }

    public static function getAllowedRoles(): array
    {
        return self::ALLOWED_ROLES;
    }
}
