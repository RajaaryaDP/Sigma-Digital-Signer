<?php

require_once __DIR__ . '/../core/Loader.php';
require_once __DIR__ . '/../models/SignaturesModel.php';
require_once __DIR__ . '/../models/DocumentsModel.php';

/**
 * Signatures Controller
 * Menyajikan berkas gambar tanda tangan resmi secara privat (ADR-04):
 * file fisik di storage/signatures/documents/ hanya dapat diakses
 * melalui rute streaming ini dengan validasi sesi & otorisasi.
 *
 * Rute:
 *   GET /signatures/{id}/preview → preview() Streaming PNG tanda tangan
 */
class Signatures
{
    private $signaturesModel;
    private $documentsModel;
    private $userId;
    private $isAdmin;
    private $storageDir;

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            http_response_code(401);
            exit('Silakan masuk terlebih dahulu.');
        }

        $this->signaturesModel = new SignaturesModel();
        $this->documentsModel  = new DocumentsModel();
        $this->userId          = $_SESSION['user']['id'];
        $this->isAdmin         = ($_SESSION['user']['role'] ?? '') === 'admin';
        $this->storageDir      = __DIR__ . '/../storage';
    }

    /**
     * Streaming gambar tanda tangan (PNG) dengan pemeriksaan hak akses:
     * Admin, pengaju dokumen, atau salah satu penandatangan dokumen terkait.
     */
    public function preview($id)
    {
        $signature = $this->signaturesModel->getById($id);

        if (!$signature) {
            http_response_code(404);
            exit('Tanda tangan tidak ditemukan.');
        }

        if (!$this->isAdmin) {
            // Boleh melihat jika pengaju dokumen atau penandatangan dokumen tersebut
            if ($signature['uploader_id'] !== $this->userId) {
                $signer = $this->documentsModel->getSignerByDocAndUser($signature['document_id'], $this->userId);
                if (!$signer) {
                    http_response_code(403);
                    exit('Akses ditolak: Anda tidak memiliki izin melihat tanda tangan ini.');
                }
            }
        }

        $filePath = $this->storageDir . '/' . ltrim($signature['signature_image_path'], '/\\');
        $realStorage = realpath($this->storageDir);
        $realFile    = realpath($filePath);

        if (!$realFile || !$realStorage || strpos($realFile, $realStorage) !== 0 || !is_readable($realFile)) {
            http_response_code(404);
            exit('Berkas fisik tanda tangan tidak tersedia di storage server.');
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
