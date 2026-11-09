<?php

require_once __DIR__ . '/../models/CivitasModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class Civitas
{
    private $civitasModel;
    private $auditModel;
    private $load;

    public function __construct()
    {
        // Guard ketat khusus peran Admin
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak: Hanya Administrator yang berwenang mengelola Master Data Civitas.'
            ];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        $this->civitasModel = new CivitasModel();
        $this->auditModel   = new AuditLogModel();
        $this->load         = new Loader();
    }

    /**
     * Menampilkan daftar civitas kampus (Dosen, Mahasiswa, Tendik)
     * Dilengkapi fitur pencarian dan filter tab kategori
     */
    public function index()
    {
        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $type   = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';

        $civitasList = $this->civitasModel->getCivitas($search, $type);
        $counts      = $this->civitasModel->countByType();

        $this->load->view('views/civitas/index.php', [
            'title'       => 'Master Data Civitas Kampus — SIGMA',
            'civitasList' => $civitasList,
            'counts'      => $counts,
            'search'      => $search,
            'currentType' => $type,
        ]);
    }

    /**
     * Menampilkan formulir tambah data civitas baru
     */
    public function create()
    {
        $this->load->view('views/civitas/form.php', [
            'title'   => 'Tambah Civitas Kampus — SIGMA',
            'isEdit'  => false,
            'values'  => [
                'type' => 'mahasiswa', // default
            ],
            'errors'  => [],
            'id'      => null,
        ]);
    }

    /**
     * Memproses penyimpanan data civitas baru (POST)
     */
    public function store()
    {
        $data = [
            'identifier'  => isset($_POST['identifier']) && is_string($_POST['identifier']) ? trim($_POST['identifier']) : '',
            'name'        => isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '',
            'type'        => isset($_POST['type']) && is_string($_POST['type']) ? trim($_POST['type']) : '',
            'department'  => isset($_POST['department']) && is_string($_POST['department']) ? trim($_POST['department']) : '',
            'position'    => isset($_POST['position']) && is_string($_POST['position']) ? trim($_POST['position']) : '',
            'email'       => isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '',
            'phone'       => isset($_POST['phone']) && is_string($_POST['phone']) ? trim($_POST['phone']) : '',
            'avatar_path' => null,
        ];

        $errors = $this->validate($data);

        if (!empty($errors)) {
            $this->load->view('views/civitas/form.php', [
                'title'  => 'Tambah Civitas Kampus — SIGMA',
                'isEdit' => false,
                'values' => $data,
                'errors' => $errors,
                'id'     => null,
            ]);
            return;
        }

        try {
            $newId = $this->civitasModel->create($data);

            // Catat mutasi penambahan ke riwayat audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.create',
                [
                    'civitas_id' => $newId,
                    'identifier' => $data['identifier'],
                    'name'       => $data['name'],
                    'type'       => $data['type'],
                    'department' => $data['department'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($data['name']) . '" berhasil ditambahkan.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::store] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menyimpan data civitas karena kendala pada basis data. Pastikan NIM/NIP dan Email belum terdaftar.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Menampilkan formulir edit data civitas
     */
    public function edit(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        $this->load->view('views/civitas/form.php', [
            'title'  => 'Ubah Data Civitas — SIGMA',
            'isEdit' => true,
            'values' => $civitas,
            'errors' => [],
            'id'     => $id,
        ]);
    }

    /**
     * Memproses pembaruan data civitas (POST)
     */
    public function update(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        $data = [
            'identifier'  => isset($_POST['identifier']) && is_string($_POST['identifier']) ? trim($_POST['identifier']) : '',
            'name'        => isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '',
            'type'        => isset($_POST['type']) && is_string($_POST['type']) ? trim($_POST['type']) : '',
            'department'  => isset($_POST['department']) && is_string($_POST['department']) ? trim($_POST['department']) : '',
            'position'    => isset($_POST['position']) && is_string($_POST['position']) ? trim($_POST['position']) : '',
            'email'       => isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '',
            'phone'       => isset($_POST['phone']) && is_string($_POST['phone']) ? trim($_POST['phone']) : '',
            'avatar_path' => $civitas['avatar_path'],
        ];

        $errors = $this->validate($data, $id);

        if (!empty($errors)) {
            $this->load->view('views/civitas/form.php', [
                'title'  => 'Ubah Data Civitas — SIGMA',
                'isEdit' => true,
                'values' => $data,
                'errors' => $errors,
                'id'     => $id,
            ]);
            return;
        }

        try {
            $this->civitasModel->update($id, $data);

            // Catat mutasi perubahan ke riwayat audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.update',
                [
                    'civitas_id' => $id,
                    'identifier' => $data['identifier'],
                    'name'       => $data['name'],
                    'type'       => $data['type'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($data['name']) . '" berhasil diperbarui.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::update] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal memperbarui data civitas karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Memproses penghapusan data civitas (soft delete) (POST)
     */
    public function delete(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        try {
            $this->civitasModel->delete($id);

            // Catat mutasi penghapusan ke audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.delete',
                [
                    'civitas_id' => $id,
                    'identifier' => $civitas['identifier'],
                    'name'       => $civitas['name'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($civitas['name']) . '" (' . htmlspecialchars($civitas['identifier']) . ') telah dihapus.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::delete] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menghapus data civitas karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Validasi input formulir civitas
     */
    private function validate(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // 1. Validasi Identifier (NIM / NIP) — VARCHAR(64), batas bisnis 50 karakter
        if (empty($data['identifier'])) {
            $errors['identifier'] = 'Nomor identitas (NIM/NIP) wajib diisi.';
        } elseif (mb_strlen($data['identifier']) > 50) {
            $errors['identifier'] = 'Panjang NIM/NIP maksimal 50 karakter.';
        } else {
            $existing = $this->civitasModel->getByIdentifier($data['identifier'], $excludeId);
            if ($existing) {
                $errors['identifier'] = 'NIM/NIP "' . htmlspecialchars($data['identifier']) . '" sudah terdaftar pada civitas lain.';
            }
        }

        // 2. Validasi Nama Lengkap — VARCHAR(255)
        if (empty($data['name'])) {
            $errors['name'] = 'Nama lengkap beserta gelar resmi wajib diisi.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors['name'] = 'Panjang nama maksimal 255 karakter.';
        }

        // 3. Validasi Kategori Civitas
        if (empty($data['type']) || !in_array($data['type'], ['dosen', 'mahasiswa', 'tendik'], true)) {
            $errors['type'] = 'Pilih kategori civitas yang valid (Dosen, Mahasiswa, atau Tenaga Kependidikan).';
        }

        // 4. Validasi Jurusan / Unit Kerja — VARCHAR(150)
        if (empty($data['department'])) {
            $errors['department'] = 'Jurusan / Program Studi / Unit Kerja wajib diisi.';
        } elseif (mb_strlen($data['department']) > 150) {
            $errors['department'] = 'Panjang Jurusan / Unit Kerja maksimal 150 karakter.';
        }

        // 5. Validasi Jabatan Resmi — VARCHAR(150)
        if (empty($data['position'])) {
            $errors['position'] = 'Jabatan / peran resmi wajib diisi (contoh: Mahasiswa Aktif, Dosen Pembimbing, Ketua Jurusan).';
        } elseif (mb_strlen($data['position']) > 150) {
            $errors['position'] = 'Panjang jabatan / peran resmi maksimal 150 karakter.';
        }

        // 6. Validasi Email Institusi — VARCHAR(255)
        if (empty($data['email'])) {
            $errors['email'] = 'Alamat email institusi wajib diisi.';
        } elseif (mb_strlen($data['email']) > 255) {
            $errors['email'] = 'Panjang alamat email maksimal 255 karakter.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } else {
            $existingEmail = $this->civitasModel->getByEmail($data['email'], $excludeId);
            if ($existingEmail) {
                $errors['email'] = 'Email "' . htmlspecialchars($data['email']) . '" sudah digunakan oleh civitas lain.';
            }
        }

        // 7. Validasi Nomor Telepon — VARCHAR(32)
        if (!empty($data['phone']) && mb_strlen($data['phone']) > 32) {
            $errors['phone'] = 'Panjang nomor telepon maksimal 32 karakter.';
        }

        return $errors;
    }
}
