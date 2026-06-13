<?php
/**
 * Admin.php - Modell für Admin-Benutzer
 */

namespace Alphaessen\Models;

class Admin
{
    public int $id;
    public string $benutzername;
    public string $passwortHash;
    public string $erstelltAm;

    public function __construct(
        int $id,
        string $benutzername,
        string $passwortHash,
        string $erstelltAm
    ) {
        $this->id = $id;
        $this->benutzername = $benutzername;
        $this->passwortHash = $passwortHash;
        $this->erstelltAm = $erstelltAm;
    }

    /**
     * Erstellt ein Admin-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['benutzername'] ?? '',
            $data['passwort_hash'] ?? '',
            $data['erstellt_am'] ?? ''
        );
    }

    /**
     * Gibt die Daten als Array zurück (für Datenbank-Operationen)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'benutzername' => $this->benutzername,
            'passwort_hash' => $this->passwortHash,
            'erstellt_am' => $this->erstelltAm,
        ];
    }

    /**
     * Prüft, ob das Passwort korrekt ist
     */
    public function pruefePasswort(string $passwort): bool
    {
        return password_verify($passwort, $this->passwortHash);
    }

    /**
     * Setzt ein neues Passwort (mit Hashing)
     */
    public function setzePasswort(string $passwort): void
    {
        $this->passwortHash = password_hash($passwort, PASSWORD_ARGON2ID);
    }
}
