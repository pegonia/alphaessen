<?php
/**
 * login.php - Admin-Login-Seite
 */

use Alphaessen\Services\AdminAuthService;
use Alphaessen\Repositories\AdminRepository;

// Services initialisieren
$adminRepository = new AdminRepository();
$adminAuthService = new AdminAuthService($adminRepository);

// Falls bereits angemeldet, zum Dashboard weiterleiten
if ($adminAuthService->istAngemeldet()) {
    header('Location: /admin/');
    exit;
}

// Fehler aus Session anzeigen
$fehler = $_SESSION['admin_login_fehler'] ?? null;
unset($_SESSION['admin_login_fehler']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alphaessen - Admin-Login</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Alphaessen - Administration</h1>
            <nav>
                <a href="/">Zurück zur Startseite</a>
            </nav>
        </header>

        <main>
            <div class="login-formular">
                <h2>Admin-Login</h2>
                
                <?php if ($fehler): ?>
                    <div class="hinweis wichtig">
                        <?php echo htmlspecialchars($fehler, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/admin/login">
                    <div class="form-group">
                        <label for="benutzername">Benutzername:</label>
                        <input type="text" id="benutzername" name="benutzername" 
                               required autofocus placeholder="Benutzername">
                    </div>

                    <div class="form-group">
                        <label for="passwort">Passwort:</label>
                        <input type="password" id="passwort" name="passwort" 
                               required placeholder="Passwort">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="button primary">Anmelden</button>
                        <a href="/" class="button">Abbrechen</a>
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
