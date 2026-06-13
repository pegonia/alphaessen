<?php
/**
 * dashboard.php - Admin-Dashboard
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\BuchungService;
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

// Statistiken abfragen
$admin = $adminAuthService->getAktuellerAdmin();
$alleJahre = $speiseplanService->getAlleJahre();
$aktuellesJahr = date('Y');

// Buchungen für das aktuelle Jahr
$buchungenAktuellesJahr = $buchungService->getBuchungenFuerWoche(1, $aktuellesJahr); // Beispiel
$anzahlBuchungen = $buchungRepository->zaehleNachJahr($aktuellesJahr);
$anzahlNutzer = $nutzerRepository->zaehle();
$anzahlEssen = $essenRepository->zaehle(true);
$anzahlSpeiseplanEintraege = $speiseplanRepository->zaehleNachJahr($aktuellesJahr);

// Letzte Buchungen
$letzteBuchungen = $buchungRepository->findeAlle();
$letzteBuchungen = array_slice($letzteBuchungen, 0, 5);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Admin-Dashboard</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen - Administration</h1>
            <nav>
                <a href="/">Zurück zur Startseite</a>
                | <a href="/admin/logout">Abmelden</a>
            </nav>
        </header>

        <main>
            <h2>Dashboard</h2>
            
            <p>Angemeldet als: <strong><?php echo htmlspecialchars($admin->benutzername, ENT_QUOTES, 'UTF-8'); ?></strong></p>

            <div class="dashboard">
                <div class="dashboard-card">
                    <h3>Buchungen (<?php echo $aktuellesJahr; ?>)</h3>
                    <div class="value"><?php echo $anzahlBuchungen; ?></div>
                </div>

                <div class="dashboard-card">
                    <h3>Nutzer</h3>
                    <div class="value"><?php echo $anzahlNutzer; ?></div>
                </div>

                <div class="dashboard-card">
                    <h3>Rezepte</h3>
                    <div class="value"><?php echo $anzahlEssen; ?></div>
                </div>

                <div class="dashboard-card">
                    <h3>Speiseplan-Einträge (<?php echo $aktuellesJahr; ?>)</h3>
                    <div class="value"><?php echo $anzahlSpeiseplanEintraege; ?></div>
                </div>
            </div>

            <div class="admin-content">
                <h3>Schnellzugriff</h3>
                <div class="aktionen">
                    <a href="/admin/speiseplan" class="button">Speiseplan verwalten</a>
                    <a href="/admin/rezepte" class="button">Rezepte verwalten</a>
                    <a href="/admin/buchungen" class="button">Buchungen verwalten</a>
                </div>
            </div>

            <div class="admin-content">
                <h3>Letzte Buchungen</h3>
                <?php if (empty($letzteBuchungen)): ?>
                    <p>Keine Buchungen vorhanden.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>E-Mail</th>
                                <th>Essen</th>
                                <th>Woche</th>
                                <th>Bestätigt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($letzteBuchungen as $buchung): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($buchung->erstelltAm)); ?></td>
                                    <td><?php echo htmlspecialchars($buchung->nutzer->email, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($buchung->speiseplanEintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $buchung->speiseplanEintrag->woche; ?></td>
                                    <td><?php echo $buchung->bestaetigt ? 'Ja' : 'Nein'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>Alphaessen - Administration</p>
        </footer>
    </div>
</body>
</html>
