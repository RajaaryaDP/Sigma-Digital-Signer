<?php

require_once __DIR__ . '/../models/UserSignaturesModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../core/Loader.php';

class UserSignatures
{
    private $signatureModel;
    private $auditModel;
    private $load;
    private $userId;
    private $storageBaseDir;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Pengguna harus sudah login untuk mengakses modul template tanda tangan
        if (empty($_SESSION['user'])) {
            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Silakan masuk terlebih dahulu untuk mengakses template tanda tangan.'
            ];
            header('Location: ' . BASE_URL . '/auth');
            exit;
        }

        $this->signatureModel = new UserSignaturesModel();
        $this->auditModel     = new AuditLogModel();
        $this->load           = new Loader();
        $this->userId         = $_SESSION['user']['id'];
        $this->storageBaseDir = __DIR__ . '/../storage';

        $templateDir = $this->storageBaseDir . '/signatures/templates';
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
    }

    /**
     * Menampilkan daftar koleksi template tanda tangan milik pengguna aktif
     */
    public function index()
    {
        $signatures = $this->signatureModel->getByUserId($this->userId);
        $defaultSig = $this->signatureModel->getDefaultByUserId($this->userId);

        $this->load->view('views/user-signatures/index.php', [
            'title'      => 'Template Tanda Tangan Saya — SIGMA',
            'signatures' => $signatures,
            'defaultSig' => $defaultSig,
        ]);
    }

    /**
     * Menampilkan antarmuka pembuatan template tanda tangan baru
     * (Pilihan Kanvas Digital HTML5 atau Unggah Gambar PNG Transparan)
     */
    public function create()
    {
        $existingCount = $this->signatureModel->countByUserId($this->userId);

        $this->load->view('views/user-signatures/form.php', [
            'title'         => 'Buat Template Tanda Tangan — SIGMA',
            'isEdit'        => false,
            'existingCount' => $existingCount,
            'values'        => [
                'label'      => '',
                'is_default' => ($existingCount === 0) ? 1 : 0,
            ],
            'errors'        => [],
            'id'            => null,
        ]);
    }

    /**
     * Memproses penyimpanan template tanda tangan baru (POST)
     */
    public function store()
    {
        $label       = trim($_POST['label'] ?? '');
        $isDefault   = !empty($_POST['is_default']) ? 1 : 0;
        $inputMethod = trim($_POST['input_method'] ?? 'canvas');
        $errors      = [];

        $templateDir = $this->storageBaseDir . '/signatures/templates';
        $uniqueName  = 'sig_tpl_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.png';
        $destPath    = $templateDir . '/' . $uniqueName;
        $dbPath      = 'signatures/templates/' . $uniqueName;

        if ($inputMethod === 'canvas') {
            $canvasData = trim($_POST['signature_canvas'] ?? '');

            if (empty($canvasData)) {
                $errors['signature'] = 'Silakan bubuhkan goresan tanda tangan Anda pada kanvas.';
            } elseif (!preg_match('/^data:image\/png;base64,(.+)$/', $canvasData, $matches)) {
                $errors['signature'] = 'Format data kanvas tanda tangan tidak valid.';
            } else {
                $decoded = base64_decode($matches[1], true);
                if ($decoded === false || strlen($decoded) < 100) {
                    $errors['signature'] = 'Gambar tanda tangan dari kanvas tidak terbaca atau kosong.';
                } else {
                    $imgInfo = @getimagesizefromstring($decoded);
                    if ($imgInfo === false || $imgInfo['mime'] !== 'image/png') {
                        $errors['signature'] = 'Hasil kanvas harus berupa gambar PNG yang valid.';
                    } else {
                        if (file_put_contents($destPath, $decoded) === false) {
                            $errors['signature'] = 'Gagal menyimpan file tanda tangan ke penyimpanan privat server.';
                        }
                    }
                }
            }
        } elseif ($inputMethod === 'upload') {
            if (!isset($_FILES['signature_file']) || $_FILES['signature_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors['signature'] = 'Silakan pilih berkas gambar tanda tangan PNG untuk diunggah.';
            } elseif ($_FILES['signature_file']['error'] !== UPLOAD_ERR_OK) {
                $errors['signature'] = 'Terjadi kesalahan saat mengunggah berkas tanda tangan.';
            } else {
                $file = $_FILES['signature_file'];
                // Maksimal 2 MB
                if ($file['size'] > 2 * 1024 * 1024) {
                    $errors['signature'] = 'Ukuran berkas tanda tangan melebihi batas maksimal 2 MB.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if ($mime !== 'image/png') {
                        $errors['signature'] = 'Berkas tanda tangan wajib berformat gambar PNG transparan (.png).';
                    } else {
                        // Verifikasi bahwa gambar PNG memiliki latar belakang transparan (alpha channel)
                        $im = @imagecreatefrompng($file['tmp_name']);
                        if ($im === false) {
                            $errors['signature'] = 'Berkas gambar PNG tanda tangan rusak atau tidak dapat diproses.';
                        } else {
                            $w = imagesx($im);
                            $h = imagesy($im);
                            $hasAlpha = false;

                            // Sampling cepat pada titik-titik sudut dan tepi
                            $sampleCoords = [
                                [0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1],
                                [intdiv($w, 2), 0], [intdiv($w, 2), $h - 1],
                                [0, intdiv($h, 2)], [$w - 1, intdiv($h, 2)]
                            ];
                            foreach ($sampleCoords as $coord) {
                                $rgba = imagecolorsforindex($im, imagecolorat($im, $coord[0], $coord[1]));
                                if ($rgba['alpha'] > 10) {
                                    $hasAlpha = true;
                                    break;
                                }
                            }

                            // Jika titik tepi belum mendeteksi alpha, jalankan grid sampling melompat
                            if (!$hasAlpha) {
                                $stepX = max(1, intdiv($w, 50));
                                $stepY = max(1, intdiv($h, 50));
                                for ($y = 0; $y < $h && !$hasAlpha; $y += $stepY) {
                                    for ($x = 0; $x < $w; $x += $stepX) {
                                        $rgba = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                                        if ($rgba['alpha'] > 10) {
                                            $hasAlpha = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            imagedestroy($im);

                            if (!$hasAlpha) {
                                $errors['signature'] = 'Gambar PNG tidak memiliki latar belakang transparan. Harap gunakan gambar tanda tangan berlatar transparan agar tidak menutupi teks dokumen.';
                            } elseif (!move_uploaded_file($file['tmp_name'], $destPath)) {
                                $errors['signature'] = 'Gagal memindahkan berkas tanda tangan ke penyimpanan privat.';
                            }
                        }
                    }
                }
            }
        } else {
            $errors['signature'] = 'Metode input tanda tangan tidak dikenali.';
        }

        if (!empty($errors)) {
            $existingCount = $this->signatureModel->countByUserId($this->userId);
            $this->load->view('views/user-signatures/form.php', [
                'title'         => 'Buat Template Tanda Tangan — SIGMA',
                'isEdit'        => false,
                'existingCount' => $existingCount,
                'values'        => [
                    'label'        => $label,
                    'is_default'   => $isDefault,
                    'input_method' => $inputMethod,
                ],
                'errors'        => $errors,
                'id'            => null,
            ]);
            return;
        }

        try {
            $newId = $this->signatureModel->create([
                'user_id'    => $this->userId,
                'label'      => $label ?: 'Tanda Tangan Pribadi',
                'image_path' => $dbPath,
                'is_default' => $isDefault,
            ]);

            $this->auditModel->log($this->userId, 'signature_template.create', [
                'id'         => $newId,
                'label'      => $label ?: 'Tanda Tangan Pribadi',
                'is_default' => $isDefault,
                'method'     => $inputMethod,
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template tanda tangan berhasil disimpan dan siap digunakan.'
            ];

            header('Location: ' . BASE_URL . '/user-signatures');
            exit;
        } catch (Throwable $e) {
            // Hapus file fisik jika simpan database gagal
            if (file_exists($destPath)) {
                @unlink($destPath);
            }

            $_SESSION['flash'] = [
                'type'    => 'danger',
                'message' => 'Gagal menyimpan template tanda tangan: ' . $e->getMessage()
            ];

            header('Location: ' . BASE_URL . '/user-signatures/create');
            exit;
        }
    }

    /**
     * Memperbarui atribut template (e.g. Setel sebagai Default atau Ubah Label)
     */
    public function update($id)
    {
        $target = $this->signatureModel->getById($id, $this->userId);
        if (!$target) {
            http_response_code(404);
            echo '404 - Template tanda tangan tidak ditemukan.';
            exit;
        }

        $actionType = $_POST['action_type'] ?? '';

        if ($actionType === 'set_default') {
            $this->signatureModel->setDefault($id, $this->userId);

            $this->auditModel->log($this->userId, 'signature_template.set_default', [
                'id'    => $id,
                'label' => $target['label'],
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Template "' . ($target['label'] ?: 'Tanda Tangan') . '" berhasil ditetapkan sebagai tanda tangan default.'
            ];
        } else {
            $label = trim($_POST['label'] ?? '');
            $this->signatureModel->update($id, $this->userId, [
                'label' => $label ?: 'Tanda Tangan Pribadi',
            ]);

            $this->auditModel->log($this->userId, 'signature_template.update', [
                'id'    => $id,
                'label' => $label,
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Informasi template tanda tangan berhasil diperbarui.'
            ];
        }

        header('Location: ' . BASE_URL . '/user-signatures');
        exit;
    }

    /**
     * Menghapus template tanda tangan pengguna (Soft Delete)
     */
    public function delete($id)
    {
        $target = $this->signatureModel->getById($id, $this->userId);
        if (!$target) {
            http_response_code(404);
            echo '404 - Template tanda tangan tidak ditemukan.';
            exit;
        }

        $this->signatureModel->delete($id, $this->userId);

        $this->auditModel->log($this->userId, 'signature_template.delete', [
            'id'    => $id,
            'label' => $target['label'],
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Template tanda tangan berhasil dihapus.'
        ];

        header('Location: ' . BASE_URL . '/user-signatures');
        exit;
    }

    /**
     * Streaming file gambar tanda tangan secara privat & aman (GET)
     * Hanya pemilik tanda tangan atau Admin yang berhak melihat pratinjau gambar
     */
    public function preview($id)
    {
        $signature = $this->signatureModel->getById($id);

        if (!$signature) {
            http_response_code(404);
            exit('Gambar tanda tangan tidak ditemukan.');
        }

        $isAdmin = (($_SESSION['user']['role'] ?? '') === 'admin');
        if ($signature['user_id'] !== $this->userId && !$isAdmin) {
            http_response_code(403);
            exit('Akses ditolak: Anda tidak memiliki izin untuk melihat tanda tangan ini.');
        }

        $filePath = $this->storageBaseDir . '/' . ltrim($signature['image_path'], '/\\');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit('Berkas fisik gambar tanda tangan tidak tersedia di storage server.');
        }

        // Streaming respon gambar PNG dengan header proteksi
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        readfile($filePath);
        exit;
    }
}
