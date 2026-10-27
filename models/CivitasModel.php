<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class CivitasModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Mengambil daftar civitas yang aktif (deleted_at IS NULL)
     * Mendukung pencarian keyword dan filter berdasarkan kategori (dosen, mahasiswa, tendik)
     */
    public function getCivitas(?string $search = null, ?string $type = null): array
    {
        $sql = "SELECT * FROM civitas WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($type) && in_array($type, ['dosen', 'mahasiswa', 'tendik'], true)) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        if (!empty($search)) {
            $keyword = '%' . trim($search) . '%';
            $sql .= " AND (identifier LIKE ? 
                           OR name LIKE ? 
                           OR department LIKE ? 
                           OR position LIKE ? 
                           OR email LIKE ?)";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil 1 record civitas berdasarkan UUID aktif
     */
    public function getById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM civitas WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Memeriksa keunikan NIM/NIP (identifier)
     */
    public function getByIdentifier(string $identifier, ?string $excludeId = null): ?array
    {
        $sql = "SELECT id, identifier FROM civitas WHERE identifier = ? AND deleted_at IS NULL";
        $params = [trim($identifier)];

        if (!empty($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Memeriksa keunikan email institusi
     */
    public function getByEmail(string $email, ?string $excludeId = null): ?array
    {
        $sql = "SELECT id, email FROM civitas WHERE email = ? AND deleted_at IS NULL";
        $params = [trim($email)];

        if (!empty($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Menghitung total data per kategori civitas untuk tab badge
     */
    public function countByType(): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN type = 'dosen' THEN 1 ELSE 0 END) AS dosen_count,
                    SUM(CASE WHEN type = 'mahasiswa' THEN 1 ELSE 0 END) AS mahasiswa_count,
                    SUM(CASE WHEN type = 'tendik' THEN 1 ELSE 0 END) AS tendik_count
                FROM civitas 
                WHERE deleted_at IS NULL";
        $stmt = $this->db->query($sql);
        $counts = $stmt->fetch();

        return [
            'total'     => (int) ($counts['total'] ?? 0),
            'dosen'     => (int) ($counts['dosen_count'] ?? 0),
            'mahasiswa' => (int) ($counts['mahasiswa_count'] ?? 0),
            'tendik'    => (int) ($counts['tendik_count'] ?? 0),
        ];
    }

    /**
     * Menyimpan data civitas baru (UUID v4)
     */
    public function create(array $data): string
    {
        $id = Uuid::uuid4()->toString();

        $stmt = $this->db->prepare(
            "INSERT INTO civitas (
                id, identifier, name, type, department, position, email, phone, avatar_path, created_at, updated_at
             ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
             )"
        );

        $stmt->execute([
            $id,
            trim($data['identifier']),
            trim($data['name']),
            $data['type'],
            trim($data['department']),
            trim($data['position']),
            trim($data['email']),
            !empty($data['phone']) ? trim($data['phone']) : null,
            !empty($data['avatar_path']) ? trim($data['avatar_path']) : null,
        ]);

        return $id;
    }

    /**
     * Memperbarui data civitas
     */
    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE civitas SET 
                identifier  = ?,
                name        = ?,
                type        = ?,
                department  = ?,
                position    = ?,
                email       = ?,
                phone       = ?,
                avatar_path = ?,
                updated_at  = NOW()
             WHERE id = ? AND deleted_at IS NULL"
        );

        return $stmt->execute([
            trim($data['identifier']),
            trim($data['name']),
            $data['type'],
            trim($data['department']),
            trim($data['position']),
            trim($data['email']),
            !empty($data['phone']) ? trim($data['phone']) : null,
            !empty($data['avatar_path']) ? trim($data['avatar_path']) : null,
            $id,
        ]);
    }

    /**
     * Soft delete data civitas
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE civitas SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([$id]);
    }
}
