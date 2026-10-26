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
             WHERE u.email = ? AND u.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Mengambil user berdasarkan UUID aktif
     */
    public function getById($id)
    {
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
        $stmt->execute([$id]);
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

        if (!empty($search)) {
            $keyword = '%' . trim($search) . '%';
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR c.identifier LIKE ? OR c.department LIKE ?)";
            $sql .= " ORDER BY u.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$keyword, $keyword, $keyword, $keyword]);
            return $stmt->fetchAll();
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

        $stmt->execute([
            $id,
            $data['civitas_id'] ?? null,
            $data['name'],
            trim($data['email']),
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'] ?? 'user',
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
            $params[] = trim($data['email']);
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
