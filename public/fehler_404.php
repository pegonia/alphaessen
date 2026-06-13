<?php
/**
 * fehler_404.php - 404 Fehlerseite
 */

http_response_code(404);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Seite nicht gefunden</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <div class="fehler-seite">
            <h1>404</h1>
            <p>Die angeforderte Seite wurde nicht gefunden.</p>
            <p><a href="/" class="button">Zur Startseite</a></p>
        </div>
    </div>
</body>
</html>
