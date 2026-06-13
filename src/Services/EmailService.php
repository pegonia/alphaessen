<?php
/**
 * EmailService.php - Service für E-Mail-Funktionalität
 * 
 * Dieser Service behandelt:
 * - Generierung von E-Mail-Inhalten
 * - Queueing von E-Mails
 * - Versand von E-Mails
 */

namespace Alphaessen\Services;

use Alphaessen\Models\Buchung;
use Alphaessen\Models\EmailQueue;
use Alphaessen\Repositories\EmailQueueRepository;

class EmailService
{
    private EmailQueueRepository $emailQueueRepository;
    private array $emailConfig;

    public function __construct(EmailQueueRepository $emailQueueRepository, array $emailConfig)
    {
        $this->emailQueueRepository = $emailQueueRepository;
        $this->emailConfig = $emailConfig;
    }

    /**
     * Erstellt eine Bestätigungs-E-Mail und fügt sie zur Queue hinzu
     * 
     * @param Buchung $buchung Die Buchung, für die die Bestätigung gesendet werden soll
     * @return EmailQueue Der Queue-Eintrag
     */
    public function queueBestaetigung(Buchung $buchung): EmailQueue
    {
        $datum = $buchung->getDatumDeutsch();
        $essenName = $buchung->speiseplanEintrag->essen->name;
        $essenTyp = $buchung->speiseplanEintrag->essen->getTypAnzeige();
        $woche = $buchung->speiseplanEintrag->woche;

        $betreff = str_replace('{Datum}', $datum, $this->emailConfig['subjects']['bestaetigung']);

        $nachricht = "Hallo,\n\n" .
                     "Sie haben erfolgreich den folgenden Essensbeitrag für den Alphaessen-Abend gebucht:\n\n" .
                     "- {$essenName} ({$essenTyp})\n" .
                     "- Woche: {$woche}\n" .
                     "- Datum: {$datum}\n\n" .
                     "Vielen Dank für Ihre Unterstützung!\n\n" .
                     "Mit freundlichen Grüßen,\n" .
                     "Ihr Alphaessen-Team";

        return $this->emailQueueRepository->erstelle(
            $buchung->nutzer->email,
            $betreff,
            $nachricht,
            EmailQueue::TYP_BESTAETIGUNG
        );
    }

    /**
     * Erstellt eine Erinnerungs-E-Mail und fügt sie zur Queue hinzu
     * 
     * @param Buchung $buchung Die Buchung, für die die Erinnerung gesendet werden soll
     * @return EmailQueue Der Queue-Eintrag
     */
    public function queueErinnerung(Buchung $buchung): EmailQueue
    {
        $datum = $buchung->getDatumDeutsch();
        $woche = $buchung->speiseplanEintrag->woche;

        $betreff = str_replace('{Datum}', $datum, $this->emailConfig['subjects']['erinnerung']);

        // Alle Buchungen des Nutzers für diesen Abend abfragen
        $buchungenDesAbends = $this->getBuchungenDesNutzersFuerAbend(
            $buchung->nutzer->email,
            $woche,
            $buchung->speiseplanEintrag->jahr
        );

        $essenListe = "";
        foreach ($buchungenDesAbends as $b) {
            $essenListe .= "- {$b->speiseplanEintrag->essen->name} ({$b->speiseplanEintrag->essen->getTypAnzeige()})\n";
        }

        $nachricht = "Hallo,\n\n" .
                     "dies ist eine Erinnerung an Ihre Buchung für den Alphaessen-Abend am **{$datum}** (Woche {$woche}).\n\n" .
                     "Sie bringen folgende Beiträge mit:\n" .
                     $essenListe .
                     "\nVielen Dank für Ihre Unterstützung!\n\n" .
                     "Mit freundlichen Grüßen,\n" .
                     "Ihr Alphaessen-Team";

        return $this->emailQueueRepository->erstelle(
            $buchung->nutzer->email,
            $betreff,
            $nachricht,
            EmailQueue::TYP_ERINNERUNG
        );
    }

    /**
     * Sendet eine E-Mail direkt (ohne Queue)
     * 
     * @param string $empfaenger Der Empfänger
     * @param string $betreff Der Betreff
     * @param string $nachricht Die Nachricht
     * @return bool True, wenn der Versand erfolgreich war
     */
    public function sendeEmail(string $empfaenger, string $betreff, string $nachricht): bool
    {
        $from = $this->emailConfig['from'];
        
        $headers = [
            'From: ' . $from['name'] . ' <' . $from['email'] . '>',
            'Reply-To: ' . $from['email'],
        ];

        // Füge zusätzliche Header aus der Konfiguration hinzu
        foreach ($this->emailConfig['headers'] as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        $headersString = implode("\r\n", $headers);

        // E-Mail senden
        $erfolg = mail($empfaenger, $betreff, $nachricht, $headersString);

        // Fehler loggen
        if (!$erfolg) {
            $error = error_get_last();
            if ($error !== null) {
                error_log("E-Mail-Versand fehlgeschlagen: " . $error['message']);
            }
        }

        return $erfolg;
    }

    /**
     * Verarbeitet die E-Mail-Queue und sendet nicht gesendete E-Mails
     * 
     * @param int $maxVersuche Maximale Anzahl von Versuchen pro E-Mail
     * @return int Die Anzahl der erfolgreich gesendeten E-Mails
     */
    public function verarbeiteQueue(int $maxVersuche = 3): int
    {
        $nichtGesendete = $this->emailQueueRepository->findeNichtGesendete($maxVersuche);
        $gesendetCount = 0;

        foreach ($nichtGesendete as $queueEintrag) {
            $erfolg = $this->sendeEmail(
                $queueEintrag->empfaenger,
                $queueEintrag->betreff,
                $queueEintrag->nachricht
            );

            if ($erfolg) {
                $this->emailQueueRepository->markiereAlsGesendet($queueEintrag->id);
                $gesendetCount++;
            } else {
                $this->emailQueueRepository->erhoeheVersuche($queueEintrag->id);
            }
        }

        return $gesendetCount;
    }

    /**
     * Gibt alle Buchungen eines Nutzers für einen bestimmten Abend zurück
     * (Hilfsmethode für Erinnerungs-E-Mails)
     * 
     * @param string $email Die E-Mail-Adresse
     * @param int $woche Die Woche
     * @param int $jahr Das Jahr
     * @return Buchung[] Array von Buchungen
     */
    private function getBuchungenDesNutzersFuerAbend(string $email, int $woche, int $jahr): array
    {
        // Diese Methode sollte eigentlich im BuchungService sein,
        // aber wir haben hier keinen Zugriff darauf.
        // Für jetzt eine einfache Implementierung:
        // In einer echten Anwendung würden wir die Abhängigkeit injizieren.
        
        // TODO: Refactoring - BuchungService injizieren
        return [];
    }

    /**
     * Setzt die Buchung als bestätigt und sendet die Bestätigungs-E-Mail
     * 
     * @param Buchung $buchung Die Buchung
     */
    public function sendeBestaetigung(Buchung $buchung): void
    {
        $this->queueBestaetigung($buchung);
        // Markiere als bestätigt
        // TODO: BuchungRepository injizieren und aufrufen
    }

    /**
     * Sendet Erinnerungs-E-Mails für alle Buchungen eines bestimmten Datums
     * 
     * @param string $datum Das Datum (YYYY-MM-DD)
     * @return int Die Anzahl der gesendeten Erinnerungen
     */
    public function sendeErinnerungenFuerDatum(string $datum): int
    {
        // TODO: Implementierung mit BuchungRepository
        // 1. Alle Buchungen für das gegebene Datum abfragen
        // 2. Für jede Buchung eine Erinnerung in die Queue stellen
        // 3. Queue verarbeiten
        
        return 0;
    }
}
