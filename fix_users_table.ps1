# Script PowerShell pour corriger la table users
# Utilise MySQL .NET Connector

Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "  Script de correction de la table users - Clinique Obstétrique" -ForegroundColor Cyan
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host ""

# Configuration de la base de données
$dbHost = "metro.proxy.rlwy.net"
$dbPort = 29698
$dbName = "railway"
$dbUser = "root"
$dbPassword = "UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI"

# Mot de passe par défaut (hash de 'password')
$defaultPasswordHash = '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy'

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Host: $dbHost" -ForegroundColor Gray
Write-Host "  Port: $dbPort" -ForegroundColor Gray
Write-Host "  Database: $dbName" -ForegroundColor Gray
Write-Host ""

# Créer les requêtes SQL
$queries = @"
-- Vérifier et ajouter la colonne password
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = '$dbName' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'password'
);

-- Ajouter la colonne si elle n'existe pas
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email', 
    'SELECT ''Column password already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mettre à jour tous les utilisateurs avec le mot de passe par défaut
UPDATE users SET password = '$defaultPasswordHash' WHERE password IS NULL OR password = '';

-- Afficher les utilisateurs
SELECT id, username, email, role, nom, prenom, 
       CASE WHEN LENGTH(password) > 0 THEN 'OK' ELSE 'Missing' END as password_status
FROM users;
"@

# Fonction pour exécuter MySQL via ligne de commande
function Invoke-MySqlQuery {
    param (
        [string]$Query
    )
    
    $connectionString = "Server=$dbHost;Port=$dbPort;Database=$dbName;Uid=$dbUser;Pwd=$dbPassword;"
    
    # Essayer avec mysql.exe si disponible
    $mysqlPath = Get-Command mysql.exe -ErrorAction SilentlyContinue
    
    if ($mysqlPath) {
        Write-Host "✓ MySQL client trouvé" -ForegroundColor Green
        $tempFile = [System.IO.Path]::GetTempFileName()
        $Query | Out-File -FilePath $tempFile -Encoding UTF8
        
        $output = & mysql.exe -h $dbHost -P $dbPort -u $dbUser -p$dbPassword -D $dbName -e $Query 2>&1
        Remove-Item $tempFile -ErrorAction SilentlyContinue
        
        return $output
    } else {
        Write-Host "❌ MySQL client non trouvé" -ForegroundColor Red
        Write-Host ""
        Write-Host "Pour installer MySQL client:" -ForegroundColor Yellow
        Write-Host "  1. Via Chocolatey: choco install mysql-cli" -ForegroundColor Gray
        Write-Host "  2. Via XAMPP/WAMP/Laragon (inclut MySQL)" -ForegroundColor Gray
        Write-Host ""
        return $null
    }
}

# Essayer d'exécuter les requêtes
Write-Host "Tentative de connexion à la base de données..." -ForegroundColor Yellow

$result = Invoke-MySqlQuery -Query $queries

if ($result) {
    Write-Host ""
    Write-Host "✅ Succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Résultat:" -ForegroundColor Cyan
    Write-Host $result
    Write-Host ""
    Write-Host "Vous pouvez maintenant vous connecter avec:" -ForegroundColor Green
    Write-Host "  Username: admin" -ForegroundColor White
    Write-Host "  Password: password" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "==================================================================" -ForegroundColor Yellow
    Write-Host "  SOLUTION ALTERNATIVE - Exécuter manuellement sur Railway" -ForegroundColor Yellow
    Write-Host "==================================================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Copiez et exécutez ces commandes SQL sur Railway:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "-- 1. Ajouter la colonne password" -ForegroundColor Gray
    Write-Host "ALTER TABLE users ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL AFTER email;" -ForegroundColor White
    Write-Host ""
    Write-Host "-- 2. Mettre à jour les mots de passe" -ForegroundColor Gray
    Write-Host "UPDATE users SET password = '$defaultPasswordHash';" -ForegroundColor White
    Write-Host ""
    Write-Host "-- 3. Vérifier" -ForegroundColor Gray
    Write-Host "SELECT id, username, email, role, LENGTH(password) as pwd_length FROM users;" -ForegroundColor White
    Write-Host ""
    Write-Host "Accédez à Railway: https://railway.app/" -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host ""

