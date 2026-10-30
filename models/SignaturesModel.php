<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

/**
 * SignaturesModel
 * Model untuk mengelola tabel `signatures` — rekam tanda tangan visual resmi
 * yang telah dikonfirmasi beserta token verifikasi QR (Task 7).
 *
 * Transaksi `signDocument()` dan `declineDocument()` menjalankan guard
 * ADR-01 (giliran jenjang) dan pola guard rowCount anti double-submit
 * (mitigasi BUG-13).
 */
class SignaturesModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    // ─────────────────────────────────────────────────────────────
    // SIGNATURES — Query
    // ─────────────────────────────────────────────────────────────

    /**
     * Ambil seluruh tanda tangan aktif untuk sebuah dokumen.
     */
    public function getByDocumentId(string $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS signer_name, u.email AS signer_email
             FROM signatures s
             JOIN users u ON s.signer_id = u.id
             WHERE s.document_id = ? AND s.deleted_at IS NULL
             ORDER BY s.signed_at ASC"
        );
        $stmt->execute([$documentId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil satu tanda tangan berdasarkan document_id + signer_id.
     * Digunakan sebagai penjaga anti penandatanganan ganda.
     */
    public function getByDocAndSigner(string $documentId, string $signerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM signatures
             WHERE document_id = ? AND signer_id = ? AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$documentId, $signerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Ambil satu tanda tangan berdasarkan UUID (untuk streaming preview).
     * Menyertakan pengaju dokumen untuk pemeriksaan akses.
     */
    public function getById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, d.uploader_id, u.name AS signer_name
             FROM signatures s
             JOIN documents d ON s.document_id = d.id
             JOIN users u ON s.signer_id = u.id
             WHERE s.id = ? AND s.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ─────────────────────────────────────────────────────────────
    // SIGNATURES — Mutasi (Task 7)
    // ─────────────────────────────────────────────────────────────

    /**
     * Transaksi penandatanganan dokumen berjenjang / mandiri.
     *
     * 1. Insert rekam `signatures` (token verifikasi QR, snapshot biodata
     *    civitas penandatangan, koordinat slot, path salinan terisolasi).
     * 2. Update `document_signers` → `signed` + `signed_at`
     *    (guard: hanya bila status sebelumnya `pending`).
     * 3. Advance step otomatis (ADR-01):
     *    - Ada signer `step_order + 1` → `documents.current_step` naik,
     *      signer berikutnya `waiting → pending`. Mengembalikan 'advanced'.
     *    - Tidak ada → dokumen `completed` + `completed_at`.
     *      Mengembalikan 'completed'.
     *
     * @param array $data Parameter lengkap baris signatures + `id` & `verification_token`
     *                    yang sudah digenerate oleh controller.
     * @return string 'advanced' | 'completed'
     */
    public function signDocument(array $data): string
    {
        $this->db->beginTransaction();
        try {
            // 1. Rekam tanda tangan (immutable record, ADR-03)
            $stmt = $this->db->prepare(
                "INSERT INTO signatures
                    (id, document_id, signer_id, civitas_id, document_signer_id,
                     signature_image_path, verification_token, qr_code_path,
                     page_number, x_percent, y_percent, width_percent, height_percent,
                     signer_name_snapshot, signer_identifier_snapshot, signer_type_snapshot,
                     signer_department_snapshot, signer_position_snapshot, signer_email_snapshot,
                     signed_at, ip_address, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())"
            );
            $stmt->execute([
                $data['id'],
                $data['document_id'],
                $data['signer_id'],
                $data['civitas_id'] ?? null,
                $data['document_signer_id'],
                $data['signature_image_path'],
                $data['verification_token'],
                (int) $data['page_number'],
                (float) $data['x_percent'],
                (float) $data['y_percent'],
                (float) $data['width_percent'],
                (float) $data['height_percent'],
                $data['signer_name_snapshot'],
                $data['signer_identifier_snapshot'] ?? null,
                $data['signer_type_snapshot'] ?? null,
                $data['signer_department_snapshot'] ?? null,
                $data['signer_position_snapshot'] ?? null,
                $data['signer_email_snapshot'],
                $data['ip_address'] ?? null,
            ]);

            // 2. Tandai tiket penandatangan sebagai selesai (guard: hanya dari 'pending')
            $stmtSigner = $this->db->prepare(
                "UPDATE document_signers
                 SET status = 'signed', signed_at = NOW()
                 WHERE id = ? AND document_id = ? AND status = 'pending' AND deleted_at IS NULL"
            );
            $stmtSigner->execute([$data['document_signer_id'], $data['document_id']]);

            if ($stmtSigner->rowCount() === 0) {
                throw new \Exception('Tiket penandatangan tidak dalam status giliran aktif (pending).');
            }

            // 3a. Dokumen harus masih dalam status 'signing'
            $stmtDoc = $this->db->prepare(
                "SELECT current_step FROM documents
                 WHERE id = ? AND status = 'signing' AND deleted_at IS NULL
                 LIMIT 1"
            );
            $stmtDoc->execute([$data['document_id']]);
            $currentStep = $stmtDoc->fetchColumn();

            if ($currentStep === false) {
                throw new \Exception('Dokumen tidak dalam status Proses Penandatanganan.');
            }

            $nextStep = (int) $currentStep + 1;

            // 3b. Cek apakah masih ada penandatangan pada urutan berikutnya
            $stmtNext = $this->db->prepare(
                "SELECT id FROM document_signers
                 WHERE document_id = ? AND step_order = ? AND status = 'waiting' AND deleted_at IS NULL
                 LIMIT 1"
            );
            $stmtNext->execute([$data['document_id'], $nextStep]);
            $nextSignerId = $stmtNext->fetchColumn();

            if ($nextSignerId) {
                // Advance step: naikkan current_step & aktifkan signer berikutnya
                $stmtAdvance = $this->db->prepare(
                    "UPDATE documents
                     SET current_step = ?
                     WHERE id = ? AND status = 'signing' AND deleted_at IS NULL"
                );
                $stmtAdvance->execute([$nextStep, $data['document_id']]);

                $stmtActivate = $this->db->prepare(
                    "UPDATE document_signers
                     SET status = 'pending'
                     WHERE document_id = ? AND step_order = ? AND status = 'waiting' AND deleted_at IS NULL"
                );
                $stmtActivate->execute([$data['document_id'], $nextStep]);

                $this->db->commit();
                return 'advanced';
            }

            // 3c. Tidak ada penandatangan berikutnya → selesaikan dokumen
            $stmtComplete = $this->db->prepare(
                "UPDATE documents
                 SET status = 'completed', completed_at = NOW()
                 WHERE id = ? AND status = 'signing' AND deleted_at IS NULL"
            );
            $stmtComplete->execute([$data['document_id']]);

            if ($stmtComplete->rowCount() === 0) {
                throw new \Exception('Gagal menyelesaikan dokumen: status tidak valid.');
            }

            $this->db->commit();
            return 'completed';
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Penolakan dokumen oleh penandatangan (decline policy, ADR-01 & ADR-08).
     *
     * - `document_signers` → `declined` + alasan penolakan
     * - `documents` seketika → `rejected` (arsip batal permanen)
     *
     * Guard rowCount memastikan hanya tiket 'pending' yang dapat menolak,
     * dan hanya dokumen berstatus 'signing' yang dibatalkan (anti double-submit).
     */
    public function declineDocument(string $documentId, string $documentSignerId, string $reason): bool
    {
        $this->db->beginTransaction();
        try {
            $stmtSigner = $this->db->prepare(
                "UPDATE document_signers
                 SET status = 'declined', decline_reason = ?
                 WHERE id = ? AND document_id = ? AND status = 'pending' AND deleted_at IS NULL"
            );
            $stmtSigner->execute([$reason, $documentSignerId, $documentId]);

            if ($stmtSigner->rowCount() === 0) {
                throw new \Exception('Tiket penandatangan tidak dalam status giliran aktif (pending).');
            }

            $stmtDoc = $this->db->prepare(
                "UPDATE documents
                 SET status = 'rejected', rejection_reason = ?
                 WHERE id = ? AND status = 'signing' AND deleted_at IS NULL"
            );
            $stmtDoc->execute([$reason, $documentId]);

            if ($stmtDoc->rowCount() === 0) {
                throw new \Exception('Dokumen tidak dalam status Proses Penandatanganan.');
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
