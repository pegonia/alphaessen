<?php
/**
 * EmailQueueRepository.php - Repository für E-Mail-Queue
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\EmailQueue;

class EmailQueueRepository
{
    /**
     * Fügt eine neue E-Mail zur Queue hinzu
     * 
     * @param string $empfaenger Der Empfänger
     * @param string $betreff Der Betreff
     * @param string $nachricht Die Nachricht
     * @param string $typ Der Typ (bestaetigung oder erinnerung)
     * @return EmailQueue Die neu erstellte Queue-Eintrag
     */
    public function erstelle(string $empfaenger, string $betreff, string $nachricht, string $typ): EmailQueue
    {
        $stmt = Database::query(
            "INSERT INTO email_queue (empfaenger, betreff, nachricht, typ) VALUES (:empfaenger, :betreff, :nachricht, :typ)",
            [
                'empfaenger' => trim($empfaenger),
                'betreff' => trim($betreff),
                'nachricht' => $nachricht,
                'typ' => $typ,
            ]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet einen Queue-Eintrag nach ID
     * 
     * @param int $id Die Queue-ID
     * @return EmailQueue|null Der Queue-Eintrag oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?EmailQueue
    {
        $stmt = Database::query(
            "SELECT * FROM email_queue WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return EmailQueue::fromArray($data);
    }

    /**
     * Gibt alle nicht gesendeten E-Mails zurück
     * 
     * @param int $maxVersuche Maximale Anzahl von Versuchen (0 = alle nicht gesendeten)
     * @return EmailQueue[] Array von Queue-Einträgen
     */
    public function findeNichtGesendete(int $maxVersuche = 3): array
    {
        $sql = "SELECT * FROM email_queue WHERE gesendet = FALSE";
        if ($maxVersuche > 0) {
            $sql .= " AND versuche < :max_versuche";
        }
        $sql .= " ORDER BY erstellt_am";

        $params = [];
        if ($maxVersuche > 0) {
            $params['max_versuche'] = $maxVersuche;
        }

        $stmt = Database::query($sql, $params);

        $queue = [];
        while ($data = $stmt->fetch()) {
            $queue[] = EmailQueue::fromArray($data);
        }

        return $queue;
    }

    /**
     * Gibt alle E-Mails eines bestimmten Typs zurück
     * 
     * @param string $typ Der Typ
     * @return EmailQueue[] Array von Queue-Einträgen
     */
    public function findeNachTyp(string $typ): array
    {
        $stmt = Database::query(
            "SELECT * FROM email_queue WHERE typ = :typ ORDER BY erstellt_am",
            ['typ' => $typ]
        );

        $queue = [];
        while ($data = $stmt->fetch()) {
            $queue[] = EmailQueue::fromArray($data);
        }

        return $queue;
    }

    /**
     * Markiert eine E-Mail als gesendet
     * 
     * @param int $id Die Queue-ID
     * @return bool True, wenn erfolgreich
     */
    public function markiereAlsGesendet(int $id): bool
    {
        $stmt = Database::query(
            "UPDATE email_queue SET gesendet = TRUE, versuche = versuche + 1 WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Erhöht den Versuchs-Zähler für eine E-Mail
     * 
     * @param int $id Die Queue-ID
     * @return bool True, wenn erfolgreich
     */
    public function erhoeheVersuche(int $id): bool
    {
        $stmt = Database::query(
            "UPDATE email_queue SET versuche = versuche + 1 WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht eine E-Mail aus der Queue
     * 
     * @param int $id Die Queue-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM email_queue WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht alle gesendeten E-Mails aus der Queue
     * 
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheGesendete(): int
    {
        $stmt = Database::query("DELETE FROM email_queue WHERE gesendet = TRUE");
        return $stmt->rowCount();
    }

    /**
     * Löscht alle E-Mails, die älter als X Tage sind
     * 
     * @param int $tage Die Anzahl der Tage
     * @return int Die Anzahl der gelöschten Einträge
     */
    public function loescheAeltereAls(int $tage): int
    {
        $stmt = Database::query(
            "DELETE FROM email_queue WHERE erstellt_am < datetime('now', '-{$tage} days')"
        );
        return $stmt->rowCount();
    }

    /**
     * Gibt die Anzahl der nicht gesendeten E-Mails zurück
     * 
     * @return int Die Anzahl der nicht gesendeten E-Mails
     */
    public function zaehleNichtGesendete(): int
    {
        $stmt = Database::query("SELECT COUNT(*) as count FROM email_queue WHERE gesendet = FALSE");
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Gibt die Anzahl aller E-Mails in der Queue zurück
     * 
     * @return int Die Anzahl aller E-Mails
     */
    public function zaehleAlle(): int
    {
        $stmt = Database::query("SELECT COUNT(*) as count FROM email_queue");
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Gibt alle Queue-Einträge zurück
     * 
     * @return EmailQueue[] Array von allen Queue-Einträgen
     */
    public function findeAlle(): array
    {
        $stmt = Database::query("SELECT * FROM email_queue ORDER BY erstellt_am DESC");

        $queue = [];
        while ($data = $stmt->fetch()) {
            $queue[] = EmailQueue::fromArray($data);
        }

        return $queue;
    }
}
