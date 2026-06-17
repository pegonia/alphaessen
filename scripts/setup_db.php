<?php
/**
 * setup_db.php - Skript zum Erstellen der Datenbank und Einfügen von Beispiel-Daten
 * 
 * Usage: php scripts/setup_db.php
 */

require_once __DIR__ . '/../src/Database.php';

use Alphaessen\Database;

// Datenbankkonfiguration laden
$config = require __DIR__ . '/../config/database.php';
Database::init($config);

echo "=== Alphaessen Datenbank-Setup ===\n\n";

try {
    // 1. Datenbankdatei erstellen (falls nicht existiert)
    $dbPath = $config['database'];
    if (!file_exists($dbPath)) {
        touch($dbPath);
        echo "✓ Datenbankdatei erstellt: $dbPath\n";
    } else {
        echo "✓ Datenbankdatei existiert bereits: $dbPath\n";
    }

    // 2. Schema ausführen
    $schemaSql = file_get_contents(__DIR__ . '/../storage/database/schema.sql');
    if ($schemaSql === false) {
        throw new RuntimeException("Schema-Datei nicht gefunden");
    }

    $statements = explode(';', $schemaSql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            echo "Fhre aus: $statement\n";
            Database::query($statement);
        }
    }
    echo "✓ Datenbank-Schema erstellt\n\n";

    // 3. Beispiel-Daten einfügen (falls Tabellen leer sind)
    $essenCount = Database::query("SELECT COUNT(*) as count FROM essen")->fetch()['count'];
    if ($essenCount == 0) {
        $beispielEssen = [
            ['name' => ' Antipasti/Oliven', 'typ' => ' Antipasti/Oliven', 'beschreibung' => ''],
            ['name' => ' Brotsalat', 'typ' => ' Brotsalat', 'beschreibung' => ''],
            ['name' => ' gefüllte Eier', 'typ' => ' gefüllte Eier', 'beschreibung' => ''],
            ['name' => ' Gemüsesalat', 'typ' => ' Gemüsesalat', 'beschreibung' => ''],
            ['name' => ' Gemüsesticks', 'typ' => ' Gemüsesticks', 'beschreibung' => ''],
            ['name' => ' Gemüsesticks mit Dip', 'typ' => ' Gemüsesticks mit Dip', 'beschreibung' => ''],
            ['name' => ' grüner Salat', 'typ' => ' grüner Salat', 'beschreibung' => ''],
            ['name' => ' Kartoffelsalat', 'typ' => ' Kartoffelsalat', 'beschreibung' => ''],
            ['name' => ' Käse/Obstplatte', 'typ' => ' Käse/Obstplatte', 'beschreibung' => ''],
            ['name' => ' Käse/Wurstteller', 'typ' => ' Käse/Wurstteller', 'beschreibung' => ''],
            ['name' => ' Kroketten', 'typ' => ' Kroketten', 'beschreibung' => ''],
            ['name' => ' Mettigel, kleiner Käseteller', 'typ' => ' Mettigel, kleiner Käseteller', 'beschreibung' => ''],
            ['name' => ' Nudeln und Streukäse', 'typ' => ' Nudeln und Streukäse', 'beschreibung' => ''],
            ['name' => ' Nudelsalat (vegetarisch)', 'typ' => ' Nudelsalat (vegetarisch)', 'beschreibung' => ''],
            ['name' => ' Obazda und süßer Senf', 'typ' => ' Obazda und süßer Senf', 'beschreibung' => ''],
            ['name' => ' Obstspieße', 'typ' => ' Obstspieße', 'beschreibung' => ''],
            ['name' => ' Reis', 'typ' => ' Reis', 'beschreibung' => ''],
            ['name' => ' Reis (ca. 1kg)', 'typ' => ' Reis (ca. 1kg)', 'beschreibung' => ''],
            ['name' => ' Rohkostsalat', 'typ' => ' Rohkostsalat', 'beschreibung' => ''],
            ['name' => ' Sauerkraut', 'typ' => ' Sauerkraut', 'beschreibung' => ''],
            ['name' => ' Schinkenplatte', 'typ' => ' Schinkenplatte', 'beschreibung' => ''],
            ['name' => ' Tomate/Mozzarella', 'typ' => ' Tomate/Mozzarella', 'beschreibung' => ''],
            ['name' => ' Traubenspieße / Obst geschnitten', 'typ' => ' Traubenspieße / Obst geschnitten', 'beschreibung' => ''],
            ['name' => ' Tzatziki und Oliven', 'typ' => ' Tzatziki und Oliven', 'beschreibung' => ''],
            ['name' => 'Brezeln, Brot und Aufstrich', 'typ' => 'Brezeln, Brot und Aufstrich', 'beschreibung' => ''],
            ['name' => 'Brot und Baguette, ein Aufstrich', 'typ' => 'Brot und Baguette, ein Aufstrich', 'beschreibung' => ''],
            ['name' => 'Brot und Baguette, Frischkäse/Kräuterquark', 'typ' => 'Brot und Baguette, Frischkäse/Kräuterquark', 'beschreibung' => ''],
            ['name' => 'Brot und einen Aufstrich', 'typ' => 'Brot und einen Aufstrich', 'beschreibung' => ''],
            ['name' => 'Brot, Baguette und einen Aufstrich', 'typ' => 'Brot, Baguette und einen Aufstrich', 'beschreibung' => ''],
            ['name' => 'Brot/Baguette und Aufstrich', 'typ' => 'Brot/Baguette und Aufstrich', 'beschreibung' => ''],
            ['name' => 'Ciabatta/Baguette und Kräuterbutter (o.ä.)', 'typ' => 'Ciabatta/Baguette und Kräuterbutter (o.ä.)', 'beschreibung' => ''],
            ['name' => ' Fruchtjoghurt', 'typ' => ' Fruchtjoghurt', 'beschreibung' => ''],
            ['name' => ' Griesbrei mit Kirschen', 'typ' => ' Griesbrei mit Kirschen', 'beschreibung' => ''],
            ['name' => ' Himbeerdessert', 'typ' => ' Himbeerdessert', 'beschreibung' => ''],
            ['name' => ' Naturjoghurt mit Honig', 'typ' => ' Naturjoghurt mit Honig', 'beschreibung' => ''],
            ['name' => ' Nonnenpudding', 'typ' => ' Nonnenpudding', 'beschreibung' => ''],
            ['name' => ' Obstsalat mit Vanillesoße', 'typ' => ' Obstsalat mit Vanillesoße', 'beschreibung' => ''],
            ['name' => ' Quarkspeise', 'typ' => ' Quarkspeise', 'beschreibung' => ''],
            ['name' => ' rote Grütze mit Vanillesoße', 'typ' => ' rote Grütze mit Vanillesoße', 'beschreibung' => ''],
            ['name' => ' Schokopudding', 'typ' => ' Schokopudding', 'beschreibung' => ''],
            ['name' => ' Tiramisu (alk.-frei)', 'typ' => ' Tiramisu (alk.-frei)', 'beschreibung' => ''],
            ['name' => ' Vanillepudding und Apfelmuß', 'typ' => ' Vanillepudding und Apfelmuß', 'beschreibung' => ''],
            ['name' => ' Bolognese (ca. 6-8 P.)', 'typ' => ' Bolognese (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Chili con Carne (ca. 6-8 P.)', 'typ' => ' Chili con Carne (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Frikassee (ca. 6-8 P.)', 'typ' => ' Frikassee (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Hackbällchen überbacken (ca. 6-8 P.)', 'typ' => ' Hackbällchen überbacken (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Hackbraten (ca. 6-8 P.)', 'typ' => ' Hackbraten (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Hähnchenkeule (ca. 6-8 P.)', 'typ' => ' Hähnchenkeule (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Kassler', 'typ' => ' Kassler', 'beschreibung' => ''],
            ['name' => ' Lasagne', 'typ' => ' Lasagne', 'beschreibung' => ''],
            ['name' => ' Nudel-Schinken-Auflauf (ca. 6-8 P.)', 'typ' => ' Nudel-Schinken-Auflauf (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Weißwürstl', 'typ' => ' Weißwürstl', 'beschreibung' => ''],
            ['name' => ' gefüllte Paprika oder Gemüse-Hack-Pfanne', 'typ' => ' gefüllte Paprika oder Gemüse-Hack-Pfanne', 'beschreibung' => ''],
            ['name' => ' Chili sin Carne (ca. 6-8 P.)', 'typ' => ' Chili sin Carne (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' gefüllte Kartoffeltaschen', 'typ' => ' gefüllte Kartoffeltaschen', 'beschreibung' => ''],
            ['name' => ' Gemüse-Nudel-Auflauf (ca. 6-8 P.)', 'typ' => ' Gemüse-Nudel-Auflauf (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Gemüsepfanne (ca. 6-8 P.)', 'typ' => ' Gemüsepfanne (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Gemüsepfanne (z.B. mit Zucchini) (ca. 6-8 P.)', 'typ' => ' Gemüsepfanne (z.B. mit Zucchini) (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Kartoffel-Brokkoli-Auflauf', 'typ' => ' Kartoffel-Brokkoli-Auflauf', 'beschreibung' => ''],
            ['name' => ' Käsespätzle (ca. 6-8 P.)', 'typ' => ' Käsespätzle (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Ratatouille', 'typ' => ' Ratatouille', 'beschreibung' => ''],
            ['name' => ' Rosmarinkartoffeln (ca. 6-8 P.)', 'typ' => ' Rosmarinkartoffeln (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' Tomaten-Frischkäse-Soße (ca. 6-8 P.)', 'typ' => ' Tomaten-Frischkäse-Soße (ca. 6-8 P.)', 'beschreibung' => ''],
            ['name' => ' gefüllte Paprika/Tomate oder Gemüse-Pfanne', 'typ' => ' gefüllte Paprika/Tomate oder Gemüse-Pfanne', 'beschreibung' => ''],
            ['name' => ' Kartoffelsuppe (vegetarisch, ca.5l) ', 'typ' => ' Kartoffelsuppe (vegetarisch, ca.5l) ', 'beschreibung' => ''],
            ['name' => ' Kartoffelsuppe (vegetarisch)', 'typ' => ' Kartoffelsuppe (vegetarisch)', 'beschreibung' => ''],
            ['name' => ' Käse-Lauch-Suppe (vegetarisch, ca.5l)', 'typ' => ' Käse-Lauch-Suppe (vegetarisch, ca.5l)', 'beschreibung' => ''],
            ['name' => ' Kraut-/Kohlsuppe (ca. 5l)', 'typ' => ' Kraut-/Kohlsuppe (ca. 5l)', 'beschreibung' => ''],
            ['name' => ' Kürbissuppe', 'typ' => ' Kürbissuppe', 'beschreibung' => ''],
            ['name' => ' Möhrensuppe (ca. 5l)', 'typ' => ' Möhrensuppe (ca. 5l)', 'beschreibung' => ''],
            ['name' => ' Tomatensuppe (ca. 5l)', 'typ' => ' Tomatensuppe (ca. 5l)', 'beschreibung' => ''],
            ['name' => ' vegetarische Linsensuppe', 'typ' => ' vegetarische Linsensuppe', 'beschreibung' => ''],
            ['name' => ' Zucchinisuppe (ca. 5l)', 'typ' => ' Zucchinisuppe (ca. 5l)', 'beschreibung' => ''],
            ['name' => ' Zwiebelsuppe (ca. 5l)', 'typ' => ' Zwiebelsuppe (ca. 5l)', 'beschreibung' => ''],
            ['name' => 'Hähnchen-Curry', 'typ' => 'Hauptgericht_Fleisch', 'beschreibung' => ''],
            ['name' => 'Gemüse-Curry', 'typ' => 'Hauptgericht_Vegetarisch', 'beschreibung' => ''],
            ['name' => 'Rindergulasch', 'typ' => 'Hauptgericht_Fleisch', 'beschreibung' => 'Traditionell mit Serviettenknödeln'],
            ['name' => 'Kartoffelsalat', 'typ' => 'Beilage', 'beschreibung' => 'Hausgemacht'],
            ['name' => 'Reis', 'typ' => 'Beilage', 'beschreibung' => 'Basmati-Reis'],
            ['name' => 'Nudelsalat', 'typ' => 'Beilage', 'beschreibung' => 'Mit Paprika und Mais'],
            ['name' => 'Brot', 'typ' => 'Brot', 'beschreibung' => 'Verschiedene Sorten'],
            ['name' => 'Gouda', 'typ' => 'Käse', 'beschreibung' => 'Junger Gouda'],
            ['name' => 'Emmentaler', 'typ' => 'Käse', 'beschreibung' => 'Schweizer Käse'],
            ['name' => 'Salami', 'typ' => 'Wurst', 'beschreibung' => 'Italienische Salami'],
            ['name' => 'Schinken', 'typ' => 'Wurst', 'beschreibung' => 'Gekochter Schinken'],
            ['name' => 'Apfelkuchen', 'typ' => 'Nachtisch', 'beschreibung' => 'Hausgemacht mit Äpfeln'],
            ['name' => 'Schokokuchen', 'typ' => 'Nachtisch', 'beschreibung' => 'Saftiger Schokokuchen'],
            ['name' => 'Obstsalat', 'typ' => 'Nachtisch', 'beschreibung' => 'Frische Früchte der Saison'],
        ];

        foreach ($beispielEssen as $essen) {
            Database::query(
                "INSERT INTO essen (name, typ, beschreibung) VALUES (:name, :typ, :beschreibung)",
                $essen
            );
        }
        echo "✓ Beispiel-Rezepte eingefügt (" . count($beispielEssen) . " Stück)\n";
    } else {
        echo "✓ Beispiel-Rezepte bereits vorhanden ($essenCount Stück)\n";
    }

    // 4. Admin-Benutzer erstellen (falls nicht existiert)
    $adminCount = Database::query("SELECT COUNT(*) as count FROM admin")->fetch()['count'];
    if ($adminCount == 0) {
        $adminBenutzername = 'planer';
        $adminPasswort = 'gaeste-werden-satt'; // Standardpasswort - bitte ändern!
        $passwortHash = password_hash($adminPasswort, PASSWORD_ARGON2ID);

        Database::query(
            "INSERT INTO admin (benutzername, passwort_hash) VALUES (:benutzername, :passwort_hash)",
            ['benutzername' => $adminBenutzername, 'passwort_hash' => $passwortHash]
        );
        echo "✓ Admin-Benutzer erstellt\n";
        echo "   Benutzername: $adminBenutzername\n";
        echo "   Passwort: $adminPasswort (Bitte ändern!)\n";
    } else {
        echo "✓ Admin-Benutzer existiert bereits\n";
    }

    // 5. Beispiel-Speiseplan für Woche 1, Jahr 2024 erstellen (falls nicht existiert)
    $speiseplanCount = Database::query("SELECT COUNT(*) as count FROM speiseplan WHERE jahr = 2024 AND woche = 1")->fetch()['count'];
    if ($speiseplanCount == 0) {
        // Essen-IDs abfragen
        $essenIds = Database::query("SELECT id, typ FROM essen")->fetchAll();
        
        // Beispiel-Speiseplan für Woche 1
        $beispielSpeiseplan = [
            ['essen_name' => 'Hähnchen-Curry', 'typ' => 'Hauptgericht_Fleisch'],
            ['essen_name' => 'Gemüse-Curry', 'typ' => 'Hauptgericht_Vegetarisch'],
            ['essen_name' => 'Kartoffelsalat', 'typ' => 'Beilage'],
            ['essen_name' => 'Reis', 'typ' => 'Beilage'],
            ['essen_name' => 'Brot', 'typ' => 'Brot'],
            ['essen_name' => 'Gouda', 'typ' => 'Käse'],
            ['essen_name' => 'Salami', 'typ' => 'Wurst'],
            ['essen_name' => 'Apfelkuchen', 'typ' => 'Nachtisch'],
        ];

        foreach ($beispielSpeiseplan as $item) {
            $essenId = null;
            foreach ($essenIds as $essen) {
                if ($essen['name'] === $item['essen_name'] && $essen['typ'] === $item['typ']) {
                    $essenId = $essen['id'];
                    break;
                }
            }
            
            if ($essenId !== null) {
                Database::query(
                    "INSERT INTO speiseplan (woche, jahr, essen_id) VALUES (1, 2024, :essen_id)",
                    ['essen_id' => $essenId]
                );
            }
        }
        echo "✓ Beispiel-Speiseplan für Woche 1, 2024 erstellt\n";
    } else {
        echo "✓ Beispiel-Speiseplan bereits vorhanden\n";
    }

    // 6. Beispiel-Buchungen erstellen (falls nicht existieren)
    $buchungenCount = Database::query("SELECT COUNT(*) as count FROM buchungen")->fetch()['count'];
    if ($buchungenCount == 0) {
        // Nutzer erstellen
        $nutzerId = Database::query(
            "INSERT INTO nutzer (email) VALUES ('max.mustermann@example.com') RETURNING id"
        )->fetch()['id'];
        
        // Speiseplan-ID für Woche 1, 2024, Hähnchen-Curry abfragen
        $speiseplanId = Database::query(
            "SELECT sp.id FROM speiseplan sp JOIN essen e ON sp.essen_id = e.id 
             WHERE sp.woche = 1 AND sp.jahr = 2024 AND e.name = 'Hähnchen-Curry' AND e.typ = 'Hauptgericht_Fleisch'"
        )->fetch()['id'];
        
        if ($speiseplanId) {
            Database::query(
                "INSERT INTO buchungen (nutzer_id, speiseplan_id, bestaetigt) VALUES (:nutzer_id, :speiseplan_id, TRUE)",
                ['nutzer_id' => $nutzerId, 'speiseplan_id' => $speiseplanId]
            );
            echo "✓ Beispiel-Buchung erstellt (Max Mustermann → Hähnchen-Curry, Woche 1)\n";
        }
    } else {
        echo "✓ Beispiel-Buchungen bereits vorhanden\n";
    }

    echo "\n=== Setup abgeschlossen! ===\n";
    echo "Datenbank: $dbPath\n";
    echo "Admin-Login: /admin/login\n";
    echo "Frontend: /\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
