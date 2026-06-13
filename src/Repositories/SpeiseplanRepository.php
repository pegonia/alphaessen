<?php
/**
 * SpeiseplanRepository.php - Repository für Speiseplan
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\SpeiseplanEintrag;
use Alphaessen\Models\Essen;

class SpeiseplanRepository
{
    private EssenRepository $essenRepository;

    public function __construct(EssenRepository $essenRepository)
    {
        $this->essenRepository = $essenRepository;
    }

    /**
     * Fügt einen Eintrag zum Speiseplan hinzu
     * 
     * @param int $woche Die Woche (1-12)
     * @param int $jahr Das Jahr
     * @param int $essenId Die Essen-ID
     * @return SpeiseplanEintrag Der neu erstellte Eintrag
     */
    public function erstelle(int $woche, int $jahr, int $essenId): SpeiseplanEintrag
    {
        $stmt = Database::query(
            "INSERT INTO speiseplan (woche, jahr, essen_id) VALUES (:woche, :jahr, :essen_id)",
            [
                'woche' => $woche,
                'jahr' => $jahr,
                'essen_id' => $essenId,
            ]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet einen Speiseplan-Eintrag nach ID
     * 
     * @param int $id Die Eintrag-ID
     * @return SpeiseplanEintrag|null Der Eintrag oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?SpeiseplanEintrag
    {
        $stmt = Database::query(
            "SELECT * FROM speiseplan WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        $essen = $this->essenRepository->findeNachId((int)$data['essen_id']);
        if ($essen === null) {
            return null;
        }

        return SpeiseplanEintrag::fromArray($data, $essen);
    }

    /**
     * Gibt alle Einträge für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche (1-12)
     * @param int $jahr Das Jahr
     * @return SpeiseplanEintrag[] Array von Einträgen
     */
    public function findeNachWocheUndJahr(int $woche, int $jahr): array
    {
        $stmt = Database::query(
            "SELECT * FROM speiseplan WHERE woche = :woche AND jahr = :jahr ORDER BY typ, name",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        $eintraege = [];
        while ($data = $stmt->fetch()) {
            $essen = $this->essenRepository->findeNachId((int)$data['essen_id']);
            if ($essen !== null) {
                $eintraege[] = SpeiseplanEintrag::fromArray($data, $essen);
            }
        }

        return $eintraege;
    }

    /**
     * Gibt alle Einträge für ein bestimmtes Jahr zurück
     * 
     * @param int $jahr Das Jahr
     * @return SpeiseplanEintrag[] Array von Einträgen
     */
    public function findeNachJahr(int $jahr): array
    {
        $stmt = Database::query(
            "SELECT * FROM speiseplan WHERE jahr = :jahr ORDER BY woche, typ, name",
            ['jahr' => $jahr]
        );

        $eintraege = [];
        while ($data = $stmt->fetch()) {
            $essen = $this->essenRepository->findeNachId((int)$data['essen_id']);
            if ($essen !== null) {
                $eintraege[] = SpeiseplanEintrag::fromArray($data, $essen);
            }
        }

        return $eintraege;
    }

    /**
     * Gibt alle Wochen für ein bestimmtes Jahr zurück, die einen Speiseplan haben
     * 
     * @param int $jahr Das Jahr
     * @return int[] Array von Wochen (1-12)
     */
    public function findeWochenNachJahr(int $jahr): array
    {
        $stmt = Database::query(
            "SELECT DISTINCT woche FROM speiseplan WHERE jahr = :jahr ORDER BY woche",
            ['jahr' => $jahr]
        );

        $wochen = [];
        while ($data = $stmt->fetch()) {
            $wochen[] = (int)$data['woche'];
        }

        return $wochen;
    }

    /**
     * Gibt alle Jahre zurück, für die ein Speiseplan existiert
     * 
     * @return int[] Array von Jahren
     */
    public function findeAlleJahre(): array
    {
        $stmt = Database::query("SELECT DISTINCT jahr FROM speiseplan ORDER BY jahr DESC");

        $jahre = [];
        while ($data = $stmt->fetch()) {
            $jahre[] = (int)$data['jahr'];
        }

        return $jahre;
    }

    /**
     * Prüft, ob ein Essen für eine bestimmte Woche und Jahr bereits im Speiseplan ist
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @param int $essenId Die Essen-ID
     * @return bool True, wenn das Essen bereits im Speiseplan ist
     */
    public function istEssenImSpeiseplan(int $woche, int $jahr, int $essenId): bool
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM speiseplan WHERE woche = :woche AND jahr = :jahr AND essen_id = :essen_id",
            ['woche' => $woche, 'jahr' => $jahr, 'essen_id' => $essenId]
        );

        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Löscht einen Eintrag aus dem Speiseplan
     * 
     * @param int $id Die Eintrag-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM speiseplan WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht alle Einträge für eine bestimmte Woche und Jahr
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheNachWocheUndJahr(int $woche, int $jahr): int
    {
        $stmt = Database::query(
            "DELETE FROM speiseplan WHERE woche = :woche AND jahr = :jahr",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        return $stmt->rowCount();
    }

    /**
     * Löscht alle Einträge für ein bestimmtes Jahr
     * 
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheNachJahr(int $jahr): int
    {
        $stmt = Database::query(
            "DELETE FROM speiseplan WHERE jahr = :jahr",
            ['jahr' => $jahr]
        );

        return $stmt->rowCount();
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
        // Zuerst alle Einträge der Zielwoche löschen
        $this->loescheNachWocheUndJahr($nachWoche, $nachJahr);

        // Alle Einträge der Quellwoche abfragen
        $eintraege = $this->findeNachWocheUndJahr($vonWoche, $vonJahr);

        $count = 0;
        foreach ($eintraege as $eintrag) {
            $this->erstelle($nachWoche, $nachJahr, $eintrag->essenId);
            $count++;
        }

        return $count;
    }

    /**
     * Gibt die Anzahl der Einträge für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der Einträge
     */
    public function zaehleNachWocheUndJahr(int $woche, int $jahr): int
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM speiseplan WHERE woche = :woche AND jahr = :jahr",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        return (int)$stmt->fetch()['count'];
    }

    /**
     * Gibt alle Einträge zurück
     * 
     * @return SpeiseplanEintrag[] Array von allen Einträgen
     */
    public function findeAlle(): array
    {
        $stmt = Database::query("SELECT * FROM speiseplan ORDER BY jahr DESC, woche, typ, name");

        $eintraege = [];
        while ($data = $stmt->fetch()) {
            $essen = $this->essenRepository->findeNachId((int)$data['essen_id']);
            if ($essen !== null) {
                $eintraege[] = SpeiseplanEintrag::fromArray($data, $essen);
            }
        }

        return $eintraege;
    }
}
