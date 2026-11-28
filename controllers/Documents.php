<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../core/SignatureImageValidator.php';
require_once __DIR__ . '/../core/StoragePath.php';
require_once __DIR__ . '/../models/DocumentsModel.php';
require_once __DIR__ . '/../models/CivitasModel.php';
require_once __DIR__ . '/../models/DepartmentsModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/SignaturesModel.php';
require_once __DIR__ . '/../models/UserSignaturesModel.php';
require_once __DIR__ . '/../models/UsersModel.php';

use Ramsey\Uuid\Uuid;

/**
 * Documents Controller
 * Mengelola pengajuan dokumen, penataan slot penandatangan berjenjang,
 * preview/download file PDF via streaming, dashboard dokumen, serta
 * penandatanganan berjenjang & visual (Task 7).
 *
 * Rute:
 *   GET  /documents              → index()    Dashboard dokumen
 *   GET  /documents/create       → create()   Form pengajuan baru
 *   POST /documents/store        → store()    Simpan pengajuan + upload PDF
 *   GET  /documents/{id}         → detail()   Detail dokumen
 *   GET  /documents/{id}/arrange → arrange()  Penataan slot tanda tangan
 *   POST /documents/{id}/update  → update()   Simpan slot koordinat & submit
 *   POST /documents/{id}/delete  → delete()   Soft delete dokumen draft
 *   GET  /documents/{id}/preview → preview()  Streaming file PDF
 *   GET+POST /documents/{id}/sign   → sign()  Halaman & proses penandatanganan
 *   POST /documents/{id}/decline    → decline() Penolakan oleh penandatangan
 */
class Documents
{
    private $load;
    private $model;
    private $civitasModel;
    private $auditModel;
    private $signaturesModel;
    private $signatureModel;
    private $usersModel;
    private $userId;
    private $user;
    private $isAdmin;
    private $storageDir;

    /** Batas ukuran upload PDF: 10 MB */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /** Batas wajar jumlah penandatangan berjenjang per dokumen (mencegah amplifikasi DOM/memori) */
    private const MAX_SEQUENTIAL_SIGNERS = 20;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Guard: harus login
        if (empty($_SESSION['user'])) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Silakan masuk terlebih dahulu.',
            ];
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }

        $this->load           = new Loader();
        $this->model          = new DocumentsModel();
        $this->civitasModel   = new CivitasModel();
        $this->auditModel     = new AuditLogModel();
        $this->signaturesModel = new SignaturesModel();
        $this->signatureModel = new UserSignaturesModel();
        $this->usersModel     = new UsersModel();
        $this->user           = $_SESSION['user'];
        $this->userId         = $_SESSION['user']['id'];
        $this->isAdmin        = ($_SESSION['user']['role'] ?? '') === 'admin';
        $this->storageDir     = __DIR__ . '/../storage';

        // Pastikan direktori storage/documents/ ada
        $docDir = $this->storageDir . '/documents';
        if (!is_dir($docDir)) {
            mkdir($docDir, 0755, true);
        }

        // Pastikan direktori salinan tanda tangan terisolasi ada (BUG-06)
        $signatureDir = $this->storageDir . '/signatures/documents';
        if (!is_dir($signatureDir)) {
            mkdir($signatureDir, 0755, true);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents — Dashboard
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        if ($this->isAdmin) {
            // Admin: tampilkan semua dokumen & stat
            $statusFilter = $_GET['status'] ?? null;
            $allowedStatuses = ['draft', 'pending_review', 'approved', 'signing', 'completed', 'rejected'];
            if ($statusFilter !== null && !in_array($statusFilter, $allowedStatuses, true)) {
                $statusFilter = null;
            }

            $documents    = $this->model->getAll($statusFilter);
            $docCounts    = $this->model->countByStatusAll();
            $civitasCounts = $this->civitasModel->countByType();

            $this->load->view('views/documents/index.php', [
                'title'        => 'Dashboard Dokumen — Administrator',
                'user'         => $this->user,
                'documents'    => $documents,
                'docCounts'    => $docCounts,
                'civitasCount' => $civitasCounts['total'] ?? 0,
                'statusFilter' => $statusFilter,
            ]);
        } else {
            // User biasa: dokumen milik sendiri + dokumen yang perlu ditandatangani
            $statusFilter = $_GET['status'] ?? null;
            $allowedStatuses = ['draft', 'pending_review', 'approved', 'signing', 'completed', 'rejected'];
            if ($statusFilter !== null && !in_array($statusFilter, $allowedStatuses, true)) {
                $statusFilter = null;
            }

            $myDocuments      = $this->model->getByUploaderId($this->userId, $statusFilter);
            $assignedDocs     = $this->model->getAssignedSignForUser($this->userId, $statusFilter);
            $docCounts        = $this->model->countByStatusForUser($this->userId);
            $pendingSignCount = $this->model->countPendingSignForUser($this->userId);
            $pendingSignDocs  = $this->model->getPendingSignForUser($this->userId);
            $waitingSignDocs  = $this->model->getWaitingSignForUser($this->userId);

            $this->load->view('views/documents/index.php', [
                'title'            => 'Dokumen Digital Saya',
                'user'             => $this->user,
                'documents'        => $myDocuments,
                'assignedDocs'     => $assignedDocs,
                'docCounts'        => $docCounts,
                'pendingSignCount' => $pendingSignCount,
                'pendingSignDocs'  => $pendingSignDocs,
                'waitingSignDocs'  => $waitingSignDocs,
                'statusFilter'     => $statusFilter,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents/create — Form Pengajuan Dokumen
    // ─────────────────────────────────────────────────────────────
    public function create()
    {
        // Hanya user non-admin yang boleh mengajukan dokumen
        if ($this->isAdmin) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Administrator tidak dapat mengajukan dokumen. Silakan login sebagai user.'
            ];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        // Ambil daftar user yang bisa dipilih sebagai penandatangan beserta data jurusan & prodi untuk filter
        $availableSigners = $this->model->getAvailableSigners(null, $this->userId);
        $deptModel        = new DepartmentsModel();
        $departments      = $deptModel->getAllDepartments();
        $studyPrograms    = $deptModel->getAllStudyPrograms();

        $this->load->view('views/documents/form.php', [
            'title'            => 'Ajukan Dokumen Baru',
            'availableSigners' => $availableSigners,
            'departments'      => $departments,
            'studyPrograms'    => $studyPrograms,
            'values'           => [
                'title'          => '',
                'description'    => '',
                'signature_type' => 'sequential',
            ],
            'errors' => [],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/store — Simpan Dokumen Baru + Upload PDF
    // ─────────────────────────────────────────────────────────────
    public function store()
    {
        if ($this->isAdmin) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Administrator tidak dapat mengajukan dokumen.'];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        $title         = isset($_POST['title']) && is_string($_POST['title']) ? trim($_POST['title']) : '';
        $description   = (isset($_POST['description']) && is_string($_POST['description'])) ? (trim($_POST['description']) ?: null) : null;
        $signatureType = isset($_POST['signature_type']) && is_string($_POST['signature_type']) ? $_POST['signature_type'] : 'sequential';
        $errors        = [];

        // Validasi judul
        if ($title === '') {
            $errors['title'] = 'Judul dokumen wajib diisi.';
        } elseif (mb_strlen($title) > 500) {
            $errors['title'] = 'Judul dokumen maksimal 500 karakter.';
        }

        // Validasi tipe tanda tangan
        if (!in_array($signatureType, ['single', 'sequential'], true)) {
            $errors['signature_type'] = 'Tipe tanda tangan tidak valid.';
            $signatureType = 'sequential';
        }

        // Validasi file PDF
        $uploadedFile = null;
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['pdf_file'] = 'Berkas dokumen PDF wajib diunggah.';
        } elseif ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas yang diizinkan server.',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas formulir.',
                UPLOAD_ERR_PARTIAL    => 'File hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Direktori temporary server tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk server.',
            ];
            $errors['pdf_file'] = $uploadErrors[$_FILES['pdf_file']['error']]
                ?? 'Terjadi kesalahan saat mengunggah berkas.';
        } else {
            $uploadedFile = $_FILES['pdf_file'];

            // Validasi ukuran (max 10 MB)
            if ($uploadedFile['size'] > self::MAX_FILE_SIZE) {
                $errors['pdf_file'] = 'Ukuran berkas PDF melebihi batas maksimal 10 MB.';
            }

            // Validasi MIME type
            if (empty($errors['pdf_file'])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $uploadedFile['tmp_name']);
                finfo_close($finfo);

                if ($mime !== 'application/pdf') {
                    $errors['pdf_file'] = 'Berkas yang diunggah harus berformat PDF (.pdf).';
                }
            }
        }

        // Validasi penandatangan (sequential wajib ≥ 1 penandatangan; single mengabaikan input penandatangan)
        $signerUserIds    = $_POST['signer_user_id'] ?? [];
        $signerRoles      = $_POST['signer_role_title'] ?? [];
        $validSigners     = [];
        $availableSigners = ($signatureType === 'sequential')
            ? $this->model->getAvailableSigners(null, $this->userId)
            : [];

        if ($signatureType === 'sequential') {
            $allowedSignerMap  = array_fill_keys(array_column($availableSigners, 'id'), true);
            $hasSelfSigner     = false;
            $hasInvalidSigner  = false;
            $hasTooManySigners = false;
            $hasRoleTooLong    = false;
            $rawScanned        = 0;

            // Filter baris kosong & paksa penomoran step_order rapat 1..N di sisi server (Anti Step-Skip / VULN-01)
            if (is_array($signerUserIds)) {
                foreach ($signerUserIds as $idx => $uid) {
                    $rawScanned++;
                    if ($rawScanned > 200) {
                        $hasTooManySigners = true;
                        break;
                    }
                    if (!is_string($uid)) {
                        continue;
                    }
                    $uid = trim($uid);
                    if ($uid !== '') {
                        if (count($validSigners) >= self::MAX_SEQUENTIAL_SIGNERS) {
                            $hasTooManySigners = true;
                            break;
                        }

                        $rawRole = (isset($signerRoles[$idx]) && is_string($signerRoles[$idx])) ? trim($signerRoles[$idx]) : '';
                        if (mb_strlen($rawRole) > 150) {
                            $hasRoleTooLong = true;
                        }

                        if ($uid === $this->userId) {
                            $hasSelfSigner = true;
                        } elseif (!isset($allowedSignerMap[$uid])) {
                            $hasInvalidSigner = true;
                        }

                        $validSigners[] = [
                            'user_id'    => $uid,
                            'role_title' => $rawRole !== '' ? mb_substr($rawRole, 0, 200) : null,
                            'step_order' => count($validSigners) + 1,
                        ];
                    }
                }
            }

            $signerUids   = array_column($validSigners, 'user_id');
            $hasDuplicate = count($signerUids) !== count(array_unique($signerUids));

            if (empty($validSigners)) {
                $errors['signers'] = 'Tanda tangan berjenjang memerlukan minimal 1 penandatangan.';
            } elseif ($hasTooManySigners) {
                $errors['signers'] = 'Jumlah penandatangan berjenjang maksimal ' . self::MAX_SEQUENTIAL_SIGNERS . ' orang dalam satu dokumen.';
            } elseif ($hasSelfSigner) {
                $errors['signers'] = 'Pengaju dokumen tidak dapat memilih dirinya sendiri pada alur berjenjang. Gunakan tipe Tanda Tangan Mandiri (Single-Sign) untuk menandatangani dokumen sendiri.';
            } elseif ($hasInvalidSigner) {
                $errors['signers'] = 'Terdapat penandatangan yang tidak valid, tidak aktif, atau tidak berwenang menandatangani dokumen.';
            } elseif ($hasDuplicate) {
                $errors['signers'] = 'Setiap penandatangan hanya boleh dipilih satu kali.';
            } elseif ($hasRoleTooLong) {
                $errors['signers'] = 'Jabatan / peran penandatangan maksimal 150 karakter.';
            }
        }

        // Jika ada error, render ulang form
        if (!empty($errors)) {
            if ($signatureType !== 'sequential') {
                $availableSigners = $this->model->getAvailableSigners(null, $this->userId);
            }
            $deptModel = new DepartmentsModel();
            $this->load->view('views/documents/form.php', [
                'title'            => 'Ajukan Dokumen Baru',
                'availableSigners' => $availableSigners,
                'departments'      => $deptModel->getAllDepartments(),
                'studyPrograms'    => $deptModel->getAllStudyPrograms(),
                'values'           => [
                    'title'          => $title,
                    'description'    => $description ?? '',
                    'signature_type' => $signatureType,
                ],
                'errors'         => $errors,
                'postedSigners'  => $validSigners ?? [],
            ]);
            return;
        }

        // ── Simpan file PDF ke storage/documents/ ──
        $rawOrigName  = is_string($uploadedFile['name'] ?? null) ? trim(str_replace("\0", '', mb_scrub($uploadedFile['name'], 'UTF-8'))) : 'document.pdf';
        if ($rawOrigName === '') {
            $rawOrigName = 'document.pdf';
        }
        if (mb_strlen($rawOrigName) > 255) {
            $originalName = mb_substr($rawOrigName, 0, 251) . '.pdf';
        } else {
            $originalName = $rawOrigName;
        }
        $uniqueName   = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $destPath     = $this->storageDir . '/documents/' . $uniqueName;
        $dbPath       = 'documents/' . $uniqueName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destPath)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan berkas PDF ke storage server.'];
            header('Location: ' . BASE_URL . '/documents/create');
            exit;
        }

        // Hitung SHA-256 hash
        $fileHash = hash_file('sha256', $destPath);

        // ── Simpan ke database (transaction) ──
        try {
            global $conn;
            $conn->beginTransaction();

            $docId = $this->model->create([
                'uploader_id'        => $this->userId,
                'title'              => $title,
                'description'        => $description,
                'file_path'          => $dbPath,
                'file_name_original' => $originalName,
                'file_size'          => $uploadedFile['size'],
                'file_hash'          => $fileHash,
                'signature_type'     => $signatureType,
            ]);

            // Tambah penandatangan (jika sequential)
            if ($signatureType === 'sequential' && !empty($validSigners)) {
                foreach ($validSigners as $signer) {
                    $this->model->addSigner([
                        'document_id' => $docId,
                        'user_id'     => $signer['user_id'],
                        'invited_by'  => $this->userId,
                        'step_order'  => $signer['step_order'],
                        'role_title'  => $signer['role_title'],
                    ]);
                }
            } elseif ($signatureType === 'single') {
                // Untuk Single-Sign, pengaju adalah penandatangan tunggal (memenuhi FK signatures.document_signer_id)
                $this->model->addSigner([
                    'document_id' => $docId,
                    'user_id'     => $this->userId,
                    'invited_by'  => $this->userId,
                    'step_order'  => 1,
                    'role_title'  => $this->user['position'] ?? 'Pemohon / Pengaju',
                ]);
            }

            // Audit log
            $this->auditModel->log($this->userId, 'document.create', [
                'document_id'    => $docId,
                'title'          => $title,
                'signature_type' => $signatureType,
                'file_name'      => $originalName,
                'file_size'      => $uploadedFile['size'],
                'signers_count'  => $signatureType === 'sequential' ? count($validSigners) : 1,
            ], $docId);

            $conn->commit();

            // Redirect ke penataan slot tanda tangan
            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => $signatureType === 'sequential'
                    ? 'Dokumen berhasil diunggah. Silakan atur posisi slot tanda tangan setiap penandatangan.'
                    : 'Dokumen mandiri berhasil diunggah. Silakan tentukan letak slot tanda tangan Anda.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $docId . '/arrange');
            exit;
        } catch (\Throwable $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            // Hapus file fisik jika gagal
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            error_log('[Documents::store] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menyimpan dokumen. Silakan coba kembali.'
                : 'Gagal menyimpan dokumen: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];
            header('Location: ' . BASE_URL . '/documents/create');
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents/{id} — Detail Dokumen
    // ─────────────────────────────────────────────────────────────
    public function detail($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Akses: admin bisa lihat semua, user hanya milik sendiri atau yang mengundangnya
        $mySigner = null;
        if (!$this->isAdmin) {
            $mySigner = $this->model->getSignerByDocAndUser($id, $this->userId);
            if ($doc['uploader_id'] !== $this->userId && !$mySigner) {
                http_response_code(403);
                echo '<h1>403 — Akses Ditolak</h1><p>Anda tidak memiliki akses ke dokumen ini.</p>';
                return;
            }
        }

        $signers    = $this->model->getSignersByDocumentId($id);
        $signatures = $this->signaturesModel->getByDocumentId($id);
        $auditLog   = $this->auditModel->getByDocumentId($id);

        $this->sendNoCacheHeaders();
        $this->load->view('views/documents/detail.php', [
            'title'      => 'Detail Dokumen — ' . $doc['title'],
            'doc'        => $doc,
            'signers'    => $signers,
            'signatures' => $signatures,
            'auditLog'   => $auditLog,
            'isOwner'    => ($doc['uploader_id'] === $this->userId),
            'isAdmin'    => $this->isAdmin,
            'mySigner'   => $mySigner,
        ]);
    }

    /**
     * Mencegah browser menyimpan halaman state-sensitif di BFCache
     * agar tombol Back tidak menampilkan draft yang sudah dihapus atau diajukan.
     */
    private function sendNoCacheHeaders(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents/{id}/arrange — Penataan Slot Tanda Tangan
    // ─────────────────────────────────────────────────────────────
    public function arrange($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Hanya pengaju yang bisa mengatur slot, dan hanya saat status draft
        if ($doc['uploader_id'] !== $this->userId) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1><p>Hanya pengaju dokumen yang dapat mengatur slot tanda tangan.</p>';
            return;
        }

        if ($doc['status'] !== 'draft') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Slot tanda tangan hanya dapat diatur saat dokumen berstatus Draft.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $signers = $this->model->getSignersByDocumentId($id);

        if (empty($signers)) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Belum ada penandatangan. Silakan buat ulang dokumen.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $this->sendNoCacheHeaders();
        $this->load->view('views/documents/arrange.php', [
            'title'   => 'Atur Slot Tanda Tangan — ' . $doc['title'],
            'doc'     => $doc,
            'signers' => $signers,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/update — Simpan Slot & Submit Dokumen
    // ─────────────────────────────────────────────────────────────
    public function update($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        if ($doc['uploader_id'] !== $this->userId) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1>';
            return;
        }

        // Keamanan State Guard: Slot koordinat hanya dapat dimutasi saat dokumen berstatus Draft
        if ($doc['status'] !== 'draft') {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Slot tanda tangan hanya dapat diatur saat status Draft.'];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $actionType = isset($_POST['action_type']) && is_string($_POST['action_type']) ? $_POST['action_type'] : '';

        // Simpan posisi slot koordinat jika data koordinat dikirimkan (dengan proteksi Anti-IDOR & Boundary Clamping)
        $slotData = $_POST['slots'] ?? [];
        if (is_array($slotData) && !empty($slotData)) {
            try {
                foreach ($slotData as $signerId => $slot) {
                    if (!is_array($slot)) {
                        continue;
                    }
                    $w = max(5.0, min(50.0, is_scalar($slot['width_percent'] ?? null) ? (float) $slot['width_percent'] : 20.0));
                    $h = max(3.0, min(30.0, is_scalar($slot['height_percent'] ?? null) ? (float) $slot['height_percent'] : 10.0));

                    // Penanda eksplisit mencegah posisi pratinjau (fallback tampilan) tersimpan
                    // sebagai slot resmi saat pengguna belum benar-benar menempatkannya.
                    $hasConfiguredFlag = array_key_exists('slot_configured', $slot);
                    $isConfigured = !$hasConfiguredFlag
                        || (is_scalar($slot['slot_configured']) && (string) $slot['slot_configured'] === '1');

                    $x = $isConfigured
                        ? max(0.0, min(100.0 - $w, is_scalar($slot['x_percent'] ?? null) ? (float) $slot['x_percent'] : 0.0))
                        : 0.0;
                    $y = $isConfigured
                        ? max(0.0, min(100.0 - $h, is_scalar($slot['y_percent'] ?? null) ? (float) $slot['y_percent'] : 0.0))
                        : 0.0;
                    $p = max(1, min(10000, is_scalar($slot['page_number'] ?? null) ? (int) $slot['page_number'] : 1));

                    $this->model->updateSignerSlot((string) $signerId, (string) $id, [
                        'page_number'    => $p,
                        'x_percent'      => $x,
                        'y_percent'      => $y,
                        'width_percent'  => $w,
                        'height_percent' => $h,
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('[Documents::update/save_slots] ' . $e->getMessage());
                $_SESSION['flash'] = [
                    'type'    => 'danger',
                    'message' => 'Gagal menyimpan posisi slot tanda tangan.'
                ];
                header('Location: ' . BASE_URL . '/documents/' . $id . '/arrange');
                exit;
            }
        }

        // ── Aksi 1: Simpan slot koordinat saja ──
        if ($actionType === 'save_slots') {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Posisi slot tanda tangan berhasil disimpan.'];
            header('Location: ' . BASE_URL . '/documents/' . $id . '/arrange');
            exit;
        }

        // ── Aksi 2: Submit dokumen (ke Admin jika sequential, atau selesai jika single) ──
        if ($actionType === 'submit') {
            if ($doc['status'] !== 'draft') {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Dokumen ini sudah pernah diajukan.'];
                header('Location: ' . BASE_URL . '/documents/' . $id);
                exit;
            }

            // Validasi: semua slot harus sudah diatur
            $signers = $this->model->getSignersByDocumentId($id);
            $unsetSlots = [];
            foreach ($signers as $s) {
                if ((float) $s['x_percent'] == 0 && (float) $s['y_percent'] == 0) {
                    $unsetSlots[] = $s['signer_name'];
                }
            }

            if (!empty($unsetSlots)) {
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Slot tanda tangan belum diatur untuk: ' . implode(', ', $unsetSlots) . '. Silakan atur posisi terlebih dahulu.'
                ];
                header('Location: ' . BASE_URL . '/documents/' . $id . '/arrange');
                exit;
            }

            try {
                $this->model->submitDocument($id, $doc['signature_type']);

                $isSingle = ($doc['signature_type'] === 'single');
                $this->auditModel->log($this->userId, 'document.submit', [
                    'document_id'   => $id,
                    'title'         => $doc['title'],
                    'status_from'   => 'draft',
                    'status_to'     => $isSingle ? 'signing' : 'pending_review',
                    'signers_count' => count($signers),
                ], $id);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => $isSingle
                        ? 'Dokumen mandiri siap ditandatangani. Silakan bubuhkan tanda tangan Anda pada slot yang tersedia.'
                        : 'Dokumen berhasil diajukan untuk ditinjau oleh Administrator.'
                ];
                // Dokumen mandiri: lanjut langsung ke halaman penandatanganan (Task 7)
                header('Location: ' . BASE_URL . '/documents/' . $id . ($isSingle ? '/sign' : ''));
                exit;
            } catch (\Throwable $e) {
                error_log('[Documents::update/arrange] ' . $e->getMessage());
                $safeMsg = ($e instanceof \PDOException)
                    ? 'Gagal mengajukan dokumen. Silakan coba kembali.'
                    : 'Gagal mengajukan dokumen: ' . $e->getMessage();

                $_SESSION['flash'] = [
                    'type'    => 'danger',
                    'message' => $safeMsg
                ];
                header('Location: ' . BASE_URL . '/documents/' . $id . '/arrange');
                exit;
            }
        }

        // Dukungan approve & reject via method update
        if ($actionType === 'approve') {
            $this->approve($id);
            return;
        }
        if ($actionType === 'reject') {
            $this->reject($id);
            return;
        }

        // Aksi tidak dikenal
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Aksi tidak dikenali.'];
        header('Location: ' . BASE_URL . '/documents/' . $id);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/approve — Setujui Dokumen (Admin Only) (Task 6)
    // ─────────────────────────────────────────────────────────────
    public function approve($id)
    {
        // Guard: Hanya Administrator
        if (!$this->isAdmin) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1><p>Hanya Administrator yang memiliki wewenang untuk menyetujui dokumen.</p>';
            return;
        }

        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Status harus pending_review
        if ($doc['status'] !== 'pending_review') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Hanya dokumen yang berstatus "Menunggu Review" yang dapat disetujui.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        try {
            $this->model->approveDocument($id, $this->userId);

            // Audit log persetujuan dokumen
            $this->auditModel->log($this->userId, 'document.approve', [
                'document_id' => $id,
                'title'       => $doc['title'],
                'reviewer_id' => $this->userId,
                'status_from' => 'pending_review',
                'status_to'   => 'signing',
            ], $id);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Dokumen "' . $doc['title'] . '" berhasil disetujui. Berkas kini diteruskan ke penandatangan urutan pertama.'
            ];
        } catch (\Throwable $e) {
            error_log('[Documents::approve] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menyetujui dokumen. Silakan coba kembali.'
                : 'Terjadi kesalahan saat menyetujui dokumen: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];
        }

        header('Location: ' . BASE_URL . '/documents/' . $id);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/reject — Tolak Dokumen (Admin Only) (Task 6)
    // ─────────────────────────────────────────────────────────────
    public function reject($id)
    {
        // Guard: Hanya Administrator
        if (!$this->isAdmin) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1><p>Hanya Administrator yang memiliki wewenang untuk menolak dokumen.</p>';
            return;
        }

        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Status harus pending_review
        if ($doc['status'] !== 'pending_review') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Hanya dokumen yang berstatus "Menunggu Review" yang dapat ditolak.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $rejectionReason = isset($_POST['rejection_reason']) && is_string($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
        if ($rejectionReason === '') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Alasan penolakan dokumen wajib diisi.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        try {
            $this->model->rejectDocument($id, $this->userId, $rejectionReason);

            // Audit log penolakan dokumen
            $this->auditModel->log($this->userId, 'document.reject', [
                'document_id' => $id,
                'title'       => $doc['title'],
                'reviewer_id' => $this->userId,
                'reason'      => $rejectionReason,
                'status_from' => 'pending_review',
                'status_to'   => 'rejected',
            ], $id);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Dokumen "' . $doc['title'] . '" telah ditolak. Alasan penolakan dicatat permanen pada berkas arsip.'
            ];
        } catch (\Throwable $e) {
            error_log('[Documents::reject] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menolak dokumen. Silakan coba kembali.'
                : 'Terjadi kesalahan saat menolak dokumen: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];
        }

        header('Location: ' . BASE_URL . '/documents/' . $id);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // GET+POST /documents/{id}/sign — Penandatanganan Berjenjang & Visual (Task 7)
    // ─────────────────────────────────────────────────────────────
    public function sign($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Administrator bukan aktor penandatangan
        if ($this->isAdmin) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Administrator tidak berpartisipasi dalam alur penandatanganan dokumen.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        // Pengguna harus menjadi salah satu penandatangan dokumen ini
        $signer = $this->model->getSignerByDocAndUser($id, $this->userId);
        if (!$signer) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1><p>Anda bukan penandatangan yang ditugaskan pada dokumen ini.</p>';
            return;
        }

        // POST → proses penyimpanan tanda tangan
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processSign($doc, $signer);
            return;
        }

        // ── GET: Guard ADR-01 sebelum menampilkan antarmuka ──
        if ($doc['status'] !== 'signing') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Dokumen ini tidak sedang dalam proses penandatanganan.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        if ($signer['status'] === 'signed') {
            $_SESSION['flash'] = [
                'type'    => 'info',
                'message' => 'Anda telah membubuhkan tanda tangan pada dokumen ini.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        if ($signer['status'] === 'declined') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Anda telah menolak dokumen ini. Proses penandatanganan dibatalkan permanen.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        // Guard giliran: hanya penandatangan dengan step_order == current_step
        // dan status 'pending' yang boleh menandatangani (ADR-01)
        if ($signer['status'] !== 'pending' || (int) $signer['step_order'] !== (int) $doc['current_step']) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Belum giliran Anda. Dokumen masih menunggu penandatangan pada Step ' . (int) $doc['current_step'] . '.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $this->load->view('views/documents/sign.php', $this->signViewPayload($doc, $signer));
    }

    /**
     * Proses penyimpanan tanda tangan (POST /documents/{id}/sign).
     * Membuat salinan berkas fisik terisolasi (BUG-06), lalu menjalankan
     * transaksi penandatanganan + advance step otomatis.
     */
    private function processSign(array $doc, array $signer)
    {
        // Guard ADR-01 ulang di sisi POST (pertahanan berlapis)
        if ($doc['status'] !== 'signing') {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Dokumen ini tidak sedang dalam proses penandatanganan.'];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        if ($signer['status'] !== 'pending' || (int) $signer['step_order'] !== (int) $doc['current_step']) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Belum giliran Anda untuk menandatangani dokumen ini.'];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        // Anti double-submit: tanda tangan untuk (dokumen, user) hanya sekali
        if ($this->signaturesModel->getByDocAndSigner($doc['id'], $this->userId)) {
            $_SESSION['flash'] = ['type' => 'info', 'message' => 'Anda telah membubuhkan tanda tangan pada dokumen ini sebelumnya.'];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        // Pastikan akun & biodata civitas penandatangan masih aktif sebelum memproses berkas (anti TOCTOU)
        $userProfile = $this->usersModel->getById($this->userId);
        if (!$userProfile || empty($userProfile['civitas_id']) || empty($userProfile['civitas_name'])) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akun Anda tidak aktif sehingga tidak dapat menandatangani.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        $signatureFormat = isset($_POST['signature_format']) && is_string($_POST['signature_format']) ? trim($_POST['signature_format']) : 'qr_and_signature';
        if (!in_array($signatureFormat, ['qr_and_signature', 'qr_only'], true)) {
            $signatureFormat = 'qr_and_signature';
        }

        $inputMethod = isset($_POST['input_method']) && is_string($_POST['input_method']) ? trim($_POST['input_method']) : 'canvas';
        $errors      = [];

        $signatureId       = Uuid::uuid4()->toString();
        $verificationToken = Uuid::uuid4()->toString();
        $destPath          = null;
        $dbPath            = null;

        if ($signatureFormat === 'qr_only') {
            // Format Hanya Kode QR: tidak memerlukan berkas fisik tanda tangan
            $dbPath = null;
        } else {
            // Format QR + Tanda Tangan + Nama: buat salinan fisik terisolasi (BUG-06)
            $signatureDir = $this->storageDir . '/signatures/documents';
            if (!is_dir($signatureDir)) {
                mkdir($signatureDir, 0755, true);
            }
            $destPath = $signatureDir . '/' . $signatureId . '.png';
            $dbPath   = 'signatures/documents/' . $signatureId . '.png';

            if ($inputMethod === 'template') {
                // Menggunakan template tersimpan milik user → salin (copy) agar independen
                $templateId = isset($_POST['template_id']) && is_string($_POST['template_id']) ? trim($_POST['template_id']) : '';
                $template   = $this->signatureModel->getById($templateId, $this->userId);
                if (!$template) {
                    $errors['signature'] = 'Template tanda tangan tidak valid atau bukan milik Anda.';
                } else {
                    $srcPath = StoragePath::resolve(
                        (string) ($template['image_path'] ?? ''),
                        'signatures/templates',
                        ['png'],
                        $this->storageDir
                    );
                    if ($srcPath === null) {
                        $errors['signature'] = 'Berkas template tanda tangan tidak tersedia di storage server.';
                    } else {
                        $tplErr = SignatureImageValidator::validateExistingFile($srcPath);
                        if ($tplErr !== null) {
                            $errors['signature'] = $tplErr;
                        } elseif (!copy($srcPath, $destPath)) {
                            $errors['signature'] = 'Gagal membuat salinan independen tanda tangan di storage server.';
                        }
                    }
                }
            } elseif ($inputMethod === 'canvas') {
                // Goresan kanvas baru → validasi batas ukuran & dimensi PNG sebelum dekode GD (VULN-07)
                $canvasData = isset($_POST['signature_canvas']) && is_string($_POST['signature_canvas']) ? $_POST['signature_canvas'] : '';
                $sigErr     = SignatureImageValidator::validateAndSaveCanvas($canvasData, $destPath);
                if ($sigErr !== null) {
                    $errors['signature'] = $sigErr;
                }
            } elseif ($inputMethod === 'upload') {
                // Unggah PNG transparan sekali pakai → validasi dimensi (VULN-07) & alpha channel (BUG-05)
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
        }

        // Jika ada error validasi, bersihkan berkas sementara bila sempat terbuat lalu render ulang
        if (!empty($errors)) {
            if ($destPath !== null && is_file($destPath)) {
                @unlink($destPath);
            }
            $payload = $this->signViewPayload($doc, $signer);
            $payload['errors']      = $errors;
            $payload['postedInput'] = $inputMethod;
            $this->load->view('views/documents/sign.php', $payload);
            return;
        }

        // Posisi halaman dan koordinat slot tanda tangan dikunci sesuai pengaturan Pengaju saat Draft (ADR-08)
        $effectivePage   = max(1, min(10000, (int) ($signer['page_number'] ?: 1)));
        $effectiveX      = (float) $signer['x_percent'];
        $effectiveY      = (float) $signer['y_percent'];
        $effectiveWidth  = (float) $signer['width_percent'] > 0 ? (float) $signer['width_percent'] : 20.0;
        $effectiveHeight = (float) $signer['height_percent'] > 0 ? (float) $signer['height_percent'] : 10.0;

        try {
            $result = $this->signaturesModel->signDocument([
                'id'                         => $signatureId,
                'verification_token'         => $verificationToken,
                'document_id'                => $doc['id'],
                'signer_id'                  => $this->userId,
                'civitas_id'                 => $userProfile['civitas_id'] ?? null,
                'document_signer_id'         => $signer['id'],
                'signature_format'           => $signatureFormat,
                'signature_image_path'       => $dbPath,
                'page_number'                => $effectivePage,
                'x_percent'                  => $effectiveX,
                'y_percent'                  => $effectiveY,
                'width_percent'              => $effectiveWidth,
                'height_percent'             => $effectiveHeight,
                'signer_name_snapshot'       => $userProfile['civitas_name'] ?: $userProfile['name'],
                'signer_identifier_snapshot' => $userProfile['civitas_identifier'] ?? null,
                'signer_type_snapshot'       => $userProfile['civitas_type'] ?? null,
                'signer_department_snapshot' => $userProfile['civitas_department'] ?? null,
                'signer_position_snapshot'   => $userProfile['civitas_position'] ?? null,
                'signer_email_snapshot'      => $userProfile['email'],
                'ip_address'                 => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Jejak audit penandatanganan
            $this->auditModel->log($this->userId, 'document.signed', [
                'document_id'        => $doc['id'],
                'title'              => $doc['title'],
                'signature_id'       => $signatureId,
                'signer_name'        => $userProfile['civitas_name'] ?: $userProfile['name'],
                'step_order'         => (int) $signer['step_order'],
                'verification_token' => $verificationToken,
                'format'             => $signatureFormat,
                'method'             => $signatureFormat === 'qr_only' ? 'qr_only' : $inputMethod,
            ], $doc['id']);

            if ($result === 'completed') {
                $this->auditModel->log($this->userId, 'document.completed', [
                    'document_id' => $doc['id'],
                    'title'       => $doc['title'],
                    'final_step'  => (int) $signer['step_order'],
                ], $doc['id']);

                // Task 9: Generate berkas PDF final berstempel seketika saat dokumen rampung
                require_once __DIR__ . '/../core/PdfStamper.php';
                try {
                    PdfStamper::stampDocument($doc['id'], $this->userId);
                } catch (\Throwable $se) {
                    error_log("Otomatisasi stamping dokumen {$doc['id']} gagal: " . $se->getMessage());
                }

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Tanda tangan berhasil. Dokumen selesai.'
                ];
            } else {
                $updatedDoc = $this->model->getById($doc['id']);
                $nextStep   = isset($updatedDoc['current_step'])
                    ? (int) $updatedDoc['current_step']
                    : ((int) $doc['current_step'] + 1);
                $this->auditModel->log($this->userId, 'document.step_advanced', [
                    'document_id' => $doc['id'],
                    'title'       => $doc['title'],
                    'step_from'   => (int) $doc['current_step'],
                    'step_to'     => $nextStep,
                ], $doc['id']);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Tanda tangan berhasil. Giliran diteruskan ke Tahap ' . $nextStep . '.'
                ];
            }
        } catch (\Throwable $e) {
            // Hapus salinan fisik bila transaksi gagal
            if ($destPath !== null && file_exists($destPath)) {
                @unlink($destPath);
            }

            error_log('[Documents::sign] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menyimpan tanda tangan. Silakan coba kembali.'
                : 'Gagal menyimpan tanda tangan: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];
        }

        header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
        exit;
    }

    /**
     * Kumpulan data untuk merender halaman penandatanganan (sign.php).
     */
    private function signViewPayload(array $doc, array $signer): array
    {
        return [
            'title'            => 'Tanda Tangani Dokumen — ' . $doc['title'],
            'doc'              => $doc,
            'signer'           => $signer,
            'templates'        => $this->signatureModel->getByUserId($this->userId),
            'defaultTemplate'  => $this->signatureModel->getDefaultByUserId($this->userId),
            'userProfile'      => $this->usersModel->getById($this->userId),
            'signaturesPlaced' => $this->signaturesModel->getByDocumentId($doc['id']),
            'allSigners'       => $this->model->getSignersByDocumentId($doc['id']),
            'errors'           => [],
            'postedInput'      => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/decline — Penolakan oleh Penandatangan (Task 7)
    // ─────────────────────────────────────────────────────────────
    public function decline($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Administrator bukan aktor penandatangan
        if ($this->isAdmin) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Administrator tidak berpartisipasi dalam alur penandatanganan dokumen.'];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $signer = $this->model->getSignerByDocAndUser($id, $this->userId);
        if (!$signer) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1><p>Anda bukan penandatangan yang ditugaskan pada dokumen ini.</p>';
            return;
        }

        // Guard ADR-01: hanya penandatangan giliran aktif yang boleh menolak
        if ($doc['status'] !== 'signing' || $signer['status'] !== 'pending' || (int) $signer['step_order'] !== (int) $doc['current_step']) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Belum giliran Anda untuk mengambil aksi pada dokumen ini.'];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        // Alasan penolakan wajib minimal 10 karakter (ADR-01 kebijakan penolakan)
        $reason = isset($_POST['decline_reason']) && is_string($_POST['decline_reason']) ? trim($_POST['decline_reason']) : '';
        if (mb_strlen($reason) < 10) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Alasan penolakan wajib diisi minimal 10 karakter.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id . '/sign');
            exit;
        }

        try {
            $this->signaturesModel->declineDocument($doc['id'], $signer['id'], $reason);

            $this->auditModel->log($this->userId, 'document.declined', [
                'document_id' => $doc['id'],
                'title'       => $doc['title'],
                'step_order'  => (int) $signer['step_order'],
                'reason'      => $reason,
                'status_to'   => 'rejected',
            ], $doc['id']);

            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Anda telah menolak dokumen "' . $doc['title'] . '". Dokumen berstatus Arsip Batal Permanen.'
            ];
        } catch (\Throwable $e) {
            error_log('[Documents::decline] ' . $e->getMessage());
            $safeMsg = ($e instanceof \PDOException)
                ? 'Gagal menolak dokumen. Silakan coba kembali.'
                : 'Terjadi kesalahan saat menolak dokumen: ' . $e->getMessage();

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => $safeMsg
            ];
        }

        header('Location: ' . BASE_URL . '/documents/' . $id);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/delete — Soft Delete Dokumen Draft
    // ─────────────────────────────────────────────────────────────
    public function delete($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Hanya pengaju pemilik dokumen yang berhak membatalkan draft pribadinya
        if ($doc['uploader_id'] !== $this->userId) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1>';
            return;
        }

        if ($doc['status'] !== 'draft') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Hanya dokumen berstatus Draft yang dapat dibatalkan.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        try {
            $deleted = $this->model->delete($doc['id']);
            if (!$deleted) {
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Draft tidak dapat dibatalkan karena statusnya sudah berubah.'
                ];
                header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
                exit;
            }

            $physicalDeleted = false;
            if (!empty($doc['file_path']) && $this->model->countActiveByFilePath((string) $doc['file_path']) === 0) {
                $realTarget = StoragePath::resolve(
                    (string) $doc['file_path'],
                    'documents',
                    ['pdf'],
                    $this->storageDir,
                    false
                );
                if ($realTarget !== null) {
                    $physicalDeleted = @unlink($realTarget);
                }
            }

            $this->auditModel->log($this->userId, 'document.delete', [
                'document_id'           => $doc['id'],
                'title'                 => $doc['title'],
                'physical_file_deleted' => $physicalDeleted,
            ], $doc['id']);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Draft dokumen "' . $doc['title'] . '" berhasil dibatalkan dan dihapus.'
            ];

            $redirectTo = $_POST['redirect_to'] ?? 'documents';
            if ($redirectTo === 'create' && !$this->isAdmin) {
                header('Location: ' . BASE_URL . '/documents/create');
                exit;
            }

            $targetUrl = BASE_URL . '/documents';
            $validStatuses = ['draft', 'pending_review', 'approved', 'signing', 'completed', 'rejected'];
            $statusFilter = $_POST['status_filter'] ?? '';
            if (in_array($statusFilter, $validStatuses, true)) {
                $targetUrl .= '?status=' . rawurlencode($statusFilter);
            }

            header('Location: ' . $targetUrl);
            exit;
        } catch (\Throwable $e) {
            error_log('[Documents::delete] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat membatalkan draft dokumen. Silakan coba kembali.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // POST /documents/{id}/withdraw — Tarik Pengajuan ke Draft
    // ─────────────────────────────────────────────────────────────
    public function withdraw($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            echo '<h1>404 — Dokumen Tidak Ditemukan</h1>';
            return;
        }

        // Hanya pengaju pemilik dokumen yang berhak menarik pengajuannya
        if ($doc['uploader_id'] !== $this->userId) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1>';
            return;
        }

        if ($doc['status'] !== 'pending_review') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Hanya dokumen yang menunggu review yang bisa ditarik ke Draft.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }

        try {
            $withdrawn = $this->model->withdrawToDraft($doc['id']);
            if (!$withdrawn) {
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Pengajuan tidak dapat ditarik karena sudah diproses Administrator.'
                ];
                header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
                exit;
            }

            $this->auditModel->log($this->userId, 'document.withdraw', [
                'document_id' => $doc['id'],
                'title'       => $doc['title'],
                'status_from' => 'pending_review',
                'status_to'   => 'draft',
            ], $doc['id']);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Pengajuan dokumen "' . $doc['title'] . '" berhasil ditarik kembali ke Draft. Silakan sesuaikan posisi slot atau batalkan draft.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id'] . '/arrange');
            exit;
        } catch (\Throwable $e) {
            error_log('[Documents::withdraw] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat menarik pengajuan dokumen. Silakan coba kembali.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $doc['id']);
            exit;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents/{id}/preview — Streaming PDF Privat
    // ─────────────────────────────────────────────────────────────
    public function preview($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            exit('Dokumen tidak ditemukan.');
        }

        // Akses: admin, pengaju, atau penandatangan
        if (!$this->isAdmin && $doc['uploader_id'] !== $this->userId) {
            $signer = $this->model->getSignerByDocAndUser($id, $this->userId);
            if (!$signer) {
                http_response_code(403);
                exit('Akses ditolak.');
            }

            // Catat read_at jika belum pernah dibaca oleh penandatangan ini
            if (empty($signer['read_at'])) {
                global $conn;
                $stmt = $conn->prepare(
                    "UPDATE document_signers SET read_at = NOW() WHERE id = ? AND read_at IS NULL"
                );
                $stmt->execute([$signer['id']]);
            }
        }

        $targetFileRel = $doc['file_path'];
        $displayName   = $doc['file_name_original'];

        // Jika dokumen completed, sajikan berkas final berstempel (Task 9)
        if ($doc['status'] === 'completed') {
            $finalRealPath = !empty($doc['final_file_path'])
                ? StoragePath::resolve((string) $doc['final_file_path'], 'documents', ['pdf'], $this->storageDir)
                : null;
            if ($finalRealPath === null) {
                require_once __DIR__ . '/../core/PdfStamper.php';
                $stampRes = PdfStamper::stampDocument($id, $this->userId);
                if ($stampRes['success']) {
                    $doc = $this->model->getById($id);
                    $finalRealPath = !empty($doc['final_file_path'])
                        ? StoragePath::resolve((string) $doc['final_file_path'], 'documents', ['pdf'], $this->storageDir)
                        : null;
                }
            }
            if ($finalRealPath !== null) {
                $targetFileRel = $doc['final_file_path'];
                $displayName   = 'FINAL_' . $doc['file_name_original'];
            }
        }

        $realFile = StoragePath::resolve((string) ($targetFileRel ?? ''), 'documents', ['pdf'], $this->storageDir);
        if ($realFile === null) {
            http_response_code(404);
            exit('Berkas fisik PDF tidak tersedia di storage server.');
        }

        $cleanDisplayName = preg_replace('/[\x00-\x1F\x7F"]/', '', basename($displayName));

        // Streaming respon PDF
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($realFile));
        header('Content-Disposition: inline; filename="' . $cleanDisplayName . '"');
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($realFile);
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // GET /documents/{id}/download — Download PDF
    // ─────────────────────────────────────────────────────────────
    public function download($id)
    {
        $doc = $this->model->getById($id);
        if (!$doc) {
            http_response_code(404);
            exit('Dokumen tidak ditemukan.');
        }

        // Akses: admin, pengaju, atau penandatangan
        if (!$this->isAdmin && $doc['uploader_id'] !== $this->userId) {
            $signer = $this->model->getSignerByDocAndUser($id, $this->userId);
            if (!$signer) {
                http_response_code(403);
                exit('Akses ditolak.');
            }
        }

        $targetFileRel = $doc['file_path'];
        $downloadName  = basename($doc['file_name_original']);
        $servedFinal   = false;

        $isOriginalRequested = (isset($_GET['type']) && $_GET['type'] === 'original');

        // Jika dokumen completed dan tidak meminta berkas asli, download berkas final berstempel (Task 9)
        if ($doc['status'] === 'completed' && !$isOriginalRequested) {
            $finalRealPath = !empty($doc['final_file_path'])
                ? StoragePath::resolve((string) $doc['final_file_path'], 'documents', ['pdf'], $this->storageDir)
                : null;
            if ($finalRealPath === null) {
                require_once __DIR__ . '/../core/PdfStamper.php';
                $stampRes = PdfStamper::stampDocument($id, $this->userId);
                if ($stampRes['success']) {
                    $doc = $this->model->getById($id);
                    $finalRealPath = !empty($doc['final_file_path'])
                        ? StoragePath::resolve((string) $doc['final_file_path'], 'documents', ['pdf'], $this->storageDir)
                        : null;
                }
            }
            if ($finalRealPath !== null) {
                $targetFileRel = $doc['final_file_path'];
                $downloadName  = 'FINAL_' . basename($doc['file_name_original']);
                $servedFinal   = true;
            }
        }

        $realFile = StoragePath::resolve((string) ($targetFileRel ?? ''), 'documents', ['pdf'], $this->storageDir);
        if ($realFile === null) {
            http_response_code(404);
            exit('Berkas fisik PDF tidak tersedia.');
        }

        // Jejak audit pengunduhan dokumen (NFR-1 / NFR-3)
        $this->auditModel->log($this->userId, 'document.download', [
            'document_id' => $id,
            'title'       => $doc['title'],
            'is_final'    => $servedFinal,
            'file_name'   => $downloadName
        ], $id);

        $cleanDownloadName = preg_replace('/[\x00-\x1F\x7F"]/', '', $downloadName);

        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($realFile));
        header('Content-Disposition: attachment; filename="' . $cleanDownloadName . '"');
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($realFile);
        exit;
    }
}
