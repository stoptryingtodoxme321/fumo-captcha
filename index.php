<?php
declare(strict_types=1);

require __DIR__ . '/views.php';

const BASE_DIR  = __DIR__;
const IMAGE_DIR = __DIR__ . '/images';
const MANIFEST  = __DIR__ . '/images/manifest.json';

const COLS              = 4;
const GRID              = 12;
const MATCHES           = 3;
const MIN_PER_CHARACTER = 3;
const TTL               = 300;
const MAX_FAILS         = 8;

if (GRID % COLS !== 0 || GRID <= MATCHES) {
    fail(500, 'GRID must be a positive multiple of COLS and larger than MATCHES');
}
if (MIN_PER_CHARACTER < MATCHES) {
    fail(500, 'MIN_PER_CHARACTER must be at least MATCHES');
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $secure]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

function manifest(): array
{
    static $cache = null;
    static $cacheMtime = null;
    $mtime = @filemtime(MANIFEST);
    if ($cache !== null && $cacheMtime === $mtime) {
        return $cache;
    }

    $raw = json_decode((string) @file_get_contents(MANIFEST), true);
    if (!is_array($raw) || !is_array($raw['characters'] ?? null)) {
        fail(500, 'images/manifest.json is missing or malformed');
    }

    $characters = [];
    $owner      = [];
    $seen       = [];

    foreach ($raw['characters'] as $id => $spec) {
        $id    = (string) $id;
        $files = array_values(array_filter((array) ($spec['images'] ?? []), 'is_string'));

        foreach ($files as $file) {

            if (!preg_match('~^[\w.\-]+\.(?:jpe?g|png|webp|gif)$~i', $file)
                || isset($seen[$file])
                || !is_file(IMAGE_DIR . '/' . $file)) {
                fail(500, 'bad, duplicate or missing manifest entry: ' . $file);
            }
            $seen[$file]  = true;
            $owner[$file] = $id;
        }

        if (count($files) >= MIN_PER_CHARACTER) {
            $characters[$id] = [
                'label'  => (string) ($spec['label'] ?? $id),
                'images' => $files,
            ];
        }
    }

    if (count($characters) < 2) {
        fail(500, 'need at least 2 characters with ' . MIN_PER_CHARACTER . '+ images each');
    }
    if (count($owner) < GRID) {
        fail(500, 'need at least ' . GRID . ' images in total');
    }

    $cacheMtime = $mtime;
    return $cache = [
        'characters' => $characters,
        'owner'      => $owner,
        'files'      => array_keys($owner),
    ];
}

function pick(array $pool, int $n): array
{
    if ($n < 0 || $n > count($pool)) {
        fail(500, 'not enough images to build a challenge');
    }
    if ($n === 0) {
        return [];
    }
    $keys = array_rand($pool, $n);
    $keys = is_array($keys) ? $keys : [$keys];
    $out  = [];
    foreach ($keys as $key) {
        $out[] = $pool[$key];
    }
    return $out;
}

function newChallenge(): array
{
    $manifest = manifest();
    $id       = (string) array_rand($manifest['characters']);
    $spec     = $manifest['characters'][$id];

    $targets = pick($spec['images'], MATCHES);

    $pool = [];
    foreach ($manifest['owner'] as $file => $character) {
        if ($character !== $id) {
            $pool[] = $file;
        }
    }

    $tiles = array_merge($targets, pick($pool, GRID - MATCHES));
    shuffle($tiles);

    $challenge = [
        'cid'     => bin2hex(random_bytes(12)),
        'char'    => $id,
        'label'   => $spec['label'],
        'targets' => $targets,
        'tiles'   => $tiles,
        'issued'  => time(),
    ];

    $_SESSION['ch'] = $challenge;

    return $challenge;
}

function publicChallenge(array $challenge): array
{
    return [
        'cid'   => $challenge['cid'],
        'label' => $challenge['label'],
        'tiles' => array_values($challenge['tiles']),
    ];
}

function pageCaptcha(?string $error = null): void
{
    $c = newChallenge();

    echo render('captcha', [
        'label' => e($c['label']),
        'cid'   => e($c['cid']),
        'tiles' => $c['tiles'],
        'cols'  => COLS,
        'error' => $error ? e($error) : '',
    ]);
    exit;
}

function pageApi(): void
{
    if (($_SESSION['fails'] ?? 0) > MAX_FAILS) {
        json(['ok' => false, 'error' => 'Too many attempts. Reload the page to start over.'], 429);
    }
    json(publicChallenge(newChallenge()));
}

function pageCheck(): void
{
    $challenge = $_SESSION['ch'] ?? null;
    unset($_SESSION['ch']);

    if ($challenge && time() - $challenge['issued'] <= TTL) {
        $picks = $_POST['pick'] ?? [];
        $picks = is_array($picks) ? array_values(array_unique($picks)) : [];

        $want = $challenge['targets'];
        sort($picks);
        sort($want);



        if ($picks !== [] && hash_equals(serialize($want), serialize($picks))) {
            session_regenerate_id(true);
            $_SESSION = ['ok' => true];

            if (wantsJson()) {
                json(['ok' => true, 'redirect' => '/']);
            }
            header('Location: /', true, 303);
            exit;
        }
    }

    $fails = (int) ($_SESSION['fails'] ?? 0) + 1;
    $_SESSION['fails'] = $fails;

    if ($fails > MAX_FAILS) {
        $msg = 'Too many attempts. Reload the page to start over.';
        if (wantsJson()) {
            json(['ok' => false, 'error' => $msg], 429);
        }
        fail(429, $msg);
    }

    $error = 'Not quite. Here is a new set.';
    if ($challenge === null) {
        $error = 'That challenge expired. Here is a new one.';
    } elseif (time() - $challenge['issued'] > TTL) {
        $error = 'That challenge timed out. Here is a new one.';
    }

    if (wantsJson()) {
        json(['ok' => false, 'error' => $error] + publicChallenge(newChallenge()));
    }
    pageCaptcha($error);
}

function pageHome(): void
{
    if (empty($_SESSION['ok'])) {
        header('Location: /captcha', true, 302);
        exit;
    }
    echo render('home', []);
    exit;
}

function pageUnlock(): void
{
    session_regenerate_id(true);
    $_SESSION = [];
    header('Location: /captcha', true, 303);
    exit;
}

function pageImage(string $path): void
{
    $challenge = $_SESSION['ch'] ?? null;

    if (!$challenge
        || !preg_match('~^/img/([0-9a-f]{24})/([\w.\-]+)$~', $path, $m)
        || !hash_equals((string) $challenge['cid'], $m[1])
        || !in_array($m[2], $challenge['tiles'] ?? [], true)) {
        fail(404, 'not found');
    }

    $real = realpath(IMAGE_DIR . '/' . $m[2]);
    if ($real === false
        || realpath(IMAGE_DIR) !== dirname($real)
        || !is_file($real)) {
        fail(404, 'not found');
    }

    static $types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',  'webp' => 'image/webp', 'gif' => 'image/gif',
    ];
    $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . (string) filesize($real));
    header('Cache-Control: private, max-age=300');
    readfile($real);
    exit;
}

function wantsJson(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'json');
}

function json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function routeCaptchaPage(): void
{
    if (($_SESSION['fails'] ?? 0) > MAX_FAILS) {
        unset($_SESSION['fails']);
    }
    pageCaptcha();
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

match (true) {
    $path === '/'              => pageHome(),
    $path === '/captcha'       => routeCaptchaPage(),
    $path === '/captcha/api'   => pageApi(),
    $path === '/captcha/check' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
                                     ? pageCheck()
                                     : fail(405, 'method not allowed'),
    $path === '/unlock'        => pageUnlock(),
    str_starts_with($path, '/img/') => pageImage($path),
    default                    => fail(404, 'not found'),
};