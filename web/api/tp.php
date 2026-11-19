<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false, 'error' => 'POST nötig']);
}

$last = (int)($_SESSION['last_tp'] ?? 0);
if (time() - $last < 8) {
    json_out(429, ['ok' => false, 'error' => 'einen Moment, der letzte Teleport läuft noch']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$id = preg_replace('/[^a-z0-9\-]/', '', (string)($data['id'] ?? ''));

$t = teleport_by_id($config, $id);
if ($t === null) {
    json_out(404, ['ok' => false, 'error' => 'unbekannter Ort']);
}

$parsed = parse_slurl((string)($t['url'] ?? ''));
if ($parsed === null) {
    json_out(400, ['ok' => false, 'error' => 'ungültige Teleport-URL']);
}

$region = $parsed['region'];
$x = $parsed['x'];
$y = $parsed['y'];
$z = $parsed['z'];
if (is_blocked_region($config, $region)) {
    json_out(403, ['ok' => false, 'error' => 'diese Region ist gesperrt']);
}

$name = trim((string)($t['name'] ?? $region));
$who = (string)($t['who'] ?? 'layla');
$url = lsl_url($who);
$lslOk = false;
$lslDetail = '';

if ($url !== '') {
    $body = http_build_query([
        'token' => $config['token'],
        'op' => 'tp',
        'id' => $id,
        'who' => $who,
        'name' => $name,
        'region' => $region,
        'x' => $x,
        'y' => $y,
        'z' => $z,
    ]);
    [$lslOk, $lslDetail] = garderobe_post_lsl($url, $body, (string)$config['token']);
} else {
    $lslDetail = 'kein Skript registriert für ' . $who;
}

$_SESSION['last_tp'] = time();
$dest = $region . ' ' . $x . '/' . $y . '/' . $z;
notify_action($config, 'TP', $dest, $id, $who, $lslOk, $lslDetail);

json_out(200, [
    'ok' => true,
    'id' => $id,
    'who' => $who,
    'name' => $name,
    'region' => $region,
    'lsl' => $lslOk,
]);
