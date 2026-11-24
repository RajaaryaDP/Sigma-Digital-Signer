<?php

/**
 * Helper Kanonikalisasi & Guard Path Storage Privat — VULN-08 (ADR-04 & BUG-23)
 *
 * Memastikan seluruh operasi baca/salin/hapus berkas di bawah `MVC_example/storage/`
 * selalu melewati verifikasi `realpath()` seragam dengan pembatas `DIRECTORY_SEPARATOR`,
 * isolasi subdirektori eksplisit, proteksi symlink/traversal, blokir dotfile,
 * serta daftar putih ekstensi berkas.
 */
class StoragePath
{
    /**
     * Mengembalikan path absolut kanonikal yang aman jika berkas berada di dalam
     * direktori storage (dan subdirektori yang diizinkan), berstatus berkas reguler,
     * serta memenuhi kriteria keamanan; mengembalikan `null` jika tidak valid/tidak ditemukan.
     *
     * @param string      $relativePath      Path relatif dari kolom DB (mis. 'documents/doc_xxx.pdf')
     * @param string      $subDir            Subdirektori wajib di bawah storage (mis. 'documents', 'signatures/documents')
     * @param string[]    $allowedExtensions Daftar putih ekstensi huruf kecil (mis. ['pdf']), kosong = semua ekstensi non-dotfile
     * @param string|null $storageDir        Override direktori root storage (default: MVC_example/storage)
     * @param bool        $requireReadable   Wajib `is_readable()` (true untuk baca/stream, false untuk unlink)
     */
    public static function resolve(
        string $relativePath,
        string $subDir = '',
        array $allowedExtensions = [],
        ?string $storageDir = null,
        bool $requireReadable = true
    ): ?string {
        if ($relativePath === '' || trim($relativePath) === '') {
            return null;
        }

        // Tolak null byte, skema stream wrapper / drive letter / NTFS ADS (:)
        if (
            strpos($relativePath, "\0") !== false
            || strpos($subDir, "\0") !== false
            || strpos($relativePath, ':') !== false
            || strpos($subDir, ':') !== false
        ) {
            return null;
        }

        // Normalisasi pemisah dan tolak segmen traversal ('..') atau akhiran titik/spasi Windows
        $normalizedRel = str_replace('\\', '/', trim($relativePath));
        $normalizedRel = ltrim($normalizedRel, '/');
        if ($normalizedRel === '' || preg_match('/[.\s]$/', $normalizedRel)) {
            return null;
        }

        foreach (explode('/', $normalizedRel) as $segment) {
            if ($segment === '..') {
                return null;
            }
        }

        $baseStorage = $storageDir !== null && $storageDir !== ''
            ? $storageDir
            : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';

        $realStorage = realpath($baseStorage);
        if ($realStorage === false || !is_dir($realStorage)) {
            return null;
        }

        // Tentukan root batas akses (storage root atau subdirektori spesifik)
        $cleanSubDir = trim(str_replace('\\', '/', $subDir), '/');
        if ($cleanSubDir !== '') {
            foreach (explode('/', $cleanSubDir) as $seg) {
                if ($seg === '' || $seg === '.' || $seg === '..') {
                    return null;
                }
            }
            $realAllowedRoot = realpath($realStorage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanSubDir));
            if ($realAllowedRoot === false || !is_dir($realAllowedRoot)) {
                return null;
            }
            if (!self::isWithinRoot($realAllowedRoot, $realStorage)) {
                return null;
            }
        } else {
            $realAllowedRoot = $realStorage;
        }

        $candidate  = $realStorage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRel);
        $realTarget = realpath($candidate);
        if ($realTarget === false) {
            return null;
        }

        // Verifikasi kanonikal: target wajib berada di dalam batas root (anti sibling prefix & symlink escape)
        if (!self::isWithinRoot($realTarget, $realAllowedRoot)) {
            return null;
        }

        // Wajib berupa berkas reguler (bukan direktori)
        if (!is_file($realTarget)) {
            return null;
        }

        if ($requireReadable && !is_readable($realTarget)) {
            return null;
        }

        // Blokir berkas tersembunyi / konfigurasi server (.htaccess, .gitkeep, dll.)
        $baseName = basename($realTarget);
        if ($baseName === '' || $baseName[0] === '.') {
            return null;
        }

        // Daftar putih ekstensi jika ditentukan
        if (!empty($allowedExtensions)) {
            $ext     = strtolower((string) pathinfo($baseName, PATHINFO_EXTENSION));
            $allowed = array_map('strtolower', $allowedExtensions);
            if ($ext === '' || !in_array($ext, $allowed, true)) {
                return null;
            }
        }

        return $realTarget;
    }

    /**
     * Memeriksa apakah `$realTarget` benar-benar berada di bawah `$realRoot`
     * dengan pembatas `DIRECTORY_SEPARATOR` eksplisit (mencegah bypass folder saudara
     * seperti `storage_backup/` terhadap `storage`).
     */
    public static function isWithinRoot(string $realTarget, string $realRoot): bool
    {
        $normalizedRoot = rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (DIRECTORY_SEPARATOR === '\\') {
            return stripos($realTarget, $normalizedRoot) === 0;
        }
        return strpos($realTarget, $normalizedRoot) === 0;
    }
}
