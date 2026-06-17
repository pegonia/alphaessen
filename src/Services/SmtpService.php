<?php
/**
 * SmtpService.php - Service für SMTP-E-Mail-Versand
 * 
 * Dieser Service implementiert eine einfache SMTP-Client-Funktionalität
 * ohne externe Abhängigkeiten (wie PHPMailer).
 */

namespace Alphaessen\Services;

class SmtpService
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private bool $auth;
    private int $timeout;

    /**
     * @param array $config SMTP-Konfiguration
     */
    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 25;
        $this->encryption = strtolower($config['encryption'] ?? '');
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->auth = $config['auth'] ?? false;
        $this->timeout = $config['timeout'] ?? 30;
    }

    /**
     * Sendet eine E-Mail über SMTP
     * 
     * @param string $from Absender (z.B. 'name <email@domain.com>')
     * @param string $to Empfänger
     * @param string $subject Betreff
     * @param string $body Nachricht
     * @param array $headers Zusätzliche Header
     * @return bool True, wenn der Versand erfolgreich war
     */
    public function send(string $from, string $to, string $subject, string $body, array $headers = []): bool
    {
        // Verbindung aufbauen
        $socket = $this->connect();
        if ($socket === false) {
            return false;
        }

        try {
            // Server begrüßen
            $this->readResponse($socket, 220);

            // EHLO/HELO
            $this->sendCommand($socket, 'EHLO ' . $this->getClientHost());
            $this->readResponse($socket, 250);

            // STARTTLS, falls Verschlüsselung aktiviert
            if ($this->encryption === 'tls') {
                $this->sendCommand($socket, 'STARTTLS');
                $this->readResponse($socket, 220);
                
                // SSL/TLS Handshake
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('STARTTLS Handshake fehlgeschlagen');
                }
                
                // EHLO erneut nach STARTTLS
                $this->sendCommand($socket, 'EHLO ' . $this->getClientHost());
                $this->readResponse($socket, 250);
            } elseif ($this->encryption === 'ssl') {
                // SSL wird beim Verbinden aktiviert
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT)) {
                    throw new \RuntimeException('SSL Handshake fehlgeschlagen');
                }
                
                $this->sendCommand($socket, 'EHLO ' . $this->getClientHost());
                $this->readResponse($socket, 250);
            }

            // Authentifizierung
            if ($this->auth && !empty($this->username) && !empty($this->password)) {
                $this->sendCommand($socket, 'AUTH LOGIN');
                $this->readResponse($socket, 334);
                
                $this->sendCommand($socket, base64_encode($this->username));
                $this->readResponse($socket, 334);
                
                $this->sendCommand($socket, base64_encode($this->password));
                $this->readResponse($socket, 235);
            }

            // MAIL FROM
            $this->sendCommand($socket, 'MAIL FROM:<' . $this->extractEmail($from) . '>');
            $this->readResponse($socket, 250);

            // RCPT TO
            $this->sendCommand($socket, 'RCPT TO:<' . $to . '>');
            $this->readResponse($socket, 250);

            // DATA
            $this->sendCommand($socket, 'DATA');
            $this->readResponse($socket, 354);

            // E-Mail-Inhalt senden
            $message = $this->buildMessage($from, $to, $subject, $body, $headers);
            $this->sendCommand($socket, $message);
            $this->sendCommand($socket, '.');
            $this->readResponse($socket, 250);

            // Verbindung schließen
            $this->sendCommand($socket, 'QUIT');
            $this->readResponse($socket, 221);

            fclose($socket);
            return true;

        } catch (\Exception $e) {
            fclose($socket);
            error_log('SMTP Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Baut eine Verbindung zum SMTP-Server auf
     * 
     * @return resource|false Socket oder false bei Fehler
     */
    private function connect()
    {
        $host = $this->host;
        $port = $this->port;

        // SSL wird direkt beim Verbinden aktiviert
        if ($this->encryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        
        if ($socket === false) {
            error_log("SMTP Verbindung fehlgeschlagen: {$errstr} ({$errno})");
            return false;
        }

        // Timeout setzen
        stream_set_timeout($socket, $this->timeout);
        stream_set_blocking($socket, true);

        return $socket;
    }

    /**
     * Sendet einen Befehl an den SMTP-Server
     */
    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    /**
     * Liest die Antwort vom SMTP-Server
     * 
     * @param resource $socket Socket
     * @param int $expectedCode Erwarteter Antwortcode
     * @return string Die vollständige Antwort
     * @throws \RuntimeException Falls der erwartete Code nicht empfangen wird
     */
    private function readResponse($socket, int $expectedCode): string
    {
        $response = '';
        $code = 0;

        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (preg_match('/^(\d{3}) /', $line, $matches)) {
                $code = (int)$matches[1];
                // Multi-line response
                if ($line[3] === '-') {
                    continue;
                }
                break;
            }
        }

        if ($code !== $expectedCode) {
            throw new \RuntimeException("SMTP Fehler: Erwarteter Code {$expectedCode}, erhalten {$code}. Antwort: " . trim($response));
        }

        return trim($response);
    }

    /**
     * Baut die E-Mail-Nachricht zusammen
     */
    private function buildMessage(string $from, string $to, string $subject, string $body, array $headers): string
    {
        $message = '';
        
        // Header
        $message .= 'From: ' . $from . "\r\n";
        $message .= 'To: ' . $to . "\r\n";
        $message .= 'Subject: ' . $this->encodeHeader($subject) . "\r\n";
        
        // Zusätzliche Header
        foreach ($headers as $key => $value) {
            $message .= "{$key}: {$value}\r\n";
        }
        
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "X-Mailer: Alphaessen\r\n";
        $message .= "\r\n";
        
        // Body
        $message .= $body;
        
        return $message;
    }

    /**
     * Kodiert Header-Strings (für Umlaute)
     */
    private function encodeHeader(string $string): string
    {
        if (preg_match('/[^\x20-\x7E]/', $string)) {
            return '=?UTF-8?B?' . base64_encode($string) . '?=';
        }
        return $string;
    }

    /**
     * Extrahiere E-Mail-Adresse aus einem String wie 'Name <email@domain.com>'
     */
    private function extractEmail(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches)) {
            return $matches[1];
        }
        return $from;
    }

    /**
     * Gibt den Client-Hostnamen zurück
     */
    private function getClientHost(): string
    {
        return gethostname() ?: 'localhost';
    }
}
