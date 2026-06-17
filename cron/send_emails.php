<?php
/**
 * send_emails.php - Cronjob zum Senden von E-Mails aus der Queue
 * 
 * Usage: php /var/www/alphaessen/cron/send_emails.php
 * 
 * Dieser Cronjob sollte alle 5 Minuten ausgeführt werden:
 * *\/5 * * * * php /var/www/alphaessen/cron/send_emails.php
 */

require_once __DIR__ . '/../src/Autoloader.php';

use Alphaessen\Database;
use Alphaessen\Repositories\EmailQueueRepository;
use Alphaessen\Services\EmailService;

// Datenbank initialisieren
$config = require __DIR__ . '/../config/database.php';
Database::init($config);

// E-Mail-Konfiguration laden
$emailConfig = require __DIR__ . '/../config/email.php';

// Services initialisieren
$emailQueueRepository = new EmailQueueRepository();
$emailService = new EmailService($emailQueueRepository, $emailConfig);

echo "=== E-Mail-Queue Verarbeitung ===\n";
echo "Start: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // E-Mails aus der Queue verarbeiten
    $gesendet = $emailService->verarbeiteQueue(3); // Max 3 Versuche
    
    echo "Gesendete E-Mails: {$gesendet}\n";
    echo "Nicht gesendete E-Mails: " . $emailQueueRepository->zaehleNichtGesendete() . "\n";
    echo "\nFertig: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
