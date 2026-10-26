<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class AuditLogModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Mencatat aksi ke dalam jejak audit (append-only)
     * Format action: {entitas}.{kejadian}, e.g.: user.login, user.logout, document.upload
     */
    public function log(?string $userId, string $action, ?array $details = null, ?string $documentId = null): bool
    {
        $id        = Uuid::uuid4()->toString();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $detailsJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (id, user_id, document_id, action, details, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        return $stmt->execute([
            $id,
            $userId,
            $documentId,
            $action,
            $detailsJson,
            $ipAddress,
            $userAgent,
        ]);
    }

    /**
     * Mengambil riwayat log audit untuk suatu dokumen
     */
    public function getByDocumentId(string $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.name AS user_name, u.email AS user_email
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.document_id = ?
             ORDER BY a.created_at ASC"
        );
        $stmt->execute([$documentId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil log audit terbaru untuk monitoring Admin
     */
    public function getRecent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.name AS user_name, u.email AS user_email
             FROM audit_logs a
             LEFT JOIN users u ON a.user_id = u.id
             ORDER BY a.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
