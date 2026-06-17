<?php
/**
 * rezepte.php - Rezepte-Verwaltung
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Services\SpeiseplanService;
use Alphaessen\Services\CsrfService;
use Alphaessen\Repositories\AdminRepository;
use Alphaessen\Repositories\SpeiseplanRepository;
use Alphaessen\Repositories\EssenRepository;

// Session starten (falls nicht bereits gestartet)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Alle Essen abfragen
$alleEssen = $speiseplanService->getAlleEssen(false); // Auch inaktive anzeigen

// Erfolgmeldung aus Session
$erfolg = $_SESSION['admin_rezepte_erfolg'] ?? null;
unset($_SESSION['admin_rezepte_erfolg']);

$fehler = $_SESSION['admin_rezepte_fehler'] ?? null;
unset($_SESSION['admin_rezepte_fehler']);

// Bearbeitungsmodus
$bearbeitenId = isset($_GET['bearbeiten']) ? (int)$_GET['bearbeiten'] : 0;
$bearbeitenEssen = null;

if ($bearbeitenId > 0) {
    $bearbeitenEssen = $essenRepository->findeNachId($bearbeitenId);
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Rezepte verwalten</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen - Administration</h1>
            <nav>
                <a href="/admin/">Dashboard</a>
                | <a href="/admin/speiseplan">Speiseplan</a>
                | <a href="/admin/buchungen">Buchungen</a>
                | <a href="/admin/logout">Abmelden</a>
            </nav>
        </header>

        <main>
            <h2>Rezepte verwalten</h2>
            
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

            <div class="admin-content">
                <?php if ($bearbeitenEssen !== null): ?>
                    <h3>Rezept bearbeiten</h3>
                    <form method="post" action="/admin/rezepte">
                        <?php echo $csrfService->getTokenInput(); ?>
                        
                        <input type="hidden" name="aktion" value="bearbeiten">
                        <input type="hidden" name="essen_id" value="<?php echo $bearbeitenEssen->id; ?>">

                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($bearbeitenEssen->name, ENT_QUOTES, 'UTF-8'); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="typ">Typ:</label>
                            <select name="typ" id="typ" required>
                                <?php foreach ($speiseplanService->getAlleEssenTypen() as $typ): ?>
                                    <option value="<?php echo $typ; ?>" 
                                        <?php echo $typ === $bearbeitenEssen->typ ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(\Alphaessen\Models\Essen::fromArray(['typ' => $typ])->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="beschreibung">Beschreibung:</label>
                            <textarea id="beschreibung" name="beschreibung" rows="3" 
                                      placeholder="Beschreibung des Essens..."><?php echo htmlspecialchars($bearbeitenEssen->beschreibung ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="aktiv" value="1" 
                                       <?php echo $bearbeitenEssen->aktiv ? 'checked' : ''; ?>>
                                Aktiv
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button primary">Speichern</button>
                            <a href="/admin/rezepte" class="button">Abbrechen</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h3>Neues Rezept erstellen</h3>
                    <form method="post" action="/admin/rezepte">
                        <?php echo $csrfService->getTokenInput(); ?>
                        
                        <input type="hidden" name="aktion" value="erstellen">

                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" 
                                   required placeholder="Name des Essens">
                        </div>

                        <div class="form-group">
                            <label for="typ">Typ:</label>
                            <select name="typ" id="typ" required>
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ($speiseplanService->getAlleEssenTypen() as $typ): ?>
                                    <option value="<?php echo $typ; ?>">
                                        <?php echo htmlspecialchars(\Alphaessen\Models\Essen::fromArray(['typ' => $typ])->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="beschreibung">Beschreibung:</label>
                            <textarea id="beschreibung" name="beschreibung" rows="3" 
                                      placeholder="Beschreibung des Essens..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button primary">Rezept erstellen</button>
                        </div>
                    </form>
                <?php endif; ?>

                <h3>Vorhandene Rezepte</h3>
                
                <?php if (empty($alleEssen)): ?>
                    <p>Keine Rezepte vorhanden.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Typ</th>
                                <th>Beschreibung</th>
                                <th>Aktiv</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alleEssen as $essen): ?>
                                <tr style="<?php echo !$essen->aktiv ? 'background-color: #f5f5f5; color: #999;' : ''; ?>">
                                    <td><?php echo htmlspecialchars($essen->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($essen->getTypAnzeige(), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($essen->beschreibung ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $essen->aktiv ? 'Ja' : 'Nein'; ?></td>
                                    <td class="admin-actions">
                                        <a href="/admin/rezepte?bearbeiten=<?php echo $essen->id; ?>" 
                                           class="button">Bearbeiten</a>
                                        <?php if ($essen->aktiv): ?>
                                            <a href="/admin/rezepte?aktion=deaktivieren&essen_id=<?php echo $essen->id; ?>" 
                                               class="button danger" 
                                               onclick="return confirm('Möchten Sie dieses Rezept wirklich deaktivieren?');">Deaktivieren</a>
                                        <?php else: ?>
                                            <a href="/admin/rezepte?aktion=aktivieren&essen_id=<?php echo $essen->id; ?>" 
                                               class="button primary">Aktivieren</a>
                                        <?php endif; ?>
                                    </td>
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

<?php
// Aktionen verarbeiten
if (isset($_GET['aktion'])) {
    $essenId = isset($_GET['essen_id']) ? (int)$_GET['essen_id'] : 0;
    
    switch ($_GET['aktion']) {
        case 'deaktivieren':
            if ($essenId > 0) {
                $essenRepository->deaktiviere($essenId);
                $_SESSION['admin_rezepte_erfolg'] = 'Rezept erfolgreich deaktiviert.';
            }
            break;
            
        case 'aktivieren':
            if ($essenId > 0) {
                $essen = $essenRepository->findeNachId($essenId);
                if ($essen !== null) {
                    $essen->aktiv = true;
                    $essenRepository->aktualisiere($essen);
                    $_SESSION['admin_rezepte_erfolg'] = 'Rezept erfolgreich aktiviert.';
                }
            }
            break;
    }
    
    header('Location: /admin/rezepte');
    exit;
}
?>
