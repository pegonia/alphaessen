<?php
/**
 * E-Mail-Konfiguration für Alphaessen
 * Verwendet PHP mail() Funktion
 */

return [
    // Absender-Informationen
    'from' => [
        'email' => 'alphaessen@Ihre-Gemeinde.de',
        'name' => 'Alphaessen Team',
    ],
    
    // Empfänger für Fehlerbenachrichtigungen
    'error_recipient' => 'admin@Ihre-Gemeinde.de',
    
    // E-Mail-Einstellungen
    'use_mail_function' => true,  // true = PHP mail(), false = SMTP (nicht implementiert)
    
    // Kopfzeilen
    'headers' => [
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Priority' => '3',
    ],
    
    // Standard-Betreffs
    'subjects' => [
        'bestaetigung' => 'Buchungsbestätigung für Alphaessen am {Datum}',
        'erinnerung' => 'Erinnerung: Alphaessen am {Datum}',
    ],
];
