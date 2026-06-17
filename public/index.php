<?php
/**
 * index.php - Frontend-Router für Alphaessen
 * 
 * Dieser Router leitet Anfragen an die entsprechenden Controller weiter.
 */
// Error Reporting (nur in Entwicklungsumgebung)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');
// Autoloader laden
require_once __DIR__ . '/../src/Autoloader.php';

// Datenbank initialisieren
use Alphaessen\Database;
$config = require __DIR__ . '/../config/database.php';
Database::init($config);

// E-Mail-Konfiguration laden
$emailConfig = require __DIR__ . '/../config/email.php';

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
#print_r($_REQUEST);
#print_r($_SESSION);

// Request-Pfad analysieren
$path = $_GET['path'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Routing-Tabelle
$routes = [
    // Frontend-Routen
    'GET /' => 'speiseplan_kalender',
    'GET kalender' => 'speiseplan_kalender',
    'GET woche/{woche}' => 'speiseplan_woche',
    'GET woche/{woche}/{jahr}' => 'speiseplan_woche',
    'GET buchen' => 'buchung_formular',
    'POST buchen' => 'buchung_erstellen',
    'GET meine-buchungen' => 'meine_buchungen',
    
    // Admin-Routen
    'GET /admin' => 'admin_redirect',
    'GET /admin/' => 'admin_dashboard',
    'GET /admin/login' => 'admin_login',
    'POST /admin/login' => 'admin_login_post',
    'GET /admin/logout' => 'admin_logout',
    'GET /admin/speiseplan' => 'admin_speiseplan',
    'GET /admin/speiseplan/{jahr}' => 'admin_speiseplan_jahr',
    'GET /admin/speiseplan/{jahr}/{woche}' => 'admin_speiseplan_woche',
    'POST /admin/speiseplan' => 'admin_speiseplan_post',
    'GET /admin/rezepte' => 'admin_rezepte',
    'POST /admin/rezepte' => 'admin_rezepte_post',
    'GET /admin/buchungen' => 'admin_buchungen',
    'POST /admin/buchungen/zuruecksetzen' => 'admin_buchungen_zuruecksetzen',
];

// Route finden
$routeKey = $method . ' ' . $path;
$action = null;
echo "Route key: $routeKey<br>";
// Exakte Übereinstimmung suchen
if (isset($routes[$routeKey])) {
    $action = $routes[$routeKey];
} else {
    // Dynamische Routen mit Parametern
    foreach ($routes as $route => $routeAction) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
        $pattern = str_replace(' ', '\\s+', $pattern);
        if (preg_match('#^' . $pattern . '$#', $routeKey, $matches)) {
            $action = $routeAction;
            $_GET = array_merge($_GET, $matches);
            break;
        }
    }
}

// Standard-Route
if ($action === null) {
    $action = 'fehler_404';
}
//$action = "admin_login";
error_log("action ist $action");
// Controller aufrufen
try {
    switch ($action) {
        // Frontend
        case 'speiseplan_kalender':
            require __DIR__ . '/speiseplan_kalender.php';
            break;
        case 'speiseplan_woche':
            require __DIR__ . '/speiseplan_woche.php';
            break;
        case 'buchung_formular':
            require __DIR__ . '/buchung_formular.php';
            break;
        case 'buchung_erstellen':
            require __DIR__ . '/buchung_erstellen.php';
            break;
        case 'meine_buchungen':
            require __DIR__ . '/meine_buchungen.php';
            break;
            
        // Admin
        case 'admin_redirect':
            header('Location: /admin/login');
            exit;
        case 'admin_dashboard':
            require __DIR__ . '/admin/dashboard.php';
            break;
        case 'admin_login':
            require __DIR__ . '/admin/login.php';
            break;
        case 'admin_login_post':
            require __DIR__ . '/admin/login_post.php';
            break;
        case 'admin_logout':
            require __DIR__ . '/admin/logout.php';
            break;
        case 'admin_speiseplan':
        case 'admin_speiseplan_jahr':
        case 'admin_speiseplan_woche':
            require __DIR__ . '/admin/speiseplan.php';
            break;
        case 'admin_speiseplan_post':
            require __DIR__ . '/admin/speiseplan_post.php';
            break;
        case 'admin_rezepte':
            require __DIR__ . '/admin/rezepte.php';
            break;
        case 'admin_rezepte_post':
            require __DIR__ . '/admin/rezepte_post.php';
            break;
        case 'admin_buchungen':
            require __DIR__ . '/admin/buchungen.php';
            break;
        case 'admin_buchungen_zuruecksetzen':
            require __DIR__ . '/admin/buchungen_zuruecksetzen.php';
            break;
            
        // Fehler
        case 'fehler_404':
        default:
            http_response_code(404);
            require __DIR__ . '/fehler_404.php';
            break;
    }
} catch (\Throwable $e) {
    // Fehlerbehandlung
    error_log("Fehler: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    require __DIR__ . '/fehler_500.php';
}
