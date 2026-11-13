<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';

// Health + getragenes Outfit über das Collar-Skript (HTTP-in-URL), wie eine Hanger-Garderobe.
// Optionaler Fallback: slbot outfit-worn, nur wenn bot_url erreichbar ist.

$who = 'layla';
$people = people_ids($config);
if ($people && !in_array($who, $people, true)) $who = $people[0];

$online = false;
$name = '';
$region = '';
$text = 'nicht verbunden';
$wornOutfits = [];
$wornStuff = [];
$wornAvailable = false;
$source = '';

$url = lsl_url($who);
if ($url === '') {
    $text = 'Skript nicht registriert';
} else {
    $body = http_build_query([
        'token' => $config['token'],
        'op' => 'ping',
        'who' => $who,
    ]);
    [$ok, $resp] = garderobe_post_lsl($url, $body, (string)$config['token'], 4000, 10);
    if ($ok) {
        $ping = parse_lsl_ping($resp);
        if ($ping !== null) {
            $online = true;
            $region = $ping['region'];
            $text = $region !== '' ? $region : 'verbunden';
            $source = 'collar';
            if ($ping['worn_available']) {
                $wornOutfits = maybe_mark_naked($config, $ping['outfits'], true);
                $wornStuff = $ping['stuff'];
                $wornAvailable = true;
            }
        } else {
            $text = 'keine Antwort';
        }
    } else {
        $text = 'keine Antwort';
    }
}

$botUrl = trim((string)($config['bot_url'] ?? ''));
if (!$wornAvailable && $botUrl !== '') {
    [$wok, $wbody] = bot_command($config, 'outfit-worn', 8);
    if ($wok) {
        $w = parse_bot_worn($wbody);
        if (!empty($w['available'])) {
            $wornOutfits = maybe_mark_naked($config, $w['outfits'], true);
            $wornStuff = $w['stuff'];
            $wornAvailable = true;
            if ($source === '') $source = 'bot';
        }
    }
}

json_out(200, [
    'ok' => true,
    'running' => $online,
    'online' => $online,
    'green' => $online,
    'name' => $name,
    'region' => $region,
    'text' => $text,
    'source' => $source,
    'worn_outfits' => $wornOutfits,
    'worn_stuff' => $wornStuff,
    'worn_available' => $wornAvailable,
]);
