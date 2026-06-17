<?php
/**
 * speiseplan_woche.php - Detaillierte Ansicht einer Woche
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

// Parameter aus URL extrahieren
$woche = isset($_GET['woche']) ? (int)$_GET['woche'] : 1;
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : (int)date('Y');

// E-Mail aus Session oder Request
$email = $_GET['email'] ?? ($_SESSION['nutzer_email'] ?? '');

// Speiseplan für diese Woche abfragen
$eintraege = $speiseplanService->getSpeiseplanFuerWoche($woche, $jahr);
$datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);

// Verfügbare und gebuchte Essen
$verfuegbar = $buchungService->getVerfuegbareEssenFuerWoche($woche, $jahr);
$verfuegbarIds = array_map(function($e) { return $e->id; }, $verfuegbar);
$gebucht = $buchungService->getGebuchteEssenFuerWoche($woche, $jahr);

// Buchungen des Nutzers für diese Woche
$meineBuchungen = $buchungService->getBuchungenFuerNutzerUndAbend($email, $woche, $jahr);
$meineBuchungIds = array_map(function($b) { return $b->speiseplanId; }, $meineBuchungen);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Woche <?php echo $woche; ?> (<?php echo $datum; ?>)</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphakurs Speiseplan</h1>
            <nav>
                <a href="/">Zurück zur Übersicht</a>
                <?php if (!empty($email)): ?>
                    | <a href="/meine-buchungen?email=<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">Meine Buchungen</a>
                <?php endif; ?>
                | <a href="/admin/login">Admin</a>
            </nav>
        </header>

        <main>
            <h2>Woche <?php echo $woche; ?> - Donnerstag, <?php echo $datum; ?></h2>
            
            <?php if (empty($eintraege)): ?>
                <p class="hinweis">Kein Speiseplan für diese Woche vorhanden.</p>
            <?php else: ?>
                <div class="woche-detail">
                    <table class="speiseplan-woche">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Essen</th>
                                <th>Beschreibung</th>
                                <th>Status</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eintraege as $eintrag): ?>
                                <?php 
                                $istVerfuegbar = in_array($eintrag->id, $verfuegbarIds);
                                $istGebucht = isset($gebucht[$eintrag->id]);
                                $istMeineBuchung = in_array($eintrag->id, $meineBuchungIds);
                                
                                // Status bestimmen
                                if ($istMeineBuchung) {
                                    $status = 'Von Ihnen gebucht';
                                    $statusClass = 'status-eigene-buchung';
                                    $aktion = '';
                                } elseif ($istGebucht) {
                                    $buchungen = $gebucht[$eintrag->id];
                                    $bucher = [];
                                    foreach ($buchungen as $buchung) {
                                        $bucher[] = htmlspecialchars($buchung->nutzer->email, ENT_QUOTES, 'UTF-8');
                                    }
                                    $status = 'Gebucht von: ' . implode(', ', $bucher);
                                    $statusClass = 'status-gebucht';
                                    $aktion = '';
                                } else {
                                    $status = 'Verfügbar';
                                    $statusClass = 'status-verfuegbar';
                                    $aktion = '<a href="/buchen?essen_id=' . $eintrag->id . '&woche=' . $woche . '&jahr=' . $jahr . '&email=' . urlencode($email) . '" class="button">Buchen</a>';
                                }
                                
                                echo "<tr>\n";
                                echo "  <td>" . htmlspecialchars($eintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8') . "</td>\n";
                                echo "  <td>" . htmlspecialchars($eintrag->essen->name, ENT_QUOTES, 'UTF-8') . "</td>\n";
                                echo "  <td>" . htmlspecialchars($eintrag->essen->beschreibung ?? '-', ENT_QUOTES, 'UTF-8') . "</td>\n";
                                echo "  <td class=\"{$statusClass}\">" . $status . "</td>\n";
                                echo "  <td>" . $aktion . "</td>\n";
                                echo "</tr>\n";
                                ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($meineBuchungen)): ?>
                <div class="meine-buchungen-vorort">
                    <h3>Ihre Buchungen für diese Woche:</h3>
                    <ul>
                        <?php foreach ($meineBuchungen as $buchung): ?>
                            <li>
                                <?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?> 
                                (<?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="hinweis">
                        <strong>Hinweis:</strong> Buchungen können nicht storniert werden. 
                        Bitte finden Sie jemanden, der Ihre Buchung übernimmt, falls Sie nicht können.
                    </p>
                </div>
            <?php endif; ?>

            <div class="aktionen">
                <a href="/" class="button">Zurück zur Übersicht</a>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Essen für den Alphakurs</p>
        </footer>
    </div>
</body>
</html>
