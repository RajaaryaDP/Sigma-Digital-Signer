<?php

require_once __DIR__ . '/../models/UserSignaturesModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../core/SignatureImageValidator.php';
require_once __DIR__ . '/../core/StoragePath.php';

class UserSignatures
{
    private $signatureModel;
    private $auditModel;
    private $load;
    private $userId;
    private $isAdmin;
    private $storageBaseDir;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Pengguna harus sudah login untuk mengakses modul template tanda tangan
        if (empty($_SESSION['user'])) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Silakan masuk terlebih dahulu untuk mengakses template tanda tangan.'
            ];
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }

        $this->signatureModel = new UserSignaturesModel();
        $this->auditModel     = new AuditLogModel();
        $this->load           = new Loader();
        $this->userId         = $_SESSION['user']['id'];
        $this->isAdmin        = (($_SESSION['user']['role'] ?? '') === 'admin');
        $this->storageBaseDir = __DIR__ . '/../storage';

        $templateDir = $this->storageBaseDir . '/signatures/templates';
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
    }

    /**
     * Memastikan hanya pengguna non-admin (pengaju/penandatangan) yang dapat mengelola
     * template tanda tangan pribadi. Admin bukan aktor penandatangan (ADR-01 / OQ-02).
     */
    private function requireSignerRole(): void
    {
        if ($this->isAdmin) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Administrator tidak berpartisipasi dalam alur penandatanganan dokumen sehingga tidak memerlukan template tanda tangan pribadi.'
            ];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }
    }

    /**
     * Menampilkan daftar koleksi template tanda tangan milik pengguna aktif
     */
    public function index()
    {
        $this->requireSignerRole();

        $signatures = $this->signatureModel->getByUserId($this->userId);
        $defaultSig = $this->signatureModel->getDefaultByUserId($this->userId);

        $this->load->view('views/user-signatures/index.php', [
            'title'      => 'Template Tanda Tangan Saya — SIGMA',
            'signatures' => $signatures,
            'defaultSig' => $defaultSig,
        ]);
    }

    /**
     * Menampilkan antarmuka pembuatan template tanda tangan baru
     * (Pilihan Kanvas Digital HTML5 atau Unggah Gambar PNG Transparan)
     */
    public function create()
    {
        $this->requireSignerRole();

        $existingCount = $this->signatureModel->countByUserId($this->userId);

        $this->load->view('views/user-signatures/form.php', [
            'title'         => 'Buat Template Tanda Tangan — SIGMA',
            'isEdit'        => false,
            'existingCount' => $existingCount,
            'values'        => [
                'label'      => '',
                'is_default' => ($existingCount === 0) ? 1 : 0,
            ],
            'errors'        => [],
            'id'            => null,
        ]);
    }

    /**
     * Memproses penyimpanan template tanda tangan baru (POST)
     */
    public function store()
    {
        $this->requireSignerRole();

        $label       = isset($_POST['label']) && is_string($_POST['label']) ? trim($_POST['label']) : '';
        $isDefault   = !empty($_POST['is_default']) ? 1 : 0;
        $inputMethod = isset($_POST['input_method']) && is_string($_POST['input_method']) ? trim($_POST['input_method']) : 'canvas';
        $errors      = [];

        if (mb_strlen($label) > 100) {
            $errors['label'] = 'Nama/label template tanda tangan maksimal 100 karakter.';
        }

        $templateDir = $this->storageBaseDir . '/signatures/templates';
        $uniqueName  = 'sig_tpl_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.png';
        $destPath    = $templateDir . '/' . $uniqueName;
        $dbPath      = 'signatures/templates/' . $uniqueName;

        if ($inputMethod === 'canvas') {
            $canvasData = isset($_POST['signature_canvas']) && is_string($_POST['signature_canvas']) ? $_POST['signature_canvas'] : '';
            $sigErr     = SignatureImageValidator::validateAndSaveCanvas($canvasData, $destPath);
            if ($sigErr !== null) {
                $errors['signature'] = $sigErr;
            }
        } elseif ($inputMethod === 'upload') {
            $uploadedFile = isset($_FILES['signature_file']) && is_array($_FILES['signature_file'])
                ? $_FILES['signature_file']
                : [];
            $sigErr = SignatureImageValidator::validateAndSaveUpload($uploadedFile, $destPath);
            if ($sigErr !== null) {
                $errors['signature'] = $sigErr;
            }
        } else {
            $errors['signature'] = 'Metode input tanda tangan tidak dikenali.';
        }

        if (!empty($errors)) {
            // Bersihkan berkas fisik jika validasi kolom lain (mis. label) gagal agar tidak ada file yatim
            if (is_file($destPath)) {
                @unlink($destPath);
            }
            $existingCount = $this->signatureModel->countByUserId($this->userId);
            $this->load->view('views/user-signatures/form.php', [
                'title'         => 'Buat Template Tanda Tangan — SIGMA',
                'isEdit'        => false,
                'existingCount' => $existingCount,
                'values'        => [
                    'label'        => $label,
                    'is_default'   => $isDefault,
                    'input_method' => $inputMethod,
                ],
                'errors'        => $errors,
                'id'            => null,
            ]);
            return;
        }

        try {
            $newId = $this->signatureModel->create([
                'user_id'    => $this->userId,
                'label'      => $label ?: 'Tanda Tangan Pribadi',
                'image_path' => $dbPath,
                'is_default' => $isDefault,
            ]);

            $this->auditModel->log($this->userId, 'signature_template.create', [
                'id'         => $newId,
                'label'      => $label ?: 'Tanda Tangan Pribadi',
                'is_default' => $isDefault,
                'method'     => $inputMethod,
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template tanda tangan berhasil disimpan dan siap digunakan.'
            ];

            header('Location: ' . BASE_URL . '/user-signatures');
            exit;
        } catch (Throwable $e) {
            // Hapus file fisik jika simpan database gagal
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            error_log('[UserSignatures::store] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menyimpan template tanda tangan karena kendala pada basis data. Silakan coba kembali.'
                : 'Gagal menyimpan template tanda tangan: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];

            header('Location: ' . BASE_URL . '/user-signatures/create');
            exit;
        }
    }

    /**
     * Memperbarui atribut template (e.g. Setel sebagai Default atau Ubah Label)
     */
    public function update($id)
    {
        $this->requireSignerRole();

        $target = $this->signatureModel->getById($id, $this->userId);
        if (!$target) {
            http_response_code(404);
            echo '404 - Template tanda tangan tidak ditemukan.';
            exit;
        }

        $actionType = isset($_POST['action_type']) && is_string($_POST['action_type']) ? $_POST['action_type'] : '';

        try {
            if ($actionType === 'set_default') {
                $this->signatureModel->setDefault($id, $this->userId);

                $this->auditModel->log($this->userId, 'signature_template.set_default', [
                    'id'    => $id,
                    'label' => $target['label'],
                ]);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Template "' . ($target['label'] ?: 'Tanda Tangan') . '" berhasil ditetapkan sebagai tanda tangan default.'
                ];
            } else {
                $label = isset($_POST['label']) && is_string($_POST['label']) ? trim($_POST['label']) : '';
                if (mb_strlen($label) > 100) {
                    $label = mb_substr($label, 0, 100);
                }
                $this->signatureModel->update($id, $this->userId, [
                    'label' => $label ?: 'Tanda Tangan Pribadi',
                ]);

                $this->auditModel->log($this->userId, 'signature_template.update', [
                    'id'    => $id,
                    'label' => $label,
                ]);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Informasi template tanda tangan berhasil diperbarui.'
                ];
            }
        } catch (Throwable $e) {
            error_log('[UserSignatures::update] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal memperbarui template tanda tangan karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/user-signatures');
        exit;
    }

    /**
     * Menghapus template tanda tangan pengguna (Soft Delete)
     */
    public function delete($id)
    {
        $this->requireSignerRole();

        $target = $this->signatureModel->getById($id, $this->userId);
        if (!$target) {
            http_response_code(404);
            echo '404 - Template tanda tangan tidak ditemukan.';
            exit;
        }

        try {
            $this->signatureModel->delete($id, $this->userId);

            $this->auditModel->log($this->userId, 'signature_template.delete', [
                'id'    => $id,
                'label' => $target['label'],
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template tanda tangan berhasil dihapus.'
            ];
        } catch (Throwable $e) {
            error_log('[UserSignatures::delete] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menghapus template tanda tangan karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/user-signatures');
        exit;
    }

    /**
     * Streaming file gambar tanda tangan secara privat & aman (GET)
     * Hanya pemilik tanda tangan atau Admin yang berhak melihat pratinjau gambar
     */
    public function preview($id)
    {
        $signature = $this->signatureModel->getById($id);

        if (!$signature) {
            http_response_code(404);
            exit('Gambar tanda tangan tidak ditemukan.');
        }

        if ($signature['user_id'] !== $this->userId && !$this->isAdmin) {
            http_response_code(403);
            exit('Akses ditolak: Anda tidak memiliki izin untuk melihat tanda tangan ini.');
        }

        $realFile = StoragePath::resolve(
            (string) ($signature['image_path'] ?? ''),
            'signatures/templates',
            ['png'],
            $this->storageBaseDir
        );

        if ($realFile === null) {
            http_response_code(404);
            exit('Berkas fisik gambar tanda tangan tidak tersedia di storage server.');
        }

        // Streaming respon gambar PNG dengan header proteksi
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($realFile));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($realFile);
        exit;
    }
}
