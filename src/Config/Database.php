<?php

namespace Clinique\Config;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;
    private array $config;

    private function __construct()
    {
        $this->loadEnv();
        $this->config = $this->resolveConfig();
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Charge .env via vlucas/phpdotenv si disponible, sinon fallback maison sécurisé
     */
    private function loadEnv(): void
    {
        $root = dirname(__DIR__, 2); // SCHIPHRAT/
        $envFile = $root . '/.env';

        // 1. Essaye vlucas/phpdotenv (recommandé)
        if (file_exists($root . '/vendor/autoload.php')) {
            require_once $root . '/vendor/autoload.php';
            if (class_exists(\Dotenv\Dotenv::class)) {
                try {
                    $dotenv = \Dotenv\Dotenv::createImmutable($root);
                    $dotenv->safeLoad();
                    return;
                } catch (\Throwable $e) {
                    // fallback ci-dessous
                }
            }
        }

        // 2. Fallback manuel sécurisé (ne casse pas sur "=" dans la valeur)
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            // Supporte KEY="valeur avec = et espaces"
            if (strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Supprime guillemets autour
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            // N'écrase pas si déjà défini dans l'environnement réel (Railway, Docker)
            if (getenv($key) === false && !isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    private function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }

    private function resolveConfig(): array
    {
        // Choix plateforme: sqlite pour démo locale sans serveur, mysql pour prod
        $connection = $this->env('DB_CONNECTION') ?? $this->env('DB_DRIVER') ?? 'mysql';
        
        // Supporte à la fois les variables Railway (MYSQL*) et les standards DB_*
        $host = $this->env('DB_HOST') ?? $this->env('MYSQLHOST') ?? $this->env('MYSQL_HOST') ?? '127.0.0.1';
        $port = $this->env('DB_PORT') ?? $this->env('MYSQLPORT') ?? $this->env('MYSQL_PORT') ?? '3306';
        $dbname = $this->env('DB_NAME') ?? $this->env('MYSQLDATABASE') ?? $this->env('MYSQL_DATABASE') ?? 'clinique_obstetrique';
        $user = $this->env('DB_USER') ?? $this->env('MYSQLUSER') ?? $this->env('MYSQL_USER') ?? 'root';
        $password = $this->env('DB_PASSWORD') ?? $this->env('MYSQLPASSWORD') ?? $this->env('MYSQL_PASSWORD') ?? '';
        $charset = $this->env('DB_CHARSET', 'utf8mb4');
        $sqlitePath = $this->env('DB_DATABASE') ?? $this->env('DB_SQLITE_PATH') ?? dirname(__DIR__, 2) . '/database/clinique.db';

        // Nettoyage port qui peut contenir host:port dans certaines configs Railway
        if (str_contains($host, ':')) {
            [$hostOnly, $portFromHost] = explode(':', $host, 2);
            $host = $hostOnly;
            if (is_numeric($portFromHost)) {
                $port = $portFromHost;
            }
        }

        return [
            'connection' => $connection,
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'user' => $user,
            'password' => $password,
            'charset' => $charset,
            'sqlite_path' => $sqlitePath,
        ];
    }

    private function connect(): void
    {
        $c = $this->config;

        // SQLite pour démo locale / tests sans serveur MySQL
        if (($c['connection'] ?? 'mysql') === 'sqlite') {
            $sqlitePath = $c['sqlite_path'];
            // Si DB_DATABASE contient :memory: ou un fichier .db
            if ($sqlitePath === ':memory:' || str_ends_with($sqlitePath, '.db') || str_ends_with($sqlitePath, '.sqlite')) {
                $dsn = "sqlite:" . $sqlitePath;
            } else {
                // Si DB_NAME est un chemin sqlite
                if (str_ends_with($c['dbname'], '.db') || str_ends_with($c['dbname'], '.sqlite') || $c['dbname'] === ':memory:') {
                    $dsn = "sqlite:" . $c['dbname'];
                } else {
                    $dsn = "sqlite:" . $sqlitePath;
                }
            }
        } else {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $c['host'],
                $c['port'],
                $c['dbname'],
                $c['charset']
            );
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        try {
            if (($c['connection'] ?? 'mysql') === 'sqlite') {
                $this->connection = new PDO($dsn, null, null, $options);
                // Activer FK pour SQLite
                $this->connection->exec("PRAGMA foreign_keys = ON;");
            } else {
                $this->connection = new PDO($dsn, $c['user'], $c['password'], $options);
            }
        } catch (PDOException $e) {
            // Ne jamais exposer le message PDO en production
            error_log("DB Connection failed [$dsn]: " . $e->getMessage());
            $isDebug = filter_var($this->env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
            if ($isDebug) {
                throw $e;
            }
            throw new PDOException("Erreur de connexion à la base de données [$dsn]. Vérifiez la configuration .env");
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    // Empêche le clonage
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Pour debug / santé – ne retourne jamais le password
     */
    public function getConfigSafe(): array
    {
        $safe = $this->config;
        $safe['password'] = str_repeat('*', 8);
        return $safe;
    }
}
