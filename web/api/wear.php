<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false, 'error' => 'POST nötig']);
}

$last = (int)($_SESSION['last_wear'] ?? 0);
if (time() - $last < 8) {
    json_out(429, ['ok' => false, 'error' => 'einen Moment, der letzte Wechsel läuft noch']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$id = preg_replace('/[^a-z0-9\-]/', '', (string)($data['id'] ?? ''));
$o = outfit_by_id($config, $id);
if ($o === null) {
    json_out(404, ['ok' => false, 'error' => 'unbekanntes Outfit']);
}
if (empty($o['wear'])) {
    json_out(400, ['ok' => false, 'error' => 'für dieses Outfit gibt es noch kein WEAR']);
}

$folder = (string)$o['folder'];
$who = (string)$o['who'];
$parent = (string)$config['parent_folder'];
$wait = (int)$config['wait_seconds'];
$url = lsl_url($who);
$lslOk = false;
$lslDetail = '';

if ($url !== '') {
    $body = http_build_query([
        'token' => $config['token'],
        'folder' => $folder,
        'parent' => $parent,
        'wait' => $wait,
        'id' => $id,
        'who' => $who,
    ]);
    [$lslOk, $lslDetail] = garderobe_post_lsl($url, $body, (string)$config['token']);
} else {
    $lslDetail = 'kein Skript registriert für ' . $who;
}

$_SESSION['last_wear'] = time();
notify_layla($config, $folder, $id, $who, $lslOk, $lslDetail);

json_out(200, [
    'ok' => true,
    'id' => $id,
    'who' => $who,
    'folder' => $folder,
    'lsl' => $lslOk,
    'wait' => $wait,
]);
