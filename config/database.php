<?php
/**
 * Datenbank-Konfiguration für Alphaessen
 * SQLite-Datenbank in storage/database/alphaessen.db
 */

return [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../storage/database/alphaessen.db',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
    ],
];
