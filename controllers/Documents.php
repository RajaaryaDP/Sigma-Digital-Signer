<?php

require_once __DIR__ . '/../core/Loader.php';

class Documents
{
    private $load;

    public function __construct()
    {
        $this->load = new Loader();
    }

    /**
     * Dashboard utama dokumen (menyesuaikan tampilan peran Admin vs Mahasiswa/Dosen)
     */
    public function index()
    {
        $user = $_SESSION['user'] ?? null;
        $title = ($user['role'] ?? '') === 'admin' ? 'Dashboard Administrator' : 'Dashboard Dokumen';

        $this->load->view('views/documents/index.php', [
            'title' => $title,
            'user'  => $user,
        ]);
    }
}
