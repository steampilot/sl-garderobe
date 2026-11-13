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

function person_img_dir(array $config, string $who): string {
    foreach ($config['people'] ?? [] as $p) {
        if (($p['id'] ?? '') === $who && !empty($p['img'])) {
            return (string)$p['img'];
        }
    }
    // Weitere Avatare später wieder ergänzen (jerome => Steampilot, cindy => Cindy, caprica => Caprica).
    $fallback = ['layla' => 'Layla'];
    return $fallback[$who] ?? $who;
}

// Relativer Pfad ab web/, z. B. img/Layla/western.png. Prüft die echte Datei (webp/png/jpg).
function outfit_image_url(array $config, array $o): string {
    $folder = (string)($o['folder'] ?? '');
    $imgDir = person_img_dir($config, (string)($o['who'] ?? ''));
    $webRoot = dirname(__DIR__);
    $candidates = [];
    if (!empty($o['image'])) {
        $img = (string)$o['image'];
        if (str_contains($img, '/')) {
            $rel = ltrim($img, '/');
            if (!str_starts_with($rel, 'img/')) {
                $rel = 'img/' . $rel;
            }
            $candidates[] = $rel;
        } else {
            $candidates[] = 'img/' . $imgDir . '/' . $img;
        }
    }
    foreach (['.webp', '.png', '.jpg', '.jpeg'] as $ext) {
        if ($folder !== '') {
            $candidates[] = 'img/' . $imgDir . '/' . $folder . $ext;
        }
    }
    foreach (array_unique($candidates) as $rel) {
        if (is_file($webRoot . '/' . $rel)) {
            return $rel;
        }
    }
    return 'img/' . $imgDir . '/' . $folder . '.webp';
}

function public_outfits(array $config): array {
    $out = [];
    foreach ($config['outfits'] as $o) {
        $folder = (string)$o['folder'];
        $out[] = [
            'id' => $o['id'],
            'who' => $o['who'],
            'name' => $o['name'],
            'folder' => $folder,
            'image' => outfit_image_url($config, $o),
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

function garderobe_post_lsl(string $url, string $body, string $token, int $maxLen = 200, int $timeout = 12): array {
    if ($maxLen < 80) $maxLen = 80;
    if ($timeout < 2) $timeout = 2;
    if ($timeout > 25) $timeout = 25;
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
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) return [false, $err ?: 'keine Antwort'];
        if ($code >= 200 && $code < 300) return [true, substr((string)$resp, 0, $maxLen)];
        return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\\s(\\d{3})\\s/', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    }
    if ($resp === false && $code === 0) return [false, 'keine Antwort'];
    if ($code >= 200 && $code < 300) return [true, substr((string)$resp, 0, $maxLen)];
    return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
}


function stuff_parent(array $config): string {
    $p = trim((string)($config['stuff_parent'] ?? 'Stuff'));
    return $p !== '' ? $p : 'Stuff';
}

function stuff_by_id(array $config, string $id): ?array {
    foreach ($config['stuff'] ?? [] as $s) {
        if (($s['id'] ?? '') === $id) return $s;
    }
    return null;
}

function stuff_image_url(array $config, array $s): string {
    $folder = (string)($s['folder'] ?? '');
    $imgDir = person_img_dir($config, (string)($s['who'] ?? 'layla'));
    $webRoot = dirname(__DIR__);
    $candidates = [];
    if (!empty($s['image'])) {
        $img = (string)$s['image'];
        if (str_contains($img, '/')) {
            $rel = ltrim($img, '/');
            if (!str_starts_with($rel, 'img/')) $rel = 'img/' . $rel;
            $candidates[] = $rel;
        } else {
            $candidates[] = 'img/' . $imgDir . '/' . $img;
        }
    }
    foreach (['.webp', '.png', '.jpg', '.jpeg'] as $ext) {
        if ($folder !== '') {
            $candidates[] = 'img/' . $imgDir . '/stuff/' . $folder . $ext;
            $candidates[] = 'img/' . $imgDir . '/' . $folder . $ext;
        }
    }
    foreach (array_unique($candidates) as $rel) {
        if (is_file($webRoot . '/' . $rel)) return $rel;
    }
    return '';
}

function public_stuff(array $config): array {
    $out = [];
    foreach ($config['stuff'] ?? [] as $s) {
        $folder = (string)($s['folder'] ?? '');
        $image = stuff_image_url($config, $s);
        $row = [
            'id' => $s['id'],
            'who' => (string)($s['who'] ?? 'layla'),
            'name' => $s['name'],
            'folder' => $folder,
            'desc' => (string)($s['desc'] ?? ''),
        ];
        if ($image !== '') $row['image'] = $image;
        $out[] = $row;
    }
    return $out;
}

function notify_action(array $config, string $kind, string $folder, string $id, string $who, bool $lslOk, string $lslDetail): void {
    $to = $config['notify_email'] ?? '';
    if ($to === '') return;
    $when = (new DateTimeImmutable('now', new DateTimeZone('Europe/Zurich')))->format('Y-m-d H:i:s T');
    $body = "Garderobe {$kind}\n"
        . "Avatar: {$who}\n"
        . "Ordner: {$folder} ({$id})\n"
        . "Zeit: {$when}\n"
        . "LSL: " . ($lslOk ? "Befehl zugestellt" : "nicht verbunden") . "\n"
        . ($lslDetail !== '' ? "Detail: {$lslDetail}\n" : '');
    $from = $config['mail_from'] ?? 'garderobe@steampilot.ch';
    $headers = "From: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Garderobe: " . strtolower($kind) . "\r\n";
    @mail($to, "Garderobe {$kind}: {$who} / {$folder}", $body, $headers);
    $line = $when . "\t" . $who . "\t" . $kind . "\t" . $id . "\t" . $folder . "\t" . ($lslOk ? 'lsl' : 'no-lsl') . "\t" . $lslDetail . "\n";
    @file_put_contents(data_path('wear.log'), $line, FILE_APPEND | LOCK_EX);
}

// Sendet einen einzelnen Textbefehl an slbot (POST auf bot_url). Nur von internen Endpunkten aufrufen.
// Gibt [ok, body] zurück. Timeout und Verbindungsfehler werden als [false, grund] gemeldet.
function bot_command(array $config, string $cmd, int $timeout = 3): array {
    $url = trim((string)($config['bot_url'] ?? ''));
    if ($url === '') return [false, 'kein bot_url'];
    if ($timeout < 1) $timeout = 1;
    if ($timeout > 15) $timeout = 15;
    $connect = min(2, $timeout);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cmd,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connect,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: text/plain; charset=utf-8'],
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false) return [false, $err !== '' ? $err : 'keine Antwort'];
        if ($code >= 200 && $code < 300) return [true, (string)$resp];
        return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: text/plain; charset=utf-8\r\n",
            'content' => $cmd,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\\s(\\d{3})\\s/', $http_response_header[0], $m)) {
        $code = (int)$m[1];
    }
    if ($resp === false && $code === 0) return [false, 'keine Antwort'];
    if ($code >= 200 && $code < 300) return [true, (string)$resp];
    return [false, 'HTTP ' . $code . ' ' . substr((string)$resp, 0, 160)];
}

function parse_bot_status(string $body): array {
    $body = trim($body);
    $out = [
        'running' => true,
        'online' => false,
        'name' => '',
        'region' => '',
        'text' => 'nicht verbunden',
    ];
    if ($body === '' || preg_match('/^offline\\b/i', $body)) {
        $out['text'] = 'Client läuft, nicht verbunden';
        return $out;
    }
    if (preg_match('/^name:\\s*(.+)$/mi', $body, $m)) {
        $out['name'] = trim($m[1]);
    }
    if (preg_match('/^region:\\s*(\\S+)/mi', $body, $m)) {
        $out['region'] = trim($m[1]);
    }
    if ($out['region'] !== '' || $out['name'] !== '') {
        $out['online'] = true;
        $out['text'] = $out['region'] !== '' ? $out['region'] : $out['name'];
    }
    return $out;
}

function parse_bot_worn(string $body): array {
    $outfits = [];
    $stuff = [];
    $body = trim($body);
    if ($body === '' || preg_match('/^offline\\b/i', $body) || stripos($body, 'unknown command') !== false) {
        return ['outfits' => $outfits, 'stuff' => $stuff, 'available' => stripos($body, 'unknown command') === false && $body !== '' && !preg_match('/^offline\\b/i', $body)];
    }
    if (preg_match('/^worn_outfits:\\s*(.+)$/mi', $body, $m)) {
        $outfits = parse_worn_list($m[1]);
    }
    if (preg_match('/^worn_stuff:\\s*(.+)$/mi', $body, $m)) {
        $stuff = parse_worn_list($m[1]);
    }
    return ['outfits' => $outfits, 'stuff' => $stuff, 'available' => true];
}

function parse_worn_list(string $raw): array {
    $raw = trim($raw);
    if ($raw === '' || $raw === '(none)') return [];
    $ids = [];
    foreach (preg_split('/\\s*,\\s*/', $raw) as $part) {
        $part = trim($part);
        if ($part !== '') $ids[] = $part;
    }
    return $ids;
}


function parse_lsl_ping(string $raw): ?array {
    $raw = trim($raw);
    if ($raw === '') return null;
    $start = strpos($raw, '{');
    $end = strrpos($raw, '}');
    if ($start === false || $end === false || $end <= $start) return null;
    $data = json_decode(substr($raw, $start, $end - $start + 1), true);
    if (!is_array($data) || empty($data['ok'])) return null;
    $outfits = [];
    $stuff = [];
    if (isset($data['outfits']) && is_array($data['outfits'])) {
        foreach ($data['outfits'] as $n) {
            $n = trim((string)$n);
            if ($n !== '') $outfits[] = $n;
        }
    }
    if (isset($data['stuff']) && is_array($data['stuff'])) {
        foreach ($data['stuff'] as $n) {
            $n = trim((string)$n);
            if ($n !== '') $stuff[] = $n;
        }
    }
    return [
        'region' => trim((string)($data['region'] ?? '')),
        'outfits' => $outfits,
        'stuff' => $stuff,
        'worn_available' => array_key_exists('outfits', $data) || array_key_exists('stuff', $data),
    ];
}

function maybe_mark_naked(array $config, array $wornOutfits, bool $wornAvailable): array {
    if (!$wornAvailable || $wornOutfits) return $wornOutfits;
    foreach ($config['outfits'] ?? [] as $o) {
        if ((string)($o['folder'] ?? '') === 'naked') return ['naked'];
    }
    return $wornOutfits;
}
