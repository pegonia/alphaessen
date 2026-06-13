<?php
/**
 * Essen.php - Modell für Essen (Rezepte)
 */

namespace Alphaessen\Models;

class Essen
{
    public const TYP_HAUPTGERICHT_FLEISCH = 'Hauptgericht_Fleisch';
    public const TYP_HAUPTGERICHT_VEGETARISCH = 'Hauptgericht_Vegetarisch';
    public const TYP_BEILAGE = 'Beilage';
    public const TYP_BROT = 'Brot';
    public const TYP_KAESE = 'Käse';
    public const TYP_WURST = 'Wurst';
    public const TYP_NACHTISCH = 'Nachtisch';

    public const ALLE_TYPEN = [
        self::TYP_HAUPTGERICHT_FLEISCH,
        self::TYP_HAUPTGERICHT_VEGETARISCH,
        self::TYP_BEILAGE,
        self::TYP_BROT,
        self::TYP_KAESE,
        self::TYP_WURST,
        self::TYP_NACHTISCH,
    ];

    public int $id;
    public string $name;
    public string $typ;
    public ?string $beschreibung;
    public string $erstelltAm;
    public bool $aktiv;

    public function __construct(
        int $id,
        string $name,
        string $typ,
        ?string $beschreibung,
        string $erstelltAm,
        bool $aktiv = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->typ = $typ;
        $this->beschreibung = $beschreibung;
        $this->erstelltAm = $erstelltAm;
        $this->aktiv = $aktiv;
    }

    /**
     * Erstellt ein Essen-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['name'] ?? '',
            $data['typ'] ?? '',
            $data['beschreibung'] ?? null,
            $data['erstellt_am'] ?? '',
            $data['aktiv'] ?? true
        );
    }

    /**
     * Gibt die Daten als Array zurück (für Datenbank-Operationen)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'typ' => $this->typ,
            'beschreibung' => $this->beschreibung,
            'erstellt_am' => $this->erstelltAm,
            'aktiv' => $this->aktiv ? 1 : 0,
        ];
    }

    /**
     * Gibt den Typ als lesbaren Text zurück
     */
    public function getTypAnzeige(): string
    {
        return match ($this->typ) {
            self::TYP_HAUPTGERICHT_FLEISCH => 'Hauptgericht (Fleisch)',
            self::TYP_HAUPTGERICHT_VEGETARISCH => 'Hauptgericht (Vegetarisch)',
            self::TYP_BEILAGE => 'Beilage',
            self::TYP_BROT => 'Brot',
            self::TYP_KAESE => 'Käse',
            self::TYP_WURST => 'Wurst',
            self::TYP_NACHTISCH => 'Nachtisch',
            default => $this->typ,
        };
    }

    /**
     * Prüft, ob der Typ gültig ist
     */
    public static function istGueltigerTyp(string $typ): bool
    {
        return in_array($typ, self::ALLE_TYPEN, true);
    }
}
