<?php
/**
 * speiseplan_kalender.php - Kalenderansicht aller 12 Wochen
 */

use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\BuchungService;
use Alphaessen\Services\CsrfService;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;
use Alphaessen\Repositories\BuchungRepository;
use Alphaessen\Repositories\NutzerRepository;
use Alphaessen\Repositories\EmailQueueRepository;
use Alphaessen\Services\EmailService;

// Services initialisieren
$essenRepository = new EssenRepository();
$speiseplanRepository = new SpeiseplanRepository($essenRepository);
$nutzerRepository = new NutzerRepository();
$buchungRepository = new BuchungRepository($nutzerRepository, $speiseplanRepository);
$emailQueueRepository = new EmailQueueRepository();
$emailService = new EmailService($emailQueueRepository, $emailConfig);
$speiseplanService = new SpeiseplanService($speiseplanRepository, $essenRepository);
$buchungService = new BuchungService($buchungRepository, $nutzerRepository, $speiseplanRepository, $emailQueueRepository, $emailService);
$csrfService = new CsrfService();

// Aktuelles Jahr bestimmen (oder aus Request)
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : (int)date('Y');

// Alle Jahre mit Speiseplan abfragen
$alleJahre = $speiseplanService->getAlleJahre();
if (empty($alleJahre)) {
    $alleJahre = [$jahr];
}

// Wochen für das ausgewählte Jahr abfragen
$wochen = $speiseplanService->getWochenFuerJahr($jahr);
if (empty($wochen)) {
    $wochen = range(1, 12);
}

// E-Mail aus Session oder Request für "Meine Buchungen" Link
$email = $_GET['email'] ?? ($_SESSION['nutzer_email'] ?? '');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Speiseplan</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen Speiseplan</h1>
            <nav>
                <a href="/">Startseite</a>
                <?php if (!empty($email)): ?>
                    | <a href="/meine-buchungen?email=<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">Meine Buchungen</a>
                <?php endif; ?>
                | <a href="/admin/login">Admin</a>
            </nav>
        </header>

        <main>
            <div class="jahr-auswahl">
                <form method="get" action="/">
                    <label for="jahr">Jahr:</label>
                    <select name="jahr" id="jahr" onchange="this.form.submit()">
                        <?php foreach ($alleJahre as $j): ?>
                            <option value="<?php echo $j; ?>" <?php echo $j === $jahr ? 'selected' : ''; ?>>
                                <?php echo $j; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript>
                        <input type="submit" value="Jahr wechseln">
                    </noscript>
                </form>
            </div>

            <h2>Speiseplan für <?php echo $jahr; ?></h2>
            
            <?php if (empty($wochen)): ?>
                <p class="hinweis">Kein Speiseplan für dieses Jahr vorhanden.</p>
            <?php else: ?>
                <div class="kalender">
                    <table class="speiseplan-kalender">
                        <thead>
                            <tr>
                                <th>Woche</th>
                                <th>Datum (Donnerstag)</th>
                                <th>Hauptgericht (Fleisch)</th>
                                <th>Hauptgericht (Vegetarisch)</th>
                                <th>Beilagen</th>
                                <th>Brot/Käse/Wurst</th>
                                <th>Nachtisch</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wochen as $woche): ?>
                                <?php 
                                $eintraege = $speiseplanService->getSpeiseplanFuerWoche($woche, $jahr);
                                $datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);
                                
                                // Essen nach Typen gruppieren
                                $hauptgerichtFleisch = [];
                                $hauptgerichtVegetarisch = [];
                                $beilagen = [];
                                $brotKaeseWurst = [];
                                $nachtisch = [];
                                
                                foreach ($eintraege as $eintrag) {
                                    switch ($eintrag->essen->typ) {
                                        case 'Hauptgericht_Fleisch':
                                            $hauptgerichtFleisch[] = $eintrag;
                                            break;
                                        case 'Hauptgericht_Vegetarisch':
                                            $hauptgerichtVegetarisch[] = $eintrag;
                                            break;
                                        case 'Beilage':
                                            $beilagen[] = $eintrag;
                                            break;
                                        case 'Brot':
                                        case 'Käse':
                                        case 'Wurst':
                                            $brotKaeseWurst[] = $eintrag;
                                            break;
                                        case 'Nachtisch':
                                            $nachtisch[] = $eintrag;
                                            break;
                                    }
                                }
                                
                                // Verfügbare Essen für diese Woche
                                $verfuegbar = $buchungService->getVerfuegbareEssenFuerWoche($woche, $jahr);
                                $verfuegbarIds = array_map(function($e) { return $e->id; }, $verfuegbar);
                                
                                // Gebuchte Essen für diese Woche
                                $gebucht = $buchungService->getGebuchteEssenFuerWoche($woche, $jahr);
                                $gebuchtIds = array_keys($gebucht);
                                
                                // Status für jede Essensgruppe
                                $statusFleisch = getStatusForEintraege($hauptgerichtFleisch, $verfuegbarIds, $gebuchtIds, $email);
                                $statusVegetarisch = getStatusForEintraege($hauptgerichtVegetarisch, $verfuegbarIds, $gebuchtIds, $email);
                                $statusBeilagen = getStatusForEintraege($beilagen, $verfuegbarIds, $gebuchtIds, $email);
                                $statusBrotKaeseWurst = getStatusForEintraege($brotKaeseWurst, $verfuegbarIds, $gebuchtIds, $email);
                                $statusNachtisch = getStatusForEintraege($nachtisch, $verfuegbarIds, $gebuchtIds, $email);
                                
                                // Zeile ausgeben
                                echo "<tr>\n";
                                echo "  <td>{$woche}</td>\n";
                                echo "  <td>{$datum}</td>\n";
                                echo "  <td class=\"{$statusFleisch}\">" . formatEintraege($hauptgerichtFleisch, $gebucht, $email) . "</td>\n";
                                echo "  <td class=\"{$statusVegetarisch}\">" . formatEintraege($hauptgerichtVegetarisch, $gebucht, $email) . "</td>\n";
                                echo "  <td class=\"{$statusBeilagen}\">" . formatEintraege($beilagen, $gebucht, $email) . "</td>\n";
                                echo "  <td class=\"{$statusBrotKaeseWurst}\">" . formatEintraege($brotKaeseWurst, $gebucht, $email) . "</td>\n";
                                echo "  <td class=\"{$statusNachtisch}\">" . formatEintraege($nachtisch, $gebucht, $email) . "</td>\n";
                                echo "  <td><a href=\"/woche/{$woche}/{$jahr}\" class=\"button\">Details</a></td>\n";
                                echo "</tr>\n";
                                ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="hinweis">
                <p><strong>Legende:</strong></p>
                <ul>
                    <li><span class="status-verfuegbar"></span> = Verfügbar (kann gebucht werden)</li>
                    <li><span class="status-gebucht"></span> = Gebucht (von jemand anderem)</li>
                    <li><span class="status-eigene-buchung"></span> = Von Ihnen gebucht</li>
                </ul>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Essen für den Alphakurs</p>
        </footer>
    </div>
</body>
</html>

<?php
/**
 * Hilfsfunktion: Bestimmt den Status für eine Gruppe von Einträgen
 */
function getStatusForEintraege(array $eintraege, array $verfuegbarIds, array $gebuchtIds, string $email): string
{
    if (empty($eintraege)) {
        return 'status-leer';
    }
    
    // Prüfen, ob alle Einträge verfügbar sind
    $alleVerfuegbar = true;
    $hatEigeneBuchung = false;
    
    foreach ($eintraege as $eintrag) {
        if (!in_array($eintrag->id, $verfuegbarIds)) {
            $alleVerfuegbar = false;
        }
        
        // Prüfen, ob der Nutzer für diesen Eintrag gebucht hat
        if (isset($gebucht[$eintrag->id])) {
            foreach ($gebucht[$eintrag->id] as $buchung) {
                if ($buchung->nutzer->email === $email) {
                    $hatEigeneBuchung = true;
                    break;
                }
            }
        }
    }
    
    if ($hatEigeneBuchung) {
        return 'status-eigene-buchung';
    }
    
    if ($alleVerfuegbar) {
        return 'status-verfuegbar';
    }
    
    return 'status-gebucht';
}

/**
 * Hilfsfunktion: Formatiert eine Gruppe von Einträgen für die Anzeige
 */
function formatEintraege(array $eintraege, array $gebucht, string $email): string
{
    if (empty($eintraege)) {
        return '-';
    }
    
    $parts = [];
    foreach ($eintraege as $eintrag) {
        $name = htmlspecialchars($eintrag->essen->name, ENT_QUOTES, 'UTF-8');
        
        // Prüfen, ob dieser Eintrag gebucht ist
        if (isset($gebucht[$eintrag->id])) {
            $buchungen = $gebucht[$eintrag->id];
            $bucher = [];
            foreach ($buchungen as $buchung) {
                if ($buchung->nutzer->email === $email) {
                    $bucher[] = 'Sie';
                } else {
                    $bucher[] = htmlspecialchars($buchung->nutzer->email, ENT_QUOTES, 'UTF-8');
                }
            }
            $name .= ' (' . implode(', ', $bucher) . ')';
        }
        
        $parts[] = $name;
    }
    
    return implode(', ', $parts);
}
