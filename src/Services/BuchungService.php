<?php
/**
 * BuchungService.php - Service für Buchungslogik
 * 
 * Dieser Service behandelt die Geschäftslogik für Buchungen, inklusive:
 * - Buchung erstellen (mit Race Condition Schutz)
 * - Buchungen verwalten
 * - Verfügbarkeit prüfen
 */

namespace Alphaessen\Services;

use Alphaessen\Database;
use Alphaessen\Models\Buchung;
use Alphaessen\Models\Nutzer;
use Alphaessen\Models\SpeiseplanEintrag;
use Alphaessen\Repositories\BuchungRepository;
use Alphaessen\Repositories\NutzerRepository;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EmailQueueRepository;
use Alphaessen\Services\EmailService;

class BuchungService
{
    private BuchungRepository $buchungRepository;
    private NutzerRepository $nutzerRepository;
    private SpeiseplanRepository $speiseplanRepository;
    private EmailQueueRepository $emailQueueRepository;
    private EmailService $emailService;

    public function __construct(
        BuchungRepository $buchungRepository,
        NutzerRepository $nutzerRepository,
        SpeiseplanRepository $speiseplanRepository,
        EmailQueueRepository $emailQueueRepository,
        EmailService $emailService
    ) {
        $this->buchungRepository = $buchungRepository;
        $this->nutzerRepository = $nutzerRepository;
        $this->speiseplanRepository = $speiseplanRepository;
        $this->emailQueueRepository = $emailQueueRepository;
        $this->emailService = $emailService;
    }

    /**
     * Erstellt eine neue Buchung für einen Nutzer und einen Speiseplan-Eintrag
     * 
     * Diese Methode verwendet eine Transaktion, um Race Conditions zu vermeiden.
     * Falls das Essen bereits gebucht ist, wird eine Exception geworfen.
     * 
     * @param string $email Die E-Mail-Adresse des Nutzers
     * @param int $speiseplanId Die Speiseplan-ID
     * @return Buchung Die neu erstellte Buchung
     * @throws \RuntimeException Falls das Essen bereits gebucht ist
     */
    public function buchen(string $email, int $speiseplanId): Buchung
    {
        // Validierung
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Ungültige E-Mail-Adresse");
        }

        $speiseplanEintrag = $this->speiseplanRepository->findeNachId($speiseplanId);
        if ($speiseplanEintrag === null) {
            throw new \InvalidArgumentException("Speiseplan-Eintrag nicht gefunden");
        }

        // Nutzer finden oder erstellen
        $nutzer = $this->nutzerRepository->findeOderErstelle($email);

        // Prüfe, ob der Nutzer bereits für diesen Speiseplan-Eintrag gebucht hat
        if ($this->buchungRepository->hatNutzerGebucht($nutzer->id, $speiseplanId)) {
            throw new \RuntimeException("Sie haben bereits für dieses Essen gebucht.");
        }

        // Transaktion starten, um Race Conditions zu vermeiden
        $buchung = Database::transaction(function() use ($nutzer, $speiseplanId) {
            // Nochmal prüfen, ob der Speiseplan-Eintrag noch verfügbar ist
            // (könnte sich seit der letzten Prüfung geändert haben)
            if ($this->buchungRepository->istSpeiseplanGebucht($speiseplanId)) {
                throw new \RuntimeException("Dieses Essen wurde bereits von jemand anderem gebucht.");
            }

            // Buchung erstellen
            $buchung = $this->buchungRepository->erstelle($nutzer->id, $speiseplanId);

            // Bestätigungs-E-Mail in Queue stellen
            $this->emailService->queueBestaetigung($buchung);

            return $buchung;
        });

        return $buchung;
    }

    /**
     * Prüft, ob ein Essen für eine bestimmte Woche und Jahr verfügbar ist
     * 
     * @param int $speiseplanId Die Speiseplan-ID
     * @return bool True, wenn das Essen verfügbar ist
     */
    public function istVerfuegbar(int $speiseplanId): bool
    {
        return !$this->buchungRepository->istSpeiseplanGebucht($speiseplanId);
    }

    /**
     * Gibt alle Buchungen für einen bestimmten Nutzer zurück
     * 
     * @param string $email Die E-Mail-Adresse des Nutzers
     * @return Buchung[] Array von Buchungen
     */
    public function getBuchungenFuerNutzer(string $email): array
    {
        $nutzer = $this->nutzerRepository->findeNachEmail($email);
        if ($nutzer === null) {
            return [];
        }

        return $this->buchungRepository->findeNachNutzer($nutzer->id);
    }

    /**
     * Gibt alle Buchungen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return Buchung[] Array von Buchungen
     */
    public function getBuchungenFuerWoche(int $woche, int $jahr): array
    {
        return $this->buchungRepository->findeNachWocheUndJahr($woche, $jahr);
    }

    /**
     * Gibt alle verfügbaren Essen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return SpeiseplanEintrag[] Array von verfügbaren Speiseplan-Einträgen
     */
    public function getVerfuegbareEssenFuerWoche(int $woche, int $jahr): array
    {
        $alleEintraege = $this->speiseplanRepository->findeNachWocheUndJahr($woche, $jahr);
        $verfuegbare = [];

        foreach ($alleEintraege as $eintrag) {
            if ($this->istVerfuegbar($eintrag->id)) {
                $verfuegbare[] = $eintrag;
            }
        }

        return $verfuegbare;
    }

    /**
     * Gibt alle gebuchten Essen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return array Array mit SpeiseplanEintrag als Schlüssel und Buchung[] als Wert
     */
    public function getGebuchteEssenFuerWoche(int $woche, int $jahr): array
    {
        $gebuchteEintraege = $this->speiseplanRepository->findeNachWocheUndJahr($woche, $jahr);
        $gebucht = [];

        foreach ($gebuchteEintraege as $eintrag) {
            $buchungen = $this->buchungRepository->findeNachSpeiseplan($eintrag->id);
            if (!empty($buchungen)) {
                $gebucht[$eintrag->id] = $buchungen;
            }
        }

        return $gebucht;
    }

    /**
     * Löscht eine Buchung
     * 
     * @param int $buchungId Die Buchung-ID
     * @return bool True, wenn erfolgreich
     */
    public function loescheBuchung(int $buchungId): bool
    {
        return $this->buchungRepository->loesche($buchungId);
    }

    /**
     * Löscht alle Buchungen für ein bestimmtes Jahr
     * 
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der gelöschten Buchungen
     */
    public function loescheBuchungenNachJahr(int $jahr): int
    {
        return $this->buchungRepository->loescheNachJahr($jahr);
    }

    /**
     * Gibt die Anzahl der Buchungen für einen bestimmten Nutzer zurück
     * 
     * @param string $email Die E-Mail-Adresse des Nutzers
     * @return int Die Anzahl der Buchungen
     */
    public function zaehleBuchungenFuerNutzer(string $email): int
    {
        $buchungen = $this->getBuchungenFuerNutzer($email);
        return count($buchungen);
    }

    /**
     * Gibt die Anzahl der verfügbaren Essen für eine bestimmte Woche und Jahr zurück
     * 
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return int Die Anzahl der verfügbaren Essen
     */
    public function zaehleVerfuegbareEssenFuerWoche(int $woche, int $jahr): int
    {
        $verfuegbare = $this->getVerfuegbareEssenFuerWoche($woche, $jahr);
        return count($verfuegbare);
    }

    /**
     * Gibt alle Buchungen eines Nutzers für einen bestimmten Abend zurück
     * 
     * @param string $email Die E-Mail-Adresse des Nutzers
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return Buchung[] Array von Buchungen
     */
    public function getBuchungenFuerNutzerUndAbend(string $email, int $woche, int $jahr): array
    {
        $alleBuchungen = $this->getBuchungenFuerNutzer($email);
        $abendBuchungen = [];

        foreach ($alleBuchungen as $buchung) {
            if ($buchung->istFuerWocheUndJahr($woche, $jahr)) {
                $abendBuchungen[] = $buchung;
            }
        }

        return $abendBuchungen;
    }
}
