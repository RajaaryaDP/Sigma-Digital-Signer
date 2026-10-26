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
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $errors   = [];

            if (empty($email)) {
                $errors['email'] = 'Email institusi wajib diisi.';
            }
            if (empty($password)) {
                $errors['password'] = 'Password wajib diisi.';
            }

            if (!empty($errors)) {
                $this->load->view('views/auth/login.php', [
                    'errors' => $errors,
                    'old'    => ['email' => $email],
                ]);
                return;
            }

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
}
