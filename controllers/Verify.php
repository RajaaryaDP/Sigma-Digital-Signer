<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/SignaturesModel.php';
require_once __DIR__ . '/../models/DocumentsModel.php';

/**
 * Verify Controller (Task 8)
 * Mengelola endpoint publik pemeriksaan keaslian dokumen via pemindaian QR code
 * atau pencarian manual token UUID 36-karakter (ADR-03 & ADR-08).
 *
 * Rute:
 *   GET /verify              → index() Pencarian manual token verifikasi
 *   GET /verify?token={UUID} → index() Hasil verifikasi keabsahan dokumen & profil civitas
 */
class Verify
{
    private $load;
    private $signaturesModel;
    private $documentsModel;
    private $storageDir;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->load            = new Loader();
        $this->signaturesModel = new SignaturesModel();
        $this->documentsModel  = new DocumentsModel();
        $this->storageDir      = __DIR__ . '/../storage';
    }

    /**
     * Halaman publik verifikasi keaslian dokumen via token QR code atau pencarian manual
     */
    public function index()
    {
        $rawInput = $_GET['token'] ?? '';
        $rawToken = is_string($rawInput) ? trim($rawInput) : '';

        // State 1: Buka tanpa parameter token → Tampilkan form pencarian manual (ADR-08)
        if ($rawToken === '') {
            $this->load->view('views/verify/index.php', [
                'status' => 'empty',
                'token'  => '',
                'title'  => 'Verifikasi Keaslian Dokumen — SIGMA Politeknik Negeri Jakarta',
            ]);
            return;
        }

        // Batasi panjang maksimal untuk mencegah konsumsi memori berlebih
        if (strlen($rawToken) > 255) {
            $rawToken = substr($rawToken, 0, 255);
        }

        // Ekstraksi UUID 36-karakter jika user menempelkan URL lengkap atau spasi/kapital
        if (preg_match('/[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}/', $rawToken, $matches)) {
            $token = strtolower($matches[0]);
        } else {
            $token = strtolower($rawToken);
        }

        // State 2: Token diberikan → Kueri data verifikasi lengkap dari basis data
        $verified = $this->signaturesModel->getByVerificationToken($token);

        if (!$verified) {
            // Token tidak terdaftar atau telah dicabut (soft-deleted)
            $this->load->view('views/verify/index.php', [
                'status' => 'invalid',
                'token'  => $token,
                'title'  => 'Dokumen Tidak Terdaftar / Tidak Valid — SIGMA PNJ',
            ]);
            return;
        }

        // Ambil data penandatangan lain dari dokumen yang sama untuk rantai jenjang lengkap
        $allSigners    = $this->documentsModel->getSignersByDocumentId($verified['document_id']);
        $allSignatures = $this->signaturesModel->getByDocumentId($verified['document_id']);

        // Pratinjau gambar tanda tangan visual privat via Base64 Data URL
        // (Aman bagi publik verifikator tanpa membuka endpoint file langsung)
        $sigImageSrc = null;
        if (!empty($verified['signature_image_path'])) {
            $absPath = $this->storageDir . '/' . ltrim($verified['signature_image_path'], '/\\');
            if (file_exists($absPath) && is_readable($absPath)) {
                $raw = @file_get_contents($absPath);
                if ($raw !== false && strlen($raw) > 50) {
                    $sigImageSrc = 'data:image/png;base64,' . base64_encode($raw);
                }
            }
        }

        // State 3: Dokumen valid & resmi terverifikasi
        $this->load->view('views/verify/index.php', [
            'status'        => 'valid',
            'token'         => $token,
            'verified'      => $verified,
            'allSigners'    => $allSigners,
            'allSignatures' => $allSignatures,
            'sigImageSrc'   => $sigImageSrc,
            'title'         => 'Dokumen Terverifikasi Resmi — ' . $verified['document_title'] . ' — SIGMA PNJ',
        ]);
    }
}
