<?php
/**
 * AdminRepository.php - Repository für Admin-Benutzer
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\Admin;

class AdminRepository
{
    /**
     * Fügt einen neuen Admin-Benutzer hinzu
     * 
     * @param string $benutzername Der Benutzername
     * @param string $passwort Das Passwort (wird gehasht)
     * @return Admin Der neu erstellte Admin
     */
    public function erstelle(string $benutzername, string $passwort): Admin
    {
        $passwortHash = password_hash($passwort, PASSWORD_ARGON2ID);

        $stmt = Database::query(
            "INSERT INTO admin (benutzername, passwort_hash) VALUES (:benutzername, :passwort_hash)",
            [
                'benutzername' => trim($benutzername),
                'passwort_hash' => $passwortHash,
            ]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet einen Admin nach ID
     * 
     * @param int $id Die Admin-ID
     * @return Admin|null Der Admin oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?Admin
    {
        $stmt = Database::query(
            "SELECT * FROM admin WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Admin::fromArray($data);
    }

    /**
     * Findet einen Admin nach Benutzername
     * 
     * @param string $benutzername Der Benutzername
     * @return Admin|null Der Admin oder null, falls nicht gefunden
     */
    public function findeNachBenutzername(string $benutzername): ?Admin
    {
        $stmt = Database::query(
            "SELECT * FROM admin WHERE benutzername = :benutzername",
            ['benutzername' => trim($benutzername)]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Admin::fromArray($data);
    }

    /**
     * Gibt alle Admins zurück
     * 
     * @return Admin[] Array von Admins
     */
    public function findeAlle(): array
    {
        $stmt = Database::query("SELECT * FROM admin ORDER BY benutzername");

        $admins = [];
        while ($data = $stmt->fetch()) {
            $admins[] = Admin::fromArray($data);
        }

        return $admins;
    }

    /**
     * Aktualisiert einen Admin
     * 
     * @param Admin $admin Der zu aktualisierende Admin
     * @return bool True, wenn erfolgreich
     */
    public function aktualisiere(Admin $admin): bool
    {
        $stmt = Database::query(
            "UPDATE admin SET benutzername = :benutzername, passwort_hash = :passwort_hash WHERE id = :id",
            [
                'id' => $admin->id,
                'benutzername' => trim($admin->benutzername),
                'passwort_hash' => $admin->passwortHash,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Aktualisiert das Passwort eines Admins
     * 
     * @param int $adminId Die Admin-ID
     * @param string $neuesPasswort Das neue Passwort (wird gehasht)
     * @return bool True, wenn erfolgreich
     */
    public function aktualisierePasswort(int $adminId, string $neuesPasswort): bool
    {
        $passwortHash = password_hash($neuesPasswort, PASSWORD_ARGON2ID);

        $stmt = Database::query(
            "UPDATE admin SET passwort_hash = :passwort_hash WHERE id = :id",
            ['id' => $adminId, 'passwort_hash' => $passwortHash]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht einen Admin
     * 
     * @param int $id Die Admin-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM admin WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Gibt die Anzahl der Admins zurück
     * 
     * @return int Die Anzahl der Admins
     */
    public function zaehle(): int
    {
        $stmt = Database::query("SELECT COUNT(*) as count FROM admin");
        return (int)$stmt->fetch()['count'];
    }
}
