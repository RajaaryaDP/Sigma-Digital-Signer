<?php

require_once __DIR__ . '/../models/UsersModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class Auth
{
    private $userModel;
    private $auditModel;
    private $load;

    public function __construct()
    {
        $this->userModel  = new UsersModel();
        $this->auditModel = new AuditLogModel();
        $this->load       = new Loader();
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

            if (empty($email)) {
                $errors['email'] = 'Email institusi wajib diisi.';
            }
            if (empty($password)) {
                $errors['password'] = 'Password wajib diisi.';
            }

            $sessionCaptcha = $_SESSION['captcha_code'] ?? '';
            if (empty($captcha)) {
                $errors['captcha'] = 'Kode captcha wajib diisi.';
            } elseif (empty($sessionCaptcha) || $captcha !== $sessionCaptcha) {
                $errors['captcha'] = 'Kode captcha yang Anda masukkan salah.';
            }

            if (!empty($errors)) {
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

            // Reset captcha setelah verifikasi berhasil
            unset($_SESSION['captcha_code']);

            $user = $this->userModel->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                // Catat kegagalan login ke audit trail
                $this->auditModel->log(
                    $user['id'] ?? null,
                    'user.login_failed',
                    ['attempted_email' => $email]
                );

                $errors['general'] = 'Email atau password yang Anda masukkan tidak sesuai.';
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

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

            // Berikan flash message selamat datang
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '!'
            ];

            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        // Tampilan formulir login (GET)
        $this->load->view('views/auth/login.php');
    }

    /**
     * Mengakhiri sesi pengguna
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            $userId = $_SESSION['user']['id'];
            $this->auditModel->log($userId, 'user.logout');

            unset($_SESSION['user']);
            session_destroy();
        }

        session_start();
        $_SESSION['flash'] = [
            'type'    => 'info',
            'message' => 'Anda telah berhasil keluar dari sistem.'
        ];

        header('Location: ' . BASE_URL . '/auth');
        exit;
    }

    /**
     * Endpoint untuk men-generate gambar CAPTCHA
     */
    public function captcha()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Karakter mudah dibaca (menghindari karakter ambigu seperti 0, O, 1, I)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $captchaCode = '';
        $length = 5;
        $maxIndex = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $captchaCode .= $characters[random_int(0, $maxIndex)];
        }

        $_SESSION['captcha_code'] = $captchaCode;

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
}
