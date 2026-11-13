<?php

require_once __DIR__ . '/../models/DepartmentsModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class Prodi
{
    private $model;
    private $audit;
    private $load;
    private $adminId;

    private const ALLOWED_DEGREES = ['D3', 'D4', 'S1 Terapan', 'S2 Terapan', 'Profesi'];

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengelola Program Studi.'
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
     * Arahkan /prodi ke halaman terpadu /departments
     */
    public function index()
    {
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Form tambah Program Studi baru
     */
    public function create()
    {
        $departments = $this->model->getAllDepartments();
        if (empty($departments)) {
            $_SESSION['flash'] = [
                'type'    => 'warning',
                'message' => 'Tambahkan minimal 1 Jurusan terlebih dahulu sebelum membuat Program Studi.'
            ];
            header('Location: ' . BASE_URL . '/departments/create');
            exit;
        }

        $preselectedDeptId = trim((string) ($_GET['department_id'] ?? ''));
        if ($preselectedDeptId !== '' && !$this->model->getDepartmentById($preselectedDeptId)) {
            $preselectedDeptId = '';
        }

        $this->load->view('views/prodi/form.php', [
            'title'       => 'Tambah Program Studi — SIGMA',
            'isEdit'      => false,
            'prodi'       => null,
            'departments' => $departments,
            'degrees'     => self::ALLOWED_DEGREES,
            'old'         => ['department_id' => $preselectedDeptId, 'degree' => 'D4'],
            'errors'      => [],
        ]);
    }

    /**
     * Proses simpan Program Studi baru (POST)
     */
    public function store()
    {
        $departments = $this->model->getAllDepartments();
        $input = [
            'department_id' => trim((string) ($_POST['department_id'] ?? '')),
            'degree'        => trim((string) ($_POST['degree'] ?? '')),
            'code'          => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'name'          => trim((string) ($_POST['name'] ?? '')),
        ];

        $errors = $this->validateProdi($input);

        if (!empty($errors)) {
            $this->load->view('views/prodi/form.php', [
                'title'       => 'Tambah Program Studi — SIGMA',
                'isEdit'      => false,
                'prodi'       => null,
                'departments' => $departments,
                'degrees'     => self::ALLOWED_DEGREES,
                'old'         => $input,
                'errors'      => $errors,
            ]);
            return;
        }

        try {
            $newId = $this->model->createStudyProgram($input);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Nama prodi pada jurusan tersebut atau kode prodi sudah terdaftar.';
                $this->load->view('views/prodi/form.php', [
                    'title'       => 'Tambah Program Studi — SIGMA',
                    'isEdit'      => false,
                    'prodi'       => null,
                    'departments' => $departments,
                    'degrees'     => self::ALLOWED_DEGREES,
                    'old'         => $input,
                    'errors'      => $errors,
                ]);
                return;
            }
            throw $e;
        }

        $this->audit->log($this->adminId, 'prodi.create', [
            'prodi_id'      => $newId,
            'department_id' => $input['department_id'],
            'degree'        => $input['degree'],
            'code'          => $input['code'],
            'name'          => $input['name'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Program Studi "' . $input['name'] . '" berhasil ditambahkan.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Form ubah data Program Studi
     */
    public function edit(string $id)
    {
        $prodi = $this->model->getStudyProgramById($id);
        if (!$prodi) {
            $this->render404();
        }

        $departments = $this->model->getAllDepartments();

        $this->load->view('views/prodi/form.php', [
            'title'       => 'Ubah Program Studi — SIGMA',
            'isEdit'      => true,
            'prodi'       => $prodi,
            'departments' => $departments,
            'degrees'     => self::ALLOWED_DEGREES,
            'old'         => $prodi,
            'errors'      => [],
        ]);
    }

    /**
     * Proses perbarui data Program Studi (POST)
     */
    public function update(string $id)
    {
        $prodi = $this->model->getStudyProgramById($id);
        if (!$prodi) {
            $this->render404();
        }

        $departments = $this->model->getAllDepartments();
        $input = [
            'department_id' => trim((string) ($_POST['department_id'] ?? '')),
            'degree'        => trim((string) ($_POST['degree'] ?? '')),
            'code'          => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'name'          => trim((string) ($_POST['name'] ?? '')),
        ];

        $errors = $this->validateProdi($input, $id);

        if (!empty($errors)) {
            $this->load->view('views/prodi/form.php', [
                'title'       => 'Ubah Program Studi — SIGMA',
                'isEdit'      => true,
                'prodi'       => $prodi,
                'departments' => $departments,
                'degrees'     => self::ALLOWED_DEGREES,
                'old'         => $input,
                'errors'      => $errors,
            ]);
            return;
        }

        try {
            $this->model->updateStudyProgram($id, $input);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Nama prodi pada jurusan tersebut atau kode prodi sudah digunakan.';
                $this->load->view('views/prodi/form.php', [
                    'title'       => 'Ubah Program Studi — SIGMA',
                    'isEdit'      => true,
                    'prodi'       => $prodi,
                    'departments' => $departments,
                    'degrees'     => self::ALLOWED_DEGREES,
                    'old'         => $input,
                    'errors'      => $errors,
                ]);
                return;
            }
            throw $e;
        }

        $this->audit->log($this->adminId, 'prodi.update', [
            'prodi_id'      => $id,
            'department_id' => $input['department_id'],
            'old_name'      => $prodi['name'],
            'new_name'      => $input['name'],
            'degree'        => $input['degree'],
            'code'          => $input['code'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Program Studi "' . $input['name'] . '" berhasil diperbarui.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Hapus (soft delete) Program Studi (POST)
     */
    public function delete(string $id)
    {
        $prodi = $this->model->getStudyProgramById($id);
        if (!$prodi) {
            $this->render404();
        }

        $activeCivitas = $this->model->countActiveCivitasByStudyProgram($id);
        if ($activeCivitas > 0) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Program Studi "' . $prodi['name'] . '" tidak dapat dihapus karena masih digunakan oleh '
                    . $activeCivitas . ' civitas/dosen aktif. Lepas relasi prodi dari data civitas terlebih dahulu.'
            ];
            header('Location: ' . BASE_URL . '/departments');
            exit;
        }

        $this->model->softDeleteStudyProgram($id);

        $this->audit->log($this->adminId, 'prodi.delete', [
            'prodi_id'      => $id,
            'department_id' => $prodi['department_id'],
            'code'          => $prodi['code'],
            'name'          => $prodi['name'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Program Studi "' . $prodi['name'] . '" berhasil dihapus.'
        ];
        header('Location: ' . BASE_URL . '/departments');
        exit;
    }

    /**
     * Validasi input form Program Studi
     */
    private function validateProdi(array $input, ?string $excludeId = null): array
    {
        $errors = [];

        if ($input['department_id'] === '' || !$this->model->getDepartmentById($input['department_id'])) {
            $errors[] = 'Jurusan induk wajib dipilih dan harus aktif.';
        }

        if ($input['name'] === '') {
            $errors[] = 'Nama Program Studi wajib diisi.';
        } elseif (mb_strlen($input['name']) > 150) {
            $errors[] = 'Nama Program Studi maksimal 150 karakter.';
        } elseif (
            $input['department_id'] !== ''
            && $this->model->existsStudyProgramName($input['department_id'], $input['name'], $excludeId)
        ) {
            $errors[] = 'Program Studi "' . $input['name'] . '" sudah terdaftar pada jurusan tersebut.';
        }

        if ($input['degree'] !== '' && !in_array($input['degree'], self::ALLOWED_DEGREES, true)) {
            $errors[] = 'Jenjang Program Studi tidak valid.';
        }

        if ($input['code'] !== '') {
            if (mb_strlen($input['code']) > 30) {
                $errors[] = 'Kode Program Studi maksimal 30 karakter.';
            } elseif (!preg_match('/^[A-Z0-9_-]+$/', $input['code'])) {
                $errors[] = 'Kode Prodi hanya boleh berisi huruf, angka, strip (-), atau underscore (_).';
            } elseif ($this->model->existsStudyProgramCode($input['code'], $excludeId)) {
                $errors[] = 'Kode Program Studi "' . $input['code'] . '" sudah digunakan.';
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

