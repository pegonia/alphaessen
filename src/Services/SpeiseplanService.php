<?php
/**
 * SpeiseplanService.php - Service für Speiseplan-Logik
 * 
 * Dieser Service behandelt:
 * - Speiseplan erstellen und verwalten
 * - Wochenplanung
 * - Verfügbarkeit von Essen prüfen
 */

namespace Alphaessen\Services;

use Alphaessen\Models\SpeiseplanEintrag;
use Alphaessen\Models\Essen;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;

class SpeiseplanService
{
    private SpeiseplanRepository $speiseplanRepository;
    private EssenRepository $essenRepository;

    public function __construct(SpeiseplanRepository $speiseplanRepository, EssenRepository $essenRepository)
    {
        $this->speiseplanRepository = $speiseplanRepository;
        $this->essenRepository = $essenRepository;
    }

    /**
     * Erstellt einen neuen Speiseplan-Eintrag
     * 
     * @param int $woche Die Woche (1-12)
     * @param int $jahr Das Jahr
     * @param int $essenId Die Essen-ID
     * @return SpeiseplanEintrag Der neu erstellte Eintrag
     * @throws \InvalidArgumentException Falls das Essen bereits im Speiseplan ist
     */
    public function erstelleEintrag(int $woche, int $jahr, int $essenId): SpeiseplanEintrag
    {
        // Validierung
        if ($woche < 1 || $woche > 12) {
            throw new \InvalidArgumentException("Woche muss zwischen 1 und 12 liegen");
        }

        if ($this->speiseplanRepository->istEssenImSpeiseplan($woche, $jahr, $essenId)) {
            throw new \InvalidArgumentException("Dieses Essen ist bereits im Speiseplan für Woche {$woche}, Jahr {$jahr}");
        }

        return $this->speiseplanRepository->erstelle($woche, $jahr, $essenId);
    }

    /**
     * Gibt den Speiseplan für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return SpeiseplanEintrag[] Array von Speiseplan-Einträgen
     */
    public function getSpeiseplanFuerWoche(int $woche, int $jahr): array
    {
        return $this->speiseplanRepository->findeNachWocheUndJahr($woche, $jahr);
    }

    /**
     * Gibt alle Wochen für ein bestimmtes Jahr zurück, die einen Speiseplan haben
     * 
     * @param int $jahr Das Jahr
     * @return int[] Array von Wochen (1-12)
     */
    public function getWochenFuerJahr(int $jahr): array
    {
        return $this->speiseplanRepository->findeWochenNachJahr($jahr);
    }

    /**
     * Gibt alle Jahre zurück, für die ein Speiseplan existiert
     * 
     * @return int[] Array von Jahren
     */
    public function getAlleJahre(): array
    {
        return $this->speiseplanRepository->findeAlleJahre();
    }

    /**
     * Kopiert den Speiseplan einer Woche in eine andere Woche
     * 
     * @param int $vonWoche Die Quellwoche
     * @param int $vonJahr Das Quelljahr
     * @param int $nachWoche Die Zielwoche
     * @param int $nachJahr Das Zieljahr
     * @return int Die Anzahl der kopierten Einträge
     */
    public function kopiereWoche(int $vonWoche, int $vonJahr, int $nachWoche, int $nachJahr): int
    {
        return $this->speiseplanRepository->kopiereWoche($vonWoche, $vonJahr, $nachWoche, $nachJahr);
    }

    /**
     * Löscht einen Eintrag aus dem Speiseplan
     * 
     * @param int $id Die Eintrag-ID
     * @return bool True, wenn erfolgreich
     */
    public function loescheEintrag(int $id): bool
    {
        return $this->speiseplanRepository->loesche($id);
    }

    /**
     * Löscht alle Einträge für eine bestimmte Woche und Jahr
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheWoche(int $woche, int $jahr): int
    {
        return $this->speiseplanRepository->loescheNachWocheUndJahr($woche, $jahr);
    }

    /**
     * Löscht alle Einträge für ein bestimmtes Jahr
     * 
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheJahr(int $jahr): int
    {
        return $this->speiseplanRepository->loescheNachJahr($jahr);
    }

    /**
     * Gibt alle verfügbaren Essen (Rezepte) zurück
     * 
     * @param bool $nurAktiv Nur aktive Essen zurückgeben
     * @return Essen[] Array von Essen
     */
    public function getAlleEssen(bool $nurAktiv = true): array
    {
        return $this->essenRepository->findeAlle($nurAktiv);
    }

    /**
     * Gibt alle Essen nach Typ zurück
     * 
     * @param string $typ Der Typ
     * @param bool $nurAktiv Nur aktive Essen zurückgeben
     * @return Essen[] Array von Essen
     */
    public function getEssenNachTyp(string $typ, bool $nurAktiv = true): array
    {
        return $this->essenRepository->findeNachTyp($typ, $nurAktiv);
    }

    /**
     * Erstellt ein neues Essen (Rezept)
     * 
     * @param string $name Der Name
     * @param string $typ Der Typ
     * @param string|null $beschreibung Die Beschreibung
     * @return Essen Das neu erstellte Essen
     */
    public function erstelleEssen(string $name, string $typ, ?string $beschreibung = null): Essen
    {
        return $this->essenRepository->erstelle($name, $typ, $beschreibung);
    }

    /**
     * Aktualisiert ein Essen
     * 
     * @param Essen $essen Das zu aktualisierende Essen
     * @return bool True, wenn erfolgreich
     */
    public function aktualisiereEssen(Essen $essen): bool
    {
        return $this->essenRepository->aktualisiere($essen);
    }

    /**
     * Deaktiviert ein Essen
     * 
     * @param int $essenId Die Essen-ID
     * @return bool True, wenn erfolgreich
     */
    public function deaktiviereEssen(int $essenId): bool
    {
        return $this->essenRepository->deaktiviere($essenId);
    }

    /**
     * Gibt das Datum für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return string Das Datum im Format YYYY-MM-DD
     */
    public function getDatumFuerWoche(int $woche, int $jahr): string
    {
        // Erstelle einen temporären SpeiseplanEintrag, um das Datum zu berechnen
        $tempEintrag = new SpeiseplanEintrag(0, $woche, $jahr, 0, new Essen(0, '', '', null, '', true));
        return $tempEintrag->getDatum();
    }

    /**
     * Gibt alle Typen von Essen zurück
     * 
     * @return string[] Array von Typen
     */
    public function getAlleEssenTypen(): array
    {
        return Essen::ALLE_TYPEN;
    }

    /**
     * Prüft, ob ein Speiseplan für eine bestimmte Woche und Jahr existiert
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return bool True, wenn ein Speiseplan existiert
     */
    public function existiertSpeiseplanFuerWoche(int $woche, int $jahr): bool
    {
        $eintraege = $this->speiseplanRepository->findeNachWocheUndJahr($woche, $jahr);
        return !empty($eintraege);
    }

    /**
     * Gibt die Anzahl der Einträge für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der Einträge
     */
    public function zaehleEintraegeFuerWoche(int $woche, int $jahr): int
    {
        return $this->speiseplanRepository->zaehleNachWocheUndJahr($woche, $jahr);
    }
}
