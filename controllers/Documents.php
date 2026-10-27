<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/CivitasModel.php';

class Documents
{
    private $load;
    private $civitasModel;

    public function __construct()
    {
        $this->load         = new Loader();
        $this->civitasModel = new CivitasModel();
    }

    /**
     * Dashboard utama dokumen (menyesuaikan tampilan peran Admin vs Mahasiswa/Dosen)
     */
    public function index()
    {
        $user  = $_SESSION['user'] ?? null;
        $title = ($user['role'] ?? '') === 'admin' ? 'Dashboard Administrator' : 'Dashboard Dokumen';

        $civitasCounts = $this->civitasModel->countByType();

        $this->load->view('views/documents/index.php', [
            'title'        => $title,
            'user'         => $user,
            'civitasCount' => $civitasCounts['total'] ?? 0,
        ]);
    }
}
