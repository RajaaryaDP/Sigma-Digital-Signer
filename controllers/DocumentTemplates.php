<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/DocumentTemplatesModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

use Ramsey\Uuid\Uuid;

/**
 * DocumentTemplates Controller
 * Mengelola bank template dokumen resmi kampus berformat .docx.
 * Seluruh pengguna terautentikasi dapat melihat daftar, mencari nama, dan mengunduh.
 * Hanya Administrator yang dapat menambah, mengubah, dan menghapus permanen (hard delete).
 */
class DocumentTemplates
{
    private $load;
    private $model;
    private $auditModel;
    private $user;
    private $userId;
    private $isAdmin;
    private $storageDir;

    /** Batas ukuran upload berkas .docx: 10 MB */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const STORAGE_SUBDIR = 'document_templates';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Silakan masuk terlebih dahulu untuk mengakses Template Dokumen.',
            ];
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }

        $this->load       = new Loader();
        $this->model      = new DocumentTemplatesModel();
        $this->auditModel = new AuditLogModel();
        $this->user       = $_SESSION['user'];
        $this->userId     = $_SESSION['user']['id'];
        $this->isAdmin    = ($_SESSION['user']['role'] ?? '') === 'admin';
        $this->storageDir = __DIR__ . '/../storage';

        $templateDir = $this->storageDir . '/' . self::STORAGE_SUBDIR;
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
    }

    /**
     * GET /document-templates — Daftar & pencarian template berdasarkan nama
     */
    public function index()
    {
        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        if (mb_strlen($search) > 200) {
            $search = mb_substr($search, 0, 200);
        }

        $templates = $this->model->getAll($search);

        $this->load->view('views/document-templates/index.php', [
            'title'     => 'Template Dokumen — SIGMA',
            'templates' => $templates,
            'search'    => $search,
            'isAdmin'   => $this->isAdmin,
        ]);
    }

    /**
     * GET /document-templates/{id} — Redirect aman ke daftar template
     */
    public function detail($id)
    {
        $template = $this->model->getById((string) $id);
        if (!$template) {
            $this->render404('Template dokumen tidak ditemukan.');
        }

        header('Location: ' . BASE_URL . '/document-templates');
        exit;
    }

    /**
     * GET /document-templates/create — Form tambah template baru (Admin only)
     */
    public function create()
    {
        $this->requireAdmin();

        $this->load->view('views/document-templates/form.php', [
            'title'    => 'Tambah Template Dokumen — SIGMA',
            'isEdit'   => false,
            'template' => null,
            'values'   => [
                'name'        => '',
                'description' => '',
            ],
            'errors'   => [],
        ]);
    }

    /**
     * POST /document-templates/store — Simpan template baru + unggah .docx (Admin only)
     */
    public function store()
    {
        $this->requireAdmin();

        $name        = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
        $errors      = [];

        $this->validateMetadata($name, $description, $errors);
        $validFile = $this->validateDocxUpload($_FILES['docx_file'] ?? null, true, $errors);

        if (!empty($errors)) {
            $this->load->view('views/document-templates/form.php', [
                'title'    => 'Tambah Template Dokumen — SIGMA',
                'isEdit'   => false,
                'template' => null,
                'values'   => [
                    'name'        => $name,
                    'description' => $description,
                ],
                'errors'   => $errors,
            ]);
            return;
        }

        $storedFile = $this->storeUploadedDocx($validFile);
        if (!$storedFile) {
            $errors['docx_file'] = 'Gagal menyimpan berkas template (.docx) ke penyimpanan server.';
            $this->load->view('views/document-templates/form.php', [
                'title'    => 'Tambah Template Dokumen — SIGMA',
                'isEdit'   => false,
                'template' => null,
                'values'   => [
                    'name'        => $name,
                    'description' => $description,
                ],
                'errors'   => $errors,
            ]);
            return;
        }

        try {
            $id = $this->model->create([
                'name'               => $name,
                'description'        => $description !== '' ? $description : null,
                'file_name_original' => $storedFile['original_name'],
                'file_path'          => $storedFile['relative_path'],
                'file_size'          => $storedFile['size'],
                'created_by'         => $this->userId,
                'updated_by'         => $this->userId,
            ]);

            $this->auditModel->log($this->userId, 'document_template.create', [
                'template_id' => $id,
                'name'        => $name,
                'file_name'   => $storedFile['original_name'],
                'file_size'   => $storedFile['size'],
            ], null);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template dokumen "' . $name . '" berhasil ditambahkan.',
            ];
            header('Location: ' . BASE_URL . '/document-templates');
            exit;
        } catch (\Throwable $e) {
            $this->deletePhysicalFile($storedFile['relative_path']);
            error_log('[DocumentTemplates::store] ' . $e->getMessage());

            $errors['general'] = 'Terjadi kesalahan saat menyimpan template dokumen ke basis data.';
            $this->load->view('views/document-templates/form.php', [
                'title'    => 'Tambah Template Dokumen — SIGMA',
                'isEdit'   => false,
                'template' => null,
                'values'   => [
                    'name'        => $name,
                    'description' => $description,
                ],
                'errors'   => $errors,
            ]);
        }
    }

    /**
     * GET /document-templates/{id}/edit — Form ubah template dokumen (Admin only)
     */
    public function edit($id)
    {
        $this->requireAdmin();

        $template = $this->model->getById((string) $id);
        if (!$template) {
            $this->render404('Template dokumen tidak ditemukan.');
        }

        $this->load->view('views/document-templates/form.php', [
            'title'    => 'Ubah Template Dokumen — SIGMA',
            'isEdit'   => true,
            'template' => $template,
            'values'   => [
                'name'        => $template['name'],
                'description' => $template['description'] ?? '',
            ],
            'errors'   => [],
        ]);
    }

    /**
     * POST /document-templates/{id}/update — Simpan perubahan metadata/berkas (Admin only)
     */
    public function update($id)
    {
        $this->requireAdmin();

        $template = $this->model->getById((string) $id);
        if (!$template) {
            $this->render404('Template dokumen tidak ditemukan.');
        }

        $name        = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) && is_string($_POST['description']) ? trim($_POST['description']) : '';
        $errors      = [];

        $this->validateMetadata($name, $description, $errors);
        $validFile = $this->validateDocxUpload($_FILES['docx_file'] ?? null, false, $errors);

        if (!empty($errors)) {
            $this->load->view('views/document-templates/form.php', [
                'title'    => 'Ubah Template Dokumen — SIGMA',
                'isEdit'   => true,
                'template' => $template,
                'values'   => [
                    'name'        => $name,
                    'description' => $description,
                ],
                'errors'   => $errors,
            ]);
            return;
        }

        $newStoredFile = null;
        if ($validFile !== null) {
            $newStoredFile = $this->storeUploadedDocx($validFile);
            if (!$newStoredFile) {
                $errors['docx_file'] = 'Gagal menyimpan berkas pengganti (.docx) ke penyimpanan server.';
                $this->load->view('views/document-templates/form.php', [
                    'title'    => 'Ubah Template Dokumen — SIGMA',
                    'isEdit'   => true,
                    'template' => $template,
                    'values'   => [
                        'name'        => $name,
                        'description' => $description,
                    ],
                    'errors'   => $errors,
                ]);
                return;
            }
        }

        $oldFilePath = (string) ($template['file_path'] ?? '');

        try {
            $payload = [
                'name'        => $name,
                'description' => $description !== '' ? $description : null,
                'updated_by'  => $this->userId,
            ];

            if ($newStoredFile !== null) {
                $payload['file_name_original'] = $newStoredFile['original_name'];
                $payload['file_path']          = $newStoredFile['relative_path'];
                $payload['file_size']          = $newStoredFile['size'];
            }

            $this->model->update($template['id'], $payload);

            // Hapus berkas fisik lama hanya setelah basis data berhasil diperbarui
            if ($newStoredFile !== null && $oldFilePath !== '' && $oldFilePath !== $newStoredFile['relative_path']) {
                if ($this->model->countByFilePath($oldFilePath) === 0) {
                    $this->deletePhysicalFile($oldFilePath);
                }
            }

            $this->auditModel->log($this->userId, 'document_template.update', [
                'template_id'   => $template['id'],
                'old_name'      => $template['name'],
                'new_name'      => $name,
                'file_replaced' => ($newStoredFile !== null),
                'file_name'     => $newStoredFile !== null ? $newStoredFile['original_name'] : $template['file_name_original'],
            ], null);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template dokumen "' . $name . '" berhasil diperbarui.',
            ];
            header('Location: ' . BASE_URL . '/document-templates');
            exit;
        } catch (\Throwable $e) {
            if ($newStoredFile !== null) {
                $this->deletePhysicalFile($newStoredFile['relative_path']);
            }
            error_log('[DocumentTemplates::update] ' . $e->getMessage());

            $errors['general'] = 'Terjadi kesalahan saat memperbarui template dokumen.';
            $this->load->view('views/document-templates/form.php', [
                'title'    => 'Ubah Template Dokumen — SIGMA',
                'isEdit'   => true,
                'template' => $template,
                'values'   => [
                    'name'        => $name,
                    'description' => $description,
                ],
                'errors'   => $errors,
            ]);
        }
    }

    /**
     * POST /document-templates/{id}/delete — Hard delete record & berkas fisik .docx (Admin only)
     */
    public function delete($id)
    {
        $this->requireAdmin();

        $template = $this->model->getById((string) $id);
        if (!$template) {
            $this->render404('Template dokumen tidak ditemukan.');
        }

        try {
            $deleted = $this->model->delete($template['id']);
            if (!$deleted) {
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Template dokumen sudah tidak tersedia.',
                ];
                header('Location: ' . BASE_URL . '/document-templates');
                exit;
            }

            $physicalDeleted = false;
            $filePath = (string) ($template['file_path'] ?? '');
            if ($filePath !== '' && $this->model->countByFilePath($filePath) === 0) {
                $physicalDeleted = $this->deletePhysicalFile($filePath);
            }

            $this->auditModel->log($this->userId, 'document_template.delete', [
                'template_id'           => $template['id'],
                'name'                  => $template['name'],
                'file_name'             => $template['file_name_original'],
                'physical_file_deleted' => $physicalDeleted,
            ], null);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template dokumen "' . $template['name'] . '" berhasil dihapus permanen.',
            ];
        } catch (\Throwable $e) {
            error_log('[DocumentTemplates::delete] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat menghapus template dokumen.',
            ];
        }

        header('Location: ' . BASE_URL . '/document-templates');
        exit;
    }

    /**
     * GET /document-templates/{id}/download — Unduh berkas .docx oleh seluruh user login
     */
    public function download($id)
    {
        $template = $this->model->getById((string) $id);
        if (!$template) {
            $this->render404('Template dokumen tidak ditemukan.');
        }

        $realFile = $this->resolveSafeTemplatePath((string) ($template['file_path'] ?? ''));
        if ($realFile === null || !is_readable($realFile)) {
            $this->render404('Berkas fisik template (.docx) tidak tersedia.');
        }

        $rawName = basename((string) ($template['file_name_original'] ?? 'template-dokumen.docx'));
        $cleanName = preg_replace('/[\x00-\x1F\x7F"\/\\\\]/', '', $rawName);
        if ($cleanName === '' || $cleanName === null) {
            $cleanName = 'template-dokumen.docx';
        }
        if (strtolower(pathinfo($cleanName, PATHINFO_EXTENSION)) !== 'docx') {
            $cleanName .= '.docx';
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Length: ' . filesize($realFile));
        header('Content-Disposition: attachment; filename="' . $cleanName . '"');
        header('Cache-Control: private, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        readfile($realFile);
        exit;
    }

    /**
     * Memastikan pengguna yang sedang login memiliki peran Admin
     */
    private function requireAdmin(): void
    {
        if (!$this->isAdmin) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak: Hanya Administrator yang dapat mengelola template dokumen.',
            ];
            header('Location: ' . BASE_URL . '/document-templates');
            exit;
        }
    }

    /**
     * Validasi nama dan deskripsi template
     */
    private function validateMetadata(string $name, string $description, array &$errors): void
    {
        if ($name === '') {
            $errors['name'] = 'Nama template wajib diisi.';
        } elseif (mb_strlen($name) > 200) {
            $errors['name'] = 'Nama template maksimal 200 karakter.';
        }

        if ($description !== '' && mb_strlen($description) > 2000) {
            $errors['description'] = 'Deskripsi template maksimal 2000 karakter.';
        }
    }

    /**
     * Validasi unggahan berkas Word (.docx) beserta pemeriksaan MIME dan struktur OOXML
     */
    private function validateDocxUpload(?array $fileInput, bool $isRequired, array &$errors): ?array
    {
        if ($fileInput === null || !isset($fileInput['error']) || (int) $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
            if ($isRequired) {
                $errors['docx_file'] = 'Berkas template Word (.docx) wajib diunggah.';
            }
            return null;
        }

        $uploadErr = (int) $fileInput['error'];
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $uploadMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran berkas melebihi batas yang diizinkan server.',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran berkas melebihi batas formulir.',
                UPLOAD_ERR_PARTIAL    => 'Berkas hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Direktori sementara server tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis berkas ke disk server.',
                UPLOAD_ERR_EXTENSION  => 'Unggahan dihentikan oleh ekstensi server.',
            ];
            $errors['docx_file'] = $uploadMessages[$uploadErr] ?? 'Terjadi kesalahan saat mengunggah berkas.';
            return null;
        }

        $tmpPath = (string) ($fileInput['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $errors['docx_file'] = 'Sumber unggahan berkas tidak valid.';
            return null;
        }

        $actualSize = @filesize($tmpPath);
        if ($actualSize === false || $actualSize <= 0) {
            $errors['docx_file'] = 'Berkas yang diunggah kosong atau tidak dapat dibaca.';
            return null;
        }
        if ($actualSize > self::MAX_FILE_SIZE) {
            $errors['docx_file'] = 'Ukuran berkas .docx melebihi batas maksimal 10 MB.';
            return null;
        }

        $origName = basename((string) ($fileInput['name'] ?? ''));
        $origName = str_replace("\0", '', mb_scrub($origName, 'UTF-8'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            $errors['docx_file'] = 'Format berkas tidak didukung. Hanya menerima dokumen Word berekstensi .docx.';
            return null;
        }

        // Pemeriksaan MIME type via libmagic (fileinfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? strtolower((string) finfo_file($finfo, $tmpPath)) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
        ];
        if (!in_array($mime, $allowedMimes, true)) {
            $errors['docx_file'] = 'Tipe MIME berkas tidak valid. Pastikan Anda mengunggah berkas Microsoft Word (.docx) asli.';
            return null;
        }

        // Verifikasi struktur internal arsip OOXML .docx
        if (!class_exists(\ZipArchive::class)) {
            $errors['docx_file'] = 'Ekstensi PHP ZipArchive tidak tersedia untuk memvalidasi berkas .docx.';
            return null;
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($tmpPath, \ZipArchive::RDONLY);
        $isValidOOXML = false;
        if ($opened === true) {
            $hasContentTypes = ($zip->locateName('[Content_Types].xml') !== false);
            $hasWordBody     = ($zip->locateName('word/document.xml') !== false);
            $isValidOOXML    = ($hasContentTypes && $hasWordBody);
            $zip->close();
        }

        if (!$isValidOOXML) {
            $errors['docx_file'] = 'Berkas tidak memiliki struktur dokumen Word (.docx) yang valid.';
            return null;
        }

        $cleanOriginal = preg_replace('/[\x00-\x1F\x7F"\/\\\\]/', '', $origName);
        if ($cleanOriginal === '' || $cleanOriginal === null) {
            $cleanOriginal = 'template-dokumen.docx';
        }

        return [
            'tmp_name'      => $tmpPath,
            'original_name' => mb_substr($cleanOriginal, 0, 255),
            'size'          => (int) $actualSize,
        ];
    }

    /**
     * Memindahkan berkas .docx tervalidasi ke folder privat storage/document_templates/
     */
    private function storeUploadedDocx(array $validFile): ?array
    {
        $storedFileName = Uuid::uuid4()->toString() . '.docx';
        $relativePath   = self::STORAGE_SUBDIR . '/' . $storedFileName;
        $destPath       = $this->storageDir . '/' . $relativePath;

        if (!@move_uploaded_file($validFile['tmp_name'], $destPath)) {
            return null;
        }

        return [
            'relative_path' => $relativePath,
            'original_name' => $validFile['original_name'],
            'size'          => $validFile['size'],
        ];
    }

    /**
     * Memastikan path berkas berada di dalam batas kanonikal storage/document_templates/
     */
    private function resolveSafeTemplatePath(string $relativePath): ?string
    {
        if ($relativePath === '') {
            return null;
        }

        $templatesRoot = realpath($this->storageDir . '/' . self::STORAGE_SUBDIR);
        $candidate     = $this->storageDir . '/' . ltrim($relativePath, '/\\');
        $realTarget    = realpath($candidate);

        if (!$templatesRoot || !$realTarget) {
            return null;
        }

        $normalizedRoot = rtrim($templatesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($realTarget, $normalizedRoot) !== 0 || !is_file($realTarget)) {
            return null;
        }

        return $realTarget;
    }

    /**
     * Menghapus berkas fisik dari folder privat secara aman
     */
    private function deletePhysicalFile(string $relativePath): bool
    {
        $realTarget = $this->resolveSafeTemplatePath($relativePath);
        if ($realTarget === null) {
            return false;
        }
        return @unlink($realTarget);
    }

    private function render404(string $message = 'Halaman tidak ditemukan.'): void
    {
        http_response_code(404);
        echo '<div style="font-family:sans-serif;padding:2rem;text-align:center;">';
        echo '<h2>404 - Tidak Ditemukan</h2>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '<a href="' . BASE_URL . '/document-templates" style="display:inline-block;padding:0.5rem 1rem;background:#064e3b;color:#fff;text-decoration:none;border-radius:6px;">Kembali ke Template Dokumen</a>';
        echo '</div>';
        exit;
    }
}

