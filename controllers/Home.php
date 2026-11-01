<?php

require_once __DIR__ . '/../core/Loader.php';

/**
 * Home Controller
 * Menangani halaman pengenalan (landing page) publik SIGMA Politeknik Negeri Jakarta.
 *
 * Rute:
 *   GET /        → index() Halaman pengenalan & informasi sistem
 *   GET /home    → index() Alias halaman pengenalan
 *   GET /landing → index() Alias halaman pengenalan
 */
class Home
{
    private $load;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->load = new Loader();
    }

    /**
     * Menampilkan Halaman Pengenalan (Landing Page) SIGMA
     */
    public function index()
    {
        $currentUser = $_SESSION['user'] ?? null;

        $this->load->view('views/home/index.php', [
            'title'       => 'SIGMA — Sistem Tanda Tangan Digital Politeknik Negeri Jakarta',
            'currentUser' => $currentUser,
        ]);
    }
}

