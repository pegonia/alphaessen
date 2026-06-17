<?php
/**
 * speiseplan_post.php - Verarbeitet Speiseplan-Formulare
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

// CSRF-Token prüfen
if (!$csrfService->validateRequest($_POST)) {
    $_SESSION['admin_speiseplan_fehler'] = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
    header('Location: /admin/speiseplan');
    exit;
}

// Aktion aus Formular
$aktion = $_POST['aktion'] ?? '';
$jahr = isset($_POST['jahr']) ? (int)$_POST['jahr'] : (int)date('Y');
$woche = isset($_POST['woche']) ? (int)$_POST['woche'] : 1;

try {
    switch ($aktion) {
        case 'hinzufuegen':
            $essenId = isset($_POST['essen_typ']) ? (int)$_POST['essen_typ'] : 0;
            
            if ($essenId <= 0) {
                throw new \InvalidArgumentException('Bitte wählen Sie ein Essen aus.');
            }
            
            // Prüfen, ob das Essen bereits im Speiseplan ist
            if ($speiseplanRepository->istEssenImSpeiseplan($woche, $jahr, $essenId)) {
                throw new \RuntimeException('Dieses Essen ist bereits im Speiseplan für Woche ' . $woche . ', Jahr ' . $jahr . '.');
            }
            
            $speiseplanService->erstelleEintrag($woche, $jahr, $essenId);
            $_SESSION['admin_speiseplan_erfolg'] = 'Essen erfolgreich zum Speiseplan hinzugefügt.';
            break;

        case 'kopieren':
            $vonJahr = isset($_POST['von_jahr']) ? (int)$_POST['von_jahr'] : $jahr;
            $vonWoche = isset($_POST['von_woche']) ? (int)$_POST['von_woche'] : $woche;
            $nachJahr = isset($_POST['nach_jahr']) ? (int)$_POST['nach_jahr'] : $jahr;
            $nachWoche = isset($_POST['nach_woche']) ? (int)$_POST['nach_woche'] : $woche;
            
            $anzahl = $speiseplanService->kopiereWoche($vonWoche, $vonJahr, $nachWoche, $nachJahr);
            $_SESSION['admin_speiseplan_erfolg'] = "Woche {$vonWoche} aus Jahr {$vonJahr} nach Woche {$nachWoche}, Jahr {$nachJahr} kopiert. {$anzahl} Einträge kopiert.";
            break;

        default:
            throw new \InvalidArgumentException('Unbekannte Aktion.');
    }

    header("Location: /admin/speiseplan?jahr={$jahr}&woche={$woche}");
    exit;

} catch (\Exception $e) {
    $_SESSION['admin_speiseplan_fehler'] = $e->getMessage();
    header("Location: /admin/speiseplan?jahr={$jahr}&woche={$woche}");
    exit;
}
