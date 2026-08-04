<?php
/**
 * Fichier de compatibilité legacy - DEPRECATED
 * Utilisez \Clinique\Config\Database depuis src/Config/Database.php
 * Ce fichier existe uniquement pour éviter de casser les anciens includes.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Config\Database as NewDatabase;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $this->connection = NewDatabase::getInstance()->getConnection();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return NewDatabase::getInstance()->getConnection();
    }

    public function query($sql, $params = [])
    {
        return NewDatabase::getInstance()->query($sql, $params);
    }

    public function fetch($sql, $params = [])
    {
        return NewDatabase::getInstance()->fetch($sql, $params);
    }

    public function fetchAll($sql, $params = [])
    {
        return NewDatabase::getInstance()->fetchAll($sql, $params);
    }

    public function lastInsertId()
    {
        return NewDatabase::getInstance()->lastInsertId();
    }
}
