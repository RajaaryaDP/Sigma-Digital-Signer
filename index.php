<?php

// Hardening cookie sesi (HttpOnly, SameSite=Lax, Strict Mode, Cookies-Only — VULN-04)
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$sigmaIsHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $sigmaIsHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

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

// Built-in CLI server static file handler (Allowlist ketat: hanya berkas statis non-PHP di /assets/ — ADR-04, VULN-03)
if (PHP_SAPI === 'cli-server') {
    $rawReqUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($rawReqUri === '' || $rawReqUri[0] !== '/') {
        $parsedPath = parse_url($rawReqUri, PHP_URL_PATH);
        if (!is_string($parsedPath)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('400 Bad Request');
        }
    } else {
        $qPos       = strpos($rawReqUri, '?');
        $parsedPath = ($qPos === false) ? $rawReqUri : substr($rawReqUri, 0, $qPos);
    }

    $decodedPath    = rawurldecode($parsedPath);
    $normalizedPath = '/' . ltrim(str_replace('\\', '/', $decodedPath), '/');

    // 1. Tolak langsung (403) jalur traversal, dotfile, direktori internal, dan berkas sensitif
    //    (case-insensitive agar aman pada NTFS Windows sekaligus selaras dengan aturan .htaccess Apache)
    $hasTraversalOrHidden = (
        strpos($decodedPath, "\0") !== false
        || strpos($parsedPath, '..') !== false
        || strpos($normalizedPath, '..') !== false
        || strpos($decodedPath, ':') !== false
        || preg_match('#(^|/)\.#', $normalizedPath) === 1
    );

    $isBlockedTarget = (
        preg_match('#^/(tools|core|database|docs|config|controllers|models|views|storage|vendor|scratch)(/|$)#i', $normalizedPath) === 1
        || preg_match('#^/(composer\.(json|lock)|readme\.md|dokumentasi_uts\.md|agents\.md|claude\.md)$#i', $normalizedPath) === 1
        || preg_match('#\.(sql|md|log|bak|env|ini|htaccess|htpasswd)$#i', $normalizedPath) === 1
    );

    if ($hasTraversalOrHidden || $isBlockedTarget) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('403 Forbidden');
    }

    // 2. Allowlist berkas statis publik: HANYA berkas non-PHP dengan ekstensi aman di dalam /assets/ (khusus GET/HEAD)
    if (
        in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)
        && preg_match('#^/assets/.+#i', $normalizedPath) === 1
    ) {
        if (preg_match('#\.ph(p[0-9]?|tml|ar)$#i', $normalizedPath) === 1) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('403 Forbidden');
        }

        $assetsRoot      = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'assets');
        $isCleanAssetUri = (
            strpos($parsedPath, '\\') === false
            && strpos($decodedPath, '\\') === false
            && strpos($parsedPath, '//') === false
            && !preg_match('#[\s.]$#', $normalizedPath)
        );

        if ($assetsRoot !== false && $isCleanAssetUri) {
            $candidate     = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
            $realCandidate = realpath($candidate);

            if ($realCandidate !== false && is_file($realCandidate)) {
                $assetsPrefix = rtrim($assetsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $cmpRoot      = (DIRECTORY_SEPARATOR === '\\') ? strtolower($assetsPrefix) : $assetsPrefix;
                $cmpFile      = (DIRECTORY_SEPARATOR === '\\') ? strtolower($realCandidate) : $realCandidate;

                $ext = strtolower(pathinfo($realCandidate, PATHINFO_EXTENSION));
                $allowedAssetExts = [
                    'css', 'js', 'mjs', 'map',
                    'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico',
                    'woff', 'woff2', 'ttf', 'otf', 'eot',
                ];

                if (strpos($cmpFile, $cmpRoot) === 0 && in_array($ext, $allowedAssetExts, true)) {
                    return false;
                }
            }
        }
    }
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Loader.php';
require_once __DIR__ . '/core/Csrf.php';

// URL Parser
$parsedUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri       = is_string($parsedUri) ? $parsedUri : '/';
$base      = (PHP_SAPI === 'cli-server')
    ? ''
    : trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$path      = trim(substr($uri, strlen($base)), '/');
$segments  = explode('/', $path);

define('BASE_URL', $base === '' ? '' : '/' . $base);

$noIdActions = ['create', 'store', 'login', 'logout', 'register', 'import', 'template', 'captcha', 'guide', 'verifikasi'];

// Routing alias & default controller
$rawPage = strtolower($segments[0] ?? '');
if ($rawPage === '' || $rawPage === 'home' || $rawPage === 'landing' || $rawPage === 'guide' || $rawPage === 'panduan' || $rawPage === 'verifikasi') {
    $page = 'home';
    if ($rawPage === 'guide' || $rawPage === 'panduan') {
        $segments[1] = 'guide';
    } elseif ($rawPage === 'verifikasi') {
        $segments[1] = 'verifikasi';
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

// Validasi keaktifan akun & civitas pada sesi berjalan + sinkronisasi profil (VULN-06)
$isLogoutRequest = ($page === 'auth' && ($segments[1] ?? '') === 'logout');
if (isset($_SESSION['user']['id']) && !$isLogoutRequest) {
    require_once __DIR__ . '/models/UsersModel.php';
    $sigmaSessionUser = (new UsersModel())->getById((string) $_SESSION['user']['id']);

    if (!UsersModel::isEligibleForActiveSession($sigmaSessionUser)) {
        $_SESSION = [];
        session_destroy();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        Csrf::rotate();

        if (!in_array($page, $publicPages, true)) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Sesi Anda telah dihentikan karena akun atau data civitas Anda sudah tidak aktif.'
            ];
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }
    } else {
        // Segarkan profil sesi agar perubahan nama/email/jabatan di Master Civitas langsung terlihat
        $_SESSION['user'] = [
            'id'           => $sigmaSessionUser['id'],
            'civitas_id'   => $sigmaSessionUser['civitas_id'],
            'name'         => $sigmaSessionUser['name'],
            'email'        => $sigmaSessionUser['email'],
            'role'         => $sigmaSessionUser['role'],
            'avatar_path'  => $sigmaSessionUser['avatar_path'],
            'identifier'   => $sigmaSessionUser['civitas_identifier'] ?? null,
            'civitas_name' => $sigmaSessionUser['civitas_name'] ?? $sigmaSessionUser['name'],
            'civitas_type' => $sigmaSessionUser['civitas_type'] ?? null,
            'department'   => $sigmaSessionUser['civitas_department'] ?? null,
            'position'     => $sigmaSessionUser['civitas_position'] ?? null,
            'phone'        => $sigmaSessionUser['civitas_phone'] ?? null,
        ];
    }
}

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

// Daftar putih (allowlist) eksplisit modul resmi SIGMA (VULN-09)
// Modul arsip contoh skeleton UTS (/accounts, /account-types, /actions) tidak terdaftar
// sehingga otomatis mengembalikan HTTP 404 tanpa mengubah/menghapus berkas arsipnya.
$allowedRoutes = [
    'home'               => 'Home',
    'auth'               => 'Auth',
    'verify'             => 'Verify',
    'documents'          => 'Documents',
    'signatures'         => 'Signatures',
    'user-signatures'    => 'UserSignatures',
    'document-templates' => 'DocumentTemplates',
    'civitas'            => 'Civitas',
    'departments'        => 'Departments',
    'prodi'              => 'Prodi',
];

$controllerName = $allowedRoutes[$page] ?? null;
$controllerFile = $controllerName !== null
    ? (__DIR__ . '/controllers/' . $controllerName . '.php')
    : null;

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

function sigma_render_403(
    string $subtitle,
    int $statusCode = 403,
    string $heading = '403 - Permintaan Ditolak'
): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }
    $backHref  = isset($_SESSION['user']) ? BASE_URL . '/documents' : BASE_URL . '/auth';
    $backLabel = isset($_SESSION['user']) ? 'Kembali ke Dashboard' : 'Kembali ke Halaman Masuk';
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . ' — SIGMA</title></head>';
    echo '<body style="margin:0;font-family:Inter,Segoe UI,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8f8fc;padding:1.5rem;">';
    echo '<div style="background:#fff;border:1px solid #fecaca;border-radius:16px;padding:2rem 2.25rem;max-width:480px;width:100%;text-align:center;box-shadow:0 4px 14px rgba(45,43,75,0.08);">';
    echo '<div style="display:inline-flex;align-items:center;justify-content:center;padding:0.25rem 0.75rem;border-radius:999px;background:#fef2f2;color:#b91c1c;font-size:0.75rem;font-weight:700;margin-bottom:0.75rem;">HTTP ' . (int) $statusCode . '</div>';
    echo '<h2 style="margin:0 0 0.5rem;color:#2d2b4b;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p style="color:#69647d;font-size:0.9rem;margin-bottom:1.25rem;">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<a href="' . htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:0.55rem 1.25rem;background:#2d2b4b;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;font-size:0.88rem;">' . $backLabel . '</a>';
    echo '</div></body></html>';
    exit;
}

if ($controllerName === null || $controllerFile === null || !file_exists($controllerFile)) {
    sigma_render_404('Halaman atau modul <code>' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '</code> tidak tersedia.');
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

if ($isAuthAction || $isCivitasImport || $isDocSign) {
    if (!in_array($method, ['GET', 'POST'], true)) {
        sigma_render_404('Metode permintaan HTTP tidak diizinkan untuk aksi ini.');
    }
} else {
    if (($expectsPost && $method !== 'POST') || (!$expectsPost && $method !== 'GET')) {
        sigma_render_404('Metode permintaan HTTP tidak diizinkan untuk aksi ini.');
    }
}

// Guard CSRF Terpusat: seluruh permintaan POST wajib membawa token CSRF sesi yang valid (VULN-04)
if ($method === 'POST') {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if (empty($_POST) && empty($_FILES) && $contentLength > 0) {
        sigma_render_403(
            'Ukuran data atau berkas yang dikirim melebihi batas maksimal server. Silakan unggah berkas dengan ukuran lebih kecil dan coba kembali.',
            413,
            '413 - Ukuran Permintaan Terlalu Besar'
        );
    }

    if (!Csrf::verify()) {
        sigma_render_403(
            'Token keamanan formulir (CSRF) tidak valid atau sesi Anda telah kedaluwarsa. Silakan kembali, muat ulang halaman, lalu coba kembali.'
        );
    }
}

$controller = new $controllerName();

if ($action === '' || $action[0] === '_' || !method_exists($controller, $action) || !is_callable([$controller, $action])) {
    sigma_render_404('Aksi atau rute yang diminta tidak tersedia.');
}

if ($id === null) {
    $controller->$action();
} else {
    $controller->$action($id);
}
