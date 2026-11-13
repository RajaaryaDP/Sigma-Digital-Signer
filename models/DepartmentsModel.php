<?php

require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

class DepartmentsModel
{
    private $db;

    public function __construct()
    {
        global $conn;
        $this->db = $conn;
    }

    /**
     * Validasi format UUID v4/standard sebelum dipakai pada kolom CHAR(36)
     */
    private function isValidUuid(?string $id): bool
    {
        if (!is_string($id) || $id === '') {
            return false;
        }
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($id));
    }

    /**
     * Ambil seluruh jurusan aktif beserta jumlah prodi dan jumlah civitas
     */
    public function getAllDepartments(?string $search = null): array
    {
        $sql = "SELECT d.*,
                       COUNT(DISTINCT CASE WHEN sp.deleted_at IS NULL THEN sp.id END) AS prodi_count,
                       COUNT(DISTINCT CASE WHEN c.deleted_at IS NULL THEN c.id END) AS civitas_count
                FROM departments d
                LEFT JOIN study_programs sp ON sp.department_id = d.id AND sp.deleted_at IS NULL
                LEFT JOIN civitas c ON c.deleted_at IS NULL
                    AND (
                        c.department_id = d.id
                        OR EXISTS (
                            SELECT 1
                            FROM civitas_study_programs csp
                            INNER JOIN study_programs sp2 ON csp.study_program_id = sp2.id AND sp2.deleted_at IS NULL
                            WHERE csp.civitas_id = c.id AND sp2.department_id = d.id
                        )
                    )
                WHERE d.deleted_at IS NULL";

        $params = [];
        $q = trim((string) $search);
        if ($q !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = '%' . $escaped . '%';
            $sql .= " AND (
                LOWER(d.name) LIKE LOWER(?) ESCAPE '\\'
                OR LOWER(COALESCE(d.code, '')) LIKE LOWER(?) ESCAPE '\\'
                OR EXISTS (
                    SELECT 1 FROM study_programs spf
                    WHERE spf.department_id = d.id
                      AND spf.deleted_at IS NULL
                      AND (
                          LOWER(spf.name) LIKE LOWER(?) ESCAPE '\\'
                          OR LOWER(COALESCE(spf.code, '')) LIKE LOWER(?) ESCAPE '\\'
                      )
                )
            )";
            $params = [$like, $like, $like, $like];
        }

        $sql .= " GROUP BY d.id ORDER BY d.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil daftar jurusan aktif lengkap dengan array prodi di bawahnya
     */
    public function getDepartmentsWithStudyPrograms(?string $search = null): array
    {
        $departments = $this->getAllDepartments($search);
        $prodiList   = $this->getAllStudyPrograms();

        $prodiByDept = [];
        foreach ($prodiList as $pr) {
            $prodiByDept[$pr['department_id']][] = $pr;
        }

        foreach ($departments as &$dept) {
            $dept['study_programs'] = $prodiByDept[$dept['id']] ?? [];
        }
        unset($dept);

        return $departments;
    }

    /**
     * Ambil satu jurusan aktif berdasarkan ID
     */
    public function getDepartmentById(string $id): ?array
    {
        if (!$this->isValidUuid($id)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM departments WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([trim($id)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cek apakah nama jurusan sudah ada (case-insensitive)
     */
    public function existsDepartmentName(string $name, ?string $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM departments WHERE LOWER(name) = LOWER(?) AND deleted_at IS NULL";
        $params = [trim($name)];

        if ($excludeId !== null && $this->isValidUuid($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim($excludeId);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek apakah kode jurusan sudah ada (case-insensitive, bila diisi)
     */
    public function existsDepartmentCode(?string $code, ?string $excludeId = null): bool
    {
        $cleanCode = trim((string) $code);
        if ($cleanCode === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM departments WHERE LOWER(code) = LOWER(?) AND deleted_at IS NULL";
        $params = [$cleanCode];

        if ($excludeId !== null && $this->isValidUuid($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim($excludeId);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tambah jurusan baru
     */
    public function createDepartment(array $data): string
    {
        $id   = Uuid::uuid4()->toString();
        $code = trim((string) ($data['code'] ?? ''));
        $codeVal = $code !== '' ? strtoupper($code) : null;
        $name = trim((string) ($data['name'] ?? ''));

        $stmt = $this->db->prepare(
            "INSERT INTO departments (id, code, name, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$id, $codeVal, $name]);

        return $id;
    }

    /**
     * Perbarui data jurusan dan sinkronkan nama teks department pada civitas terkait
     */
    public function updateDepartment(string $id, array $data): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $code = trim((string) ($data['code'] ?? ''));
        $codeVal = $code !== '' ? strtoupper($code) : null;
        $name = trim((string) ($data['name'] ?? ''));

        $startedTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTx = true;
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE departments
                 SET code = ?, name = ?, updated_at = NOW()
                 WHERE id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$codeVal, $name, trim($id)]);

            $stmtSyncCivitas = $this->db->prepare(
                "UPDATE civitas
                 SET department = ?, updated_at = NOW()
                 WHERE department_id = ? AND deleted_at IS NULL"
            );
            $stmtSyncCivitas->execute([$name, trim($id)]);

            if ($startedTx) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($startedTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Hitung jumlah prodi aktif di bawah suatu jurusan
     */
    public function countActiveStudyProgramsByDepartment(string $departmentId): int
    {
        if (!$this->isValidUuid($departmentId)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM study_programs WHERE department_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([trim($departmentId)]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Hitung jumlah civitas aktif yang terhubung ke suatu jurusan
     */
    public function countActiveCivitasByDepartment(string $departmentId): int
    {
        if (!$this->isValidUuid($departmentId)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM civitas WHERE department_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([trim($departmentId)]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Soft delete jurusan
     */
    public function softDeleteDepartment(string $id): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE departments SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([trim($id)]) && $stmt->rowCount() > 0;
    }

    /**
     * Ambil seluruh program studi aktif (bisa difilter per jurusan) beserta jumlah civitas aktif
     */
    public function getAllStudyPrograms(?string $departmentId = null): array
    {
        $sql = "SELECT sp.*,
                       d.name AS department_name,
                       d.code AS department_code,
                       COUNT(DISTINCT CASE WHEN c.deleted_at IS NULL THEN c.id END) AS civitas_count,
                       COUNT(DISTINCT CASE WHEN c.deleted_at IS NULL AND c.type = 'dosen' THEN c.id END) AS dosen_count
                FROM study_programs sp
                INNER JOIN departments d ON sp.department_id = d.id AND d.deleted_at IS NULL
                LEFT JOIN civitas_study_programs csp ON csp.study_program_id = sp.id
                LEFT JOIN civitas c ON csp.civitas_id = c.id AND c.deleted_at IS NULL
                WHERE sp.deleted_at IS NULL";

        $params = [];
        if ($departmentId !== null && $departmentId !== '') {
            if (!$this->isValidUuid($departmentId)) {
                return [];
            }
            $sql .= " AND sp.department_id = ?";
            $params[] = trim($departmentId);
        }

        $sql .= " GROUP BY sp.id, d.name, d.code ORDER BY d.name ASC, sp.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil detail satu program studi aktif
     */
    public function getStudyProgramById(string $id): ?array
    {
        if (!$this->isValidUuid($id)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT sp.*, d.name AS department_name, d.code AS department_code
             FROM study_programs sp
             INNER JOIN departments d ON sp.department_id = d.id AND d.deleted_at IS NULL
             WHERE sp.id = ? AND sp.deleted_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([trim($id)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cek duplikat nama prodi di dalam jurusan yang sama (case-insensitive)
     */
    public function existsStudyProgramName(string $departmentId, string $name, ?string $excludeId = null): bool
    {
        if (!$this->isValidUuid($departmentId)) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM study_programs
                WHERE department_id = ? AND LOWER(name) = LOWER(?) AND deleted_at IS NULL";
        $params = [trim($departmentId), trim($name)];

        if ($excludeId !== null && $this->isValidUuid($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim($excludeId);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cek duplikat kode prodi global (case-insensitive, bila diisi)
     */
    public function existsStudyProgramCode(?string $code, ?string $excludeId = null): bool
    {
        $cleanCode = trim((string) $code);
        if ($cleanCode === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM study_programs WHERE LOWER(code) = LOWER(?) AND deleted_at IS NULL";
        $params = [$cleanCode];

        if ($excludeId !== null && $this->isValidUuid($excludeId)) {
            $sql .= " AND id != ?";
            $params[] = trim($excludeId);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Tambah program studi baru
     */
    public function createStudyProgram(array $data): string
    {
        $id     = Uuid::uuid4()->toString();
        $deptId = trim((string) ($data['department_id'] ?? ''));
        $code   = trim((string) ($data['code'] ?? ''));
        $codeVal = $code !== '' ? strtoupper($code) : null;
        $degree = trim((string) ($data['degree'] ?? ''));
        $degreeVal = $degree !== '' ? $degree : null;
        $name   = trim((string) ($data['name'] ?? ''));

        $stmt = $this->db->prepare(
            "INSERT INTO study_programs (id, department_id, code, degree, name, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$id, $deptId, $codeVal, $degreeVal, $name]);

        return $id;
    }

    /**
     * Perbarui data program studi
     */
    public function updateStudyProgram(string $id, array $data): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $deptId = trim((string) ($data['department_id'] ?? ''));
        $code   = trim((string) ($data['code'] ?? ''));
        $codeVal = $code !== '' ? strtoupper($code) : null;
        $degree = trim((string) ($data['degree'] ?? ''));
        $degreeVal = $degree !== '' ? $degree : null;
        $name   = trim((string) ($data['name'] ?? ''));

        $stmt = $this->db->prepare(
            "UPDATE study_programs
             SET department_id = ?, code = ?, degree = ?, name = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([$deptId, $codeVal, $degreeVal, $name, trim($id)]);
    }

    /**
     * Hitung jumlah civitas aktif yang terhubung ke program studi
     */
    public function countActiveCivitasByStudyProgram(string $studyProgramId): int
    {
        if (!$this->isValidUuid($studyProgramId)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT c.id)
             FROM civitas_study_programs csp
             INNER JOIN civitas c ON csp.civitas_id = c.id AND c.deleted_at IS NULL
             WHERE csp.study_program_id = ?"
        );
        $stmt->execute([trim($studyProgramId)]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Soft delete program studi
     */
    public function softDeleteStudyProgram(string $id): bool
    {
        if (!$this->isValidUuid($id)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE study_programs SET deleted_at = NOW(), updated_at = NOW() WHERE id = ? AND deleted_at IS NULL"
        );
        return $stmt->execute([trim($id)]) && $stmt->rowCount() > 0;
    }

    /**
     * Ambil daftar prodi aktif yang diampu/diikuti satu civitas
     */
    public function getStudyProgramsByCivitasId(string $civitasId): array
    {
        if (!$this->isValidUuid($civitasId)) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT sp.*, d.name AS department_name, d.code AS department_code
             FROM civitas_study_programs csp
             INNER JOIN study_programs sp ON csp.study_program_id = sp.id AND sp.deleted_at IS NULL
             INNER JOIN departments d ON sp.department_id = d.id AND d.deleted_at IS NULL
             WHERE csp.civitas_id = ?
             ORDER BY d.name ASC, sp.name ASC"
        );
        $stmt->execute([trim($civitasId)]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil daftar ID prodi milik satu civitas
     */
    public function getStudyProgramIdsByCivitasId(string $civitasId): array
    {
        $rows = $this->getStudyProgramsByCivitasId($civitasId);
        return array_values(array_map(fn($r) => $r['id'], $rows));
    }

    /**
     * Ambil map prodi berdasarkan array ID civitas (menghindari query N+1)
     */
    public function getStudyProgramsByCivitasIds(array $civitasIds): array
    {
        $validIds = array_values(array_unique(array_filter(
            array_map(fn($v) => trim((string) $v), $civitasIds),
            fn($v) => $this->isValidUuid($v)
        )));

        if (empty($validIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($validIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT csp.civitas_id,
                    sp.id, sp.department_id, sp.code, sp.degree, sp.name,
                    d.name AS department_name, d.code AS department_code
             FROM civitas_study_programs csp
             INNER JOIN study_programs sp ON csp.study_program_id = sp.id AND sp.deleted_at IS NULL
             INNER JOIN departments d ON sp.department_id = d.id AND d.deleted_at IS NULL
             WHERE csp.civitas_id IN ({$placeholders})
             ORDER BY d.name ASC, sp.name ASC"
        );
        $stmt->execute($validIds);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['civitas_id']][] = $row;
        }
        return $map;
    }

    /**
     * Validasi daftar ID prodi agar hanya menyimpan ID yang valid & aktif di DB
     */
    public function filterValidActiveStudyProgramIds(array $rawIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(
            array_map(fn($v) => trim((string) $v), $rawIds),
            fn($v) => $this->isValidUuid($v)
        )));

        if (empty($cleanIds)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($cleanIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT sp.id
             FROM study_programs sp
             INNER JOIN departments d ON sp.department_id = d.id AND d.deleted_at IS NULL
             WHERE sp.id IN ({$placeholders}) AND sp.deleted_at IS NULL"
        );
        $stmt->execute($cleanIds);

        return array_values($stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Sinkronisasi relasi M:N antara civitas (termasuk dosen >1 prodi) dan study_programs
     */
    public function syncCivitasStudyPrograms(string $civitasId, array $studyProgramIds): void
    {
        if (!$this->isValidUuid($civitasId)) {
            return;
        }

        $validIds = $this->filterValidActiveStudyProgramIds($studyProgramIds);

        $startedTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedTx = true;
        }

        try {
            $stmtDel = $this->db->prepare("DELETE FROM civitas_study_programs WHERE civitas_id = ?");
            $stmtDel->execute([trim($civitasId)]);

            if (!empty($validIds)) {
                $stmtIns = $this->db->prepare(
                    "INSERT INTO civitas_study_programs (id, civitas_id, study_program_id, created_at)
                     VALUES (?, ?, ?, NOW())
                     ON CONFLICT (civitas_id, study_program_id) DO NOTHING"
                );
                foreach ($validIds as $spId) {
                    $stmtIns->execute([Uuid::uuid4()->toString(), trim($civitasId), $spId]);
                }
            }

            if ($startedTx) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($startedTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cari jurusan aktif berdasarkan nama atau kode (untuk pencocokan import CSV / fallback teks)
     */
    public function findDepartmentByText(?string $text): ?array
    {
        $clean = trim((string) $text);
        if ($clean === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM departments
             WHERE deleted_at IS NULL
               AND (LOWER(name) = LOWER(?) OR (code IS NOT NULL AND LOWER(code) = LOWER(?)))
             LIMIT 1"
        );
        $stmt->execute([$clean, $clean]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

