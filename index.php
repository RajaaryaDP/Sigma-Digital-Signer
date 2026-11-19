<?php

session_start();

/**
 * Sanitasi rekursif input eksternal dari byte NUL (\0) dan urutan byte non-UTF-8
 * agar PostgreSQL (ENCODING 'UTF8') tidak melempar SQLSTATE[22021].
 */
function sigma_sanitize_utf8($value)
{
    if (is_array($value)) {
        $cleaned = [];
        foreach ($value as $k => $v) {
            $cleanKey = is_string($k) ? str_replace("\0", '', mb_scrub($k, 'UTF-8')) : $k;
            $cleaned[$cleanKey] = sigma_sanitize_utf8($v);
        }
        return $cleaned;
    }
    if (is_string($value)) {
        return str_replace("\0", '', mb_scrub($value, 'UTF-8'));
    }
    return $value;
}

$_GET    = sigma_sanitize_utf8($_GET);
$_POST   = sigma_sanitize_utf8($_POST);
$_COOKIE = sigma_sanitize_utf8($_COOKIE);

// Normalisasi $_GET agar selalu bernilai string skalar (mencegah Array Injection TypeError pada trim/filter)
foreach ($_GET as $gk => $gv) {
    if (!is_string($gv)) {
        $_GET[$gk] = '';
    }
}

// Sanitasi nama berkas unggahan pada $_FILES dari urutan byte non-UTF-8 dan byte NUL
if (!empty($_FILES) && is_array($_FILES)) {
    foreach ($_FILES as $fk => $fv) {
        if (isset($fv['name'])) {
            $_FILES[$fk]['name'] = sigma_sanitize_utf8($fv['name']);
        }
    }
}

if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = sigma_sanitize_utf8($_SERVER['REQUEST_URI']);
}
if (isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = sigma_sanitize_utf8($_SERVER['HTTP_USER_AGENT']);
}

// Catch uncaught exceptions and render safe 500 error page without leaking internal paths/SQL
set_exception_handler(function (Throwable $exception) {
    error_log(sprintf(
        '[SIGMA Uncaught %s] %s in %s:%d',
        get_class($exception),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (file_exists(__DIR__ . '/views/errors/crash.php')) {
        require __DIR__ . '/views/errors/crash.php';
    } else {
        echo '<h1>500 - Kesalahan Internal Server</h1>';
        echo '<p>Terjadi kendala teknis saat memproses permintaan Anda. Silakan coba beberapa saat lagi.</p>';
    }
});

// Built-in CLI server static file handler
if (PHP_SAPI === 'cli-server') {
    $uriPath    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $normalized = '/' . trim(str_replace('\\', '/', $uriPath), '/');

    // Proteksi direktori privat saat memakai built-in server (php -S):
    // berkas di /storage dkk. tidak boleh disajikan statis karena
    // .htaccess "Require all denied" tidak berlaku pada CLI server (ADR-04).
    $blockedPrefixes = ['/storage', '/config', '/controllers', '/models', '/views', '/core', '/tools'];
    $isBlocked = false;
    foreach ($blockedPrefixes as $prefix) {
        if ($normalized === $prefix || strpos($normalized, $prefix . '/') === 0) {
            $isBlocked = true;
            break;
        }
    }

    if (!$isBlocked) {
        $requestedFile = __DIR__ . $uriPath;
        if (is_file($requestedFile)) {
            return false;
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Loader.php';

// URL Parser
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base     = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$path     = trim(substr($uri, strlen($base)), '/');
$segments = explode('/', $path);

define('BASE_URL', $base === '' ? '' : '/' . $base);

$noIdActions = ['create', 'store', 'login', 'logout', 'register', 'import', 'template', 'captcha', 'guide'];

// Routing alias & default controller
$rawPage = strtolower($segments[0] ?? '');
if ($rawPage === '' || $rawPage === 'home' || $rawPage === 'landing' || $rawPage === 'guide' || $rawPage === 'panduan') {
    $page = 'home';
    if ($rawPage === 'guide' || $rawPage === 'panduan') {
        $segments[1] = 'guide';
    }
} elseif ($rawPage === 'login') {
    $page = 'auth';
} elseif ($rawPage === 'register') {
    $page = 'auth';
    $segments[1] = 'register';
} elseif ($rawPage === 'logout') {
    $page = 'auth';
    $segments[1] = 'logout';
} else {
    $page = $rawPage;
}

// Public endpoints that do not require authentication
$publicPages = ['home', 'auth', 'verify'];

// Global Auth Guard: Redirect unauthenticated user to login
if (!isset($_SESSION['user']) && !in_array($page, $publicPages, true)) {
    header('Location: ' . BASE_URL . '/auth');
    exit;
}

// Prevent logged-in user from visiting login page unless logging out
if (isset($_SESSION['user']) && $page === 'auth' && ($segments[1] ?? '') !== 'logout') {
    header('Location: ' . BASE_URL . '/documents');
    exit;
}

// Role Guard: Restrict admin-only routes
$adminOnlyPages = ['civitas', 'departments', 'prodi'];
if (isset($_SESSION['user']) && in_array($page, $adminOnlyPages, true)) {
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Akses ditolak: Modul ini hanya dapat diakses oleh Administrator.'
        ];
        header('Location: ' . BASE_URL . '/documents');
        exit;
    }
}

// Determine action and id parameters
if (!isset($segments[1]) || $segments[1] === '') {
    $id     = null;
    $action = ($page === 'auth') ? 'login' : 'index';
} elseif (in_array($segments[1], $noIdActions, true)) {
    $id     = null;
    $action = $segments[1];
} else {
    $id     = $segments[1];
    $action = $segments[2] ?? 'detail';
}

$method = $_SERVER['REQUEST_METHOD'];

// Actions requiring POST method
$postOnlyActions = ['store', 'update', 'delete', 'approve', 'reject', 'decline', 'withdraw'];
$expectsPost     = in_array($action, $postOnlyActions, true);

$controllerName = str_replace('-', '', ucwords($page, '-'));
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

function sigma_render_404(string $subtitle): void
{
    if (!headers_sent()) {
        http_response_code(404);
    }
    $backHref = isset($_SESSION['user']) ? BASE_URL . '/documents' : BASE_URL . '/';
    $backLabel = isset($_SESSION['user']) ? 'Kembali ke Dashboard' : 'Kembali ke Beranda';
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>404 - Halaman Tidak Ditemukan — SIGMA</title></head>';
    echo '<body style="margin:0;font-family:Inter,Segoe UI,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8f8fc;padding:1.5rem;">';
    echo '<div style="background:#fff;border:1px solid #e6e2f0;border-radius:16px;padding:2rem 2.25rem;max-width:460px;width:100%;text-align:center;box-shadow:0 4px 14px rgba(45,43,75,0.08);">';
    echo '<div style="display:inline-flex;align-items:center;justify-content:center;padding:0.25rem 0.75rem;border-radius:999px;background:#f0ecf5;color:#77638f;font-size:0.75rem;font-weight:700;margin-bottom:0.75rem;">HTTP 404</div>';
    echo '<h2 style="margin:0 0 0.5rem;color:#2d2b4b;">404 - Halaman Tidak Ditemukan</h2>';
    echo '<p style="color:#69647d;font-size:0.9rem;margin-bottom:1.25rem;">' . $subtitle . '</p>';
    echo '<a href="' . htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:0.55rem 1.25rem;background:#2d2b4b;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:0.88rem;">' . $backLabel . '</a>';
    echo '</div></body></html>';
    exit;
}

if (!file_exists($controllerFile)) {
    sigma_render_404('Halaman atau modul <code>' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '</code> belum tersedia.');
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    sigma_render_404('Kelas controller tidak ditemukan.');
}

// Pengecualian metode ganda (GET + POST):
//  - Auth::login      → menampilkan form (GET) & memproses kredensial (POST)
//  - Auth::register   → menampilkan form registrasi (GET) & memproses pendaftaran (POST)
//  - Civitas::import  → menampilkan form/pratinjau CSV (GET) & memproses pratinjau/commit CSV (POST)
//  - Documents::sign  → menampilkan halaman penandatanganan (GET) & menyimpan tanda tangan (POST)
$isAuthAction    = ($controllerName === 'Auth' && in_array($action, ['login', 'register'], true));
$isCivitasImport = ($controllerName === 'Civitas' && $action === 'import');
$isDocSign       = ($controllerName === 'Documents' && $action === 'sign');

if (!$isAuthAction && !$isCivitasImport && !$isDocSign) {
    if (($expectsPost && $method !== 'POST') || (!$expectsPost && $method !== 'GET')) {
        sigma_render_404('Metode permintaan HTTP tidak diizinkan untuk aksi ini.');
    }
}

$controller = new $controllerName();

if (!method_exists($controller, $action)) {
    sigma_render_404('Aksi atau rute yang diminta tidak tersedia.');
}

if ($id === null) {
    $controller->$action();
} else {
    $controller->$action($id);
}
