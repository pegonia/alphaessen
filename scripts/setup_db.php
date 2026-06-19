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
            [
                "typ" => "Beilage",
                "name" => "Antipasti/Oliven",
                "beschreibung" => ""
            ],
            [
                "typ" => "Kuchen",
                "name" => "Apfelkuchen",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Bolognese",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Brotzeit",
                "name" => "Brezeln, Brot und Aufstrich",
                "beschreibung" => ""
            ],
            [
                "typ" => "Brotzeit",
                "name" => "Brot und Baguette, Frischkäse/Kräuterquark",
                "beschreibung" => ""
            ],
            [
                "typ" => "Brotzeit",
                "name" => "Brot, Baguette und einen Aufstrich",
                "beschreibung" => ""
            ],
            [
                "typ" => "Brotzeit",
                "name" => "Brot/Baguette und Aufstrich",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Brotsalat",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Chili con Carne",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Chili sin Carne",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Brotzeit",
                "name" => "Ciabatta/Baguette und Kräuterbutter",
                "beschreibung" => "o.ä."
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Frikassee",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Dessert",
                "name" => "Fruchtjoghurt",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "gefüllte Eier",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "gefüllte Kartoffeltaschen",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "gefüllte Paprika oder Gemüse-Hack-Pfanne",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "gefüllte Paprika/Tomate oder Gemüse-Pfanne",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Gemüse-Nudel-Auflauf",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Gemüsepfanne",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Gemüsepfanne",
                "beschreibung" => "z.B. mit Zucchini  6-8 P."
            ],
            [
                "typ" => "Beilage",
                "name" => "Gemüsesalat",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Gemüsesticks mit Dip",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Griesbrei mit Kirschen",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "grüner Salat",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Hackbällchen überbacken",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Hackbraten",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Hähnchenkeule",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Dessert",
                "name" => "Himbeerdessert",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Kartoffel-Brokkoli-Auflauf",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Kartoffelsalat",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Kartoffelsuppe",
                "beschreibung" => "vegetarisch, ca.5l"
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Käse-Lauch-Suppe",
                "beschreibung" => "vegetarisch, ca.5l"
            ],
            [
                "typ" => "Beilage",
                "name" => "Käse/Obstplatte",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Käsespätzle",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Kassler",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Kraut-/Kohlsuppe",
                "beschreibung" => "ca. 5l"
            ],
            [
                "typ" => "Beilage",
                "name" => "Kroketten",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Kürbissuppe",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Lasagne",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Mettigel, kleiner Käseteller",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Möhrensuppe",
                "beschreibung" => "ca. 5l"
            ],
            [
                "typ" => "Dessert",
                "name" => "Naturjoghurt mit Honig",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Nonnenpudding",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Nudel-Schinken-Auflauf",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Beilage",
                "name" => "Nudeln und Streukäse",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Nudelsalat",
                "beschreibung" => "vegetarisch"
            ],
            [
                "typ" => "Beilage",
                "name" => "Obazda und süßer Senf",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Obstsalat mit Vanillesoße",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Obstspieße",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Quarkspeise",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Ratatouille",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Reis",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Reis",
                "beschreibung" => "ca. 1kg"
            ],
            [
                "typ" => "Beilage",
                "name" => "Rohkostsalat",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Rosmarinkartoffeln",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Dessert",
                "name" => "rote Grütze mit Vanillesoße",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Sauerkraut",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Schinkenplatte",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Schokopudding",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Tiramisu",
                "beschreibung" => "alk.-frei"
            ],
            [
                "typ" => "Beilage",
                "name" => "Tomate/Mozzarella",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Vegetarisch",
                "name" => "Tomaten-Frischkäse-Soße",
                "beschreibung" => "ca. 6-8 P."
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Tomatensuppe",
                "beschreibung" => "ca. 5l"
            ],
            [
                "typ" => "Beilage",
                "name" => "Traubenspieße / Obst geschnitten",
                "beschreibung" => ""
            ],
            [
                "typ" => "Beilage",
                "name" => "Tzatziki und Oliven",
                "beschreibung" => ""
            ],
            [
                "typ" => "Dessert",
                "name" => "Vanillepudding und Apfelmuß",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "vegetarische Linsensuppe",
                "beschreibung" => ""
            ],
            [
                "typ" => "Hauptgericht_Fleisch",
                "name" => "Weißwürstl",
                "beschreibung" => ""
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Zucchinisuppe",
                "beschreibung" => "ca. 5l"
            ],
            [
                "typ" => "Vorspeise",
                "name" => "Zwiebelsuppe",
                "beschreibung" => "ca. 5l"
            ]
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


    echo "\n=== Setup abgeschlossen! ===\n";
    echo "Datenbank: $dbPath\n";
    echo "Admin-Login: /admin/login\n";
    echo "Frontend: /\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
