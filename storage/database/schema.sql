-- Alphaessen Datenbank-Schema
-- SQLite3

-- Tabellen in der richtigen Reihenfolge (wegen Foreign Keys)

-- 1. Nutzer (Personen, die Buchungen vornehmen)
CREATE TABLE IF NOT EXISTS nutzer (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE COLLATE NOCASE,  -- Groß-/Kleinschreibung ignorieren
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Essen (Rezeptedatenbank - persistent über Jahre)
CREATE TABLE IF NOT EXISTS essen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    typ TEXT NOT NULL CHECK(typ IN ('Hauptgericht_Fleisch', 'Hauptgericht_Vegetarisch', 'Beilage','Vorspeise','Brot', 'Käse', 'Wurst', 'Nachtisch')),
    beschreibung TEXT,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    aktiv BOOLEAN DEFAULT TRUE
);

-- 3. Admin (Admin-Login)
CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    benutzername TEXT NOT NULL UNIQUE,
    passwort_hash TEXT NOT NULL,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Speiseplan (Wochenplanung - wird jährlich neu erstellt)
CREATE TABLE IF NOT EXISTS speiseplan (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    woche INTEGER NOT NULL CHECK(woche >= 1 AND woche <= 12),
    jahr INTEGER NOT NULL,
    essen_id INTEGER NOT NULL,
    FOREIGN KEY (essen_id) REFERENCES essen(id) ON DELETE CASCADE,
    UNIQUE(woche, jahr, essen_id)  -- Pro Woche+Jahr nur 1x pro Essen
);

-- 5. Buchungen (Wer bringt was wann - wird jährlich gelöscht)
CREATE TABLE IF NOT EXISTS buchungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nutzer_id INTEGER NOT NULL,
    speiseplan_id INTEGER NOT NULL,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    bestaetigt BOOLEAN DEFAULT FALSE,
    erinnerung_gesendet BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (nutzer_id) REFERENCES nutzer(id) ON DELETE CASCADE,
    FOREIGN KEY (speiseplan_id) REFERENCES speiseplan(id) ON DELETE CASCADE,
    UNIQUE(speiseplan_id, nutzer_id)  -- 1 Nutzer kann pro Essen nur 1x buchen
);

-- 6. E-Mail-Queue (Für zuverlässigen E-Mail-Versand)
CREATE TABLE IF NOT EXISTS email_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empfaenger TEXT NOT NULL,
    betreff TEXT NOT NULL,
    nachricht TEXT NOT NULL,
    typ TEXT NOT NULL CHECK(typ IN ('bestaetigung', 'erinnerung')),
    gesendet BOOLEAN DEFAULT FALSE,
    versuche INTEGER DEFAULT 0,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Indizes für bessere Performance
CREATE INDEX IF NOT EXISTS idx_nutzer_email ON nutzer(email);
CREATE INDEX IF NOT EXISTS idx_essen_typ ON essen(typ);
CREATE INDEX IF NOT EXISTS idx_speiseplan_woche_jahr ON speiseplan(woche, jahr);
CREATE INDEX IF NOT EXISTS idx_buchungen_nutzer ON buchungen(nutzer_id);
CREATE INDEX IF NOT EXISTS idx_buchungen_speiseplan ON buchungen(speiseplan_id);
CREATE INDEX IF NOT EXISTS idx_buchungen_erstellt_am ON buchungen(erstellt_am);
CREATE INDEX IF NOT EXISTS idx_email_queue_gesendet ON email_queue(gesendet);
CREATE INDEX IF NOT EXISTS idx_email_queue_erstellt_am ON email_queue(erstellt_am);

-- 8. Trigger für automatische Aktualisierung von erstellt_am
-- (SQLite unterstützt keine UPDATE-Triggers für DEFAULT-Werte, daher manuell setzen)
