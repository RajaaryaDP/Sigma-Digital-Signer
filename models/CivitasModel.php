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

        if ($search !== null && $search !== '') {
            $cleanSearch = trim(str_replace("\0", '', mb_scrub($search, 'UTF-8')));
            if ($cleanSearch !== '') {
                $escapedSearch = strtr($cleanSearch, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
                $keyword = '%' . $escapedSearch . '%';
                $sql .= " AND (LOWER(identifier) LIKE LOWER(?) ESCAPE '\\'
                               OR LOWER(name) LIKE LOWER(?) ESCAPE '\\'
                               OR LOWER(department) LIKE LOWER(?) ESCAPE '\\'
                               OR LOWER(position) LIKE LOWER(?) ESCAPE '\\'
                               OR LOWER(email) LIKE LOWER(?) ESCAPE '\\')";
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
                $params[] = $keyword;
            }
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
        $id = trim(str_replace("\0", '', mb_scrub($id, 'UTF-8')));
        if ($id === '') {
            return null;
        }
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
        $identifier = trim(str_replace("\0", '', mb_scrub($identifier, 'UTF-8')));
        $sql = "SELECT id, identifier FROM civitas WHERE LOWER(identifier) = LOWER(?) AND deleted_at IS NULL";
        $params = [$identifier];

        if (!empty($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim(str_replace("\0", '', mb_scrub($excludeId, 'UTF-8')));
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
        $email = trim(str_replace("\0", '', mb_scrub($email, 'UTF-8')));
        $sql = "SELECT id, email FROM civitas WHERE LOWER(email) = LOWER(?) AND deleted_at IS NULL";
        $params = [$email];

        if (!empty($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim(str_replace("\0", '', mb_scrub($excludeId, 'UTF-8')));
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
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['identifier'], 'UTF-8'))), 0, 64),
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['name'], 'UTF-8'))), 0, 255),
            $data['type'],
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['department'], 'UTF-8'))), 0, 150),
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['position'], 'UTF-8'))), 0, 150),
            mb_substr(strtolower(trim(str_replace("\0", '', mb_scrub((string) $data['email'], 'UTF-8')))), 0, 255),
            !empty($data['phone']) ? mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['phone'], 'UTF-8'))), 0, 32) : null,
            !empty($data['avatar_path']) ? mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['avatar_path'], 'UTF-8'))), 0, 500) : null,
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
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['identifier'], 'UTF-8'))), 0, 64),
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['name'], 'UTF-8'))), 0, 255),
            $data['type'],
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['department'], 'UTF-8'))), 0, 150),
            mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['position'], 'UTF-8'))), 0, 150),
            mb_substr(strtolower(trim(str_replace("\0", '', mb_scrub((string) $data['email'], 'UTF-8')))), 0, 255),
            !empty($data['phone']) ? mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['phone'], 'UTF-8'))), 0, 32) : null,
            !empty($data['avatar_path']) ? mb_substr(trim(str_replace("\0", '', mb_scrub((string) $data['avatar_path'], 'UTF-8'))), 0, 500) : null,
            trim(str_replace("\0", '', mb_scrub($id, 'UTF-8'))),
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
