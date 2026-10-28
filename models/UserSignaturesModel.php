<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class UserSignaturesModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Mengambil seluruh template tanda tangan aktif milik pengguna
     * Diurutkan dari template default (is_default = 1) lalu created_at DESC
     */
    public function getByUserId(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_signatures 
             WHERE user_id = ? AND deleted_at IS NULL 
             ORDER BY is_default DESC, created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil 1 record template tanda tangan berdasarkan UUID
     * Opsional memeriksa kepemilikan user_id
     */
    public function getById(string $id, ?string $userId = null): ?array
    {
        $sql = "SELECT * FROM user_signatures WHERE id = ? AND deleted_at IS NULL";
        $params = [$id];

        if (!empty($userId)) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Mengambil template tanda tangan default milik pengguna
     */
    public function getDefaultByUserId(string $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM user_signatures 
             WHERE user_id = ? AND is_default = 1 AND deleted_at IS NULL 
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Menghitung total template tanda tangan aktif milik pengguna
     */
    public function countByUserId(string $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_signatures WHERE user_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Membuat template tanda tangan baru
     * Jika ini template pertama atau is_default = 1, reset default lama pengguna
     */
    public function create(array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $userId = $data['user_id'];
        $label = !empty($data['label']) ? trim($data['label']) : null;
        $imagePath = $data['image_path'];

        $existingCount = $this->countByUserId($userId);
        $isDefault = ($existingCount === 0 || !empty($data['is_default'])) ? 1 : 0;

        try {
            $this->db->beginTransaction();

            if ($isDefault === 1) {
                // Reset is_default pada template lain milik user
                $resetStmt = $this->db->prepare(
                    "UPDATE user_signatures SET is_default = 0 WHERE user_id = ? AND deleted_at IS NULL"
                );
                $resetStmt->execute([$userId]);
            }

            $stmt = $this->db->prepare(
                "INSERT INTO user_signatures (id, user_id, label, image_path, is_default, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$id, $userId, $label, $imagePath, $isDefault]);

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Menjadikan template tanda tangan tertentu sebagai default utama
     */
    public function setDefault(string $id, string $userId): bool
    {
        $target = $this->getById($id, $userId);
        if (!$target) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Setel semua template aktif user ke is_default = 0
            $resetStmt = $this->db->prepare(
                "UPDATE user_signatures SET is_default = 0 WHERE user_id = ? AND deleted_at IS NULL"
            );
            $resetStmt->execute([$userId]);

            // Setel target ke is_default = 1
            $setStmt = $this->db->prepare(
                "UPDATE user_signatures SET is_default = 1, updated_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL"
            );
            $setStmt->execute([$id, $userId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Memperbarui label / atribut template tanda tangan
     */
    public function update(string $id, string $userId, array $data): bool
    {
        $target = $this->getById($id, $userId);
        if (!$target) {
            return false;
        }

        $label = array_key_exists('label', $data) ? trim($data['label']) : $target['label'];
        $isDefault = isset($data['is_default']) ? ($data['is_default'] ? 1 : 0) : $target['is_default'];

        try {
            $this->db->beginTransaction();

            if ($isDefault === 1 && $target['is_default'] !== 1) {
                $resetStmt = $this->db->prepare(
                    "UPDATE user_signatures SET is_default = 0 WHERE user_id = ? AND deleted_at IS NULL"
                );
                $resetStmt->execute([$userId]);
            }

            $stmt = $this->db->prepare(
                "UPDATE user_signatures SET label = ?, is_default = ?, updated_at = NOW()
                 WHERE id = ? AND user_id = ? AND deleted_at IS NULL"
            );
            $result = $stmt->execute([$label, $isDefault, $id, $userId]);

            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Soft delete template tanda tangan
     * Jika yang dihapus adalah template default, jadikan template aktif terbaru berikutnya sebagai default
     */
    public function delete(string $id, string $userId): bool
    {
        $target = $this->getById($id, $userId);
        if (!$target) {
            return false;
        }

        $wasDefault = ($target['is_default'] == 1);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE user_signatures SET deleted_at = NOW(), is_default = 0 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$id, $userId]);

            // Jika yang dihapus adalah default, promosikan salah satu yang tersisa
            if ($wasDefault) {
                $nextStmt = $this->db->prepare(
                    "SELECT id FROM user_signatures 
                     WHERE user_id = ? AND deleted_at IS NULL 
                     ORDER BY created_at DESC LIMIT 1"
                );
                $nextStmt->execute([$userId]);
                $nextId = $nextStmt->fetchColumn();

                if ($nextId) {
                    $promoteStmt = $this->db->prepare(
                        "UPDATE user_signatures SET is_default = 1, updated_at = NOW() WHERE id = ?"
                    );
                    $promoteStmt->execute([$nextId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
