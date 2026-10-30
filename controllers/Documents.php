<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/DocumentsModel.php';
require_once __DIR__ . '/../models/CivitasModel.php';
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

            $myDocuments     = $this->model->getByUploaderId($this->userId, $statusFilter);
            $docCounts       = $this->model->countByStatusForUser($this->userId);
            $pendingSignCount = $this->model->countPendingSignForUser($this->userId);
            $pendingSignDocs = $this->model->getPendingSignForUser($this->userId);
            $waitingSignDocs = $this->model->getWaitingSignForUser($this->userId);

            $this->load->view('views/documents/index.php', [
                'title'            => 'Dokumen Digital Saya',
                'user'             => $this->user,
                'documents'        => $myDocuments,
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

        // Ambil daftar user yang bisa dipilih sebagai penandatangan
        $availableSigners = $this->model->getAvailableSigners(null, $this->userId);

        $this->load->view('views/documents/form.php', [
            'title'            => 'Ajukan Dokumen Baru',
            'availableSigners' => $availableSigners,
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

        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '') ?: null;
        $signatureType = $_POST['signature_type'] ?? 'sequential';
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

        // Validasi penandatangan (sequential wajib ≥ 1 penandatangan)
        $signerUserIds  = $_POST['signer_user_id'] ?? [];
        $signerRoles    = $_POST['signer_role_title'] ?? [];
        $signerSteps    = $_POST['signer_step_order'] ?? [];

        if ($signatureType === 'sequential') {
            // Filter baris kosong
            $validSigners = [];
            if (is_array($signerUserIds)) {
                foreach ($signerUserIds as $idx => $uid) {
                    $uid = trim($uid);
                    if ($uid !== '') {
                        $validSigners[] = [
                            'user_id'    => $uid,
                            'role_title' => trim($signerRoles[$idx] ?? '') ?: null,
                            'step_order' => max(1, (int) ($signerSteps[$idx] ?? ($idx + 1))),
                        ];
                    }
                }
            }

            if (empty($validSigners)) {
                $errors['signers'] = 'Tanda tangan berjenjang memerlukan minimal 1 penandatangan.';
            }

            // Cek duplikat user_id
            $signerUids = array_column($validSigners, 'user_id');
            if (count($signerUids) !== count(array_unique($signerUids))) {
                $errors['signers'] = 'Setiap penandatangan hanya boleh dipilih satu kali.';
            }
        }

        // Jika ada error, render ulang form
        if (!empty($errors)) {
            $availableSigners = $this->model->getAvailableSigners(null, $this->userId);
            $this->load->view('views/documents/form.php', [
                'title'            => 'Ajukan Dokumen Baru',
                'availableSigners' => $availableSigners,
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
        $originalName = $uploadedFile['name'];
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

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menyimpan dokumen: ' . $e->getMessage()
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
                'message' => 'Tidak ada penandatangan yang ditambahkan. Silakan buat ulang dokumen dengan penandatangan.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

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

        $actionType = $_POST['action_type'] ?? '';

        // Simpan posisi slot koordinat jika data koordinat dikirimkan (dengan proteksi Anti-IDOR & Boundary Clamping)
        $slotData = $_POST['slots'] ?? [];
        if (is_array($slotData) && !empty($slotData)) {
            foreach ($slotData as $signerId => $slot) {
                $w = max(5.0, min(50.0, (float) ($slot['width_percent'] ?? 20.0)));
                $h = max(3.0, min(30.0, (float) ($slot['height_percent'] ?? 10.0)));
                $x = max(0.0, min(100.0 - $w, (float) ($slot['x_percent'] ?? 0.0)));
                $y = max(0.0, min(100.0 - $h, (float) ($slot['y_percent'] ?? 0.0)));
                $p = max(1, (int) ($slot['page_number'] ?? 1));

                $this->model->updateSignerSlot((string) $signerId, (string) $id, [
                    'page_number'    => $p,
                    'x_percent'      => $x,
                    'y_percent'      => $y,
                    'width_percent'  => $w,
                    'height_percent' => $h,
                ]);
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
                $_SESSION['flash'] = [
                    'type'    => 'danger',
                    'message' => 'Gagal mengajukan dokumen: ' . $e->getMessage()
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
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat menyetujui dokumen: ' . $e->getMessage()
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

        $rejectionReason = trim($_POST['rejection_reason'] ?? '');
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
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat menolak dokumen: ' . $e->getMessage()
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

        $inputMethod = trim($_POST['input_method'] ?? 'canvas');
        $errors      = [];

        // Salinan fisik independen ke storage/signatures/documents/{signature_id}.png (BUG-06)
        $signatureId       = Uuid::uuid4()->toString();
        $verificationToken = Uuid::uuid4()->toString();
        $signatureDir      = $this->storageDir . '/signatures/documents';
        if (!is_dir($signatureDir)) {
            mkdir($signatureDir, 0755, true);
        }
        $destPath = $signatureDir . '/' . $signatureId . '.png';
        $dbPath   = 'signatures/documents/' . $signatureId . '.png';

        if ($inputMethod === 'template') {
            // Menggunakan template tersimpan milik user → salin (copy) agar independen
            $templateId = trim($_POST['template_id'] ?? '');
            $template   = $this->signatureModel->getById($templateId, $this->userId);
            if (!$template) {
                $errors['signature'] = 'Template tanda tangan tidak valid atau bukan milik Anda.';
            } else {
                $srcPath = $this->storageDir . '/' . ltrim($template['image_path'], '/\\');
                if (!file_exists($srcPath) || !is_readable($srcPath)) {
                    $errors['signature'] = 'Berkas template tanda tangan tidak tersedia di storage server.';
                } elseif (!copy($srcPath, $destPath)) {
                    $errors['signature'] = 'Gagal membuat salinan independen tanda tangan di storage server.';
                }
            }
        } elseif ($inputMethod === 'canvas') {
            // Goresan kanvas baru → tulis langsung ke salinan terisolasi
            $canvasData = trim($_POST['signature_canvas'] ?? '');

            if (empty($canvasData)) {
                $errors['signature'] = 'Silakan bubuhkan goresan tanda tangan Anda pada kanvas.';
            } elseif (!preg_match('/^data:image\/png;base64,(.+)$/', $canvasData, $matches)) {
                $errors['signature'] = 'Format data kanvas tanda tangan tidak valid.';
            } else {
                $decoded = base64_decode($matches[1], true);
                if ($decoded === false || strlen($decoded) < 100) {
                    $errors['signature'] = 'Gambar tanda tangan dari kanvas tidak terbaca atau kosong.';
                } else {
                    $imgInfo = @getimagesizefromstring($decoded);
                    if ($imgInfo === false || $imgInfo['mime'] !== 'image/png') {
                        $errors['signature'] = 'Hasil kanvas harus berupa gambar PNG yang valid.';
                    } elseif (file_put_contents($destPath, $decoded) === false) {
                        $errors['signature'] = 'Gagal menyimpan berkas tanda tangan ke penyimpanan privat server.';
                    }
                }
            }
        } elseif ($inputMethod === 'upload') {
            // Unggah PNG transparan sekali pakai → wajib lolos validasi alpha channel (BUG-05)
            if (!isset($_FILES['signature_file']) || $_FILES['signature_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors['signature'] = 'Silakan pilih berkas gambar tanda tangan PNG untuk diunggah.';
            } elseif ($_FILES['signature_file']['error'] !== UPLOAD_ERR_OK) {
                $errors['signature'] = 'Terjadi kesalahan saat mengunggah berkas tanda tangan.';
            } else {
                $file = $_FILES['signature_file'];
                if ($file['size'] > 2 * 1024 * 1024) {
                    $errors['signature'] = 'Ukuran berkas tanda tangan melebihi batas maksimal 2 MB.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if ($mime !== 'image/png') {
                        $errors['signature'] = 'Berkas tanda tangan wajib berformat gambar PNG transparan (.png).';
                    } else {
                        $im = @imagecreatefrompng($file['tmp_name']);
                        if ($im === false) {
                            $errors['signature'] = 'Berkas gambar PNG tanda tangan rusak atau tidak dapat diproses.';
                        } else {
                            $w = imagesx($im);
                            $h = imagesy($im);
                            $hasAlpha = false;

                            // Sampling titik sudut & tepi
                            $sampleCoords = [
                                [0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1],
                                [intdiv($w, 2), 0], [intdiv($w, 2), $h - 1],
                                [0, intdiv($h, 2)], [$w - 1, intdiv($h, 2)]
                            ];
                            foreach ($sampleCoords as $coord) {
                                $rgba = imagecolorsforindex($im, imagecolorat($im, $coord[0], $coord[1]));
                                if ($rgba['alpha'] > 10) {
                                    $hasAlpha = true;
                                    break;
                                }
                            }

                            // Grid sampling melompat jika tepi belum mendeteksi alpha
                            if (!$hasAlpha) {
                                $stepX = max(1, intdiv($w, 50));
                                $stepY = max(1, intdiv($h, 50));
                                for ($y = 0; $y < $h && !$hasAlpha; $y += $stepY) {
                                    for ($x = 0; $x < $w; $x += $stepX) {
                                        $rgba = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                                        if ($rgba['alpha'] > 10) {
                                            $hasAlpha = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            imagedestroy($im);

                            if (!$hasAlpha) {
                                $errors['signature'] = 'Gambar PNG tidak memiliki latar belakang transparan. Harap gunakan gambar tanda tangan berlatar transparan agar tidak menutupi teks dokumen.';
                            } elseif (!move_uploaded_file($file['tmp_name'], $destPath)) {
                                $errors['signature'] = 'Gagal memindahkan berkas tanda tangan ke penyimpanan privat.';
                            }
                        }
                    }
                }
            }
        } else {
            $errors['signature'] = 'Metode input tanda tangan tidak dikenali.';
        }

        // Jika ada error validasi, render ulang halaman penandatanganan
        if (!empty($errors)) {
            $payload = $this->signViewPayload($doc, $signer);
            $payload['errors']      = $errors;
            $payload['postedInput'] = $inputMethod;
            $this->load->view('views/documents/sign.php', $payload);
            return;
        }

        // Snapshot biodata resmi civitas penandatangan (ADR-03 — immutable)
        $userProfile = $this->usersModel->getById($this->userId);

        try {
            $result = $this->signaturesModel->signDocument([
                'id'                         => $signatureId,
                'verification_token'         => $verificationToken,
                'document_id'                => $doc['id'],
                'signer_id'                  => $this->userId,
                'civitas_id'                 => $userProfile['civitas_id'] ?? null,
                'document_signer_id'         => $signer['id'],
                'signature_image_path'       => $dbPath,
                'page_number'                => (int) $signer['page_number'],
                'x_percent'                  => (float) $signer['x_percent'],
                'y_percent'                  => (float) $signer['y_percent'],
                'width_percent'              => (float) $signer['width_percent'],
                'height_percent'             => (float) $signer['height_percent'],
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
                'method'             => $inputMethod,
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
                    'message' => 'Tanda tangan Anda berhasil dibubuhkan. Seluruh pihak telah menandatangani — dokumen kini berstatus Selesai.'
                ];
            } else {
                $nextStep = (int) $doc['current_step'] + 1;
                $this->auditModel->log($this->userId, 'document.step_advanced', [
                    'document_id' => $doc['id'],
                    'title'       => $doc['title'],
                    'step_from'   => (int) $doc['current_step'],
                    'step_to'     => $nextStep,
                ], $doc['id']);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => 'Tanda tangan Anda berhasil dibubuhkan. Giliran diteruskan ke penandatangan Step ' . $nextStep . '.'
                ];
            }
        } catch (\Throwable $e) {
            // Hapus salinan fisik bila transaksi gagal
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menyimpan tanda tangan: ' . $e->getMessage()
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
        $reason = trim($_POST['decline_reason'] ?? '');
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
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Terjadi kesalahan saat menolak dokumen: ' . $e->getMessage()
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

        // Hanya pengaju yang bisa hapus dokumen miliknya
        if ($doc['uploader_id'] !== $this->userId && !$this->isAdmin) {
            http_response_code(403);
            echo '<h1>403 — Akses Ditolak</h1>';
            return;
        }

        if ($doc['status'] !== 'draft') {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Hanya dokumen berstatus Draft yang dapat dihapus.'
            ];
            header('Location: ' . BASE_URL . '/documents/' . $id);
            exit;
        }

        $this->model->delete($id);

        $this->auditModel->log($this->userId, 'document.delete', [
            'document_id' => $id,
            'title'       => $doc['title'],
        ], $id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Dokumen berhasil dihapus.'];
        header('Location: ' . BASE_URL . '/documents');
        exit;
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
            $finalPath = $this->storageDir . '/' . ltrim($doc['final_file_path'] ?? '', '/\\');
            if (empty($doc['final_file_path']) || !file_exists($finalPath)) {
                require_once __DIR__ . '/../core/PdfStamper.php';
                $stampRes = PdfStamper::stampDocument($id, $this->userId);
                if ($stampRes['success']) {
                    $doc = $this->model->getById($id);
                    $finalPath = $this->storageDir . '/' . ltrim($doc['final_file_path'] ?? '', '/\\');
                }
            }
            if (!empty($doc['final_file_path']) && file_exists($finalPath) && is_readable($finalPath)) {
                $targetFileRel = $doc['final_file_path'];
                $displayName   = 'FINAL_' . $doc['file_name_original'];
            }
        }

        $filePath = $this->storageDir . '/' . ltrim($targetFileRel, '/\\');
        $realStorage = realpath($this->storageDir);
        $realFile    = realpath($filePath);

        if (!$realFile || !$realStorage || strpos($realFile, $realStorage) !== 0 || !is_readable($realFile)) {
            http_response_code(404);
            exit('Berkas fisik PDF tidak tersedia di storage server.');
        }

        $cleanDisplayName = preg_replace('/[\x00-\x1F\x7F"]/', '', basename($displayName));

        // Streaming respon PDF
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . $cleanDisplayName . '"');
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
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
            $finalPath = $this->storageDir . '/' . ltrim($doc['final_file_path'] ?? '', '/\\');
            if (empty($doc['final_file_path']) || !file_exists($finalPath)) {
                require_once __DIR__ . '/../core/PdfStamper.php';
                $stampRes = PdfStamper::stampDocument($id, $this->userId);
                if ($stampRes['success']) {
                    $doc = $this->model->getById($id);
                    $finalPath = $this->storageDir . '/' . ltrim($doc['final_file_path'] ?? '', '/\\');
                }
            }
            if (!empty($doc['final_file_path']) && file_exists($finalPath) && is_readable($finalPath)) {
                $targetFileRel = $doc['final_file_path'];
                $downloadName  = 'FINAL_' . basename($doc['file_name_original']);
                $servedFinal   = true;
            }
        }

        $filePath = $this->storageDir . '/' . ltrim($targetFileRel, '/\\');
        $realStorage = realpath($this->storageDir);
        $realFile    = realpath($filePath);

        if (!$realFile || !$realStorage || strpos($realFile, $realStorage) !== 0 || !is_readable($realFile)) {
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
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $cleanDownloadName . '"');
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }
}
