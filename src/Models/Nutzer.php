<?php
/**
 * Nutzer.php - Modell für Nutzer (Personen, die Buchungen vornehmen)
 */

namespace Alphaessen\Models;

class Nutzer
{
    public int $id;
    public string $email;
    public string $erstelltAm;

    public function __construct(int $id, string $email, string $erstelltAm)
    {
        $this->id = $id;
        $this->email = $email;
        $this->erstelltAm = $erstelltAm;
    }

    public function short_email(): string {
        if ($this->email) {
            $teile = explode('@', $this->email);
            $gekuerzt = $teile[0];
            return substr($gekuerzt, 0, 10);
        }
        return "";
    }

    /**
     * Erstellt ein Nutzer-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['email'] ?? '',
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
            'email' => $this->email,
            'erstellt_am' => $this->erstelltAm,
        ];
    }
}
