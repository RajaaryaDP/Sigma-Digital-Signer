<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

/**
 * DocumentsModel
 * Model untuk mengelola tabel `documents` dan `document_signers`.
 * Mengikuti pola arsitektur Native PHP MVC dengan PDO Prepared Statements,
 * UUID v4, dan Soft Delete (ADR-06).
 */
class DocumentsModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    // ─────────────────────────────────────────────────────────────
    // DOCUMENTS — Query
    // ─────────────────────────────────────────────────────────────

    /**
     * Ambil semua dokumen aktif milik seorang user (pengaju).
     * Untuk dashboard user/pengaju biasa.
     */
    public function getByUploaderId(string $uploaderId, ?string $status = null): array
    {
        $sql = "SELECT d.*, u.name AS uploader_name
                FROM documents d
                JOIN users u ON d.uploader_id = u.id
                WHERE d.uploader_id = ? AND d.deleted_at IS NULL";
        $params = [$uploaderId];

        if ($status !== null) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua dokumen yang menunggu review admin (status = pending_review).
     * Untuk dashboard admin (antrean verifikasi).
     */
    public function getPendingReview(): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, u.name AS uploader_name
             FROM documents d
             JOIN users u ON d.uploader_id = u.id
             WHERE d.status = 'pending_review' AND d.deleted_at IS NULL
             ORDER BY d.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ambil semua dokumen aktif (semua status, untuk admin).
     * Opsional filter berdasarkan status.
     */
    public function getAll(?string $status = null): array
    {
        $sql = "SELECT d.*, u.name AS uploader_name, rev.name AS reviewer_name
                FROM documents d
                JOIN users u ON d.uploader_id = u.id
                LEFT JOIN users rev ON d.reviewed_by = rev.id
                WHERE d.deleted_at IS NULL";
        $params = [];

        if ($status !== null) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu dokumen berdasarkan ID (aktif, belum dihapus).
     * Menyertakan JOIN ke user pengaju dan reviewer admin.
     */
    public function getById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, 
                    u.name AS uploader_name, 
                    u.email AS uploader_email,
                    rev.name AS reviewer_name,
                    rev.email AS reviewer_email
             FROM documents d
             JOIN users u ON d.uploader_id = u.id
             LEFT JOIN users rev ON d.reviewed_by = rev.id
             WHERE d.id = ? AND d.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Hitung jumlah dokumen per status untuk seorang user.
     * Digunakan untuk stat cards di dashboard user.
     */
    public function countByStatusForUser(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS total
             FROM documents
             WHERE uploader_id = ? AND deleted_at IS NULL
             GROUP BY status"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $counts = [
            'draft' => 0, 'pending_review' => 0, 'approved' => 0,
            'signing' => 0, 'completed' => 0, 'rejected' => 0, 'total' => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
            $counts['total'] += (int) $row['total'];
        }
        return $counts;
    }

    /**
     * Hitung jumlah dokumen per status (semua dokumen, untuk admin).
     */
    public function countByStatusAll(): array
    {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS total
             FROM documents
             WHERE deleted_at IS NULL
             GROUP BY status"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $counts = [
            'draft' => 0, 'pending_review' => 0, 'approved' => 0,
            'signing' => 0, 'completed' => 0, 'rejected' => 0, 'total' => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
            $counts['total'] += (int) $row['total'];
        }
        return $counts;
    }

    /**
     * Hitung jumlah dokumen yang perlu ditandatangani oleh user tertentu.
     * (status signer = 'pending')
     */
    public function countPendingSignForUser(string $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM document_signers ds
             JOIN documents d ON ds.document_id = d.id
             WHERE ds.user_id = ? AND ds.status = 'pending'
               AND d.status = 'signing' AND d.deleted_at IS NULL
               AND ds.deleted_at IS NULL"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Ambil dokumen yang perlu ditandatangani oleh user tertentu.
     */
    public function getPendingSignForUser(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, ds.step_order, ds.role_title, ds.status AS signer_status,
                    u.name AS uploader_name
             FROM document_signers ds
             JOIN documents d ON ds.document_id = d.id
             JOIN users u ON d.uploader_id = u.id
             WHERE ds.user_id = ? AND ds.status = 'pending'
               AND d.status = 'signing' AND d.deleted_at IS NULL
               AND ds.deleted_at IS NULL
             ORDER BY d.created_at ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // ─────────────────────────────────────────────────────────────
    // DOCUMENTS — Mutasi
    // ─────────────────────────────────────────────────────────────

    /**
     * Buat dokumen baru (status awal: draft).
     * Mengembalikan UUID dokumen yang baru dibuat.
     */
    public function create(array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $stmt = $this->db->prepare(
            "INSERT INTO documents
                (id, uploader_id, title, description, file_path,
                 file_name_original, file_size, file_hash, signature_type)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $id,
            $data['uploader_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['file_path'],
            $data['file_name_original'],
            $data['file_size'],
            $data['file_hash'],
            $data['signature_type'],
        ]);
        return $id;
    }

    /**
     * Update status dokumen.
     */
    public function updateStatus(string $id, string $newStatus, ?string $rejectionReason = null): bool
    {
        $sql = "UPDATE documents SET status = ?";
        $params = [$newStatus];

        if ($rejectionReason !== null) {
            $sql .= ", rejection_reason = ?";
            $params[] = $rejectionReason;
        }

        if ($newStatus === 'completed') {
            $sql .= ", completed_at = NOW()";
        }

        $sql .= " WHERE id = ? AND deleted_at IS NULL";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete dokumen (hanya bisa saat draft).
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE documents SET deleted_at = NOW()
             WHERE id = ? AND status = 'draft' AND deleted_at IS NULL"
        );
        return $stmt->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────
    // DOCUMENT_SIGNERS — Query & Mutasi
    // ─────────────────────────────────────────────────────────────

    /**
     * Ambil semua penandatangan untuk sebuah dokumen (aktif).
     * Menyertakan JOIN ke users dan civitas untuk data profil.
     */
    public function getSignersByDocumentId(string $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ds.*, u.name AS signer_name, u.email AS signer_email,
                    c.identifier AS signer_identifier, c.type AS signer_type,
                    c.position AS signer_position, c.department AS signer_department
             FROM document_signers ds
             JOIN users u ON ds.user_id = u.id
             LEFT JOIN civitas c ON u.civitas_id = c.id AND c.deleted_at IS NULL
             WHERE ds.document_id = ? AND ds.deleted_at IS NULL
             ORDER BY ds.step_order ASC"
        );
        $stmt->execute([$documentId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu signer berdasarkan document_id dan user_id.
     */
    public function getSignerByDocAndUser(string $documentId, string $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ds.*, u.name AS signer_name
             FROM document_signers ds
             JOIN users u ON ds.user_id = u.id
             WHERE ds.document_id = ? AND ds.user_id = ? AND ds.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$documentId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tambah penandatangan ke dokumen.
     */
    public function addSigner(array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $stmt = $this->db->prepare(
            "INSERT INTO document_signers
                (id, document_id, user_id, invited_by, step_order, role_title, status)
             VALUES (?, ?, ?, ?, ?, ?, 'waiting')"
        );
        $stmt->execute([
            $id,
            $data['document_id'],
            $data['user_id'],
            $data['invited_by'],
            $data['step_order'],
            $data['role_title'] ?? null,
        ]);
        return $id;
    }

    /**
     * Hapus semua penandatangan dari sebuah dokumen (hard delete, hanya saat draft).
     * Digunakan saat mengulang penataan penandatangan.
     */
    public function removeAllSigners(string $documentId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM document_signers WHERE document_id = ? AND status = 'waiting'"
        );
        return $stmt->execute([$documentId]);
    }

    /**
     * Update posisi slot tanda tangan penandatangan.
     */
    public function updateSignerSlot(string $signerId, array $slot): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE document_signers
             SET page_number = ?, x_percent = ?, y_percent = ?,
                 width_percent = ?, height_percent = ?
             WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([
            $slot['page_number'],
            $slot['x_percent'],
            $slot['y_percent'],
            $slot['width_percent'],
            $slot['height_percent'],
            $signerId,
        ]);
    }

    /**
     * Submit dokumen: ubah status draft → pending_review (sequential)
     * atau draft → completed (single).
     * Untuk sequential, set signer step 1 ke status 'pending'.
     */
    public function submitDocument(string $documentId, string $signatureType): bool
    {
        $this->db->beginTransaction();
        try {
            if ($signatureType === 'single') {
                // Single: langsung completed, tanpa admin review
                $stmt = $this->db->prepare(
                    "UPDATE documents SET status = 'completed', completed_at = NOW()
                     WHERE id = ? AND status = 'draft' AND deleted_at IS NULL"
                );
                $stmt->execute([$documentId]);
            } else {
                // Sequential: pindah ke pending_review untuk admin review
                $stmt = $this->db->prepare(
                    "UPDATE documents SET status = 'pending_review'
                     WHERE id = ? AND status = 'draft' AND deleted_at IS NULL"
                );
                $stmt->execute([$documentId]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil daftar user non-admin yang bisa dipilih sebagai penandatangan.
     * Tidak termasuk user yang sudah menjadi signer di dokumen ini.
     */
    public function getAvailableSigners(?string $excludeDocId = null, ?string $excludeUserId = null): array
    {
        $sql = "SELECT u.id, u.name, u.email,
                       c.identifier, c.type AS civitas_type,
                       c.position, c.department
                FROM users u
                LEFT JOIN civitas c ON u.civitas_id = c.id AND c.deleted_at IS NULL
                WHERE u.role = 'user' AND u.deleted_at IS NULL";
        $params = [];

        if ($excludeUserId !== null) {
            $sql .= " AND u.id != ?";
            $params[] = $excludeUserId;
        }

        if ($excludeDocId !== null) {
            $sql .= " AND u.id NOT IN (
                        SELECT ds.user_id FROM document_signers ds
                        WHERE ds.document_id = ? AND ds.deleted_at IS NULL
                       )";
            $params[] = $excludeDocId;
        }

        $sql .= " ORDER BY c.type ASC, u.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah semua slot penandatangan sudah diatur untuk sebuah dokumen.
     * Sebuah slot dianggap sudah diatur jika x_percent > 0 ATAU y_percent > 0.
     */
    public function allSignerSlotsConfigured(string $documentId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM document_signers
             WHERE document_id = ? AND deleted_at IS NULL
               AND (x_percent = 0 AND y_percent = 0)"
        );
        $stmt->execute([$documentId]);
        return (int) $stmt->fetchColumn() === 0;
    }

    /**
     * Hitung jumlah penandatangan aktif untuk sebuah dokumen.
     */
    public function countSigners(string $documentId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM document_signers
             WHERE document_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$documentId]);
        return (int) $stmt->fetchColumn();
    }

    // ─────────────────────────────────────────────────────────────
    // ADMIN REVIEW ACTIONS (Task 6)
    // ─────────────────────────────────────────────────────────────

    /**
     * Menyetujui dokumen berjenjang oleh Administrator.
     * Mengubah status dokumen dari pending_review -> signing,
     * mencatat reviewed_by & reviewed_at, serta mengaktifkan
     * penandatangan urutan pertama (step_order = 1) menjadi status 'pending'.
     */
    public function approveDocument(string $documentId, string $adminId): bool
    {
        $this->db->beginTransaction();
        try {
            // Update status dokumen menjadi signing dan catat reviewer
            $stmt = $this->db->prepare(
                "UPDATE documents
                 SET status = 'signing',
                     current_step = 1,
                     reviewed_by = ?,
                     reviewed_at = NOW()
                 WHERE id = ? AND status = 'pending_review' AND deleted_at IS NULL"
            );
            $stmt->execute([$adminId, $documentId]);

            if ($stmt->rowCount() === 0) {
                throw new \Exception('Dokumen tidak ditemukan atau sudah tidak berstatus Menunggu Review.');
            }

            // Aktifkan signer giliran pertama (step_order = 1) menjadi status 'pending'
            $stmtSigner = $this->db->prepare(
                "UPDATE document_signers
                 SET status = 'pending'
                 WHERE document_id = ? AND step_order = 1 AND deleted_at IS NULL"
            );
            $stmtSigner->execute([$documentId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Menolak dokumen berjenjang oleh Administrator dengan alasan penolakan resmi.
     * Mengubah status dokumen menjadi 'rejected' (arsip permanen sesuai ADR-08).
     */
    public function rejectDocument(string $documentId, string $adminId, string $rejectionReason): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE documents
             SET status = 'rejected',
                 rejection_reason = ?,
                 reviewed_by = ?,
                 reviewed_at = NOW()
             WHERE id = ? AND status = 'pending_review' AND deleted_at IS NULL"
        );
        $stmt->execute([$rejectionReason, $adminId, $documentId]);

        if ($stmt->rowCount() === 0) {
            throw new \Exception('Dokumen tidak ditemukan atau sudah tidak berstatus Menunggu Review.');
        }

        return true;
    }
}

