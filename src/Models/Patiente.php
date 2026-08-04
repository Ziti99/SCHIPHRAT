<?php

namespace Clinique\Models;

use Clinique\Config\Database;

class Patiente
{
    public static function all(int $limit = 100, int $offset = 0, string $search = ''): array
    {
        $db = Database::getInstance();
        
        if ($search !== '') {
            $like = "%$search%";
            return $db->fetchAll(
                "SELECT * FROM patientes WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? OR dossier_number LIKE ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [$like, $like, $like, $like, $limit, $offset]
            );
        }

        return $db->fetchAll(
            "SELECT * FROM patientes ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM patientes WHERE id = ? LIMIT 1", [$id]);
    }

    public static function create(array $data): int
    {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO patientes (dossier_number, nom, prenom, date_naissance, telephone, adresse, groupe_sanguin, antecedents, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['dossier_number'] ?? self::generateDossierNumber(),
                $data['nom'],
                $data['prenom'],
                $data['date_naissance'] ?? null,
                $data['telephone'] ?? null,
                $data['adresse'] ?? null,
                $data['groupe_sanguin'] ?? null,
                $data['antecedents'] ?? null,
                $data['created_by'] ?? null
            ]
        );
        return (int) $db->lastInsertId();
    }

    public static function count(): int
    {
        try {
            $db = Database::getInstance();
            return (int) $db->fetchColumn("SELECT COUNT(*) FROM patientes");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function countThisMonth(): int
    {
        try {
            $db = Database::getInstance();
            return (int) $db->fetchColumn("SELECT COUNT(*) FROM patientes WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function generateDossierNumber(): string
    {
        return 'DOS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function stats(): array
    {
        try {
            $db = Database::getInstance();
            $total = $db->fetchColumn("SELECT COUNT(*) FROM patientes");
            $thisMonth = $db->fetchColumn("SELECT COUNT(*) FROM patientes WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())");
            $groupeSanguin = $db->fetchAll("SELECT groupe_sanguin, COUNT(*) as total FROM patientes WHERE groupe_sanguin IS NOT NULL GROUP BY groupe_sanguin");
            return [
                'total' => (int)$total,
                'this_month' => (int)$thisMonth,
                'by_blood' => $groupeSanguin
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'this_month' => 0, 'by_blood' => []];
        }
    }
}
