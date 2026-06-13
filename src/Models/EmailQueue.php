<?php
/**
 * EmailQueue.php - Modell für E-Mail-Queue-Einträge
 */

namespace Alphaessen\Models;

class EmailQueue
{
    public const TYP_BESTAETIGUNG = 'bestaetigung';
    public const TYP_ERINNERUNG = 'erinnerung';

    public int $id;
    public string $empfaenger;
    public string $betreff;
    public string $nachricht;
    public string $typ;
    public bool $gesendet;
    public int $versuche;
    public string $erstelltAm;

    public function __construct(
        int $id,
        string $empfaenger,
        string $betreff,
        string $nachricht,
        string $typ,
        bool $gesendet = false,
        int $versuche = 0,
        string $erstelltAm = ''
    ) {
        $this->id = $id;
        $this->empfaenger = $empfaenger;
        $this->betreff = $betreff;
        $this->nachricht = $nachricht;
        $this->typ = $typ;
        $this->gesendet = $gesendet;
        $this->versuche = $versuche;
        $this->erstelltAm = $erstelltAm;
    }

    /**
     * Erstellt ein EmailQueue-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['empfaenger'] ?? '',
            $data['betreff'] ?? '',
            $data['nachricht'] ?? '',
            $data['typ'] ?? '',
            $data['gesendet'] ?? false,
            $data['versuche'] ?? 0,
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
            'empfaenger' => $this->empfaenger,
            'betreff' => $this->betreff,
            'nachricht' => $this->nachricht,
            'typ' => $this->typ,
            'gesendet' => $this->gesendet ? 1 : 0,
            'versuche' => $this->versuche,
            'erstellt_am' => $this->erstelltAm,
        ];
    }

    /**
     * Prüft, ob der Typ gültig ist
     */
    public static function istGueltigerTyp(string $typ): bool
    {
        return in_array($typ, [self::TYP_BESTAETIGUNG, self::TYP_ERINNERUNG], true);
    }
}
