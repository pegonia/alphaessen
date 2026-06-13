<?php
/**
 * NutzerRepository.php - Repository für Nutzer
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\Nutzer;

class NutzerRepository
{
    /**
     * Fügt einen neuen Nutzer hinzu oder gibt den bestehenden zurück
     * 
     * @param string $email Die E-Mail-Adresse des Nutzers
     * @return Nutzer Der Nutzer (neu oder bestehend)
     */
    public function findeOderErstelle(string $email): Nutzer
    {
        // Normalisiere E-Mail (Kleinschreibung)
        $email = strtolower(trim($email));

        // Prüfe, ob Nutzer bereits existiert
        $existing = $this->findeNachEmail($email);
        if ($existing !== null) {
            return $existing;
        }

        // Neuen Nutzer erstellen
        $stmt = Database::query(
            "INSERT INTO nutzer (email) VALUES (:email)",
            ['email' => $email]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet einen Nutzer nach ID
     * 
     * @param int $id Die Nutzer-ID
     * @return Nutzer|null Der Nutzer oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?Nutzer
    {
        $stmt = Database::query(
            "SELECT * FROM nutzer WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Nutzer::fromArray($data);
    }

    /**
     * Findet einen Nutzer nach E-Mail-Adresse
     * 
     * @param string $email Die E-Mail-Adresse
     * @return Nutzer|null Der Nutzer oder null, falls nicht gefunden
     */
    public function findeNachEmail(string $email): ?Nutzer
    {
        $email = strtolower(trim($email));
        
        $stmt = Database::query(
            "SELECT * FROM nutzer WHERE email = :email COLLATE NOCASE",
            ['email' => $email]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Nutzer::fromArray($data);
    }

    /**
     * Gibt alle Nutzer zurück
     * 
     * @return Nutzer[] Array von Nutzern
     */
    public function findeAlle(): array
    {
        $stmt = Database::query("SELECT * FROM nutzer ORDER BY email");
        
        $nutzer = [];
        while ($data = $stmt->fetch()) {
            $nutzer[] = Nutzer::fromArray($data);
        }

        return $nutzer;
    }

    /**
     * Aktualisiert einen Nutzer
     * 
     * @param Nutzer $nutzer Der zu aktualisierende Nutzer
     * @return bool True, wenn erfolgreich
     */
    public function aktualisiere(Nutzer $nutzer): bool
    {
        $stmt = Database::query(
            "UPDATE nutzer SET email = :email WHERE id = :id",
            [
                'id' => $nutzer->id,
                'email' => strtolower(trim($nutzer->email)),
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht einen Nutzer
     * 
     * @param int $id Die Nutzer-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM nutzer WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Gibt die Anzahl der Nutzer zurück
     * 
     * @return int Die Anzahl der Nutzer
     */
    public function zaehle(): int
    {
        $stmt = Database::query("SELECT COUNT(*) as count FROM nutzer");
        return (int)$stmt->fetch()['count'];
    }
}
