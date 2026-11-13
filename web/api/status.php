<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';

// Health + derzeit getragene Outfits/Stuff. Nur lesend: status und outfit-worn.
// Kein Benutzer-Befehl wird an den Bot weitergereicht.

$running = false;
$online = false;
$name = '';
$region = '';
$text = 'Client nicht erreichbar';
$wornOutfits = [];
$wornStuff = [];
$wornAvailable = false;

[$ok, $body] = bot_command($config, 'status', 3);
if ($ok) {
    $st = parse_bot_status($body);
    $running = !empty($st['running']);
    $online = !empty($st['online']);
    $name = (string)($st['name'] ?? '');
    $region = (string)($st['region'] ?? '');
    $text = (string)($st['text'] ?? $text);
} else {
    $text = 'Client nicht erreichbar';
}

if ($running && $online) {
    [$wok, $wbody] = bot_command($config, 'outfit-worn', 8);
    if ($wok) {
        $w = parse_bot_worn($wbody);
        $wornAvailable = !empty($w['available']);
        if ($wornAvailable) {
            $wornOutfits = $w['outfits'];
            $wornStuff = $w['stuff'];
        }
    }
}

json_out(200, [
    'ok' => true,
    'running' => $running,
    'online' => $online,
    'green' => $running && $online,
    'name' => $name,
    'region' => $region,
    'text' => $text,
    'worn_outfits' => $wornOutfits,
    'worn_stuff' => $wornStuff,
    'worn_available' => $wornAvailable,
]);
