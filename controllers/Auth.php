<?php

require_once __DIR__ . '/../models/UsersModel.php';
require_once __DIR__ . '/../models/CivitasModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/RateLimiter.php';

class Auth
{
    /** Masa berlaku kode CAPTCHA di sesi (5 menit) — VULN-10 */
    private const CAPTCHA_TTL_SECONDS = 300;

    private $userModel;
    private $civitasModel;
    private $auditModel;
    private $load;

    public function __construct()
    {
        $this->userModel    = new UsersModel();
        $this->civitasModel = new CivitasModel();
        $this->auditModel   = new AuditLogModel();
        $this->load         = new Loader();
    }

    /**
     * Menampilkan formulir login (GET) atau memproses kredensial pengguna (POST)
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $captcha  = isset($_POST['captcha']) && is_string($_POST['captcha']) ? strtoupper(trim($_POST['captcha'])) : '';
            $errors   = [];

            // Guard Rate Limiting Login (VULN-10): hentikan brute-force sebelum query DB / password_verify
            $rateCheck = RateLimiter::check('login');
            if (!$rateCheck['allowed']) {
                $this->clearCaptcha('login');
                $retryAfter = (int) $rateCheck['retry_after'];

                $this->auditModel->log(
                    null,
                    'user.login_rate_limited',
                    [
                        'attempted_email' => mb_substr($email, 0, 255),
                        'reason'          => $rateCheck['reason'],
                        'retry_after'     => $retryAfter,
                    ]
                );

                http_response_code(429);
                header('Retry-After: ' . $retryAfter);
                $errors['general'] = 'Terlalu banyak percobaan masuk yang gagal. Silakan tunggu ' . $retryAfter . ' detik sebelum mencoba kembali.';
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

            // Selalu konsumsi & reset kode CAPTCHA pada setiap permintaan POST (gagal maupun sukses — VULN-10)
            $captchaStatus = $this->consumeCaptcha('login', $captcha);

            if (empty($email)) {
                $errors['email'] = 'Email institusi wajib diisi.';
            }
            if (empty($password)) {
                $errors['password'] = 'Password wajib diisi.';
            }

            if ($captchaStatus === 'empty') {
                $errors['captcha'] = 'Kode captcha wajib diisi.';
            } elseif ($captchaStatus === 'expired') {
                $errors['captcha'] = 'Kode captcha telah kedaluwarsa. Silakan masukkan kode captcha yang baru.';
            } elseif ($captchaStatus !== 'valid') {
                $errors['captcha'] = 'Kode captcha yang Anda masukkan salah.';
            }

            if (!empty($errors)) {
                if ($captchaStatus !== 'valid') {
                    $rlResult = RateLimiter::recordFailure('login');
                    if (!$rlResult['allowed']) {
                        http_response_code(429);
                        header('Retry-After: ' . (int) $rlResult['retry_after']);
                        $errors['general'] = 'Terlalu banyak percobaan masuk yang gagal. Silakan tunggu ' . (int) $rlResult['retry_after'] . ' detik sebelum mencoba kembali.';
                    }
                }
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

            $user = $this->userModel->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $rlResult = RateLimiter::recordFailure('login');

                // Catat kegagalan login ke audit trail
                $this->auditModel->log(
                    $user['id'] ?? null,
                    'user.login_failed',
                    ['attempted_email' => mb_substr($email, 0, 255)]
                );

                if (!$rlResult['allowed']) {
                    http_response_code(429);
                    header('Retry-After: ' . (int) $rlResult['retry_after']);
                    $errors['general'] = 'Email atau password tidak sesuai. Terlalu banyak percobaan gagal — silakan tunggu ' . (int) $rlResult['retry_after'] . ' detik sebelum mencoba kembali.';
                } else {
                    $errors['general'] = 'Email atau password yang Anda masukkan tidak sesuai.';
                }
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

            // Guard VULN-06: akun non-admin wajib tertaut ke data civitas yang masih aktif
            if (!UsersModel::isEligibleForActiveSession($user)) {
                RateLimiter::recordFailure('login');
                $this->auditModel->log(
                    $user['id'],
                    'user.login_blocked_inactive_civitas',
                    [
                        'attempted_email' => mb_substr($email, 0, 255),
                        'civitas_id'      => $user['civitas_id'] ?? null,
                    ]
                );

                $errors['general'] = 'Akun Anda tidak dapat digunakan karena data civitas terkait sudah tidak aktif. Hubungi Administrator.';
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

            // Bersihkan pembatas percobaan sesi setelah kredensial terverifikasi
            RateLimiter::clearSession('login');

            // Anti Session Fixation (VULN-05): regenerasi ID sesi & bersihkan state pra-login
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION = [];

            // Rotasi token CSRF pada transisi autentikasi login (VULN-04)
            Csrf::rotate();

            // Simpan data kredensial dan profil civitas ke dalam sesi
            $_SESSION['user'] = [
                'id'                 => $user['id'],
                'civitas_id'         => $user['civitas_id'],
                'name'               => $user['name'],
                'email'              => $user['email'],
                'role'               => $user['role'], // 'admin' atau 'user'
                'avatar_path'        => $user['avatar_path'],
                'identifier'         => $user['civitas_identifier'] ?? null,
                'civitas_name'       => $user['civitas_name'] ?? $user['name'],
                'civitas_type'       => $user['civitas_type'] ?? null, // 'dosen', 'mahasiswa', 'tendik'
                'department'         => $user['civitas_department'] ?? null,
                'position'           => $user['civitas_position'] ?? null,
                'phone'              => $user['civitas_phone'] ?? null,
            ];

            // Catat keberhasilan login ke audit trail
            $this->auditModel->log(
                $user['id'],
                'user.login',
                [
                    'role'       => $user['role'],
                    'identifier' => $user['civitas_identifier'] ?? null,
                ]
            );

            // Berikan flash message selamat datang (plain text; HTML escaping ditangani view header.php — VULN-11)
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Selamat datang kembali, ' . $user['name'] . '!'
            ];

            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        // Tampilan formulir login (GET)
        $this->load->view('views/auth/login.php');
    }

    /**
     * Menampilkan formulir registrasi mandiri (GET) atau memproses aktivasi akun civitas (POST)
     * Dilengkapi 4 Pintu Filter Keamanan (Task 11):
     *  1. Validasi Domain Email Resmi (@stu.pnj.ac.id, @pnj.ac.id, @sigma.local)
     *  2. Whitelist Match Gate (Pencocokan Ganda NIM/NIP + Email terhadap Master Data Civitas)
     *  3. Anti-Impersonation Lock (Nama, Kategori, Jurusan, Jabatan, & Peran dikunci dari Master Civitas)
     *  4. Proteksi Akun Ganda (Menolak jika civitas_id atau email sudah terdaftar di tabel users)
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier      = isset($_POST['identifier']) && is_string($_POST['identifier']) ? trim($_POST['identifier']) : '';
            $name            = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
            $email           = isset($_POST['email']) && is_string($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
            $password        = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
            $passwordConfirm = isset($_POST['password_confirm']) && is_string($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
            $captcha         = isset($_POST['captcha']) && is_string($_POST['captcha']) ? strtoupper(trim($_POST['captcha'])) : '';

            $errors = [];
            $old    = [
                'identifier' => $identifier,
                'name'       => $name,
                'email'      => $email,
            ];

            // Guard Rate Limiting Registrasi (VULN-10): cegah enumerasi (NIM/NIP, email) civitas
            $rateCheck = RateLimiter::check('register');
            if (!$rateCheck['allowed']) {
                $this->clearCaptcha('register');
                $retryAfter = (int) $rateCheck['retry_after'];

                $this->auditModel->log(
                    null,
                    'user.register_rate_limited',
                    [
                        'attempted_identifier' => mb_substr($identifier, 0, 64),
                        'attempted_email'      => mb_substr($email, 0, 255),
                        'reason'               => $rateCheck['reason'],
                        'retry_after'          => $retryAfter,
                    ]
                );

                http_response_code(429);
                header('Retry-After: ' . $retryAfter);
                $errors['general'] = 'Terlalu banyak percobaan registrasi yang gagal. Silakan tunggu ' . $retryAfter . ' detik sebelum mencoba kembali.';
                $this->load->view('views/auth/register.php', [
                    'errors' => $errors,
                    'old'    => $old,
                ]);
                return;
            }

            // Selalu konsumsi & reset CAPTCHA registrasi pada setiap POST (VULN-10)
            $captchaStatus = $this->consumeCaptcha('register', $captcha);
            if ($captchaStatus === 'empty') {
                $errors['captcha'] = 'Kode captcha wajib diisi.';
            } elseif ($captchaStatus === 'expired') {
                $errors['captcha'] = 'Kode captcha telah kedaluwarsa. Silakan masukkan kode captcha yang baru.';
            } elseif ($captchaStatus !== 'valid') {
                $errors['captcha'] = 'Kode captcha yang Anda masukkan salah.';
            }

            // Validasi NIM / NIP
            if ($identifier === '') {
                $errors['identifier'] = 'Nomor identitas (NIM / NIP) wajib diisi.';
            } elseif (mb_strlen($identifier) > 50) {
                $errors['identifier'] = 'Panjang NIM / NIP maksimal 50 karakter.';
            }

            // Validasi Nama Lengkap
            if ($name === '') {
                $errors['name'] = 'Nama lengkap wajib diisi.';
            } elseif (mb_strlen($name) > 255) {
                $errors['name'] = 'Panjang nama lengkap maksimal 255 karakter.';
            }

            // Pintu Filter 1: Validasi Format & Domain Email Resmi Kampus
            $allowedDomains = ['stu.pnj.ac.id', 'pnj.ac.id', 'sigma.local'];
            $emailDomain    = '';

            if ($email === '') {
                $errors['email'] = 'Alamat email resmi kampus wajib diisi.';
            } elseif (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Format alamat email tidak valid.';
            } else {
                $atPos       = strrpos($email, '@');
                $emailDomain = ($atPos !== false) ? strtolower(substr($email, $atPos + 1)) : '';

                if (!in_array($emailDomain, $allowedDomains, true)) {
                    $errors['email'] = 'Domain email ditolak. Gunakan email resmi kampus (@stu.pnj.ac.id untuk Mahasiswa, @pnj.ac.id untuk Dosen/Tendik, atau @sigma.local). Email publik (@gmail.com, @yahoo.com, dll) tidak diizinkan.';
                }
            }

            // Validasi Keamanan Kata Sandi (Minimal 8 Karakter & Konfirmasi)
            if ($password === '') {
                $errors['password'] = 'Password wajib diisi.';
            } elseif (mb_strlen($password) < 8) {
                $errors['password'] = 'Password wajib terdiri dari minimal 8 karakter.';
            } elseif (mb_strlen($password) > 72) {
                $errors['password'] = 'Password maksimal 72 karakter.';
            }

            if ($passwordConfirm === '') {
                $errors['password_confirm'] = 'Konfirmasi password wajib diisi.';
            } elseif ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Konfirmasi password tidak cocok dengan password yang Anda masukkan.';
            }

            // Jika CAPTCHA atau validasi dasar gagal, hentikan SEBELUM menyentuh Master Data Civitas (anti enumerasi)
            if (!empty($errors)) {
                if ($captchaStatus !== 'valid') {
                    $rlResult = RateLimiter::recordFailure('register');
                    if (!$rlResult['allowed']) {
                        http_response_code(429);
                        header('Retry-After: ' . (int) $rlResult['retry_after']);
                        $errors['general'] = 'Terlalu banyak percobaan registrasi yang gagal. Silakan tunggu ' . (int) $rlResult['retry_after'] . ' detik sebelum mencoba kembali.';
                    }
                }
                $this->load->view('views/auth/register.php', [
                    'errors' => $errors,
                    'old'    => $old,
                ]);
                return;
            }

            // Pintu Filter 2: Pencocokan Ganda terhadap Master Data Civitas (Whitelist Match Gate)
            $matchedCivitas = $this->civitasModel->findForRegistration($identifier, $email);
            $recordThrottle = false;

            if (!$matchedCivitas) {
                $recordThrottle = true;
                $this->auditModel->log(
                    null,
                    'user.register_failed',
                    [
                        'reason'               => 'civitas_mismatch',
                        'attempted_identifier' => mb_substr($identifier, 0, 64),
                        'attempted_email'      => mb_substr($email, 0, 255),
                    ]
                );

                $errors['general'] = 'Verifikasi gagal: Kombinasi NIM/NIP dan Email tidak ditemukan atau tidak cocok pada Master Data Civitas Kampus. Pastikan NIM/NIP dan Email sesuai dengan data yang terdaftar di Admin.';
            } else {
                // Validasi kesesuaian domain dengan kategori civitas
                if ($emailDomain === 'stu.pnj.ac.id' && $matchedCivitas['type'] !== 'mahasiswa') {
                    $recordThrottle  = true;
                    $errors['email'] = 'Domain @stu.pnj.ac.id hanya diperuntukkan bagi civitas berkategori Mahasiswa.';
                } elseif ($emailDomain === 'pnj.ac.id' && !in_array($matchedCivitas['type'], ['dosen', 'tendik'], true)) {
                    $recordThrottle  = true;
                    $errors['email'] = 'Civitas Mahasiswa wajib menggunakan domain @stu.pnj.ac.id.';
                }
            }

            // Pintu Filter 4: Proteksi Akun Ganda (Cek berdasarkan civitas_id maupun email)
            if (empty($errors) && $matchedCivitas) {
                $existingByCivitas = $this->userModel->getByCivitasId($matchedCivitas['id']);
                $existingByEmail   = $this->userModel->getByEmail($matchedCivitas['email']);

                if ($existingByCivitas || $existingByEmail) {
                    $recordThrottle    = true;
                    $errors['general'] = 'Akun untuk NIM/NIP atau Email civitas ini sudah terdaftar dan aktif di sistem. Silakan langsung masuk melalui halaman Login.';
                }
            }

            if (!empty($errors)) {
                if ($recordThrottle) {
                    $rlResult = RateLimiter::recordFailure('register');
                    if (!$rlResult['allowed']) {
                        http_response_code(429);
                        header('Retry-After: ' . (int) $rlResult['retry_after']);
                        $errors['general'] = ($errors['general'] ?? 'Verifikasi registrasi gagal.')
                            . ' Terlalu banyak percobaan gagal — silakan tunggu ' . (int) $rlResult['retry_after'] . ' detik sebelum mencoba kembali.';
                    }
                }
                $this->load->view('views/auth/register.php', [
                    'errors' => $errors,
                    'old'    => $old,
                ]);
                return;
            }

            // Pintu Filter 3: Proteksi Anti-Impersonasi (Kunci Nama, Email, Peran, & Identitas dari Master Civitas)
            try {
                $newUserId = $this->userModel->create([
                    'civitas_id' => $matchedCivitas['id'],
                    'name'       => $matchedCivitas['name'],
                    'email'      => strtolower($matchedCivitas['email']),
                    'password'   => $password,
                    'role'       => 'user',
                ]);

                RateLimiter::clearSession('register');

                // Catat keberhasilan registrasi mandiri ke tabel audit_logs
                $this->auditModel->log(
                    $newUserId,
                    'user.registered',
                    [
                        'civitas_id' => $matchedCivitas['id'],
                        'identifier' => $matchedCivitas['identifier'],
                        'type'       => $matchedCivitas['type'],
                        'department' => $matchedCivitas['department'],
                    ]
                );

                // Aktivasi instan tanpa dependensi SMTP: arahkan langsung ke halaman login
                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Registrasi berhasil! Akun resmi untuk ' . $matchedCivitas['name'] . ' (' . $matchedCivitas['identifier'] . ') telah aktif. Silakan masuk dengan password yang baru Anda buat.'
                ];

                header('Location: ' . BASE_URL . '/auth?email=' . urlencode(strtolower($matchedCivitas['email'])));
                exit;
            } catch (\Throwable $e) {
                error_log('[Auth::register] ' . $e->getMessage());
                $errors['general'] = 'Gagal memproses pendaftaran karena kendala basis data atau akun telah terdaftar.';
                $this->load->view('views/auth/register.php', [
                    'errors' => $errors,
                    'old'    => $old,
                ]);
                return;
            }
        }

        // Tampilan formulir registrasi mandiri (GET)
        $this->load->view('views/auth/register.php', [
            'errors' => [],
            'old'    => [],
        ]);
    }

    /**
     * Mengakhiri sesi pengguna
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'];
            $this->auditModel->log($userId, 'user.logout');

            $_SESSION = [];
            session_destroy();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
        Csrf::rotate();
        $_SESSION['flash'] = [
            'type'    => 'info',
            'message' => 'Anda telah berhasil keluar dari sistem.'
        ];

        header('Location: ' . BASE_URL . '/auth');
        exit;
    }

    /**
     * Endpoint untuk men-generate gambar CAPTCHA per scope ('login' atau 'register') — VULN-10
     */
    public function captcha()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $rawScope = isset($_GET['scope']) && is_string($_GET['scope']) ? strtolower(trim($_GET['scope'])) : 'login';
        $scope    = in_array($rawScope, ['login', 'register'], true) ? $rawScope : 'login';

        // Karakter mudah dibaca (menghindari karakter ambigu seperti 0, O, 1, I)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $captchaCode = '';
        $length = 5;
        $maxIndex = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $captchaCode .= $characters[random_int(0, $maxIndex)];
        }

        if (!isset($_SESSION['captcha_codes']) || !is_array($_SESSION['captcha_codes'])) {
            $_SESSION['captcha_codes'] = [];
        }
        $_SESSION['captcha_codes'][$scope] = [
            'code'      => $captchaCode,
            'issued_at' => time(),
        ];
        // Bersihkan kunci legacy agar tidak tersisa di sesi
        unset($_SESSION['captcha_code']);

        $width = 140;
        $height = 46;

        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 246, 245, 251);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Garis gangguan halus (noise)
        for ($i = 0; $i < 4; $i++) {
            $lineColor = imagecolorallocate($image, random_int(180, 210), random_int(180, 210), random_int(220, 240));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        // Titik-titik noise
        for ($i = 0; $i < 60; $i++) {
            $dotColor = imagecolorallocate($image, random_int(170, 200), random_int(160, 200), random_int(200, 230));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
        }

        // Gambar karakter
        $textColor = imagecolorallocate($image, 29, 39, 72);
        for ($i = 0; $i < $length; $i++) {
            $x = 18 + ($i * 24);
            $y = random_int(10, 18);
            imagechar($image, 5, $x, $y, $captchaCode[$i], $textColor);
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        imagepng($image);
        imagedestroy($image);
        exit;
    }

    /**
     * Menghapus kode CAPTCHA pada scope tertentu dari sesi.
     */
    private function clearCaptcha(string $scope): void
    {
        if (isset($_SESSION['captcha_codes'][$scope])) {
            unset($_SESSION['captcha_codes'][$scope]);
        }
        unset($_SESSION['captcha_code']);
    }

    /**
     * Memverifikasi kode CAPTCHA sekali pakai (*one-time use*) dan langsung menghapusnya
     * dari sesi baik saat cocok, salah, kosong, maupun kedaluwarsa (VULN-10).
     *
     * @return string 'valid' | 'empty' | 'expired' | 'invalid'
     */
    private function consumeCaptcha(string $scope, string $input): string
    {
        $entry = $_SESSION['captcha_codes'][$scope] ?? null;
        // Hapus seketika sebelum evaluasi agar satu kode tidak pernah bisa dicoba dua kali
        $this->clearCaptcha($scope);

        if ($input === '') {
            return 'empty';
        }

        if (!is_array($entry) || empty($entry['code']) || !is_string($entry['code'])) {
            return 'expired';
        }

        $issuedAt = (int) ($entry['issued_at'] ?? 0);
        if ($issuedAt <= 0 || (time() - $issuedAt) > self::CAPTCHA_TTL_SECONDS) {
            return 'expired';
        }

        return hash_equals($entry['code'], $input) ? 'valid' : 'invalid';
    }
}
