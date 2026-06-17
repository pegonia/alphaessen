<?php
/**
 * buchung_formular.php - Formular für neue Buchungen
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

// Parameter aus URL extrahieren
$essenId = isset($_GET['essen_id']) ? (int)$_GET['essen_id'] : 0;
$woche = isset($_GET['woche']) ? (int)$_GET['woche'] : 1;
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : (int)date('Y');
$email = isset($_GET['email']) ? $_GET['email'] : '';

// Speiseplan-Eintrag abfragen
$eintrag = $speiseplanRepository->findeNachId($essenId);

// Falls kein Eintrag gefunden, zurück zur Wochenansicht
if ($eintrag === null) {
    header("Location: /woche/{$woche}/{$jahr}");
    exit;
}

// Prüfen, ob das Essen noch verfügbar ist
if (!$buchungService->istVerfuegbar($eintrag->id)) {
    header("Location: /woche/{$woche}/{$jahr}?error=bereits_gebucht");
    exit;
}

// Datum für die Woche abfragen
$datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Buchung bestätigen</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen Speiseplan</h1>
            <nav>
                <a href="/">Startseite</a>
                | <a href="/woche/<?php echo $woche; ?>/<?php echo $jahr; ?>">Zurück zur Woche <?php echo $woche; ?></a>
                | <a href="/admin/login">Admin</a>
            </nav>
        </header>

        <main>
            <h2>Buchung bestätigen</h2>
            
            <div class="buchung-formular">
                <p>Sie sind dabei, folgende Buchung vorzunehmen:</p>
                
                <table class="buchung-uebersicht">
                    <tr>
                        <th>Woche:</th>
                        <td><?php echo $woche; ?></td>
                    </tr>
                    <tr>
                        <th>Datum:</th>
                        <td><?php echo $datum; ?> (Donnerstag)</td>
                    </tr>
                    <tr>
                        <th>Essen:</th>
                        <td><?php echo htmlspecialchars($eintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <th>Typ:</th>
                        <td><?php echo htmlspecialchars($eintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php if ($eintrag->essen->beschreibung): ?>
                    <tr>
                        <th>Beschreibung:</th>
                        <td><?php echo htmlspecialchars($eintrag->essen->beschreibung, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <form method="post" action="/buchen">
                    <?php echo $csrfService->getTokenInput(); ?>
                    
                    <input type="hidden" name="essen_id" value="<?php echo $essenId; ?>">
                    <input type="hidden" name="woche" value="<?php echo $woche; ?>">
                    <input type="hidden" name="jahr" value="<?php echo $jahr; ?>">
                    
                    <div class="form-group">
                        <label for="email">Ihre E-Mail-Adresse:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" 
                               required placeholder="Ihre E-Mail-Adresse">
                        <p class="hinweis">Bitte geben Sie Ihre E-Mail-Adresse ein. Sie erhalten eine Bestätigung per E-Mail.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Buchung bestätigen</button>
                        <a href="/woche/<?php echo $woche; ?>/<?php echo $jahr; ?>" class="button">Abbrechen</a>
                    </div>
                </form>

                <div class="hinweis wichtig">
                    <p><strong>Wichtig:</strong></p>
                    <ul>
                        <li>Buchungen können <strong>nicht storniert</strong> werden.</li>
                        <li>Falls Sie nicht können, müssen Sie jemanden finden, der Ihre Buchung übernimmt.</li>
                        <li>Sie erhalten eine Bestätigungs-E-Mail und am Tag vorher eine Erinnerung.</li>
                    </ul>
                </div>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Essen für den Alphakurs</p>
        </footer>
    </div>
</body>
</html>
