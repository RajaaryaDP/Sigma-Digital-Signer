<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class DocumentTemplatesModel
{
    /** Daftar kategori baku template dokumen resmi */
    public const CATEGORIES = [
        'surat'    => 'Surat & Permohonan',
        'akademik' => 'Akademik & Tugas Akhir',
        'lainnya'  => 'Administrasi Lainnya',
    ];

    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Mengembalikan daftar kategori baku [kode => label]
     */
    public static function getCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Memeriksa apakah kode kategori termasuk daftar baku yang sah
     */
    public static function isValidCategory(?string $category): bool
    {
        return is_string($category) && array_key_exists($category, self::CATEGORIES);
    }

    /**
     * Mengembalikan label kategori untuk tampilan UI
     */
    public static function getCategoryLabel(?string $category): string
    {
        if (is_string($category) && isset(self::CATEGORIES[$category])) {
            return self::CATEGORIES[$category];
        }
        return self::CATEGORIES['lainnya'];
    }

    /**
     * Memvalidasi format UUID agar aman untuk kolom CHAR(36)
     */
    private function isValidUuid(?string $id): bool
    {
        if (!is_string($id) || $id === '') {
            return false;
        }
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($id));
    }

    /**
     * Membersihkan input teks dari null-byte, karakter UTF-8 rusak, dan membatasi panjang
     */
    private function sanitizeText(?string $value, int $maxLen): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = trim(str_replace("\0", '', mb_scrub($value, 'UTF-8')));
        if ($clean === '') {
            return null;
        }
        return mb_substr($clean, 0, $maxLen);
    }

    /**
     * Mengambil seluruh template dokumen (opsional filter pencarian nama & kategori)
     */
    public function getAll(?string $search = null, ?string $category = null): array
    {
        $sql = "SELECT dt.*,
                       uc.name AS created_by_name,
                       uu.name AS updated_by_name
                FROM document_templates dt
                LEFT JOIN users uc ON dt.created_by = uc.id
                LEFT JOIN users uu ON dt.updated_by = uu.id";

        $conditions = [];
        $params     = [];

        $q = trim((string) $search);
        if ($q !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = '%' . $escaped . '%';
            $conditions[] = "LOWER(dt.name) LIKE LOWER(?) ESCAPE '\\'";
            $params[] = $like;
        }

        $cat = trim((string) $category);
        if ($cat !== '' && self::isValidCategory($cat)) {
            $conditions[] = "dt.category = ?";
            $params[] = $cat;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY dt.updated_at DESC, dt.created_at DESC, dt.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Menghitung jumlah template per kategori (menghormati kata kunci pencarian aktif)
     */
    public function countByCategory(?string $search = null): array
    {
        $counts = ['all' => 0];
        foreach (array_keys(self::CATEGORIES) as $key) {
            $counts[$key] = 0;
        }

        $sql    = "SELECT dt.category, COUNT(*) AS total FROM document_templates dt";
        $params = [];

        $q = trim((string) $search);
        if ($q !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $sql .= " WHERE LOWER(dt.name) LIKE LOWER(?) ESCAPE '\\'";
            $params[] = '%' . $escaped . '%';
        }

        $sql .= " GROUP BY dt.category";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll() as $row) {
            $cat   = (string) ($row['category'] ?? 'lainnya');
            $total = (int) ($row['total'] ?? 0);
            if (!isset($counts[$cat])) {
                $cat = 'lainnya';
            }
            $counts[$cat] += $total;
            $counts['all'] += $total;
        }

        return $counts;
    }

    /**
     * Mengambil satu record template dokumen berdasarkan UUID
     */
    public function getById(string $id): ?array
    {
        if (!$this->isValidUuid($id)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT dt.*,
                    uc.name AS created_by_name,
                    uu.name AS updated_by_name
             FROM document_templates dt
             LEFT JOIN users uc ON dt.created_by = uc.id
             LEFT JOIN users uu ON dt.updated_by = uu.id
             WHERE dt.id = ?
             LIMIT 1"
        );
        $stmt->execute([trim($id)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Menyimpan template dokumen baru ke basis data
     */
    public function create(array $data): string
    {
        $id = Uuid::uuid4()->toString();
        $name = $this->sanitizeText((string) ($data['name'] ?? ''), 200) ?? '';
        $rawCat = isset($data['category']) && is_string($data['category']) ? trim($data['category']) : '';
        $category = self::isValidCategory($rawCat) ? $rawCat : 'lainnya';
        $description = $this->sanitizeText($data['description'] ?? null, 2000);
        $fileNameOriginal = $this->sanitizeText((string) ($data['file_name_original'] ?? ''), 255) ?? 'template.docx';
        $filePath = $this->sanitizeText((string) ($data['file_path'] ?? ''), 500) ?? '';
        $fileSize = max(0, (int) ($data['file_size'] ?? 0));
        $createdBy = !empty($data['created_by']) && $this->isValidUuid((string) $data['created_by'])
            ? trim((string) $data['created_by'])
            : null;
        $updatedBy = !empty($data['updated_by']) && $this->isValidUuid((string) $data['updated_by'])
            ? trim((string) $data['updated_by'])
            : $createdBy;

        $stmt = $this->db->prepare(
            "INSERT INTO document_templates (
                id, name, category, description, file_name_original, file_path, file_size,
                created_by, updated_by, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        $stmt->execute([
            $id,
            $name,
            $category,
            $description,
            $fileNameOriginal,
            $filePath,
            $fileSize,
            $createdBy,
            $updatedBy,
        ]);

        return $id;
    }

    /**
     * Memperbarui metadata template dan/atau berkas .docx pengganti
     */
    public function update(string $id, array $data): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $name = $this->sanitizeText((string) ($data['name'] ?? ''), 200) ?? '';
        $rawCat = isset($data['category']) && is_string($data['category']) ? trim($data['category']) : '';
        $category = self::isValidCategory($rawCat) ? $rawCat : 'lainnya';
        $description = $this->sanitizeText($data['description'] ?? null, 2000);
        $updatedBy = !empty($data['updated_by']) && $this->isValidUuid((string) $data['updated_by'])
            ? trim((string) $data['updated_by'])
            : null;

        if (!empty($data['file_path'])) {
            $fileNameOriginal = $this->sanitizeText((string) ($data['file_name_original'] ?? ''), 255) ?? 'template.docx';
            $filePath = $this->sanitizeText((string) $data['file_path'], 500) ?? '';
            $fileSize = max(0, (int) ($data['file_size'] ?? 0));

            $stmt = $this->db->prepare(
                "UPDATE document_templates
                 SET name = ?,
                     category = ?,
                     description = ?,
                     file_name_original = ?,
                     file_path = ?,
                     file_size = ?,
                     updated_by = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([
                $name,
                $category,
                $description,
                $fileNameOriginal,
                $filePath,
                $fileSize,
                $updatedBy,
                trim($id),
            ]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE document_templates
                 SET name = ?,
                     category = ?,
                     description = ?,
                     updated_by = ?,
                     updated_at = NOW()
                 WHERE id = ?"
            );

            $stmt->execute([
                $name,
                $category,
                $description,
                $updatedBy,
                trim($id),
            ]);
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Menghapus permanen (hard delete) record template dokumen
     */
    public function delete(string $id): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM document_templates WHERE id = ?");
        $stmt->execute([trim($id)]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Menghitung sisa referensi file fisik pada tabel document_templates
     */
    public function countByFilePath(string $filePath): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM document_templates WHERE file_path = ?");
        $stmt->execute([$filePath]);
        return (int) $stmt->fetchColumn();
    }
}

