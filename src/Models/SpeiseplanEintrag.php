<?php
/**
 * SpeiseplanEintrag.php - Modell für einen Eintrag im Speiseplan
 */

namespace Alphaessen\Models;

class SpeiseplanEintrag
{
    public int $id;
    public int $woche;
    public int $jahr;
    public int $essenId;
    public Essen $essen;

    public function __construct(
        int $id,
        int $woche,
        int $jahr,
        int $essenId,
        Essen $essen
    ) {
        $this->id = $id;
        $this->woche = $woche;
        $this->jahr = $jahr;
        $this->essenId = $essenId;
        $this->essen = $essen;
    }

    /**
     * Erstellt ein SpeiseplanEintrag-Objekt aus einem Datenbank-Array
     */
    public static function fromArray(array $data, Essen $essen): self
    {
        return new self(
            $data['id'] ?? 0,
            $data['woche'] ?? 0,
            $data['jahr'] ?? 0,
            $data['essen_id'] ?? 0,
            $essen
        );
    }

    /**
     * Gibt die Daten als Array zurück (für Datenbank-Operationen)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'woche' => $this->woche,
            'jahr' => $this->jahr,
            'essen_id' => $this->essenId,
        ];
    }

    /**
     * Gibt das Datum des Donnerstags für diese Woche zurück
     */
    public function getDatum(): string
    {
        // Erster Donnerstag im Jahr finden
        $date = new \DateTime("$this->jahr-01-01");
        if ($this->jahr == 2026) {
            $date = new \DateTime("2026-09-03");
        }
        // Auf den ersten Donnerstag gehen
        $dayOfWeek = (int)$date->format('N'); // 1=Montag, 4=Donnerstag
        $daysToAdd = (4 - $dayOfWeek + 7) % 7;
        $date->add(new \DateInterval("P{$daysToAdd}D"));
        
        // (Woche-1) Wochen hinzufügen
        $date->add(new \DateInterval("P" . ($this->woche - 1) . "W"));
        
        return $date->format('d.m.');
    }

    /**
     * Gibt das Datum im deutschen Format zurück
     */
    public function getDatumDeutsch(): string
    {
        $date = new \DateTime($this->getDatum());
        return $date->format('d.m.Y');
    }

    /**
     * Gibt den Wochentag-Namen zurück (sollte immer "Donnerstag" sein)
     */
    public function getWochentag(): string
    {
        $date = new \DateTime($this->getDatum());
        return $date->format('l'); // Voller Wochentagsname
    }
}
