<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/DocumentsModel.php';
require_once __DIR__ . '/../models/CivitasModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

/**
 * Documents Controller
 * Mengelola pengajuan dokumen, penataan slot penandatangan berjenjang,
 * preview/download file PDF via streaming, serta dashboard dokumen.
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
 */
class Documents
{
    private $load;
    private $model;
    private $civitasModel;
    private $auditModel;
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

        $this->load         = new Loader();
        $this->model        = new DocumentsModel();
        $this->civitasModel = new CivitasModel();
        $this->auditModel   = new AuditLogModel();
        $this->user         = $_SESSION['user'];
        $this->userId       = $_SESSION['user']['id'];
        $this->isAdmin      = ($_SESSION['user']['role'] ?? '') === 'admin';
        $this->storageDir   = __DIR__ . '/../storage';

        // Pastikan direktori storage/documents/ ada
        $docDir = $this->storageDir . '/documents';
        if (!is_dir($docDir)) {
            mkdir($docDir, 0755, true);
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

            $this->load->view('views/documents/index.php', [
                'title'            => 'Dokumen Digital Saya',
                'user'             => $this->user,
                'documents'        => $myDocuments,
                'docCounts'        => $docCounts,
                'pendingSignCount' => $pendingSignCount,
                'pendingSignDocs'  => $pendingSignDocs,
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
        if (!$this->isAdmin && $doc['uploader_id'] !== $this->userId) {
            // Cek apakah user adalah salah satu penandatangan
            $signer = $this->model->getSignerByDocAndUser($id, $this->userId);
            if (!$signer) {
                http_response_code(403);
                echo '<h1>403 — Akses Ditolak</h1><p>Anda tidak memiliki akses ke dokumen ini.</p>';
                return;
            }
        }

        $signers  = $this->model->getSignersByDocumentId($id);
        $auditLog = $this->auditModel->getByDocumentId($id);

        $this->load->view('views/documents/detail.php', [
            'title'    => 'Detail Dokumen — ' . $doc['title'],
            'doc'      => $doc,
            'signers'  => $signers,
            'auditLog' => $auditLog,
            'isOwner'  => ($doc['uploader_id'] === $this->userId),
            'isAdmin'  => $this->isAdmin,
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

        $actionType = $_POST['action_type'] ?? '';

        // Simpan posisi slot koordinat terlebih dahulu jika data koordinat dikirimkan
        $slotData = $_POST['slots'] ?? [];
        if (is_array($slotData) && !empty($slotData)) {
            foreach ($slotData as $signerId => $slot) {
                $this->model->updateSignerSlot($signerId, [
                    'page_number'    => max(1, (int) ($slot['page_number'] ?? 1)),
                    'x_percent'      => max(0, min(100, (float) ($slot['x_percent'] ?? 0))),
                    'y_percent'      => max(0, min(100, (float) ($slot['y_percent'] ?? 0))),
                    'width_percent'  => max(5, min(50, (float) ($slot['width_percent'] ?? 20))),
                    'height_percent' => max(3, min(30, (float) ($slot['height_percent'] ?? 10))),
                ]);
            }
        }

        // ── Aksi 1: Simpan slot koordinat saja ──
        if ($actionType === 'save_slots') {
            if ($doc['status'] !== 'draft') {
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Slot hanya dapat diatur saat status Draft.'];
                header('Location: ' . BASE_URL . '/documents/' . $id);
                exit;
            }

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
                    'status_to'     => $isSingle ? 'completed' : 'pending_review',
                    'signers_count' => count($signers),
                ], $id);

                $_SESSION['flash'] = [
                    'type'    => 'success',
                    'message' => $isSingle
                        ? 'Dokumen mandiri berhasil diselesaikan.'
                        : 'Dokumen berhasil diajukan untuk ditinjau oleh Administrator.'
                ];
                header('Location: ' . BASE_URL . '/documents/' . $id);
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

        $filePath = $this->storageDir . '/' . ltrim($doc['file_path'], '/\\');
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit('Berkas fisik PDF tidak tersedia di storage server.');
        }

        // Streaming respon PDF
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . basename($doc['file_name_original']) . '"');
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

        $filePath = $this->storageDir . '/' . ltrim($doc['file_path'], '/\\');
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit('Berkas fisik PDF tidak tersedia.');
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . basename($doc['file_name_original']) . '"');
        header('Cache-Control: private, max-age=600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }
}
