<?php
/**
 * CsrfService.php - Service für CSRF-Schutz
 * 
 * Dieser Service generiert und validiert CSRF-Tokens für Formulare.
 */

namespace Alphaessen\Services;

class CsrfService
{
    private string $sessionKey = 'csrf_token';
    private int $tokenLength = 32;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generiert ein neues CSRF-Token
     * 
     * @return string Das generierte Token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes($this->tokenLength));
        $_SESSION[$this->sessionKey] = $token;
        return $token;
    }

    /**
     * Gibt das aktuelle CSRF-Token zurück
     * 
     * @return string|null Das aktuelle Token oder null, falls keins existiert
     */
    public function getToken(): ?string
    {
        return $_SESSION[$this->sessionKey] ?? null;
    }

    /**
     * Validiert ein CSRF-Token
     * 
     * @param string $token Das zu validierende Token
     * @return bool True, wenn das Token gültig ist
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->getToken();
        
        if ($sessionToken === null) {
            return false;
        }

        // Zeitbasierte Validierung (Token ist 1 Stunde gültig)
        if (isset($_SESSION[$this->sessionKey . '_time'])) {
            $tokenTime = $_SESSION[$this->sessionKey . '_time'];
            if (time() - $tokenTime > 3600) {
                $this->clearToken();
                return false;
            }
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Löscht das aktuelle CSRF-Token
     */
    public function clearToken(): void
    {
        unset($_SESSION[$this->sessionKey]);
        unset($_SESSION[$this->sessionKey . '_time']);
    }

    /**
     * Generiert ein neues Token und gibt es als HTML-Hidden-Input zurück
     * 
     * @return string HTML-Code für das Hidden-Input-Feld
     */
    public function getTokenInput(): string
    {
        $token = $this->generateToken();
        $_SESSION[$this->sessionKey . '_time'] = time();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Prüft, ob ein CSRF-Token in den Request-Daten vorhanden und gültig ist
     * 
     * @param array $requestData Die Request-Daten ($_POST oder $_GET)
     * @return bool True, wenn das Token gültig ist
     */
    public function validateRequest(array $requestData): bool
    {
        $token = $requestData['csrf_token'] ?? null;
        
        if ($token === null) {
            return false;
        }

        return $this->validateToken($token);
    }
}
