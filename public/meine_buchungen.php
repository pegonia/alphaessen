<?php
/**
 * meine_buchungen.php - Übersicht der Buchungen eines Nutzers
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

// Session starten (falls nicht bereits gestartet)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// E-Mail aus Request oder Session
$email = $_GET['email'] ?? ($_SESSION['nutzer_email'] ?? '');

// Falls keine E-Mail angegeben, zur Startseite weiterleiten
if (empty($email)) {
    header('Location: /');
    exit;
}

// Buchungen des Nutzers abfragen
$buchungen = $buchungService->getBuchungenFuerNutzer($email);

// Nach Jahren und Wochen gruppieren
$buchungenNachJahr = [];
foreach ($buchungen as $buchung) {
    $jahr = $buchung->speiseplanEintrag->jahr;
    $woche = $buchung->speiseplanEintrag->woche;
    
    if (!isset($buchungenNachJahr[$jahr])) {
        $buchungenNachJahr[$jahr] = [];
    }
    
    if (!isset($buchungenNachJahr[$jahr][$woche])) {
        $buchungenNachJahr[$jahr][$woche] = [];
    }
    
    $buchungenNachJahr[$jahr][$woche][] = $buchung;
}

// Alle Jahre mit Buchungen sortieren
krsort($buchungenNachJahr);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Meine Buchungen</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen Speiseplan</h1>
            <nav>
                <a href="/">Startseite</a>
                | <a href="/">Speiseplan</a>
                | <a href="/admin/login">Admin</a>
            </nav>
        </header>

        <main>
            <h2>Meine Buchungen</h2>
            <p>E-Mail: <strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong></p>
            
            <?php if (empty($buchungen)): ?>
                <p class="hinweis">Sie haben noch keine Buchungen vorgenommen.</p>
                <p><a href="/" class="button">Zum Speiseplan</a></p>
            <?php else: ?>
                <?php foreach ($buchungenNachJahr as $jahr => $wochen): ?>
                    <div class="jahr-buchungen">
                        <h3>Jahr <?php echo $jahr; ?></h3>
                        
                        <?php foreach ($wochen as $woche => $wochenBuchungen): ?>
                            <?php 
                            $datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);
                            sort($wochenBuchungen, function($a, $b) {
                                return strcmp($a->speiseplanEintrag->essen->typ, $b->speiseplanEintrag->essen->typ);
                            });
                            ?>
                            <div class="woche-buchungen">
                                <h4>Woche <?php echo $woche; ?> - <?php echo $datum; ?> (Donnerstag)</h4>
                                
                                <table class="meine-buchungen-tabelle">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Essen</th>
                                            <th>Beschreibung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wochenBuchungen as $buchung): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->beschreibung ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <p class="buchungs-hinweis">
                                    <strong>Hinweis:</strong> 
                                    Buchungen können nicht storniert werden. 
                                    Bitte finden Sie jemanden, der Ihre Buchung übernimmt, falls Sie nicht können.
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="aktionen">
                <a href="/" class="button">Zum Speiseplan</a>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Essen für den Alphakurs</p>
        </footer>
    </div>
</body>
</html>
