<?php

require_once __DIR__ . '/../core/Loader.php';

// Serves public landing routes (/ , /home, /landing) without requiring authentication.
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

    public function index()
    {
        $currentUser = $_SESSION['user'] ?? null;

        $this->load->view('views/home/index.php', [
            'title'       => 'SIGMA | Sistem Tanda Tangan Digital Politeknik Negeri Jakarta',
            'currentUser' => $currentUser,
        ]);
    }
}
