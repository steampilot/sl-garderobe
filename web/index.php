<?php
declare(strict_types=1);
require __DIR__ . '/api/inc.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$payload = json_encode([
    'people' => public_people($config),
    'outfits' => public_outfits($config),
], JSON_UNESCAPED_UNICODE);
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <meta name="color-scheme" content="dark">
  <meta name="theme-color" content="#161617">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Garderobe">
  <title>Garderobe</title>
  <link rel="manifest" href="manifest.webmanifest">
  <link rel="apple-touch-icon" href="icon-180.png">
  <link rel="icon" href="icon-192.png" sizes="192x192" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@1,9..144,500;1,9..144,600&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/garderobe.css">
</head>
<body>
  <div id="app">
    <header class="top">
      <div class="top-row">
        <div class="brand">Garderobe</div>
      </div>
      <div id="who" class="who" role="tablist" aria-label="Wessen Outfits">
        <button type="button" role="tab" data-who="layla" aria-selected="true">Layla</button>
        <button type="button" role="tab" data-who="jerome" aria-selected="false">Jérôme</button>
        <button type="button" role="tab" data-who="cindy" aria-selected="false">Cindy</button>
        <button type="button" role="tab" data-who="caprica" aria-selected="false">Caprica</button>
      </div>
    </header>

    <div class="stage-wrap" id="stage">
      <div class="frame" id="frame">
        <img id="img-a" alt="">
        <img id="img-b" alt="">
      </div>
    </div>

    <div id="empty" class="empty" hidden>
      <p>Die Bilder für <span id="empty-who"></span> kommen noch.</p>
      <p class="hint">Sobald du sie generiert hast, legen wir sie hier ab — Vorderansicht, 832×1248.</p>
    </div>

    <div id="dots" class="dots"></div>

    <footer class="meta" id="meta">
      <h1 id="look-name"></h1>
      <p id="look-desc" class="desc"></p>
      <div class="controls">
        <button type="button" class="arrow" id="prev" aria-label="Vorheriges Outfit">‹</button>
        <button type="button" class="wear" id="wear">WEAR</button>
        <button type="button" class="arrow" id="next" aria-label="Nächstes Outfit">›</button>
      </div>
      <p id="status" class="status"></p>
    </footer>
  </div>

  <script>window.GARDEROBE = <?php echo $payload; ?>;</script>
  <script src="js/garderobe.js"></script>
</body>
</html>
