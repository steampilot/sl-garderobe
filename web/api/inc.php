<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

function data_path(string $name): string {
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    return $dir . '/' . $name;
}

function start_session(): void {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('garderobe');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function json_out(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function people_ids(array $config): array {
    $ids = [];
    foreach ($config['people'] ?? [] as $p) {
        $ids[] = $p['id'];
    }
    return $ids;
}

function public_people(array $config): array {
    $out = [];
    foreach ($config['people'] ?? [] as $p) {
        $row = ['id' => $p['id'], 'name' => $p['name']];
        if (!empty($p['img'])) $row['img'] = $p['img'];
        $out[] = $row;
    }
    return $out;
}

function outfit_by_id(array $config, string $id): ?array {
    foreach ($config['outfits'] as $o) {
        if ($o['id'] === $id) return $o;
    }
    return null;
}

function public_outfits(array $config): array {
    $out = [];
    foreach ($config['outfits'] as $o) {
        $out[] = [
            'id' => $o['id'],
            'who' => $o['who'],
            'name' => $o['name'],
            'folder' => $o['folder'],
            'desc' => $o['desc'],
            'wear' => !empty($o['wear']),
        ];
    }
    return $out;
}

function lsl_url(?string $who = null): string {
    if ($who) {
        $f = data_path('lsl_url_' . $who . '.txt');
        if (is_file($f)) {
            $u = trim((string)file_get_contents($f));
            if ($u !== '') return $u;
        }
        // ältere Einzel-Datei, nur für Layla
        if ($who === 'layla') {
            $f = data_path('lsl_url.txt');
            if (is_file($f)) return trim((string)file_get_contents($f));
        }
        return '';
    }
    $f = data_path('lsl_url.txt');
    if (!is_file($f)) return '';
    return trim((string)file_get_contents($f));
}

function valid_lsl_url(string $url): bool {
    if (strlen($url) < 12 || strlen($url) > 500) return false;
    if (!preg_match('#^https?://[^\s<>"\']+$#i', $url)) return false;
    return true;
}

function notify_layla(array $config, string $folder, string $id, string $who, bool $lslOk, string $lslDetail): void {
    $to = $config['notify_email'] ?? '';
    if ($to === '') return;
    $when = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d H:i:s T');
    $body = "Garderobe WEAR\n"
        . "Avatar: {$who}\n"
        . "Outfit: {$folder} ({$id})\n"
        . "Zeit: {$when}\n"
        . "LSL: " . ($lslOk ? "Befehl zugestellt" : "nicht verbunden") . "\n"
        . ($lslDetail !== '' ? "Detail: {$lslDetail}\n" : '');
    $from = $config['mail_from'] ?? 'garderobe@steampilot.ch';
    $headers = "From: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Garderobe: wear\r\n";
    @mail($to, "Garderobe WEAR: {$who} / {$folder}", $body, $headers);
    $line = $when . "\t" . $who . "\t" . $id . "\t" . $folder . "\t" . ($lslOk ? 'lsl' : 'no-lsl') . "\t" . $lslDetail . "\n";
    @file_put_contents(data_path('wear.log'), $line, FILE_APPEND | LOCK_EX);
}

function garderobe_post_lsl(string $url, string $body, string $token): array {
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'X-Garderobe-Token: ' . $token,
    ];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) return [false, $err ?: 'keine Antwort'];
        if ($code >= 200 && $code < 300) return [true, substr((string)$resp, 0, 200)];
        return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => 12,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\\s(\\d{3})\\s/', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    }
    if ($resp === false && $code === 0) return [false, 'keine Antwort'];
    if ($code >= 200 && $code < 300) return [true, substr((string)$resp, 0, 200)];
    return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
}
