<?php
/**
 * logout.php - Admin-Logout
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Repositories\AdminRepository;

// Services initialisieren
$adminRepository = new AdminRepository();
$adminAuthService = new AdminAuthService($adminRepository);

// Abmelden
$adminAuthService->logout();

// Zur Startseite weiterleiten
header('Location: /');
exit;
