<?php
/**
 * EssenRepository.php - Repository für Essen (Rezepte)
 */

namespace Alphaessen\Repositories;

use Alphaessen\Database;
use Alphaessen\Models\Essen;

class EssenRepository
{
    /**
     * Fügt ein neues Essen hinzu
     * 
     * @param string $name Der Name des Essens
     * @param string $typ Der Typ des Essens
     * @param string|null $beschreibung Die Beschreibung (optional)
     * @return Essen Das neu erstellte Essen
     */
    public function erstelle(string $name, string $typ, ?string $beschreibung = null): Essen
    {
        $stmt = Database::query(
            "INSERT INTO essen (name, typ, beschreibung) VALUES (:name, :typ, :beschreibung)",
            [
                'name' => trim($name),
                'typ' => $typ,
                'beschreibung' => $beschreibung !== null ? trim($beschreibung) : null,
            ]
        );

        $id = Database::lastInsertId();
        return $this->findeNachId((int)$id);
    }

    /**
     * Findet ein Essen nach ID
     * 
     * @param int $id Die Essen-ID
     * @return Essen|null Das Essen oder null, falls nicht gefunden
     */
    public function findeNachId(int $id): ?Essen
    {
        $stmt = Database::query(
            "SELECT * FROM essen WHERE id = :id",
            ['id' => $id]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Essen::fromArray($data);
    }

    /**
     * Findet ein Essen nach Name und Typ
     * 
     * @param string $name Der Name des Essens
     * @param string $typ Der Typ des Essens
     * @return Essen|null Das Essen oder null, falls nicht gefunden
     */
    public function findeNachNameUndTyp(string $name, string $typ): ?Essen
    {
        $stmt = Database::query(
            "SELECT * FROM essen WHERE name = :name AND typ = :typ",
            ['name' => trim($name), 'typ' => $typ]
        );

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Essen::fromArray($data);
    }

    /**
     * Gibt alle Essen zurück
     * 
     * @param bool $nurAktiv Nur aktive Essen zurückgeben
     * @return Essen[] Array von Essen
     */
    public function findeAlle(bool $nurAktiv = false): array
    {
        $sql = "SELECT * FROM essen";
        if ($nurAktiv) {
            $sql .= " WHERE aktiv = 1";
        }
        $sql .= " ORDER BY typ, name";

        $stmt = Database::query($sql);
        
        $essen = [];
        while ($data = $stmt->fetch()) {
            $essen[] = Essen::fromArray($data);
        }

        return $essen;
    }

    /**
     * Gibt alle Essen nach Typ zurück
     * 
     * @param string $typ Der Typ der Essen
     * @param bool $nurAktiv Nur aktive Essen zurückgeben
     * @return Essen[] Array von Essen
     */
    public function findeNachTyp(string $typ, bool $nurAktiv = false): array
    {
        $sql = "SELECT * FROM essen WHERE typ = :typ";
        if ($nurAktiv) {
            $sql .= " AND aktiv = 1";
        }
        $sql .= " ORDER BY name";

        $stmt = Database::query($sql, ['typ' => $typ]);
        
        $essen = [];
        while ($data = $stmt->fetch()) {
            $essen[] = Essen::fromArray($data);
        }

        return $essen;
    }

    /**
     * Aktualisiert ein Essen
     * 
     * @param Essen $essen Das zu aktualisierende Essen
     * @return bool True, wenn erfolgreich
     */
    public function aktualisiere(Essen $essen): bool
    {
        $stmt = Database::query(
            "UPDATE essen SET name = :name, typ = :typ, beschreibung = :beschreibung, aktiv = :aktiv WHERE id = :id",
            [
                'id' => $essen->id,
                'name' => trim($essen->name),
                'typ' => $essen->typ,
                'beschreibung' => $essen->beschreibung !== null ? trim($essen->beschreibung) : null,
                'aktiv' => $essen->aktiv ? 1 : 0,
            ]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht ein Essen (setzt aktiv = false, löscht nicht physisch)
     * 
     * @param int $id Die Essen-ID
     * @return bool True, wenn erfolgreich
     */
    public function deaktiviere(int $id): bool
    {
        $stmt = Database::query(
            "UPDATE essen SET aktiv = 0 WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Löscht ein Essen physisch (Vorsicht: kann Buchungen beeinflussen!)
     * 
     * @param int $id Die Essen-ID
     * @return bool True, wenn erfolgreich
     */
    public function loesche(int $id): bool
    {
        $stmt = Database::query(
            "DELETE FROM essen WHERE id = :id",
            ['id' => $id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Gibt die Anzahl der Essen zurück
     * 
     * @param bool $nurAktiv Nur aktive Essen zählen
     * @return int Die Anzahl der Essen
     */
    public function zaehle(bool $nurAktiv = false): int
    {
        $sql = "SELECT COUNT(*) as count FROM essen";
        if ($nurAktiv) {
            $sql .= " WHERE aktiv = 1";
        }

        $stmt = Database::query($sql);
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Sucht nach Essen
     * 
     * @param string $suchbegriff Der Suchbegriff
     * @param bool $nurAktiv Nur aktive Essen durchsuchen
     * @return Essen[] Array von passenden Essen
     */
    public function suche(string $suchbegriff, bool $nurAktiv = false): array
    {
        $suchbegriff = trim($suchbegriff);
        if (empty($suchbegriff)) {
            return $this->findeAlle($nurAktiv);
        }

        $sql = "SELECT * FROM essen WHERE name LIKE :suchbegriff OR beschreibung LIKE :suchbegriff";
        if ($nurAktiv) {
            $sql .= " AND aktiv = 1";
        }
        $sql .= " ORDER BY typ, name";

        $searchPattern = '%' . $suchbegriff . '%';
        $stmt = Database::query($sql, ['suchbegriff' => $searchPattern]);
        
        $essen = [];
        while ($data = $stmt->fetch()) {
            $essen[] = Essen::fromArray($data);
        }

        return $essen;
    }
}
