<?php
/**
 * AdminAuthService.php - Service für Admin-Authentifizierung
 * 
 * Dieser Service behandelt:
 * - Admin-Login
 * - Session-Verwaltung
 * - Passwort-Prüfung
 */

namespace Alphaessen\Services;

use Alphaessen\Repositories\AdminRepository;
use Alphaessen\Models\Admin;

class AdminAuthService
{
    private AdminRepository $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Meldet einen Admin an
     * 
     * @param string $benutzername Der Benutzername
     * @param string $passwort Das Passwort
     * @return Admin|null Der Admin, falls Anmeldung erfolgreich, sonst null
     */
    public function login(string $benutzername, string $passwort): ?Admin
    {
        $admin = $this->adminRepository->findeNachBenutzername($benutzername);
        
        if ($admin === null) {
            return null;
        }

        if (!$admin->pruefePasswort($passwort)) {
            return null;
        }

        // Session starten und Admin speichern
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['admin_id'] = $admin->id;
        $_SESSION['admin_benutzername'] = $admin->benutzername;
        $_SESSION['admin_angemeldet'] = true;
        $_SESSION['admin_login_zeit'] = time();

        // Session-ID regenerieren, um Session-Fixation zu verhindern
        session_regenerate_id(true);

        return $admin;
    }

    /**
     * Meldet einen Admin ab
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Alle Session-Daten löschen
        $_SESSION = [];

        // Session-Cookie löschen
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Session zerstören
        session_destroy();
    }

    /**
     * Prüft, ob ein Admin angemeldet ist
     * 
     * @return bool True, wenn ein Admin angemeldet ist
     */
    public function istAngemeldet(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['admin_angemeldet']) && $_SESSION['admin_angemeldet'] === true;
    }

    /**
     * Gibt den aktuellen Admin zurück
     * 
     * @return Admin|null Der aktuelle Admin oder null, falls nicht angemeldet
     */
    public function getAktuellerAdmin(): ?Admin
    {
        if (!$this->istAngemeldet()) {
            return null;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;
        if ($adminId === null) {
            return null;
        }

        return $this->adminRepository->findeNachId($adminId);
    }

    /**
     * Prüft, ob der aktuelle Admin berechtigt ist
     * 
     * @return bool True, wenn der Admin angemeldet und berechtigt ist
     */
    public function istBerechtigt(): bool
    {
        return $this->istAngemeldet() && $this->getAktuellerAdmin() !== null;
    }

    /**
     * Erstellt einen neuen Admin-Benutzer
     * 
     * @param string $benutzername Der Benutzername
     * @param string $passwort Das Passwort
     * @return Admin Der neu erstellte Admin
     * @throws \InvalidArgumentException Falls der Benutzername bereits existiert
     */
    public function erstelleAdmin(string $benutzername, string $passwort): Admin
    {
        // Prüfen, ob Benutzername bereits existiert
        $existing = $this->adminRepository->findeNachBenutzername($benutzername);
        if ($existing !== null) {
            throw new \InvalidArgumentException("Benutzername bereits vergeben");
        }

        return $this->adminRepository->erstelle($benutzername, $passwort);
    }

    /**
     * Aktualisiert das Passwort eines Admins
     * 
     * @param int $adminId Die Admin-ID
     * @param string $altesPasswort Das alte Passwort
     * @param string $neuesPasswort Das neue Passwort
     * @return bool True, wenn erfolgreich
     */
    public function aenderePasswort(int $adminId, string $altesPasswort, string $neuesPasswort): bool
    {
        $admin = $this->adminRepository->findeNachId($adminId);
        if ($admin === null) {
            return false;
        }

        if (!$admin->pruefePasswort($altesPasswort)) {
            return false;
        }

        return $this->adminRepository->aktualisierePasswort($adminId, $neuesPasswort);
    }

    /**
     * Löscht einen Admin-Benutzer
     * 
     * @param int $adminId Die Admin-ID
     * @return bool True, wenn erfolgreich
     */
    public function loescheAdmin(int $adminId): bool
    {
        // Verhindere, dass der letzte Admin gelöscht wird
        if ($this->adminRepository->zaehle() <= 1) {
            return false;
        }

        return $this->adminRepository->loesche($adminId);
    }

    /**
     * Gibt alle Admin-Benutzer zurück
     * 
     * @return Admin[] Array von Admins
     */
    public function getAlleAdmins(): array
    {
        return $this->adminRepository->findeAlle();
    }
}
