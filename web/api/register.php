<?php
declare(strict_types=1);
require __DIR__ . '/inc.php';

// Called by the worn LSL script whenever its HTTP-in URL is (re)granted.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(405, ['ok' => false, 'error' => 'POST nötig']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = [];
    parse_str($raw, $parsed);
    if (is_array($parsed)) $data = $parsed;
}
if (!$data) $data = $_POST;

$token = (string)($data['token'] ?? $_SERVER['HTTP_X_GARDEROBE_TOKEN'] ?? '');
$url = trim((string)($data['url'] ?? ''));
$who = strtolower(preg_replace('/[^a-z]/', '', (string)($data['who'] ?? '')));

if ($token === '' || !hash_equals((string)$config['token'], $token)) {
    json_out(403, ['ok' => false, 'error' => 'token']);
}
if (!valid_lsl_url($url)) {
    json_out(400, ['ok' => false, 'error' => 'url']);
}

$allowed = people_ids($config);
// ältere Skripte ohne who gehören zu Layla
if ($who === '') $who = 'layla';
if (!in_array($who, $allowed, true)) {
    json_out(400, ['ok' => false, 'error' => 'who']);
}

file_put_contents(data_path('lsl_url_' . $who . '.txt'), $url, LOCK_EX);
file_put_contents(
    data_path('lsl_registered.log'),
    (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('c') . "	" . $who . "	" . $url . "
",
    FILE_APPEND | LOCK_EX
);
json_out(200, ['ok' => true, 'who' => $who]);
