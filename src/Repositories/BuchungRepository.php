<?php
/**
 * BuchungRepository.php - Repository für Buchungen
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\Buchung;
use Alphaessen\Models\Nutzer;
use Alphaessen\Models\SpeiseplanEintrag;

class BuchungRepository
{
    private NutzerRepository $nutzerRepository;
    private SpeiseplanRepository $speiseplanRepository;

    public function __construct(NutzerRepository $nutzerRepository, SpeiseplanRepository $speiseplanRepository)
    {
        $this->nutzerRepository = $nutzerRepository;
        $this->speiseplanRepository = $speiseplanRepository;
    }

    /**
     * Fügt eine neue Buchung hinzu
     * 
     * @param int $nutzerId Die Nutzer-ID
     * @param int $speiseplanId Die Speiseplan-ID
     * @return Buchung Die neu erstellte Buchung
     */
    public function erstelle(int $nutzerId, int $speiseplanId): Buchung
    {
        $stmt = Database::query(
            "INSERT INTO buchungen (nutzer_id, speiseplan_id, bestaetigt) VALUES (:nutzer_id, :speiseplan_id, FALSE)",
            [
                'nutzer_id' => $nutzerId,
                'speiseplan_id' => $speiseplanId,
            ]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet eine Buchung nach ID
     * 
     * @param int $id Die Buchung-ID
     * @return Buchung|null Die Buchung oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?Buchung
    {
        $stmt = Database::query(
            "SELECT * FROM buchungen WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        $nutzer = $this->nutzerRepository->findeNachId((int)$data['nutzer_id']);
        $speiseplanEintrag = $this->speiseplanRepository->findeNachId((int)$data['speiseplan_id']);

        if ($nutzer === null || $speiseplanEintrag === null) {
            return null;
        }

        return Buchung::fromArray($data, $nutzer, $speiseplanEintrag);
    }

    /**
     * Gibt alle Buchungen für einen bestimmten Nutzer zurück
     * 
     * @param int $nutzerId Die Nutzer-ID
     * @return Buchung[] Array von Buchungen
     */
    public function findeNachNutzer(int $nutzerId): array
    {
        $stmt = Database::query(
            "SELECT * FROM buchungen WHERE nutzer_id = :nutzer_id ORDER BY erstellt_am DESC",
            ['nutzer_id' => $nutzerId]
        );

        $buchungen = [];
        while ($data = $stmt->fetch()) {
            $buchung = $this->findeNachId((int)$data['id']);
            if ($buchung !== null) {
                $buchungen[] = $buchung;
            }
        }

        return $buchungen;
    }

    /**
     * Gibt alle Buchungen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return Buchung[] Array von Buchungen
     */
    public function findeNachWocheUndJahr(int $woche, int $jahr): array
    {
        $stmt = Database::query(
            "SELECT b.* FROM buchungen b 
             JOIN speiseplan sp ON b.speiseplan_id = sp.id 
             WHERE sp.woche = :woche AND sp.jahr = :jahr 
             ORDER BY b.erstellt_am",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        $buchungen = [];
        while ($data = $stmt->fetch()) {
            $buchung = $this->findeNachId((int)$data['id']);
            if ($buchung !== null) {
                $buchungen[] = $buchung;
            }
        }

        return $buchungen;
    }

    /**
     * Gibt alle Buchungen für einen bestimmten Speiseplan-Eintrag zurück
     * 
     * @param int $speiseplanId Die Speiseplan-ID
     * @return Buchung[] Array von Buchungen
     */
    public function findeNachSpeiseplan(int $speiseplanId): array
    {
        $stmt = Database::query(
            "SELECT * FROM buchungen WHERE speiseplan_id = :speiseplan_id ORDER BY erstellt_am",
            ['speiseplan_id' => $speiseplanId]
        );

        $buchungen = [];
        while ($data = $stmt->fetch()) {
            $buchung = $this->findeNachId((int)$data['id']);
            if ($buchung !== null) {
                $buchungen[] = $buchung;
            }
        }

        return $buchungen;
    }

    /**
     * Gibt alle Buchungen für ein bestimmtes Jahr zurück
     * 
     * @param int $jahr Das Jahr
     * @return Buchung[] Array von Buchungen
     */
    public function findeNachJahr(int $jahr): array
    {
        $stmt = Database::query(
            "SELECT b.* FROM buchungen b 
             JOIN speiseplan sp ON b.speiseplan_id = sp.id 
             WHERE sp.jahr = :jahr 
             ORDER BY sp.woche, b.erstellt_am",
            ['jahr' => $jahr]
        );

        $buchungen = [];
        while ($data = $stmt->fetch()) {
            $buchung = $this->findeNachId((int)$data['id']);
            if ($buchung !== null) {
                $buchungen[] = $buchung;
            }
        }

        return $buchungen;
    }

    /**
     * Prüft, ob ein Nutzer bereits für einen bestimmten Speiseplan-Eintrag gebucht hat
     * 
     * @param int $nutzerId Die Nutzer-ID
     * @param int $speiseplanId Die Speiseplan-ID
     * @return bool True, wenn der Nutzer bereits gebucht hat
     */
    public function hatNutzerGebucht(int $nutzerId, int $speiseplanId): bool
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM buchungen WHERE nutzer_id = :nutzer_id AND speiseplan_id = :speiseplan_id",
            ['nutzer_id' => $nutzerId, 'speiseplan_id' => $speiseplanId]
        );

        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Prüft, ob ein Speiseplan-Eintrag bereits gebucht ist
     * 
     * @param int $speiseplanId Die Speiseplan-ID
     * @return bool True, wenn der Eintrag bereits gebucht ist
     */
    public function istSpeiseplanGebucht(int $speiseplanId): bool
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM buchungen WHERE speiseplan_id = :speiseplan_id",
            ['speiseplan_id' => $speiseplanId]
        );

        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Aktualisiert eine Buchung
     * 
     * @param Buchung $buchung Die zu aktualisierende Buchung
     * @return bool True, wenn erfolgreich
     */
    public function aktualisiere(Buchung $buchung): bool
    {
        $stmt = Database::query(
            "UPDATE buchungen SET 
                nutzer_id = :nutzer_id,
                speiseplan_id = :speiseplan_id,
                bestaetigt = :bestaetigt,
                erinnerung_gesendet = :erinnerung_gesendet
             WHERE id = :id",
            [
                'id' => $buchung->id,
                'nutzer_id' => $buchung->nutzerId,
                'speiseplan_id' => $buchung->speiseplanId,
                'bestaetigt' => $buchung->bestaetigt ? 1 : 0,
                'erinnerung_gesendet' => $buchung->erinnerungGesendet ? 1 : 0,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht eine Buchung
     * 
     * @param int $id Die Buchung-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM buchungen WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht alle Buchungen für ein bestimmtes Jahr
     * 
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Buchungen
     */
    public function loescheNachJahr(int $jahr): int
    {
        $stmt = Database::query(
            "DELETE FROM buchungen 
             WHERE speiseplan_id IN (
                 SELECT id FROM speiseplan WHERE jahr = :jahr
             )",
            ['jahr' => $jahr]
        );

        return $stmt->rowCount();
    }

    /**
     * Löscht alle Buchungen für eine bestimmte Woche und Jahr
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Buchungen
     */
    public function loescheNachWocheUndJahr(int $woche, int $jahr): int
    {
        $stmt = Database::query(
            "DELETE FROM buchungen 
             WHERE speiseplan_id IN (
                 SELECT id FROM speiseplan WHERE woche = :woche AND jahr = :jahr
             )",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        return $stmt->rowCount();
    }

    /**
     * Gibt die Anzahl der Buchungen für einen bestimmten Speiseplan-Eintrag zurück
     * 
     * @param int $speiseplanId Die Speiseplan-ID
     * @return int Die Anzahl der Buchungen
     */
    public function zaehleNachSpeiseplan(int $speiseplanId): int
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM buchungen WHERE speiseplan_id = :speiseplan_id",
            ['speiseplan_id' => $speiseplanId]
        );

        return (int)$stmt->fetch()['count'];
    }

    /**
     * Gibt die Anzahl der Buchungen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der Buchungen
     */
    public function zaehleNachWocheUndJahr(int $woche, int $jahr): int
    {
        $stmt = Database::query(
            "SELECT COUNT(*) as count FROM buchungen b 
             JOIN speiseplan sp ON b.speiseplan_id = sp.id 
             WHERE sp.woche = :woche AND sp.jahr = :jahr",
            ['woche' => $woche, 'jahr' => $jahr]
        );

        return (int)$stmt->fetch()['count'];
    }

    // In BuchungRepository.php
    public function zaehleNachJahr(int $jahr): int
    {
        $stmt = Database::query(
        "SELECT COUNT(*) as count FROM buchungen b 
             JOIN speiseplan sp ON b.speiseplan_id = sp.id 
             WHERE sp.jahr = :jahr",
            ['jahr' => $jahr]
        );
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Gibt alle Buchungen zurück
     * 
     * @return Buchung[] Array von allen Buchungen
     */
    public function findeAlle(): array
    {
        $stmt = Database::query("SELECT * FROM buchungen ORDER BY erstellt_am DESC");

        $buchungen = [];
        while ($data = $stmt->fetch()) {
            $buchung = $this->findeNachId((int)$data['id']);
            if ($buchung !== null) {
                $buchungen[] = $buchung;
            }
        }

        return $buchungen;
    }

    /**
     * Setzt den Bestätigungsstatus einer Buchung
     * 
     * @param int $buchungId Die Buchung-ID
     * @param bool $bestaetigt Der neue Bestätigungsstatus
     * @return bool True, wenn erfolgreich
     */
    public function setzeBestaetigt(int $buchungId, bool $bestaetigt = true): bool
    {
        $stmt = Database::query(
            "UPDATE buchungen SET bestaetigt = :bestaetigt WHERE id = :id",
            ['id' => $buchungId, 'bestaetigt' => $bestaetigt ? 1 : 0]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Setzt den Erinnerungsstatus einer Buchung
     * 
     * @param int $buchungId Die Buchung-ID
     * @param bool $erinnerungGesendet Der neue Erinnerungsstatus
     * @return bool True, wenn erfolgreich
     */
    public function setzeErinnerungGesendet(int $buchungId, bool $erinnerungGesendet = true): bool
    {
        $stmt = Database::query(
            "UPDATE buchungen SET erinnerung_gesendet = :erinnerung_gesendet WHERE id = :id",
            ['id' => $buchungId, 'erinnerung_gesendet' => $erinnerungGesendet ? 1 : 0]
        );

        return $stmt->rowCount() > 0;
    }


}
