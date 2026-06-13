<?php
/**
 * send_reminders.php - Cronjob zum Senden von Erinnerungs-E-Mails
 * 
 * Usage: php /var/www/alphaessen/cron/send_reminders.php
 * 
 * Dieser Cronjob sollte täglich um 10:00 Uhr ausgeführt werden:
 * 0 10 * * * php /var/www/alphaessen/cron/send_reminders.php
 * 
 * Er sendet Erinnerungen für alle Buchungen, deren Termin am nächsten Tag ist.
 */

require_once __DIR__ . '/../src/Autoloader.php';

use Alphaessen\Database;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;
use Alphaessen\Repositories\BuchungRepository;
use Alphaessen\Repositories\NutzerRepository;
use Alphaessen\Repositories\EmailQueueRepository;
use Alphaessen\Services\EmailService;
use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\BuchungService;

// Datenbank initialisieren
$config = require __DIR__ . '/../config/database.php';
Database::init($config);

// E-Mail-Konfiguration laden
$emailConfig = require __DIR__ . '/../config/email.php';

// Services initialisieren
$essenRepository = new EssenRepository();
$speiseplanRepository = new SpeiseplanRepository($essenRepository);
$nutzerRepository = new NutzerRepository();
$buchungRepository = new BuchungRepository($nutzerRepository, $speiseplanRepository);
$emailQueueRepository = new EmailQueueRepository();
$emailService = new EmailService($emailQueueRepository, $emailConfig);
$speiseplanService = new SpeiseplanService($speiseplanRepository, $essenRepository);
$buchungService = new BuchungService($buchungRepository, $nutzerRepository, $speiseplanRepository, $emailQueueRepository, $emailService);

echo "=== Erinnerungs-E-Mails senden ===\n";
echo "Start: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Morgen berechnen
    $morgen = new DateTime('tomorrow');
    $morgenDatum = $morgen->format('Y-m-d');
    
    echo "Suche Buchungen für: {$morgenDatum}\n\n";

    // Alle Speiseplan-Einträge für das Jahr abfragen
    $aktuellesJahr = (int)date('Y');
    $alleEintraege = $speiseplanRepository->findeNachJahr($aktuellesJahr);
    
    // Nach Datum filtern
    $morgenEintraege = [];
    foreach ($alleEintraege as $eintrag) {
        if ($eintrag->getDatum() === $morgenDatum) {
            $morgenEintraege[] = $eintrag;
        }
    }
    
    echo "Gefundene Speiseplan-Einträge für morgen: " . count($morgenEintraege) . "\n";

    // Für jeden Eintrag die Buchungen abfragen
    $erinnerungenGesendet = 0;
    foreach ($morgenEintraege as $eintrag) {
        $buchungen = $buchungRepository->findeNachSpeiseplan($eintrag->id);
        
        foreach ($buchungen as $buchung) {
            // Prüfen, ob Erinnerung bereits gesendet wurde
            if ($buchung->erinnerungGesendet) {
                continue;
            }
            
            // Erinnerung in Queue stellen
            $emailService->queueErinnerung($buchung);
            
            // Markieren, dass Erinnerung gesendet wurde
            $buchungRepository->setzeErinnerungGesendet($buchung->id, true);
            
            $erinnerungenGesendet++;
            echo "Erinnerung für " . $buchung->nutzer->email . " (" . $eintrag->essen->name . ") in Queue\n";
        }
    }
    
    // Queue verarbeiten
    $gesendet = $emailService->verarbeiteQueue(3);
    
    echo "\nErinnerungen in Queue: {$erinnerungenGesendet}\n";
    echo "Erinnerungen gesendet: {$gesendet}\n";
    echo "\nFertig: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
