<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class UsersModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Mengambil user berdasarkan email aktif (deleted_at IS NULL)
     * Mengikutsertakan join ke tabel civitas untuk data profil resmi
     */
    public function getByEmail($email)
    {
        $cleanEmail = trim(str_replace("\0", '', mb_scrub((string) $email, 'UTF-8')));
        if ($cleanEmail === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT u.*, 
                    c.identifier AS civitas_identifier, 
                    c.name AS civitas_name, 
                    c.type AS civitas_type, 
                    c.department AS civitas_department, 
                    c.position AS civitas_position,
                    c.phone AS civitas_phone
             FROM users u
             LEFT JOIN civitas c ON u.civitas_id = c.id AND c.deleted_at IS NULL
             WHERE LOWER(u.email) = LOWER(?) AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$cleanEmail]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Mengambil user berdasarkan UUID aktif
     */
    public function getById($id)
    {
        $cleanId = trim(str_replace("\0", '', mb_scrub((string) $id, 'UTF-8')));
        if ($cleanId === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT u.*, 
                    c.identifier AS civitas_identifier, 
                    c.name AS civitas_name, 
                    c.type AS civitas_type, 
                    c.department AS civitas_department, 
                    c.position AS civitas_position,
                    c.phone AS civitas_phone
             FROM users u
             LEFT JOIN civitas c ON u.civitas_id = c.id AND c.deleted_at IS NULL
             WHERE u.id = ? AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$cleanId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Mengambil user aktif berdasarkan civitas_id (Pintu Filter 4: Proteksi Akun Ganda Task 11)
     */
    public function getByCivitasId(string $civitasId): ?array
    {
        $cleanCivitasId = trim(str_replace("\0", '', mb_scrub($civitasId, 'UTF-8')));
        if ($cleanCivitasId === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT u.*
             FROM users u
             WHERE u.civitas_id = ? AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$cleanCivitasId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Mengambil seluruh pengguna aktif (opsional dengan pencarian)
     */
    public function getAll($search = null)
    {
        $sql = "SELECT u.id, u.civitas_id, u.name, u.email, u.role, u.created_at,
                       c.identifier AS civitas_identifier, 
                       c.type AS civitas_type, 
                       c.department AS civitas_department, 
                       c.position AS civitas_position
                FROM users u
                LEFT JOIN civitas c ON u.civitas_id = c.id AND c.deleted_at IS NULL
                WHERE u.deleted_at IS NULL";

        if ($search !== null && $search !== '') {
            $cleanSearch = trim(str_replace("\0", '', mb_scrub((string) $search, 'UTF-8')));
            if ($cleanSearch !== '') {
                $escapedSearch = strtr($cleanSearch, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
                $keyword = '%' . $escapedSearch . '%';
                $sql .= " AND (LOWER(u.name) LIKE LOWER(?) ESCAPE '\\' OR LOWER(u.email) LIKE LOWER(?) ESCAPE '\\' OR LOWER(c.identifier) LIKE LOWER(?) ESCAPE '\\' OR LOWER(c.department) LIKE LOWER(?) ESCAPE '\\')";
                $sql .= " ORDER BY u.created_at DESC";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$keyword, $keyword, $keyword, $keyword]);
                return $stmt->fetchAll();
            }
        }

        $sql .= " ORDER BY u.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Mendaftarkan pengguna baru (UUID v4)
     */
    public function create(array $data)
    {
        $id = Uuid::uuid4()->toString();
        $stmt = $this->db->prepare(
            "INSERT INTO users (id, civitas_id, name, email, password, role, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $cleanName  = mb_substr(trim(str_replace("\0", '', mb_scrub((string) ($data['name'] ?? ''), 'UTF-8'))), 0, 255);
        $cleanEmail = mb_substr(strtolower(trim(str_replace("\0", '', mb_scrub((string) ($data['email'] ?? ''), 'UTF-8')))), 0, 255);
        $role       = in_array($data['role'] ?? 'user', ['admin', 'user'], true) ? $data['role'] : 'user';

        $stmt->execute([
            $id,
            $data['civitas_id'] ?? null,
            $cleanName,
            $cleanEmail,
            password_hash((string) $data['password'], PASSWORD_BCRYPT),
            $role,
        ]);

        return $id;
    }

    /**
     * Memperbarui data pengguna
     */
    public function update($id, array $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($data['email']));
        }
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        if (array_key_exists('civitas_id', $data)) {
            $fields[] = "civitas_id = ?";
            $params[] = $data['civitas_id'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";
        $params[] = $id;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete akun pengguna
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([$id]);
    }
}
