<?php
declare(strict_types=1);
require __DIR__ . '/api/inc.php';
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$people = public_people($config);
$payload = json_encode([
    'people' => $people,
    'outfits' => public_outfits($config),
    'stuff' => public_stuff($config),
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
        <div class="health" id="health" data-state="unknown" title="Collar verbunden">
          <span class="health-dot" aria-hidden="true"></span>
          <span class="health-text" id="health-text">prüfe …</span>
        </div>
        <button type="button" class="stuff-toggle" id="stuff-toggle" aria-expanded="false" aria-controls="stuff-panel">STUFF</button>
      </div>
<?php if (count($people) > 1): ?>
      <div id="who" class="who" role="tablist" aria-label="Wessen Outfits">
<?php foreach ($people as $i => $p): ?>
        <button type="button" role="tab" data-who="<?php echo htmlspecialchars($p['id'], ENT_QUOTES); ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($p['name']); ?></button>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div id="who" class="who" role="tablist" aria-label="Wessen Outfits" hidden></div>
<?php endif; ?>
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

  <div id="stuff-backdrop" class="stuff-backdrop" hidden></div>
  <aside id="stuff-panel" class="stuff-panel" hidden>
    <div class="stuff-head">
      <h2>Stuff</h2>
      <button type="button" class="stuff-close" id="stuff-close" aria-label="Schliessen">×</button>
    </div>
    <ul id="stuff-list" class="stuff-list"></ul>
  </aside>

  <script>window.GARDEROBE = <?php echo $payload; ?>;</script>
  <script src="js/garderobe.js"></script>
</body>
</html>
