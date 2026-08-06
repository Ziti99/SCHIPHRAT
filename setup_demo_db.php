<?php
// setup_demo_db.php
// Usage (CLI): php setup_demo_db.php
// Or open in browser (only in debug/local): http://localhost:8000/setup_demo_db.php

$root = __DIR__;
require_once __DIR__ . '/vendor/autoload.php';

// Load env similar to Database.php
$envFile = $root . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        if (getenv($k) === false && !isset($_ENV[$k])) putenv("$k=$v");
    }
}

$appDebug = filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'true'), FILTER_VALIDATE_BOOLEAN);
$remoteOk = PHP_SAPI === 'cli' || ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') === '127.0.0.1' || ($_SERVER['REMOTE_ADDR'] ?? '') === '::1';
if (!$appDebug && !$remoteOk) {
    http_response_code(403);
    echo "Not allowed\n";
    exit;
}

$dbDir = $root . '/database';
if (!is_dir($dbDir)) mkdir($dbDir, 0755, true);
$dbFile = $dbDir . '/clinique.db';

try {
    $dsn = "sqlite:" . $dbFile;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create minimal users table if missing (compatible with src/Models/User.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'medecin',
        nom TEXT,
        prenom TEXT,
        email TEXT,
        created_at TEXT DEFAULT (datetime('now'))
    );");

    // Ensure seed admin exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    $count = (int) $stmt->fetchColumn();
    if ($count === 0) {
        $pass = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role, nom, prenom, email) VALUES (?,?,?,?,?,?)');
        $ins->execute(['admin', $pass, 'admin', 'Admin', 'Demo', 'admin@example.local']);
        $created = true;
    } else {
        $created = false;
    }

    $out = [];
    $out[] = "DB file: $dbFile";
    $out[] = "DB size: " . (file_exists($dbFile) ? filesize($dbFile) : 0) . " bytes";
    $out[] = $created ? "Admin account created: admin / password" : "Admin account already present";

    if (PHP_SAPI === 'cli') {
        echo implode("\n", $out) . "\n";
    } else {
        echo "<pre>" . htmlspecialchars(implode("\n", $out), ENT_QUOTES, 'UTF-8') . "</pre>";
    }

} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo "<pre>Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
    }
    http_response_code(500);
    exit(1);
}
