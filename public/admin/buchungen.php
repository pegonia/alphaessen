<?php
/**
 * buchungen.php - Buchungen-Verwaltung
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

// Parameter aus URL extrahieren
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : (int)date('Y');
$woche = isset($_GET['woche']) ? (int)$_GET['woche'] : null;

// Alle Jahre mit Buchungen abfragen
$alleJahre = $speiseplanService->getAlleJahre();
if (empty($alleJahre)) {
    $alleJahre = [$jahr];
}

// Buchungen abfragen
if ($woche !== null) {
    $buchungen = $buchungService->getBuchungenFuerWoche($woche, $jahr);
    $datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);
    $titel = "Buchungen für Woche {$woche} - {$datum}";
} else {
    $buchungen = $buchungRepository->findeNachJahr($jahr);
    $titel = "Alle Buchungen für Jahr {$jahr}";
}

// Erfolgmeldung aus Session
$erfolg = $_SESSION['admin_buchungen_erfolg'] ?? null;
unset($_SESSION['admin_buchungen_erfolg']);

$fehler = $_SESSION['admin_buchungen_fehler'] ?? null;
unset($_SESSION['admin_buchungen_fehler']);

// Löschaktion
if (isset($_GET['loeschen'])) {
    $buchungId = (int)$_GET['loeschen'];
    $buchungRepository->loesche($buchungId);
    $_SESSION['admin_buchungen_erfolg'] = 'Buchung erfolgreich gelöscht.';
    header("Location: /admin/buchungen?jahr={$jahr}" . ($woche !== null ? "&woche={$woche}" : ''));
    exit;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Buchungen verwalten</title>
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
                | <a href="/admin/logout">Abmelden</a>
            </nav>
        </header>

        <main>
            <h2>Buchungen verwalten</h2>
            
            <?php if ($erfolg): ?>
                <div class="hinweis">
                    <?php echo htmlspecialchars($erfolg, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($fehler): ?>
                <div class="hinweis wichtig">
                    <?php echo htmlspecialchars($fehler, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="jahr-auswahl">
                <form method="get" action="/admin/buchungen">
                    <label for="jahr">Jahr:</label>
                    <select name="jahr" id="jahr" onchange="this.form.submit()">
                        <?php foreach ($alleJahre as $j): ?>
                            <option value="<?php echo $j; ?>" <?php echo $j === $jahr ? 'selected' : ''; ?>>
                                <?php echo $j; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="woche" style="margin-left: 15px;">Woche (optional):</label>
                    <select name="woche" id="woche" onchange="this.form.submit()">
                        <option value="">-- Alle Wochen --</option>
                        <?php for ($w = 1; $w <= 12; $w++): ?>
                            <option value="<?php echo $w; ?>" <?php echo $w === $woche ? 'selected' : ''; ?>>
                                Woche <?php echo $w; ?> (<?php echo $speiseplanService->getDatumFuerWoche($w, $jahr); ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                    <noscript>
                        <input type="submit" value="Auswählen">
                    </noscript>
                </form>
            </div>

            <div class="admin-content">
                <h3><?php echo $titel; ?></h3>
                
                <?php if (empty($buchungen)): ?>
                    <p>Keine Buchungen gefunden.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>E-Mail</th>
                                <th>Essen</th>
                                <th>Typ</th>
                                <th>Woche</th>
                                <th>Bestätigt</th>
                                <th>Erinnerung</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buchungen as $buchung): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($buchung->erstelltAm)); ?></td>
                                    <td><?php echo htmlspecialchars($buchung->nutzer->email, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $buchung->speiseplanEintrag->woche; ?></td>
                                    <td><?php echo $buchung->bestaetigt ? 'Ja' : 'Nein'; ?></td>
                                    <td><?php echo $buchung->erinnerungGesendet ? 'Ja' : 'Nein'; ?></td>
                                    <td class="admin-actions">
                                        <a href="/admin/buchungen?jahr=<?php echo $jahr; ?>&woche=<?php echo $buchung->speiseplanEintrag->woche; ?>&loeschen=<?php echo $buchung->id; ?>" 
                                           class="button danger" 
                                           onclick="return confirm('Möchten Sie diese Buchung wirklich löschen?');">Löschen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="aktionen" style="margin-top: 20px;">
                    <a href="/admin/buchungen/zuruecksetzen" class="button danger">
                        Buchungen zurücksetzen
                    </a>
                </div>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Administration</p>
        </footer>
    </div>
</body>
</html>
