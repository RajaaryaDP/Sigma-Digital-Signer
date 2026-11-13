<?php

require_once __DIR__ . '/../models/DepartmentsModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class Departments
{
    private $model;
    private $audit;
    private $load;
    private $adminId;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola Master Jurusan & Program Studi.'
            ];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        $this->model   = new DepartmentsModel();
        $this->audit   = new AuditLogModel();
        $this->load    = new Loader();
        $this->adminId = $_SESSION['user']['id'];
    }

    /**
     * Tampilkan halaman terpadu Master Jurusan beserta daftar Prodi di bawahnya
     */
    public function index()
    {
        $search      = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $departments = $this->model->getDepartmentsWithStudyPrograms($search);

        $totalProdi = 0;
        foreach ($departments as $dept) {
            $totalProdi += count($dept['study_programs']);
        }

        $this->load->view('views/departments/index.php', [
            'title'       => 'Master Jurusan & Prodi — SIGMA',
            'departments' => $departments,
            'search'      => $search,
            'totalProdi'  => $totalProdi,
        ]);
    }

    /**
     * Form tambah Jurusan baru
     */
    public function create()
    {
        $this->load->view('views/departments/form.php', [
            'title'      => 'Tambah Jurusan — SIGMA',
            'isEdit'     => false,
            'department' => null,
            'old'        => [],
            'errors'     => [],
        ]);
    }

    /**
     * Proses simpan Jurusan baru (POST)
     */
    public function store()
    {
        $input = [
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
        ];

        $errors = $this->validateDepartment($input);

        if (!empty($errors)) {
            $this->load->view('views/departments/form.php', [
                'title'      => 'Tambah Jurusan — SIGMA',
                'isEdit'     => false,
                'department' => null,
                'old'        => $input,
                'errors'     => $errors,
            ]);
            return;
        }

        try {
            $newId = $this->model->createDepartment($input);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Nama atau kode jurusan tersebut sudah terdaftar.';
                $this->load->view('views/departments/form.php', [
                    'title'      => 'Tambah Jurusan — SIGMA',
                    'isEdit'     => false,
                    'department' => null,
                    'old'        => $input,
                    'errors'     => $errors,
                ]);
                return;
            }
            throw $e;
        }

        $this->audit->log($this->adminId, 'department.create', [
            'department_id' => $newId,
            'code'          => $input['code'],
            'name'          => $input['name'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Jurusan "' . $input['name'] . '" berhasil ditambahkan.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Form ubah data Jurusan
     */
    public function edit(string $id)
    {
        $department = $this->model->getDepartmentById($id);
        if (!$department) {
            $this->render404();
        }

        $this->load->view('views/departments/form.php', [
            'title'      => 'Ubah Jurusan — SIGMA',
            'isEdit'     => true,
            'department' => $department,
            'old'        => $department,
            'errors'     => [],
        ]);
    }

    /**
     * Proses perbarui data Jurusan (POST)
     */
    public function update(string $id)
    {
        $department = $this->model->getDepartmentById($id);
        if (!$department) {
            $this->render404();
        }

        $input = [
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'name' => trim((string) ($_POST['name'] ?? '')),
        ];

        $errors = $this->validateDepartment($input, $id);

        if (!empty($errors)) {
            $this->load->view('views/departments/form.php', [
                'title'      => 'Ubah Jurusan — SIGMA',
                'isEdit'     => true,
                'department' => $department,
                'old'        => $input,
                'errors'     => $errors,
            ]);
            return;
        }

        try {
            $this->model->updateDepartment($id, $input);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Nama atau kode jurusan tersebut sudah digunakan oleh jurusan lain.';
                $this->load->view('views/departments/form.php', [
                    'title'      => 'Ubah Jurusan — SIGMA',
                    'isEdit'     => true,
                    'department' => $department,
                    'old'        => $input,
                    'errors'     => $errors,
                ]);
                return;
            }
            throw $e;
        }

        $this->audit->log($this->adminId, 'department.update', [
            'department_id' => $id,
            'old_name'      => $department['name'],
            'new_name'      => $input['name'],
            'code'          => $input['code'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Data jurusan "' . $input['name'] . '" berhasil diperbarui.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Hapus (soft delete) Jurusan (POST)
     */
    public function delete(string $id)
    {
        $department = $this->model->getDepartmentById($id);
        if (!$department) {
            $this->render404();
        }

        $activeProdiCount = $this->model->countActiveStudyProgramsByDepartment($id);
        if ($activeProdiCount > 0) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Jurusan "' . $department['name'] . '" tidak dapat dihapus karena masih memiliki '
                    . $activeProdiCount . ' Program Studi aktif. Hapus atau pindahkan prodi terlebih dahulu.'
            ];
            header('Location: ' . BASE_URL . '/departments');
            exit;
        }

        $activeCivitasCount = $this->model->countActiveCivitasByDepartment($id);
        if ($activeCivitasCount > 0) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Jurusan "' . $department['name'] . '" tidak dapat dihapus karena masih digunakan oleh '
                    . $activeCivitasCount . ' data civitas aktif.'
            ];
            header('Location: ' . BASE_URL . '/departments');
            exit;
        }

        $this->model->softDeleteDepartment($id);

        $this->audit->log($this->adminId, 'department.delete', [
            'department_id' => $id,
            'code'          => $department['code'],
            'name'          => $department['name'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Jurusan "' . $department['name'] . '" berhasil dihapus.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Validasi input form Jurusan
     */
    private function validateDepartment(array $input, ?string $excludeId = null): array
    {
        $errors = [];

        if ($input['name'] === '') {
            $errors[] = 'Nama Jurusan wajib diisi.';
        } elseif (mb_strlen($input['name']) > 150) {
            $errors[] = 'Nama Jurusan maksimal 150 karakter.';
        } elseif ($this->model->existsDepartmentName($input['name'], $excludeId)) {
            $errors[] = 'Nama Jurusan "' . $input['name'] . '" sudah terdaftar.';
        }

        if ($input['code'] !== '') {
            if (mb_strlen($input['code']) > 30) {
                $errors[] = 'Kode Jurusan maksimal 30 karakter.';
            } elseif (!preg_match('/^[A-Z0-9_-]+$/', $input['code'])) {
                $errors[] = 'Kode Jurusan hanya boleh berisi huruf, angka, strip (-), atau underscore (_).';
            } elseif ($this->model->existsDepartmentCode($input['code'], $excludeId)) {
                $errors[] = 'Kode Jurusan "' . $input['code'] . '" sudah digunakan.';
            }
        }

        return $errors;
    }

    private function render404(): void
    {
        http_response_code(404);
        echo '404 Not Found';
        exit;
    }
}

