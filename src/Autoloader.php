<?php
/**
 * Autoloader.php - Einfacher PSR-4-kompatibler Autoloader für Alphaessen
 * 
 * Dieser Autoloader lädt Klassen automatisch basierend auf ihrem Namespace.
 * Namespace: Alphaessen\ (Root-Namespace)
 * Basisverzeichnis: src/
 */

spl_autoload_register(function (string $class): void {
    // Nur Klassen im Alphaessen-Namespace laden
    $prefix = 'Alphaessen\\';
    $baseDir = __DIR__ . '/';

    // Prüfe, ob die Klasse zum Alphaessen-Namespace gehört
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Relative Klassenname extrahieren
    $relativeClass = substr($class, $len);

    // Dateipfad erstellen
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Datei laden, falls sie existiert
    if (file_exists($file)) {
        require $file;
    }
});
