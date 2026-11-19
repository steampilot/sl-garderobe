<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false, 'error' => 'POST nötig']);
}

$last = (int)($_SESSION['last_sit'] ?? 0);
if (time() - $last < 4) {
    json_out(429, ['ok' => false, 'error' => 'einen Moment, der letzte Sit-Befehl läuft noch']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) $data = $_POST;
$action = strtolower(preg_replace('/[^a-z]/', '', (string)($data['action'] ?? 'sit')));
if ($action !== 'sit' && $action !== 'unsit') {
    json_out(400, ['ok' => false, 'error' => 'action muss sit oder unsit sein']);
}

$who = preg_replace('/[^a-z0-9\-]/', '', (string)($data['who'] ?? 'layla'));
if ($who === '') $who = 'layla';

$url = lsl_url($who);
$lslOk = false;
$lslDetail = '';

if ($url !== '') {
    $body = http_build_query([
        'token' => $config['token'],
        'op' => $action,
        'who' => $who,
    ]);
    [$lslOk, $lslDetail] = garderobe_post_lsl($url, $body, (string)$config['token']);
} else {
    $lslDetail = 'kein Skript registriert für ' . $who;
}

$_SESSION['last_sit'] = time();
notify_action($config, strtoupper($action), $action, $action, $who, $lslOk, $lslDetail);

json_out(200, [
    'ok' => true,
    'action' => $action,
    'who' => $who,
    'lsl' => $lslOk,
]);
