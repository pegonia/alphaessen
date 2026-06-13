<?php
/**
 * buchungen_zuruecksetzen.php - Buchungen für ein Jahr zurücksetzen
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\BuchungService;
use Alphaessen\Services\CsrfService;
use Alphaessen\Repositories\AdminRepository;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;
use Alphaessen\Repositories\BuchungRepository;
use Alphaessen\Repositories\NutzerRepository;
use Alphaessen\Repositories\EmailQueueRepository;
use Alphaessen\Services\EmailService;

// Services initialisieren
$adminRepository = new AdminRepository();
$adminAuthService = new AdminAuthService($adminRepository);

// Authentifizierung prüfen
if (!$adminAuthService->istBerechtigt()) {
    header('Location: /admin/login');
    exit;
}

$essenRepository = new EssenRepository();
$speiseplanRepository = new SpeiseplanRepository($essenRepository);
$nutzerRepository = new NutzerRepository();
$buchungRepository = new BuchungRepository($nutzerRepository, $speiseplanRepository);
$emailQueueRepository = new EmailQueueRepository();
$emailService = new EmailService($emailQueueRepository, $emailConfig);
$speiseplanService = new SpeiseplanService($speiseplanRepository, $essenRepository);
$buchungService = new BuchungService($buchungRepository, $nutzerRepository, $speiseplanRepository, $emailQueueRepository, $emailService);
$csrfService = new CsrfService();

// Alle Jahre mit Speiseplan abfragen
$alleJahre = $speiseplanService->getAlleJahre();

// Falls keine Jahre vorhanden, zum Dashboard zurück
if (empty($alleJahre)) {
    $_SESSION['admin_buchungen_fehler'] = 'Keine Jahre mit Speiseplan gefunden.';
    header('Location: /admin/buchungen');
    exit;
}

// Standard-Jahr
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : $alleJahre[0];

// Anzahl der Buchungen für das Jahr abfragen
$anzahlBuchungen = $buchungRepository->zaehleNachJahr($jahr);

// Falls POST-Request: Buchungen löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token prüfen
    if (!$csrfService->validateRequest($_POST)) {
        $_SESSION['admin_buchungen_fehler'] = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
        header('Location: /admin/buchungen/zuruecksetzen');
        exit;
    }

    $jahr = isset($_POST['jahr']) ? (int)$_POST['jahr'] : 0;
    
    if ($jahr <= 0) {
        $_SESSION['admin_buchungen_fehler'] = 'Bitte wählen Sie ein Jahr aus.';
        header('Location: /admin/buchungen/zuruecksetzen');
        exit;
    }

    // Buchungen löschen
    $geloescht = $buchungService->loescheBuchungenNachJahr($jahr);
    
    $_SESSION['admin_buchungen_erfolg'] = "Alle Buchungen für Jahr {$jahr} gelöscht. {$geloescht} Buchungen entfernt.";
    header('Location: /admin/buchungen');
    exit;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Buchungen zurücksetzen</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen - Administration</h1>
            <nav>
                <a href="/admin/">Dashboard</a>
                | <a href="/admin/speiseplan">Speiseplan</a>
                | <a href="/admin/rezepte">Rezepte</a>
                | <a href="/admin/buchungen">Buchungen</a>
                | <a href="/admin/logout">Abmelden</a>
            </nav>
        </header>

        <main>
            <h2>Buchungen zurücksetzen</h2>
            
            <div class="admin-content">
                <div class="hinweis wichtig">
                    <p><strong>Achtung:</strong> Diese Aktion löscht <strong>alle Buchungen</strong> für das ausgewählte Jahr.</p>
                    <p>Dies kann nicht rückgängig gemacht werden!</p>
                </div>

                <p>Wählen Sie das Jahr aus, dessen Buchungen Sie zurücksetzen möchten:</p>

                <form method="post" action="/admin/buchungen/zuruecksetzen">
                    <?php echo $csrfService->getTokenInput(); ?>

                    <div class="form-group">
                        <label for="jahr">Jahr:</label>
                        <select name="jahr" id="jahr" required>
                            <?php foreach ($alleJahre as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo $j === $jahr ? 'selected' : ''; ?>>
                                    <?php echo $j; ?> (<?php echo $buchungRepository->zaehleNachJahr($j); ?> Buchungen)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <p>Für Jahr <strong><?php echo $jahr; ?></strong> werden <strong><?php echo $anzahlBuchungen; ?></strong> Buchungen gelöscht.</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button danger" 
                                onclick="return confirm('Sind Sie sicher, dass Sie ALLE Buchungen für Jahr <?php echo $jahr; ?> löschen möchten?');">
                            Buchungen zurücksetzen
                        </button>
                        <a href="/admin/buchungen" class="button">Abbrechen</a>
                    </div>
                </form>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Administration</p>
        </footer>
    </div>
</body>
</html>
