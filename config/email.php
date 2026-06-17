<?php
/**
 * E-Mail-Konfiguration für Alphaessen
 * Unterstützt PHP mail() Funktion oder SMTP mit Authentifizierung
 * 
 * BEISPIEL-KONFIGURATIONEN:
 * 
 * Gmail:
 * 'transport' => 'smtp',
 * 'smtp' => [
 *     'host' => 'smtp.gmail.com',
 *     'port' => 587,
 *     'encryption' => 'tls',
 *     'username' => 'Ihre-E-Mail@gmail.com',
 *     'password' => 'Ihr-App-Passwort',  // Verwenden Sie ein App-Passwort!
 *     'auth' => true,
 * ],
 * 
 * IONOS:
 * 'transport' => 'smtp',
 * 'smtp' => [
 *     'host' => 'smtp.ionos.de',
 *     'port' => 587,
 *     'encryption' => 'tls',
 *     'username' => 'Ihre-E-Mail@Ihre-Domain.de',
 *     'password' => 'Ihr-Passwort',
 *     'auth' => true,
 * ],
 * 
 * All-inkl:
 * 'transport' => 'smtp',
 * 'smtp' => [
 *     'host' => 'smtpall.allocated-server.de',
 *     'port' => 587,
 *     'encryption' => 'tls',
 *     'username' => 'Ihre-E-Mail@Ihre-Domain.de',
 *     'password' => 'Ihr-Passwort',
 *     'auth' => true,
 * ],
 * 
 * Ohne Verschlüsselung (nur für lokale Tests):
 * 'transport' => 'smtp',
 * 'smtp' => [
 *     'host' => 'localhost',
 *     'port' => 25,
 *     'encryption' => '',
 *     'username' => '',
 *     'password' => '',
 *     'auth' => false,
 * ],
 * 
 * PHP mail() Funktion (Standard):
 * 'transport' => 'mail',
 */

return [
    // Absender-Informationen
    'from' => [
        'email' => 'alphaessen@Ihre-Gemeinde.de',
        'name' => 'Alphaessen Team',
    ],
    
    // Empfänger für Fehlerbenachrichtigungen
    'error_recipient' => 'admin@Ihre-Gemeinde.de',
    
    // E-Mail-Versandmethode
    // 'mail' = PHP mail() Funktion (Standard)
    // 'smtp' = SMTP mit Authentifizierung
    'transport' => 'smtp',
    
    // SMTP-Konfiguration (wird nur verwendet, wenn transport = 'smtp')
    'smtp' => [
        'host' => 'smtp.Ihr-SMTP-Server.de',  // z.B. smtp.gmail.com, smtp.ionos.de
        'port' => 587,                      // 587 (TLS), 465 (SSL), 25
        'encryption' => 'tls',             // 'tls', 'ssl' oder '' (keine Verschlüsselung)
        'username' => 'Ihre-E-Mail@Ihre-Domain.de',
        'password' => 'Ihr-Passwort',
        'auth' => true,                    // SMTP-Authentifizierung aktivieren
        'timeout' => 30,                   // Timeout in Sekunden
    ],
    
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
