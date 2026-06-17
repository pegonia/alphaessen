<?php
/**
 * buchung_erstellen.php - Verarbeitet Buchungsanfragen
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

// CSRF-Token prüfen
if (!$csrfService->validateRequest($_POST)) {
    header("Location: /?error=csrf");
    exit;
}

// Parameter aus Formular extrahieren
$essenId = isset($_POST['essen_id']) ? (int)$_POST['essen_id'] : 0;
$woche = isset($_POST['woche']) ? (int)$_POST['woche'] : 1;
$jahr = isset($_POST['jahr']) ? (int)$_POST['jahr'] : (int)date('Y');
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validierung
$fehler = [];

if (empty($email)) {
    $fehler[] = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // E-Mail-Syntax ist ungültig, aber wir speichern trotzdem (wie vereinbart)
    // Kein Fehler, aber Hinweis
}

if ($essenId <= 0) {
    $fehler[] = 'Ungültiges Essen ausgewählt.';
}

if ($woche < 1 || $woche > 12) {
    $fehler[] = 'Ungültige Woche.';
}

// Speiseplan-Eintrag abfragen
$eintrag = $speiseplanRepository->findeNachId($essenId);
if ($eintrag === null) {
    $fehler[] = 'Das ausgewählte Essen existiert nicht.';
}

// Prüfen, ob das Essen noch verfügbar ist
if ($eintrag !== null && !$buchungService->istVerfuegbar($eintrag->id)) {
    $fehler[] = 'Dieses Essen wurde bereits von jemand anderem gebucht.';
}

// Falls Fehler vorhanden, zurück zum Formular
if (!empty($fehler)) {
    $_SESSION['buchung_fehler'] = implode(' ', $fehler);
    $_SESSION['buchung_daten'] = $_POST;
    header("Location: /woche/{$woche}/{$jahr}?error=" . urlencode(implode(' ', $fehler)));
    exit;
}

// Buchung erstellen
try {
    $buchung = $buchungService->buchen($email, $essenId);
    
    // E-Mail in Session speichern für "Meine Buchungen"
    $_SESSION['nutzer_email'] = $email;
    
    // Bestätigungsstatus setzen
    $buchungRepository->setzeBestaetigt($buchung->id, true);
    
    // Erfolgmeldung
    $_SESSION['buchung_erfolg'] = 'Ihre Buchung wurde erfolgreich gespeichert. Sie erhalten eine Bestätigungs-E-Mail.';
    
    // Weiterleitung zur Wochenansicht
    header("Location: /woche/{$woche}/{$jahr}?email=" . urlencode($email) . "&success=1");
    exit;

} catch (\RuntimeException $e) {
    // Fehler bei der Buchung
    $_SESSION['buchung_fehler'] = $e->getMessage();
    header("Location: /woche/{$woche}/{$jahr}?error=" . urlencode($e->getMessage()));
    exit;
} catch (\Exception $e) {
    // Unerwarteter Fehler
    error_log("Buchungsfehler: " . $e->getMessage());
    $_SESSION['buchung_fehler'] = 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.';
    header("Location: /woche/{$woche}/{$jahr}?error=fehler");
    exit;
}
