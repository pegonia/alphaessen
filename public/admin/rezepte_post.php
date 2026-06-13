<?php
/**
 * rezepte_post.php - Verarbeitet Rezepte-Formulare
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

// CSRF-Token prüfen
if (!$csrfService->validateRequest($_POST)) {
    $_SESSION['admin_rezepte_fehler'] = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
    header('Location: /admin/rezepte');
    exit;
}

// Aktion aus Formular
$aktion = $_POST['aktion'] ?? '';

try {
    switch ($aktion) {
        case 'erstellen':
            $name = trim($_POST['name'] ?? '');
            $typ = $_POST['typ'] ?? '';
            $beschreibung = trim($_POST['beschreibung'] ?? '');
            
            if (empty($name)) {
                throw new \InvalidArgumentException('Bitte geben Sie einen Namen ein.');
            }
            
            if (empty($typ)) {
                throw new \InvalidArgumentException('Bitte wählen Sie einen Typ aus.');
            }
            
            if (!\Alphaessen\Models\Essen::istGueltigerTyp($typ)) {
                throw new \InvalidArgumentException('Ungültiger Typ ausgewählt.');
            }
            
            $speiseplanService->erstelleEssen($name, $typ, $beschreibung);
            $_SESSION['admin_rezepte_erfolg'] = 'Rezept erfolgreich erstellt.';
            break;

        case 'bearbeiten':
            $essenId = isset($_POST['essen_id']) ? (int)$_POST['essen_id'] : 0;
            $name = trim($_POST['name'] ?? '');
            $typ = $_POST['typ'] ?? '';
            $beschreibung = trim($_POST['beschreibung'] ?? '');
            $aktiv = isset($_POST['aktiv']);
            
            if ($essenId <= 0) {
                throw new \InvalidArgumentException('Ungültige Rezept-ID.');
            }
            
            if (empty($name)) {
                throw new \InvalidArgumentException('Bitte geben Sie einen Namen ein.');
            }
            
            if (empty($typ)) {
                throw new \InvalidArgumentException('Bitte wählen Sie einen Typ aus.');
            }
            
            if (!\Alphaessen\Models\Essen::istGueltigerTyp($typ)) {
                throw new \InvalidArgumentException('Ungültiger Typ ausgewählt.');
            }
            
            $essen = $essenRepository->findeNachId($essenId);
            if ($essen === null) {
                throw new \InvalidArgumentException('Rezept nicht gefunden.');
            }
            
            $essen->name = $name;
            $essen->typ = $typ;
            $essen->beschreibung = $beschreibung;
            $essen->aktiv = $aktiv;
            
            $essenRepository->aktualisiere($essen);
            $_SESSION['admin_rezepte_erfolg'] = 'Rezept erfolgreich aktualisiert.';
            break;

        default:
            throw new \InvalidArgumentException('Unbekannte Aktion.');
    }

    header('Location: /admin/rezepte');
    exit;

} catch (\Exception $e) {
    $_SESSION['admin_rezepte_fehler'] = $e->getMessage();
    header('Location: /admin/rezepte');
    exit;
}
