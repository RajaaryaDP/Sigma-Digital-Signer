<?php

require_once __DIR__ . '/../models/CivitasModel.php';
require_once __DIR__ . '/../models/DepartmentsModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class Civitas
{
    private $civitasModel;
    private $departmentsModel;
    private $auditModel;
    private $load;

    public function __construct()
    {
        // Guard ketat khusus peran Admin
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Akses ditolak: Hanya Administrator yang berwenang mengelola Master Data Civitas.'
            ];
            header('Location: ' . BASE_URL . '/documents');
            exit;
        }

        $this->civitasModel     = new CivitasModel();
        $this->departmentsModel = new DepartmentsModel();
        $this->auditModel       = new AuditLogModel();
        $this->load             = new Loader();
    }

    /**
     * Menampilkan daftar civitas kampus (Dosen, Mahasiswa, Tendik)
     * Dilengkapi fitur pencarian dan filter tab kategori
     */
    public function index()
    {
        $search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
        $type   = isset($_GET['type']) && is_string($_GET['type']) ? trim($_GET['type']) : '';

        $civitasList = $this->civitasModel->getCivitas($search, $type);
        $counts      = $this->civitasModel->countByType();

        $this->load->view('views/civitas/index.php', [
            'title'       => 'Master Data Civitas Kampus — SIGMA',
            'civitasList' => $civitasList,
            'counts'      => $counts,
            'search'      => $search,
            'currentType' => $type,
        ]);
    }

    /**
     * Menampilkan formulir tambah data civitas baru
     */
    public function create()
    {
        $departmentsWithProdi = $this->departmentsModel->getDepartmentsWithStudyPrograms();

        $this->load->view('views/civitas/form.php', [
            'title'                => 'Tambah Civitas Kampus — SIGMA',
            'isEdit'               => false,
            'values'               => [
                'type'              => 'mahasiswa', // default
                'department_id'     => '',
                'study_program_ids' => [],
            ],
            'departmentsWithProdi' => $departmentsWithProdi,
            'errors'               => [],
            'id'                   => null,
        ]);
    }

    /**
     * Memproses penyimpanan data civitas baru (POST)
     */
    public function store()
    {
        $departmentsWithProdi = $this->departmentsModel->getDepartmentsWithStudyPrograms();
        $rawDepartmentId      = isset($_POST['department_id']) && is_string($_POST['department_id']) ? trim($_POST['department_id']) : '';
        $rawProdiIds          = is_array($_POST['study_program_ids'] ?? null) ? $_POST['study_program_ids'] : [];
        $validProdiIds        = $this->departmentsModel->filterValidActiveStudyProgramIds($rawProdiIds);

        $departmentId   = null;
        $departmentText = isset($_POST['department']) && is_string($_POST['department']) ? trim($_POST['department']) : '';
        $selectedDept   = null;

        if ($rawDepartmentId !== '') {
            $selectedDept = $this->departmentsModel->getDepartmentById($rawDepartmentId);
            if ($selectedDept) {
                $departmentId = $selectedDept['id'];
                if ($departmentText === '') {
                    $departmentText = $selectedDept['name'];
                }
            }
        } elseif (!empty($validProdiIds) && $departmentText === '') {
            $firstProdi = $this->departmentsModel->getStudyProgramById($validProdiIds[0]);
            if ($firstProdi) {
                $departmentId   = $firstProdi['department_id'];
                $departmentText = $firstProdi['department_name'];
            }
        }

        $data = [
            'identifier'        => isset($_POST['identifier']) && is_string($_POST['identifier']) ? trim($_POST['identifier']) : '',
            'name'              => isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '',
            'type'              => isset($_POST['type']) && is_string($_POST['type']) ? trim($_POST['type']) : '',
            'department_id'     => $departmentId,
            'raw_department_id' => $rawDepartmentId,
            'department'        => $departmentText,
            'position'          => isset($_POST['position']) && is_string($_POST['position']) ? trim($_POST['position']) : '',
            'email'             => isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '',
            'phone'             => isset($_POST['phone']) && is_string($_POST['phone']) ? trim($_POST['phone']) : '',
            'study_program_ids' => $validProdiIds,
            'avatar_path'       => null,
        ];

        $errors = $this->validate($data);
        if ($rawDepartmentId !== '' && !$selectedDept) {
            $errors['department_id'] = 'Jurusan yang dipilih tidak valid atau sudah tidak aktif.';
        }

        if (!empty($errors)) {
            $this->load->view('views/civitas/form.php', [
                'title'                => 'Tambah Civitas Kampus — SIGMA',
                'isEdit'               => false,
                'values'               => $data,
                'departmentsWithProdi' => $departmentsWithProdi,
                'errors'               => $errors,
                'id'                   => null,
            ]);
            return;
        }

        try {
            $newId = $this->civitasModel->create($data);

            // Catat mutasi penambahan ke riwayat audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.create',
                [
                    'civitas_id'        => $newId,
                    'identifier'        => $data['identifier'],
                    'name'              => $data['name'],
                    'type'              => $data['type'],
                    'department_id'     => $data['department_id'],
                    'department'        => $data['department'],
                    'study_program_ids' => $data['study_program_ids'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($data['name']) . '" berhasil ditambahkan.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::store] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menyimpan data civitas karena kendala pada basis data. Pastikan NIM/NIP dan Email belum terdaftar.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Menampilkan formulir edit data civitas
     */
    public function edit(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        $departmentsWithProdi = $this->departmentsModel->getDepartmentsWithStudyPrograms();

        $this->load->view('views/civitas/form.php', [
            'title'                => 'Ubah Data Civitas — SIGMA',
            'isEdit'               => true,
            'values'               => $civitas,
            'departmentsWithProdi' => $departmentsWithProdi,
            'errors'               => [],
            'id'                   => $id,
        ]);
    }

    /**
     * Memproses pembaruan data civitas (POST)
     */
    public function update(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        $departmentsWithProdi = $this->departmentsModel->getDepartmentsWithStudyPrograms();
        $rawDepartmentId      = isset($_POST['department_id']) && is_string($_POST['department_id']) ? trim($_POST['department_id']) : '';
        $rawProdiIds          = is_array($_POST['study_program_ids'] ?? null) ? $_POST['study_program_ids'] : [];
        $validProdiIds        = $this->departmentsModel->filterValidActiveStudyProgramIds($rawProdiIds);

        $departmentId   = null;
        $departmentText = isset($_POST['department']) && is_string($_POST['department']) ? trim($_POST['department']) : '';
        $selectedDept   = null;

        if ($rawDepartmentId !== '') {
            $selectedDept = $this->departmentsModel->getDepartmentById($rawDepartmentId);
            if ($selectedDept) {
                $departmentId = $selectedDept['id'];
                if ($departmentText === '') {
                    $departmentText = $selectedDept['name'];
                }
            }
        } elseif (!empty($validProdiIds) && $departmentText === '') {
            $firstProdi = $this->departmentsModel->getStudyProgramById($validProdiIds[0]);
            if ($firstProdi) {
                $departmentId   = $firstProdi['department_id'];
                $departmentText = $firstProdi['department_name'];
            }
        }

        $data = [
            'identifier'        => isset($_POST['identifier']) && is_string($_POST['identifier']) ? trim($_POST['identifier']) : '',
            'name'              => isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '',
            'type'              => isset($_POST['type']) && is_string($_POST['type']) ? trim($_POST['type']) : '',
            'department_id'     => $departmentId,
            'raw_department_id' => $rawDepartmentId,
            'department'        => $departmentText,
            'position'          => isset($_POST['position']) && is_string($_POST['position']) ? trim($_POST['position']) : '',
            'email'             => isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '',
            'phone'             => isset($_POST['phone']) && is_string($_POST['phone']) ? trim($_POST['phone']) : '',
            'study_program_ids' => $validProdiIds,
            'avatar_path'       => $civitas['avatar_path'],
        ];

        $errors = $this->validate($data, $id);
        if ($rawDepartmentId !== '' && !$selectedDept) {
            $errors['department_id'] = 'Jurusan yang dipilih tidak valid atau sudah tidak aktif.';
        }

        if (!empty($errors)) {
            $this->load->view('views/civitas/form.php', [
                'title'                => 'Ubah Data Civitas — SIGMA',
                'isEdit'               => true,
                'values'               => $data,
                'departmentsWithProdi' => $departmentsWithProdi,
                'errors'               => $errors,
                'id'                   => $id,
            ]);
            return;
        }

        try {
            $this->civitasModel->update($id, $data);

            // Catat mutasi perubahan ke riwayat audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.update',
                [
                    'civitas_id'        => $id,
                    'identifier'        => $data['identifier'],
                    'name'              => $data['name'],
                    'type'              => $data['type'],
                    'department_id'     => $data['department_id'],
                    'study_program_ids' => $data['study_program_ids'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($data['name']) . '" berhasil diperbarui.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::update] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal memperbarui data civitas karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Memproses penghapusan data civitas (soft delete) (POST)
     */
    public function delete(string $id)
    {
        $civitas = $this->civitasModel->getById($id);

        if (!$civitas) {
            http_response_code(404);
            echo 'Data civitas tidak ditemukan.';
            exit;
        }

        try {
            $this->civitasModel->delete($id);

            // Catat mutasi penghapusan ke audit trail
            $this->auditModel->log(
                $_SESSION['user']['id'],
                'civitas.delete',
                [
                    'civitas_id' => $id,
                    'identifier' => $civitas['identifier'],
                    'name'       => $civitas['name'],
                ]
            );

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Data civitas "' . htmlspecialchars($civitas['name']) . '" (' . htmlspecialchars($civitas['identifier']) . ') telah dihapus.'
            ];
        } catch (\Throwable $e) {
            error_log('[Civitas::delete] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menghapus data civitas karena kendala pada basis data.'
            ];
        }

        header('Location: ' . BASE_URL . '/civitas');
        exit;
    }

    /**
     * Mengunduh berkas template resmi CSV untuk Import Massal Civitas (GET /civitas/template)
     * Struktur kolom baku: identifier,name,email,type,department,position,phone
     */
    public function template()
    {
        $filename = 'template_civitas_sigma.csv';

        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        $out = fopen('php://output', 'w');
        // Header wajib sesuai spesifikasi Task 10
        fputcsv($out, ['identifier', 'name', 'email', 'type', 'department', 'position', 'phone'], ',', '"', '');

        // Contoh baris data referensi (Mahasiswa, Dosen, Tendik)
        fputcsv($out, [
            '2207411099',
            'Rizky Pratama Putra',
            'rizky.pratama.tik22@stu.pnj.ac.id',
            'mahasiswa',
            'Teknik Informatika dan Komputer',
            'Mahasiswa Aktif D4 Teknik Informatika',
            '081234567890',
        ], ',', '"', '');

        fputcsv($out, [
            '198504122010121004',
            'Dr. Hendra Wijaya, S.Kom., M.T.',
            'hendra.wijaya@pnj.ac.id',
            'dosen',
            'Teknik Informatika dan Komputer',
            'Dosen Pembimbing Akademik',
            '081298765432',
        ], ',', '"', '');

        fputcsv($out, [
            '199008152015032001',
            'Siti Nurhaliza, S.AP.',
            'siti.nurhaliza@pnj.ac.id',
            'tendik',
            'Subbagian Akademik dan Kemahasiswaan',
            'Staf Administrasi Akademik',
            '081311223344',
        ], ',', '"', '');

        fclose($out);
        exit;
    }

    /**
     * Modul Import Massal Master Data Civitas via CSV (GET & POST /civitas/import)
     * - GET: Menampilkan halaman unggah berkas CSV atau pratinjau interaktif yang sedang aktif
     * - POST (stage=preview): Memvalidasi berkas, header wajib, dan menyusun pratinjau interaktif
     * - POST (stage=commit): Menyimpan baris valid secara atomik (beginTransaction / rollBack)
     */
    public function import()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['reset']) && $_GET['reset'] === '1') {
                unset($_SESSION['civitas_import_preview']);
                header('Location: ' . BASE_URL . '/civitas/import');
                exit;
            }

            $this->load->view('views/civitas/import.php', [
                'title'   => 'Import Massal Civitas (CSV) — SIGMA',
                'preview' => $_SESSION['civitas_import_preview'] ?? null,
                'errors'  => [],
            ]);
            return;
        }

        // Метоde POST: periksa tahapan (preview, commit, atau cancel)
        $stage = isset($_POST['stage']) && is_string($_POST['stage']) ? trim($_POST['stage']) : 'preview';

        if ($stage === 'cancel') {
            unset($_SESSION['civitas_import_preview']);
            $_SESSION['flash'] = [
                'type'    => 'info',
                'message' => 'Pratinjau import CSV telah dibatalkan. Tidak ada perubahan pada database.'
            ];
            header('Location: ' . BASE_URL . '/civitas/import');
            exit;
        }

        if ($stage === 'commit') {
            $preview = $_SESSION['civitas_import_preview'] ?? null;
            if (empty($preview) || !isset($preview['all_rows']) || !is_array($preview['all_rows'])) {
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Sesi pratinjau CSV tidak ditemukan atau sudah kedaluwarsa. Silakan unggah ulang berkas CSV Anda.'
                ];
                header('Location: ' . BASE_URL . '/civitas/import');
                exit;
            }

            try {
                $result = $this->civitasModel->bulkImport($preview['all_rows']);
                unset($_SESSION['civitas_import_preview']);

                $insertedCount = (int) ($result['inserted'] ?? 0);
                $skippedCount  = (int) ($result['skipped'] ?? 0);
                $totalRows     = (int) ($preview['total_rows'] ?? ($insertedCount + $skippedCount));

                // Catat aktivitas import massal ke tabel audit_logs
                $this->auditModel->log(
                    $_SESSION['user']['id'],
                    'civitas.bulk_imported',
                    [
                        'filename'       => $preview['filename'] ?? 'civitas.csv',
                        'total_rows'     => $totalRows,
                        'inserted_count' => $insertedCount,
                        'skipped_count'  => $skippedCount,
                    ]
                );

                if ($insertedCount > 0) {
                    $msg = sprintf(
                        'Import massal CSV berhasil: %d data civitas baru telah disimpan ke database.',
                        $insertedCount
                    );
                    if ($skippedCount > 0) {
                        $msg .= sprintf(' (%d baris duplikat/tidak valid dilewati otomatis).', $skippedCount);
                    }
                    $_SESSION['flash'] = [
                        'type'    => 'success',
                        'message' => $msg,
                    ];
                } else {
                    $_SESSION['flash'] = [
                        'type'    => 'warning',
                        'message' => sprintf(
                            'Tidak ada data baru yang ditambahkan. Seluruh %d baris pada berkas CSV merupakan duplikat atau tidak valid dan telah dilewati.',
                            $skippedCount
                        ),
                    ];
                }
            } catch (\Throwable $e) {
                error_log('[Civitas::import commit] ' . $e->getMessage());
                $_SESSION['flash'] = [
                    'type'    => 'danger',
                    'message' => 'Terjadi kegagalan saat menyimpan data import CSV. Transaksi telah dibatalkan otomatis (rollback) sehingga database tetap bersih.'
                ];
            }

            header('Location: ' . BASE_URL . '/civitas');
            exit;
        }

        // Tahap 1 (stage === 'preview'): Validasi unggahan berkas CSV & Header Wajib
        unset($_SESSION['civitas_import_preview']);
        $errors = [];

        if (empty($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            $errors[] = 'Pilih berkas CSV terlebih dahulu sebelum melakukan pratinjau.';
            $this->renderImportView(null, $errors);
            return;
        }

        $file     = $_FILES['csv_file'];
        $errCode  = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $origName = isset($file['name']) && is_string($file['name']) ? trim($file['name']) : '';
        $tmpPath  = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
        $fileSize = (int) ($file['size'] ?? 0);
        $maxSize  = 5 * 1024 * 1024; // Maksimal 5 MB

        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = 'Ukuran berkas CSV melebihi batas maksimal 5 MB.';
        } elseif ($errCode === UPLOAD_ERR_NO_FILE || $tmpPath === '') {
            $errors[] = 'Berkas CSV wajib dipilih untuk diunggah.';
        } elseif ($errCode !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah berkas CSV (Kode: ' . $errCode . ').';
        }

        if (empty($errors)) {
            if ($fileSize <= 0) {
                $errors[] = 'Berkas CSV yang diunggah kosong (0 byte).';
            } elseif ($fileSize > $maxSize) {
                $errors[] = 'Ukuran berkas CSV melebihi batas maksimal 5 MB.';
            }

            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $errors[] = 'Format berkas ditolak: Hanya berkas berekstensi .csv yang diizinkan.';
            }
        }

        if (empty($errors)) {
            if (!is_file($tmpPath) || !is_readable($tmpPath)) {
                $errors[] = 'Berkas sementara CSV tidak dapat dibaca oleh server.';
            } else {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime  = strtolower((string) $finfo->file($tmpPath));
                $allowedMimes = [
                    'text/csv',
                    'text/plain',
                    'application/csv',
                    'application/x-csv',
                    'text/x-csv',
                    'text/comma-separated-values',
                    'text/x-comma-separated-values',
                    'application/vnd.ms-excel',
                ];

                // Tolak berkas biner yang menyamar sebagai .csv
                $rawHead = (string) file_get_contents($tmpPath, false, null, 0, 4096);
                if (strpos($rawHead, "\0") !== false || !in_array($mime, $allowedMimes, true)) {
                    $errors[] = 'Tipe MIME berkas tidak valid (' . htmlspecialchars($mime) . '). Pastikan berkas merupakan teks CSV murni (text/csv atau text/plain), bukan berkas Excel .xlsx atau biner.';
                }
            }
        }

        if (!empty($errors)) {
            $this->renderImportView(null, $errors);
            return;
        }

        // Buka berkas dan periksa baris pertama (Header Wajib — Anti-Wrong-File)
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            $this->renderImportView(null, ['Gagal membuka berkas CSV untuk pembacaan.']);
            return;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $this->renderImportView(null, ['Berkas CSV tidak memiliki baris header.']);
            return;
        }

        // Bersihkan UTF-8 BOM jika ada (umum pada CSV keluaran Excel Windows)
        if (strncmp($firstLine, "\xEF\xBB\xBF", 3) === 0) {
            $firstLine = substr($firstLine, 3);
        }

        // Deteksi otomatis delimiter koma (,) atau titik-koma (;)
        $commaCount     = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $delimiter      = ($semicolonCount > $commaCount) ? ';' : ',';

        $rawHeaders = str_getcsv(trim($firstLine, "\r\n"), $delimiter, '"', '');
        $headerMap  = [];
        foreach ($rawHeaders as $idx => $colName) {
            $cleanCol = strtolower(trim(str_replace("\0", '', mb_scrub((string) $colName, 'UTF-8')), " \t\n\r\0\x0B\"'"));
            if ($cleanCol !== '' && !isset($headerMap[$cleanCol])) {
                $headerMap[$cleanCol] = $idx;
            }
        }

        $requiredColumns = ['identifier', 'name', 'email', 'type', 'department', 'position', 'phone'];
        $missingColumns  = [];
        foreach ($requiredColumns as $reqCol) {
            if (!array_key_exists($reqCol, $headerMap)) {
                $missingColumns[] = $reqCol;
            }
        }

        if (!empty($missingColumns)) {
            fclose($handle);
            $errors[] = 'Struktur kolom header CSV tidak sesuai! Kolom wajib yang belum ada: '
                . implode(', ', $missingColumns)
                . '. Struktur baku wajib memuat: identifier, name, email, type, department, position, phone.';
            $this->renderImportView(null, $errors);
            return;
        }

        // Ambil peta keunikan dari database hanya setelah header dinyatakan valid
        $existingLookup  = $this->civitasModel->getExistingLookupMap();
        $dbIdentifiers   = $existingLookup['identifiers'];
        $dbEmails        = $existingLookup['emails'];
        $seenInFileIds   = [];
        $seenInFileEmail = [];

        $allRows        = [];
        $sampleRows     = [];
        $issueRows      = [];
        $validCount     = 0;
        $duplicateCount = 0;
        $invalidCount   = 0;
        $lineNumber     = 1;
        $maxRowsAllowed = 5000;

        while (($rowValues = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $lineNumber++;

            // Lewati baris yang benar-benar kosong
            if ($rowValues === [null] || empty(array_filter($rowValues, fn($v) => trim((string) $v) !== ''))) {
                continue;
            }

            if (count($allRows) >= $maxRowsAllowed) {
                fclose($handle);
                $this->renderImportView(null, [
                    'Jumlah baris dalam satu berkas CSV melebihi batas aman maksimal (' . $maxRowsAllowed . ' baris per unggahan).'
                ]);
                return;
            }

            $identifier = trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['identifier']] ?? ''), 'UTF-8')));
            $name       = trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['name']] ?? ''), 'UTF-8')));
            $email      = strtolower(trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['email']] ?? ''), 'UTF-8'))));
            $type       = strtolower(trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['type']] ?? ''), 'UTF-8'))));
            $department = trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['department']] ?? ''), 'UTF-8')));
            $position   = trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['position']] ?? ''), 'UTF-8')));
            $phone      = trim(str_replace("\0", '', mb_scrub((string) ($rowValues[$headerMap['phone']] ?? ''), 'UTF-8')));

            $rowStatus = 'valid';
            $rowReason = 'Siap diimpor ke database';

            // Validasi kelengkapan & panjang kolom
            $rowErrors = [];
            if ($identifier === '') {
                $rowErrors[] = 'NIM/NIP kosong';
            } elseif (mb_strlen($identifier) > 50) {
                $rowErrors[] = 'NIM/NIP > 50 karakter';
            }

            if ($name === '') {
                $rowErrors[] = 'Nama kosong';
            } elseif (mb_strlen($name) > 255) {
                $rowErrors[] = 'Nama > 255 karakter';
            }

            if ($email === '') {
                $rowErrors[] = 'Email kosong';
            } elseif (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Format email tidak valid';
            }

            if (!in_array($type, ['dosen', 'mahasiswa', 'tendik'], true)) {
                $rowErrors[] = 'Tipe harus dosen/mahasiswa/tendik';
            }

            if ($department === '') {
                $rowErrors[] = 'Jurusan/Unit kosong';
            } elseif (mb_strlen($department) > 150) {
                $rowErrors[] = 'Jurusan/Unit > 150 karakter';
            }

            if ($position === '') {
                $rowErrors[] = 'Jabatan kosong';
            } elseif (mb_strlen($position) > 150) {
                $rowErrors[] = 'Jabatan > 150 karakter';
            }

            if ($phone !== '' && mb_strlen($phone) > 32) {
                $rowErrors[] = 'No. Telepon > 32 karakter';
            }

            if (!empty($rowErrors)) {
                $rowStatus = 'invalid';
                $rowReason = 'Tidak Valid: ' . implode(', ', $rowErrors);
                $invalidCount++;
            } else {
                $idKey    = strtolower($identifier);
                $emailKey = strtolower($email);

                if (isset($dbIdentifiers[$idKey])) {
                    $rowStatus = 'duplicate';
                    $rowReason = 'Duplikat: NIM/NIP sudah terdaftar di database';
                    $duplicateCount++;
                } elseif (isset($dbEmails[$emailKey])) {
                    $rowStatus = 'duplicate';
                    $rowReason = 'Duplikat: Email sudah terdaftar di database';
                    $duplicateCount++;
                } elseif (isset($seenInFileIds[$idKey])) {
                    $rowStatus = 'duplicate';
                    $rowReason = 'Duplikat: NIM/NIP ganda di dalam berkas CSV (baris #' . $seenInFileIds[$idKey] . ')';
                    $duplicateCount++;
                } elseif (isset($seenInFileEmail[$emailKey])) {
                    $rowStatus = 'duplicate';
                    $rowReason = 'Duplikat: Email ganda di dalam berkas CSV (baris #' . $seenInFileEmail[$emailKey] . ')';
                    $duplicateCount++;
                } else {
                    $validCount++;
                    $seenInFileIds[$idKey]     = $lineNumber;
                    $seenInFileEmail[$emailKey] = $lineNumber;
                }
            }

            $parsedRow = [
                'line'       => $lineNumber,
                'identifier' => $identifier,
                'name'       => $name,
                'email'      => $email,
                'type'       => $type,
                'department' => $department,
                'position'   => $position,
                'phone'      => $phone,
                'status'     => $rowStatus,
                'reason'     => $rowReason,
            ];

            $allRows[] = $parsedRow;

            if (count($sampleRows) < 10) {
                $sampleRows[] = $parsedRow;
            }
            if ($rowStatus !== 'valid' && count($issueRows) < 15) {
                $issueRows[] = $parsedRow;
            }
        }

        fclose($handle);

        if (empty($allRows)) {
            $this->renderImportView(null, ['Berkas CSV hanya berisi baris header dan tidak memiliki baris data untuk diimpor.']);
            return;
        }

        $previewData = [
            'filename'        => mb_substr($origName, 0, 120),
            'uploaded_at'     => date('Y-m-d H:i:s'),
            'delimiter'       => $delimiter,
            'total_rows'      => count($allRows),
            'valid_count'     => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count'   => $invalidCount,
            'sample_rows'     => $sampleRows,
            'issue_rows'      => $issueRows,
            'all_rows'        => $allRows,
        ];

        $_SESSION['civitas_import_preview'] = $previewData;
        $this->renderImportView($previewData, []);
    }

    /**
     * Helper untuk menampilkan view import CSV
     */
    private function renderImportView(?array $preview, array $errors): void
    {
        $this->load->view('views/civitas/import.php', [
            'title'   => 'Import Massal Civitas (CSV) — SIGMA',
            'preview' => $preview,
            'errors'  => $errors,
        ]);
    }

    /**
     * Validasi input formulir civitas
     */
    private function validate(array $data, ?string $excludeId = null): array
    {
        $errors = [];

        // 1. Validasi Identifier (NIM / NIP) — VARCHAR(64), batas bisnis 50 karakter
        if (empty($data['identifier'])) {
            $errors['identifier'] = 'Nomor identitas (NIM/NIP) wajib diisi.';
        } elseif (mb_strlen($data['identifier']) > 50) {
            $errors['identifier'] = 'Panjang NIM/NIP maksimal 50 karakter.';
        } else {
            $existing = $this->civitasModel->getByIdentifier($data['identifier'], $excludeId);
            if ($existing) {
                $errors['identifier'] = 'NIM/NIP "' . htmlspecialchars($data['identifier']) . '" sudah terdaftar pada civitas lain.';
            }
        }

        // 2. Validasi Nama Lengkap — VARCHAR(255)
        if (empty($data['name'])) {
            $errors['name'] = 'Nama lengkap beserta gelar resmi wajib diisi.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors['name'] = 'Panjang nama maksimal 255 karakter.';
        }

        // 3. Validasi Kategori Civitas
        if (empty($data['type']) || !in_array($data['type'], ['dosen', 'mahasiswa', 'tendik'], true)) {
            $errors['type'] = 'Pilih kategori civitas yang valid (Dosen, Mahasiswa, atau Tenaga Kependidikan).';
        }

        // 4. Validasi Jurusan / Unit Kerja — VARCHAR(150)
        if (empty($data['department'])) {
            $errors['department'] = 'Jurusan / Program Studi / Unit Kerja wajib diisi.';
        } elseif (mb_strlen($data['department']) > 150) {
            $errors['department'] = 'Panjang Jurusan / Unit Kerja maksimal 150 karakter.';
        }

        // 5. Validasi Jabatan Resmi — VARCHAR(150)
        if (empty($data['position'])) {
            $errors['position'] = 'Jabatan / peran resmi wajib diisi (contoh: Mahasiswa Aktif, Dosen Pembimbing, Ketua Jurusan).';
        } elseif (mb_strlen($data['position']) > 150) {
            $errors['position'] = 'Panjang jabatan / peran resmi maksimal 150 karakter.';
        }

        // 6. Validasi Email Institusi — VARCHAR(255)
        if (empty($data['email'])) {
            $errors['email'] = 'Alamat email institusi wajib diisi.';
        } elseif (mb_strlen($data['email']) > 255) {
            $errors['email'] = 'Panjang alamat email maksimal 255 karakter.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } else {
            $existingEmail = $this->civitasModel->getByEmail($data['email'], $excludeId);
            if ($existingEmail) {
                $errors['email'] = 'Email "' . htmlspecialchars($data['email']) . '" sudah digunakan oleh civitas lain.';
            }
        }

        // 7. Validasi Nomor Telepon — VARCHAR(32)
        if (!empty($data['phone']) && mb_strlen($data['phone']) > 32) {
            $errors['phone'] = 'Panjang nomor telepon maksimal 32 karakter.';
        }

        return $errors;
    }
}
