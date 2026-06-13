<?php
/**
 * login_post.php - Verarbeitet Admin-Login
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Services\CsrfService;
use Alphaessen\Repositories\AdminRepository;

// Services initialisieren
$adminRepository = new AdminRepository();
$adminAuthService = new AdminAuthService($adminRepository);
$csrfService = new CsrfService();

// Falls bereits angemeldet, zum Dashboard weiterleiten
if ($adminAuthService->istAngemeldet()) {
    header('Location: /admin/');
    exit;
}

// CSRF-Token prüfen
if (!$csrfService->validateRequest($_POST)) {
    $_SESSION['admin_login_fehler'] = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
    header('Location: /admin/login');
    exit;
}

// Anmeldedaten aus Formular
$benutzername = $_POST['benutzername'] ?? '';
$passwort = $_POST['passwort'] ?? '';

// Validierung
if (empty($benutzername) || empty($passwort)) {
    $_SESSION['admin_login_fehler'] = 'Bitte geben Sie Benutzername und Passwort ein.';
    header('Location: /admin/login');
    exit;
}

// Anmelden
$admin = $adminAuthService->login($benutzername, $passwort);

if ($admin === null) {
    $_SESSION['admin_login_fehler'] = 'Ungültiger Benutzername oder Passwort.';
    header('Location: /admin/login');
    exit;
}

// Erfolgreich angemeldet - zum Dashboard weiterleiten
header('Location: /admin/');
exit;
