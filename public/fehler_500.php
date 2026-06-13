<?php
/**
 * fehler_500.php - 500 Fehlerseite
 */

http_response_code(500);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Interner Serverfehler</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <div class="container">
        <div class="fehler-seite">
            <h1>500</h1>
            <p>Es ist ein interner Serverfehler aufgetreten.</p>
            <p>Bitte versuchen Sie es später erneut oder kontaktieren Sie den Administrator.</p>
            <p><a href="/" class="button">Zur Startseite</a></p>
        </div>
    </div>
</body>
</html>
