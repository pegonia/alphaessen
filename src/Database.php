<?php
/**
 * Database.php - PDO Singleton Wrapper für SQLite
 * 
 * Diese Klasse stellt eine einfache Schnittstelle für den Datenbankzugriff bereit.
 * Sie verwendet das Singleton-Pattern, um eine einzige Datenbankverbindung
 * für die gesamte Anwendung zu gewährleisten.
 */

namespace Alphaessen;

use PDO;
use PDOException;

class Database
{
    /** @var PDO|null Die Datenbankverbindung */
    private static ?PDO $connection = null;

    /** @var array Die Datenbankkonfiguration */
    private static array $config;

    /**
     * Privater Konstruktor - verhindert direkte Instanzierung
     */
    private function __construct() {}

    /**
     * Initialisiert die Datenbankkonfiguration
     * 
     * @param array $config Die Datenbankkonfiguration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Gibt die Datenbankverbindung zurück (Singleton)
     * 
     * @return PDO Die PDO-Instanz
     * @throws PDOException Falls die Verbindung fehlschlägt
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }
        return self::$connection;
    }

    /**
     * Stellt eine neue Verbindung zur Datenbank her
     * 
     * @throws PDOException Falls die Verbindung fehlschlägt
     */
    private static function connect(): void
    {
        $config = self::$config ?? require __DIR__ . '/../config/database.php';
        
        $dsn = match ($config['driver']) {
            'sqlite' => 'sqlite:' . $config['database'],
            default => throw new PDOException("Unsupported database driver: {$config['driver']}"),
        };

        // Stelle sicher, dass das Verzeichnis existiert
        if ($config['driver'] === 'sqlite') {
            $dbPath = dirname($config['database']);
            if (!is_dir($dbPath)) {
                mkdir($dbPath, 0755, true);
            }
        }

        self::$connection = new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['options'] ?? []
        );
    }

    /**
     * Führt eine SQL-Abfrage mit Parametern aus
     * 
     * @param string $sql Die SQL-Abfrage
     * @param array $params Die Parameter für die Abfrage
     * @return PDOStatement Das vorbereitete Statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Führt eine Transaktion aus
     * 
     * @param callable $callback Die Funktion, die in der Transaktion ausgeführt werden soll
     * @return mixed Das Ergebnis der Callback-Funktion
     * @throws PDOException Falls die Transaktion fehlschlägt
     */
    public static function transaction(callable $callback)
    {
        $conn = self::getConnection();
        $conn->beginTransaction();
        
        try {
            $result = $callback($conn);
            $conn->commit();
            return $result;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Gibt die letzte eingefügte ID zurück
     * 
     * @return string Die letzte ID
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Schließt die Datenbankverbindung
     */
    public static function close(): void
    {
        self::$connection = null;
    }
}
