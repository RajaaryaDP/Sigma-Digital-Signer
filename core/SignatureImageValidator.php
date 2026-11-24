<?php

/**
 * Validator Aman Gambar Tanda Tangan PNG (Upload & Canvas) — VULN-07 & BUG-05
 *
 * Mencegah serangan *decompression bomb* / *Memory Exhaustion* (OOM) pada ekstensi GD
 * dengan membatasi ukuran berkas, panjang string base64, serta dimensi lebar × tinggi
 * piksel melalui inspeksi header `getimagesize()` / `getimagesizefromstring()` SEBELUM
 * mendekode gambar ke memori menggunakan `imagecreatefrompng()` / `imagecreatefromstring()`.
 */
class SignatureImageValidator
{
    /** Batas ukuran berkas PNG biner maksimal: 2 MB */
    public const MAX_FILE_BYTES = 2 * 1024 * 1024;

    /** Batas panjang string data URI base64 kanvas (~2 MB biner + prefix header) */
    public const MAX_CANVAS_DATA_LENGTH = 2900000;

    /** Batas dimensi piksel maksimal (mencegah OOM pada GD & FPDI) */
    public const MAX_WIDTH  = 4000;
    public const MAX_HEIGHT = 4000;
    public const MAX_PIXELS = 16000000; // 16 MP

    /**
     * Memeriksa apakah dimensi gambar berada dalam batas aman alokasi memori.
     */
    public static function isSafeDimensions(int $width, int $height): bool
    {
        if ($width < 1 || $height < 1) {
            return false;
        }
        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            return false;
        }
        return ($width * $height) <= self::MAX_PIXELS;
    }

    /**
     * Memvalidasi data URI kanvas base64 PNG lalu menyimpannya ke `$destPath`.
     * Mengembalikan pesan error ramah bila tidak valid, atau `null` bila sukses.
     */
    public static function validateAndSaveCanvas(string $canvasData, string $destPath): ?string
    {
        $canvasData = trim($canvasData);

        if ($canvasData === '') {
            return 'Silakan bubuhkan goresan tanda tangan Anda pada kanvas.';
        }

        if (strlen($canvasData) > self::MAX_CANVAS_DATA_LENGTH) {
            return 'Ukuran data tanda tangan dari kanvas melebihi batas maksimal 2 MB.';
        }

        if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/\r\n=]+)$/', $canvasData, $matches)) {
            return 'Format data kanvas tanda tangan tidak valid.';
        }

        $b64Clean = preg_replace('/\s+/', '', $matches[1]);
        $decoded  = base64_decode($b64Clean, true);
        if ($decoded === false || strlen($decoded) < 100) {
            return 'Gambar tanda tangan dari kanvas tidak terbaca atau kosong.';
        }

        if (strlen($decoded) > self::MAX_FILE_BYTES) {
            return 'Ukuran gambar tanda tangan dari kanvas melebihi batas maksimal 2 MB.';
        }

        // Inspeksi dimensi & tipe PNG dari header SEBELUM alokasi memori GD (VULN-07)
        $imgInfo = @getimagesizefromstring($decoded);
        if ($imgInfo === false || ($imgInfo[2] ?? 0) !== IMAGETYPE_PNG || ($imgInfo['mime'] ?? '') !== 'image/png') {
            return 'Hasil kanvas harus berupa gambar PNG yang valid.';
        }

        $w = (int) ($imgInfo[0] ?? 0);
        $h = (int) ($imgInfo[1] ?? 0);
        if (!self::isSafeDimensions($w, $h)) {
            return sprintf(
                'Dimensi gambar tanda tangan (%d × %d px) melebihi batas aman maksimal %d × %d piksel.',
                $w,
                $h,
                self::MAX_WIDTH,
                self::MAX_HEIGHT
            );
        }

        // Verifikasi integritas stream PNG setelah dimensi dipastikan aman
        $im = @imagecreatefromstring($decoded);
        if ($im === false) {
            return 'Data gambar PNG dari kanvas rusak atau tidak dapat diproses.';
        }
        imagedestroy($im);

        if (file_put_contents($destPath, $decoded) === false) {
            return 'Gagal menyimpan berkas tanda tangan ke penyimpanan privat server.';
        }

        return null;
    }

    /**
     * Memvalidasi berkas unggahan PNG transparan (`$_FILES['signature_file']`) lalu
     * memindahkannya ke `$destPath` (atau menyalin bila `$requireUploadedFile === false` untuk uji).
     * Mengembalikan pesan error ramah bila tidak valid, atau `null` bila sukses.
     */
    public static function validateAndSaveUpload(array $file, string $destPath, bool $requireUploadedFile = true): ?string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return 'Silakan pilih berkas gambar tanda tangan PNG untuk diunggah.';
        }

        $errCode = (int) $file['error'];

        if ($errCode === UPLOAD_ERR_NO_FILE) {
            return 'Silakan pilih berkas gambar tanda tangan PNG untuk diunggah.';
        }
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran berkas tanda tangan melebihi batas maksimal 2 MB.';
        }
        if ($errCode !== UPLOAD_ERR_OK) {
            return 'Terjadi kesalahan saat mengunggah berkas tanda tangan.';
        }

        $tmpPath = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
        if ($tmpPath === '' || !is_file($tmpPath) || !is_readable($tmpPath)) {
            return 'Berkas sementara tanda tangan tidak dapat dibaca oleh server.';
        }
        if ($requireUploadedFile && !is_uploaded_file($tmpPath)) {
            return 'Sumber unggahan berkas tanda tangan tidak valid.';
        }

        $reportedSize = isset($file['size']) && is_scalar($file['size']) ? (int) $file['size'] : 0;
        $realSize     = @filesize($tmpPath);
        if ($realSize === false || $realSize <= 0 || $realSize > self::MAX_FILE_BYTES || $reportedSize > self::MAX_FILE_BYTES) {
            return 'Ukuran berkas tanda tangan melebihi batas maksimal 2 MB atau berkas kosong.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $tmpPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime !== 'image/png') {
            return 'Berkas tanda tangan wajib berformat gambar PNG transparan (.png).';
        }

        // Inspeksi dimensi PNG dari header SEBELUM imagecreatefrompng() agar PNG bomb tidak menyebabkan OOM (VULN-07)
        $imgInfo = @getimagesize($tmpPath);
        if ($imgInfo === false || ($imgInfo[2] ?? 0) !== IMAGETYPE_PNG || ($imgInfo['mime'] ?? '') !== 'image/png') {
            return 'Berkas gambar PNG tanda tangan rusak atau tidak valid.';
        }

        $headerW = (int) ($imgInfo[0] ?? 0);
        $headerH = (int) ($imgInfo[1] ?? 0);
        if (!self::isSafeDimensions($headerW, $headerH)) {
            return sprintf(
                'Resolusi gambar PNG (%d × %d px) terlalu besar. Batas maksimal yang diizinkan adalah %d × %d piksel (16 MP).',
                $headerW,
                $headerH,
                self::MAX_WIDTH,
                self::MAX_HEIGHT
            );
        }

        // Dekode aman ke GD setelah dimensi dipastikan berada dalam batas memori
        $im = @imagecreatefrompng($tmpPath);
        if ($im === false) {
            return 'Berkas gambar PNG tanda tangan rusak atau tidak dapat diproses.';
        }

        $hasAlpha = self::hasTransparentPixels($im);
        imagedestroy($im);

        if (!$hasAlpha) {
            return 'Gambar PNG tidak memiliki latar belakang transparan. Harap gunakan gambar tanda tangan berlatar transparan agar tidak menutupi teks dokumen.';
        }

        $saved = $requireUploadedFile
            ? move_uploaded_file($tmpPath, $destPath)
            : copy($tmpPath, $destPath);

        if (!$saved) {
            return 'Gagal memindahkan berkas tanda tangan ke penyimpanan privat.';
        }

        return null;
    }

    /**
     * Validasi cepat berbasis header untuk berkas PNG yang sudah tersimpan di storage
     * (tanpa mendekode piksel ke memori GD). Mengembalikan pesan error atau `null` bila aman.
     */
    public static function validateExistingFile(string $filePath): ?string
    {
        if ($filePath === '' || !is_file($filePath) || !is_readable($filePath)) {
            return 'Berkas template tanda tangan tidak tersedia di storage server.';
        }

        $realSize = @filesize($filePath);
        if ($realSize === false || $realSize <= 0 || $realSize > self::MAX_FILE_BYTES) {
            return 'Ukuran berkas tanda tangan tidak valid atau melebihi batas maksimal 2 MB.';
        }

        $imgInfo = @getimagesize($filePath);
        if ($imgInfo === false || ($imgInfo[2] ?? 0) !== IMAGETYPE_PNG || ($imgInfo['mime'] ?? '') !== 'image/png') {
            return 'Berkas tanda tangan bukan gambar PNG yang valid.';
        }

        $w = (int) ($imgInfo[0] ?? 0);
        $h = (int) ($imgInfo[1] ?? 0);
        if (!self::isSafeDimensions($w, $h)) {
            return sprintf(
                'Resolusi gambar tanda tangan (%d × %d px) melebihi batas aman maksimal %d × %d piksel (16 MP).',
                $w,
                $h,
                self::MAX_WIDTH,
                self::MAX_HEIGHT
            );
        }

        return null;
    }

    /**
     * Memeriksa keberadaan kanal transparansi (alpha > 10) pada gambar GD (BUG-05).
     */
    private static function hasTransparentPixels($im): bool
    {
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            return false;
        }

        // Sampling cepat pada titik sudut dan tepi
        $sampleCoords = [
            [0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1],
            [intdiv($w, 2), 0], [intdiv($w, 2), $h - 1],
            [0, intdiv($h, 2)], [$w - 1, intdiv($h, 2)],
        ];
        foreach ($sampleCoords as $coord) {
            $rgba = imagecolorsforindex($im, imagecolorat($im, $coord[0], $coord[1]));
            if (($rgba['alpha'] ?? 0) > 10) {
                return true;
            }
        }

        // Grid sampling melompat jika tepi belum mendeteksi alpha
        $stepX = max(1, intdiv($w, 50));
        $stepY = max(1, intdiv($h, 50));
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $rgba = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                if (($rgba['alpha'] ?? 0) > 10) {
                    return true;
                }
            }
        }

        return false;
    }
}
