<?php
/**
 * Buchung.php - Modell für Buchungen
 */

namespace Alphaessen\Models;

class Buchung
{
    public int $id;
    public int $nutzerId;
    public Nutzer $nutzer;
    public int $speiseplanId;
    public SpeiseplanEintrag $speiseplanEintrag;
    public string $erstelltAm;
    public bool $bestaetigt;
    public bool $erinnerungGesendet;

    public function __construct(
        int $id,
        int $nutzerId,
        Nutzer $nutzer,
        int $speiseplanId,
        SpeiseplanEintrag $speiseplanEintrag,
        string $erstelltAm,
        bool $bestaetigt = false,
        bool $erinnerungGesendet = false
    ) {
        $this->id = $id;
        $this->nutzerId = $nutzerId;
        $this->nutzer = $nutzer;
        $this->speiseplanId = $speiseplanId;
        $this->speiseplanEintrag = $speiseplanEintrag;
        $this->erstelltAm = $erstelltAm;
        $this->bestaetigt = $bestaetigt;
        $this->erinnerungGesendet = $erinnerungGesendet;
    }

    /**
     * Erstellt ein Buchung-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(
        array $data,
        Nutzer $nutzer,
        SpeiseplanEintrag $speiseplanEintrag
    ): self {
        return new self(
            $data['id'] ?? 0,
            $data['nutzer_id'] ?? 0,
            $nutzer,
            $data['speiseplan_id'] ?? 0,
            $speiseplanEintrag,
            $data['erstellt_am'] ?? '',
            $data['bestaetigt'] ?? false,
            $data['erinnerung_gesendet'] ?? false
        );
    }

    /**
     * Gibt die Daten als Array zurück (für Datenbank-Operationen)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nutzer_id' => $this->nutzerId,
            'speiseplan_id' => $this->speiseplanId,
            'erstellt_am' => $this->erstelltAm,
            'bestaetigt' => $this->bestaetigt ? 1 : 0,
            'erinnerung_gesendet' => $this->erinnerungGesendet ? 1 : 0,
        ];
    }

    /**
     * Prüft, ob diese Buchung für den gegebenen Nutzer ist
     */
    public function istVonNutzer(int $nutzerId): bool
    {
        return $this->nutzerId === $nutzerId;
    }

    /**
     * Prüft, ob diese Buchung für die gegebene Woche und Jahr ist
     */
    public function istFuerWocheUndJahr(int $woche, int $jahr): bool
    {
        return $this->speiseplanEintrag->woche === $woche 
            && $this->speiseplanEintrag->jahr === $jahr;
    }

    /**
     * Gibt das Datum der Buchung zurück
     */
    public function getDatum(): string
    {
        return $this->speiseplanEintrag->getDatum();
    }

    /**
     * Gibt das Datum im deutschen Format zurück
     */
    public function getDatumDeutsch(): string
    {
        return $this->speiseplanEintrag->getDatumDeutsch();
    }
}
