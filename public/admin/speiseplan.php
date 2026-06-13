<?php
/**
 * speiseplan.php - Speiseplan-Verwaltung
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\CsrfService;
use Alphaessen\Repositories\AdminRepository;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;

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
$speiseplanService = new SpeiseplanService($speiseplanRepository, $essenRepository);
$csrfService = new CsrfService();

// Parameter aus URL extrahieren
$jahr = isset($_GET['jahr']) ? (int)$_GET['jahr'] : (int)date('Y');
$woche = isset($_GET['woche']) ? (int)$_GET['woche'] : null;

// Alle Jahre mit Speiseplan abfragen
$alleJahre = $speiseplanService->getAlleJahre();
if (empty($alleJahre)) {
    $alleJahre = [$jahr];
}

// Falls keine Woche angegeben, erste Woche des Jahres nehmen
if ($woche === null) {
    $wochen = $speiseplanService->getWochenFuerJahr($jahr);
    $woche = !empty($wochen) ? $wochen[0] : 1;
}

// Speiseplan für die ausgewählte Woche abfragen
$eintraege = $speiseplanService->getSpeiseplanFuerWoche($woche, $jahr);
$datum = $speiseplanService->getDatumFuerWoche($woche, $jahr);

// Alle verfügbaren Essen abfragen
$alleEssen = $speiseplanService->getAlleEssen(true);

// Nach Typen gruppieren
$essenNachTyp = [];
foreach ($alleEssen as $essen) {
    if (!isset($essenNachTyp[$essen->typ])) {
        $essenNachTyp[$essen->typ] = [];
    }
    $essenNachTyp[$essen->typ][] = $essen;
}

// Erfolgmeldung aus Session
$erfolg = $_SESSION['admin_speiseplan_erfolg'] ?? null;
unset($_SESSION['admin_speiseplan_erfolg']);

$fehler = $_SESSION['admin_speiseplan_fehler'] ?? null;
unset($_SESSION['admin_speiseplan_fehler']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Speiseplan verwalten</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen - Administration</h1>
            <nav>
                <a href="/admin/">Dashboard</a>
                | <a href="/admin/rezepte">Rezepte</a>
                | <a href="/admin/buchungen">Buchungen</a>
                | <a href="/admin/logout">Abmelden</a>
            </nav>
        </header>

        <main>
            <h2>Speiseplan verwalten</h2>
            
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
                <form method="get" action="/admin/speiseplan">
                    <label for="jahr">Jahr:</label>
                    <select name="jahr" id="jahr" onchange="this.form.submit()">
                        <?php foreach ($alleJahre as $j): ?>
                            <option value="<?php echo $j; ?>" <?php echo $j === $jahr ? 'selected' : ''; ?>>
                                <?php echo $j; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <label for="woche" style="margin-left: 15px;">Woche:</label>
                    <select name="woche" id="woche" onchange="this.form.submit()">
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
                <h3>Woche <?php echo $woche; ?> - <?php echo $datum; ?> (Donnerstag)</h3>
                
                <h4>Aktueller Speiseplan</h4>
                <?php if (empty($eintraege)): ?>
                    <p>Kein Speiseplan für diese Woche vorhanden.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Essen</th>
                                <th>Beschreibung</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eintraege as $eintrag): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($eintrag->essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($eintrag->essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($eintrag->essen->beschreibung ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="admin-actions">
                                        <a href="/admin/speiseplan?jahr=<?php echo $jahr; ?>&woche=<?php echo $woche; ?>&loeschen=<?php echo $eintrag->id; ?>" 
                                           class="button danger" 
                                           onclick="return confirm('Möchten Sie diesen Eintrag wirklich löschen?');">Löschen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <h4>Essen hinzufügen</h4>
                <form method="post" action="/admin/speiseplan">
                    <?php echo $csrfService->getTokenInput(); ?>
                    
                    <input type="hidden" name="aktion" value="hinzufuegen">
                    <input type="hidden" name="jahr" value="<?php echo $jahr; ?>">
                    <input type="hidden" name="woche" value="<?php echo $woche; ?>">

                    <div class="form-group">
                        <label for="essen_typ">Typ:</label>
                        <select name="essen_typ" id="essen_typ" required>
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ($essenNachTyp as $typ => $essenListe): ?>
                                <optgroup label="<?php echo htmlspecialchars(\Alphaessen\Models\Essen::fromArray(['typ' => $typ])->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach ($essenListe as $essen): ?>
                                        <option value="<?php echo $essen->id; ?>">
                                            <?php echo htmlspecialchars($essen->name, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Essen hinzufügen</button>
                    </div>
                </form>

                <div class="aktionen" style="margin-top: 20px;">
                    <a href="/admin/speiseplan?jahr=<?php echo $jahr; ?>&woche=<?php echo $woche; ?>&aktion=woche_loeschen" 
                       class="button danger" 
                       onclick="return confirm('Möchten Sie den gesamten Speiseplan für Woche <?php echo $woche; ?> wirklich löschen?');">
                        Woche <?php echo $woche; ?> löschen
                    </a>
                    
                    <a href="/admin/speiseplan?jahr=<?php echo $jahr; ?>&aktion=jahr_loeschen" 
                       class="button danger" 
                       onclick="return confirm('Möchten Sie den gesamten Speiseplan für Jahr <?php echo $jahr; ?> wirklich löschen?');">
                        Jahr <?php echo $jahr; ?> löschen
                    </a>
                </div>

                <h4>Woche kopieren</h4>
                <form method="post" action="/admin/speiseplan">
                    <?php echo $csrfService->getTokenInput(); ?>
                    
                    <input type="hidden" name="aktion" value="kopieren">
                    <input type="hidden" name="von_jahr" value="<?php echo $jahr; ?>">
                    <input type="hidden" name="von_woche" value="<?php echo $woche; ?>">

                    <div class="form-group">
                        <label for="nach_jahr">Zieljahr:</label>
                        <select name="nach_jahr" id="nach_jahr" required>
                            <?php foreach ($alleJahre as $j): ?>
                                <option value="<?php echo $j; ?>" <?php echo $j === $jahr ? 'selected' : ''; ?>>
                                    <?php echo $j; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nach_woche">Zielwoche:</label>
                        <select name="nach_woche" id="nach_woche" required>
                            <?php for ($w = 1; $w <= 12; $w++): ?>
                                <option value="<?php echo $w; ?>" <?php echo $w === $woche ? 'selected' : ''; ?>>
                                    Woche <?php echo $w; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button">Woche kopieren</button>
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

<?php
// Löschaktionen verarbeiten
if (isset($_GET['loeschen'])) {
    $eintragId = (int)$_GET['loeschen'];
    $speiseplanRepository->loesche($eintragId);
    $_SESSION['admin_speiseplan_erfolg'] = 'Eintrag erfolgreich gelöscht.';
    header("Location: /admin/speiseplan?jahr={$jahr}&woche={$woche}");
    exit;
}

if (isset($_GET['aktion']) && $_GET['aktion'] === 'woche_loeschen') {
    $geloescht = $speiseplanRepository->loescheNachWocheUndJahr($woche, $jahr);
    $_SESSION['admin_speiseplan_erfolg'] = "Woche {$woche} gelöscht. {$geloescht} Einträge entfernt.";
    header("Location: /admin/speiseplan?jahr={$jahr}");
    exit;
}

if (isset($_GET['aktion']) && $_GET['aktion'] === 'jahr_loeschen') {
    $geloescht = $speiseplanRepository->loescheNachJahr($jahr);
    $_SESSION['admin_speiseplan_erfolg'] = "Jahr {$jahr} gelöscht. {$geloescht} Einträge entfernt.";
    header("Location: /admin/speiseplan");
    exit;
}
?>
