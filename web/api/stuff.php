<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false, 'error' => 'POST nötig']);
}

$last = (int)($_SESSION['last_stuff'] ?? 0);
if (time() - $last < 3) {
    json_out(429, ['ok' => false, 'error' => 'einen Moment, der letzte Stuff-Befehl läuft noch']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$id = preg_replace('/[^a-z0-9\-]/', '', (string)($data['id'] ?? ''));
$action = strtolower(preg_replace('/[^a-z]/', '', (string)($data['action'] ?? '')));
if ($action !== 'add' && $action !== 'remove') {
    json_out(400, ['ok' => false, 'error' => 'action muss add oder remove sein']);
}

$s = stuff_by_id($config, $id);
if ($s === null) {
    json_out(404, ['ok' => false, 'error' => 'unbekanntes Stuff']);
}

$folder = (string)$s['folder'];
$who = (string)($s['who'] ?? 'layla');
$parent = stuff_parent($config);
$url = lsl_url($who);
$lslOk = false;
$lslDetail = '';

if ($url !== '') {
    $body = http_build_query([
        'token' => $config['token'],
        'folder' => $folder,
        'parent' => $parent,
        'op' => $action,
        'id' => $id,
        'who' => $who,
    ]);
    [$lslOk, $lslDetail] = garderobe_post_lsl($url, $body, (string)$config['token']);
} else {
    $lslDetail = 'kein Skript registriert für ' . $who;
}

$_SESSION['last_stuff'] = time();
$kind = $action === 'add' ? 'STUFF-ADD' : 'STUFF-REMOVE';
notify_action($config, $kind, $folder, $id, $who, $lslOk, $lslDetail);

json_out(200, [
    'ok' => true,
    'id' => $id,
    'who' => $who,
    'folder' => $folder,
    'action' => $action,
    'lsl' => $lslOk,
]);
