<?php

require_once __DIR__ . '/../core/Loader.php';

class Verify
{
    private $load;

    public function __construct()
    {
        $this->load = new Loader();
    }

    /**
     * Halaman publik verifikasi keaslian dokumen via token QR code atau pencarian manual
     */
    public function index()
    {
        $token = trim($_GET['token'] ?? '');

        // Tampilkan halaman verifikasi publik
        $this->load->view('views/verify/index.php', [
            'token' => $token,
        ]);
    }
}
