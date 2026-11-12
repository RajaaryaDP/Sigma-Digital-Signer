<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DocumentsModel.php';
require_once __DIR__ . '/../models/SignaturesModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';

use setasign\Fpdi\Fpdi;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use PdfDecompressor\Normalizer;

/**
 * Service: Generator Dokumen PDF Final & Stamping Tanda Tangan (Task 9)
 *
 * Menggabungkan lembar PDF asli dengan seluruh stempel tanda tangan visual
 * dan QR code verifikasi civitas, lalu menghitung nilai integritas final_file_hash (SHA-256).
 */
class PdfStamper
{
    /**
     * Stamping tanda tangan dan QR Code ke dokumen PDF asli.
     *
     * @param string $documentId UUID dokumen
     * @param string|null $actorUserId ID user yang memicu (untuk jejak audit)
     * @return array [
     *     'success' => bool,
     *     'final_file_path' => string,
     *     'final_file_hash' => string,
     *     'message' => string
     * ]
     */
    public static function stampDocument(string $documentId, ?string $actorUserId = null): array
    {
        global $conn;

        $docModel   = new DocumentsModel();
        $sigModel   = new SignaturesModel();
        $auditModel = new AuditLogModel();

        $doc = $docModel->getById($documentId);
        if (!$doc) {
            return ['success' => false, 'message' => 'Dokumen tidak ditemukan.'];
        }

        if ($doc['status'] !== 'completed') {
            return ['success' => false, 'message' => 'Dokumen belum berstatus completed. Stamping hanya dilakukan pada dokumen yang telah selesai ditandatangani.'];
        }

        $storageDir = dirname(__DIR__) . '/storage';

        // Tentukan path output file PDF final
        $finalRelPath = 'documents/final_' . $doc['id'] . '.pdf';
        $finalAbsPath = $storageDir . '/' . $finalRelPath;

        // Early return: berkas final sudah pernah digenerate & DB sudah mencatat hash.
        // Mencegah regenerasi berulang (race antara trigger otomatis & lazy download)
        // serta spam audit log document.final_generated.
        if (!empty($doc['final_file_path']) && !empty($doc['final_file_hash'])
            && file_exists($finalAbsPath) && filesize($finalAbsPath) > 0) {
            return [
                'success'         => true,
                'final_file_path' => $doc['final_file_path'],
                'final_file_hash' => $doc['final_file_hash'],
                'message'         => 'Berkas PDF final berstempel sudah tersedia.'
            ];
        }

        $signatures = $sigModel->getByDocumentId($documentId);
        if (empty($signatures)) {
            return ['success' => false, 'message' => 'Tidak ada rekam tanda tangan yang ditemukan untuk dokumen ini.'];
        }

        $sourcePdfPath = $storageDir . '/' . ltrim($doc['file_path'], '/\\');

        if (!file_exists($sourcePdfPath) || !is_readable($sourcePdfPath)) {
            return ['success' => false, 'message' => 'Berkas PDF asli tidak ditemukan di direktori penyimpanan privat.'];
        }

        // Pastikan direktori tujuan penyimpanan tersedia
        $finalDir = dirname($finalAbsPath);
        if (!is_dir($finalDir)) {
            mkdir($finalDir, 0755, true);
        }

        $normalizedTempPath = null;

        try {
            // Mitigasi FPDI Free Parser: PDF 1.5+ dengan Object Streams (/ObjStm) atau compressed XRef streams
            // ditolak oleh parser FPDI gratis bawaan Setasign. Lakukan normalisasi dekompresi otomatis
            // menjadi struktur PDF 1.4 classic xref table agar kompatibel tanpa ketergantungan binary eksternal.
            $effectiveSourcePdf = $sourcePdfPath;
            $pdfBytes = @file_get_contents($sourcePdfPath);
            if ($pdfBytes !== false && class_exists('\PdfDecompressor\Normalizer') && Normalizer::isCompressed($pdfBytes)) {
                try {
                    $normalizer = new Normalizer();
                    $normalizedTempPath = sys_get_temp_dir() . '/sigma_norm_' . uniqid('', true) . '.pdf';
                    $normalizer->normalizeFile($sourcePdfPath, $normalizedTempPath);
                    if (file_exists($normalizedTempPath) && filesize($normalizedTempPath) > 0) {
                        $effectiveSourcePdf = $normalizedTempPath;
                    }
                } catch (\Throwable $ne) {
                    error_log("Peringatan Normalizer PDF decompressor gagal [{$documentId}]: " . $ne->getMessage());
                }
            }

            $pdf = new Fpdi();
            // Nonaktifkan AutoPageBreak FPDF agar pencetakan footer/caption di dekat margin bawah
            // tidak memicu pembuatan halaman kosong siluman (phantom blank page).
            $pdf->SetAutoPageBreak(false);

            try {
                $pageCount = $pdf->setSourceFile($effectiveSourcePdf);
            } catch (\Throwable $fe) {
                // Jika masih ditolak FPDI dan belum pernah dinormalisasi, lakukan normalisasi sebagai fallback
                if ($effectiveSourcePdf === $sourcePdfPath && class_exists('\PdfDecompressor\Normalizer')) {
                    $normalizer = new Normalizer();
                    $normalizedTempPath = sys_get_temp_dir() . '/sigma_norm_' . uniqid('', true) . '.pdf';
                    $normalizer->normalizeFile($sourcePdfPath, $normalizedTempPath);
                    $pageCount = $pdf->setSourceFile($normalizedTempPath);
                    $effectiveSourcePdf = $normalizedTempPath;
                } else {
                    throw $fe;
                }
            }

            // Kelompokkan tanda tangan per nomor halaman (dengan batasan maksimal halaman PDF)
            $signaturesByPage = [];
            foreach ($signatures as $sig) {
                $rawPageNum = (int)($sig['page_number'] ?: 1);
                $pageNum = min($pageCount, max(1, $rawPageNum));
                $signaturesByPage[$pageNum][] = $sig;
            }

            // Opsi generator QR Code
            $qrOptions = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'scale'           => 4,
            ]);
            $qrGen = new QRCode($qrOptions);

            // Base URL halaman verifikasi publik (dengan proteksi Host header injection)
            $verifyBaseUrl = self::resolveVerifyBaseUrl();

            for ($p = 1; $p <= $pageCount; $p++) {
                $tplId = $pdf->importPage($p);
                $size  = $pdf->getTemplateSize($tplId);

                // Tambahkan halaman baru dengan ukuran & orientasi persis dokumen asli
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                $pageW = (float)$size['width'];
                $pageH = (float)$size['height'];

                // Stempel watermark / footer identitas resmi SIGMA PNJ (OQ-07 & ADR-08)
                $pdf->SetFont('Helvetica', 'I', 6);
                $pdf->SetTextColor(100, 116, 139); // Slate muted
                $pdf->SetXY(10.0, $pageH - 6.0);
                $pdf->Cell($pageW - 20.0, 3.5, 'Dokumen ini telah disahkan secara elektronik melalui SIGMA PNJ. Keaslian tanda tangan dapat diverifikasi melalui pemindaian QR Code.', 0, 0, 'C');

                // Jika ada tanda tangan pada halaman ini
                if (isset($signaturesByPage[$p])) {
                    foreach ($signaturesByPage[$p] as $sig) {
                        $sigFormat = $sig['signature_format'] ?? 'qr_and_signature';

                        // Batasi koordinat dan persentase secara defensif
                        $xPercent = max(0.0, min(95.0, (float)$sig['x_percent']));
                        $yPercent = max(0.0, min(95.0, (float)$sig['y_percent']));
                        $wPercent = max(5.0, min(100.0, (float)$sig['width_percent']));
                        $hPercent = max(3.0, min(100.0, (float)$sig['height_percent']));

                        $xPt = ($xPercent / 100.0) * $pageW;
                        $yPt = ($yPercent / 100.0) * $pageH;
                        $wPt = ($wPercent / 100.0) * $pageW;
                        $hPt = ($hPercent / 100.0) * $pageH;

                        // Pastikan slot tidak melebihi batas lembar kanan dan bawah
                        if ($xPt + $wPt > $pageW) {
                            $xPt = max(0.0, $pageW - $wPt);
                        }
                        if ($yPt + $hPt > $pageH - 7.0) {
                            $yPt = max(0.0, $pageH - $hPt - 7.0);
                        }

                        // ── PENATAAN TERPADU: FORMAT HANYA QR ('qr_only') & QR + TTD ('qr_and_signature') ──
                        // Kedua format menampilkan identitas resmi (Nama + Garis Pemisah + NIP/NIM) di kolom kiri
                        // dan Stempel QR Code Verifikasi di kolom kanan (di dalam kotak slot):
                        //   ┌──────────────────────┬──────────┐
                        //   │ (Coretan jika TTD)   │          │
                        //   │ Nama Penandatangan   │    QR    │
                        //   │ ──────────────────   │          │
                        //   │ NIP/NIM: 1234567890  │ Verified │
                        //   └──────────────────────┴──────────┘
                        $isQrOnly = ($sigFormat === 'qr_only');

                        $pad    = min(1.0, max(0.4, $hPt * 0.05)); // padding internal kotak (mm)
                        $gap    = min(1.5, max(0.8, $wPt * 0.03)); // jarak antara kolom identitas dan QR (mm)
                        $availH = max(3.5, $hPt - 2.0 * $pad);
                        $capH   = min(2.2, max(1.4, $availH * 0.14)); // tinggi teks caption di bawah QR (mm)

                        // Dimensi QR persegi di kolom kanan (proporsional terhadap kotak slot)
                        $qrDim = max(6.0, min($availH - $capH, $wPt * 0.40, 26.0));
                        $qrX   = $xPt + $wPt - $pad - $qrDim;
                        $qrY   = $yPt + $pad + max(0.0, ($availH - $qrDim - $capH) / 2.0);

                        // Kolom kiri: (coretan opsional) + nama + garis + NIP/NIM
                        $leftX = $xPt + $pad;
                        $leftW = max(8.0, $wPt - $qrDim - $gap - 2.0 * $pad);

                        // Siapkan teks identitas (Helvetica core font → Latin-1)
                        $signerName = self::toLatin1(trim((string)($sig['signer_name_snapshot'] ?? '')));
                        $signerId   = self::toLatin1(trim((string)($sig['signer_identifier_snapshot'] ?? '')));
                        $signerPos  = self::toLatin1(trim((string)($sig['signer_position_snapshot'] ?? '')));
                        $signerType = strtolower(trim((string)($sig['signer_type_snapshot'] ?? $sig['signer_category_snapshot'] ?? '')));
                        $idLabel    = ($signerType === 'mahasiswa') ? 'NIM' : (($signerType === '') ? 'NIP/NIM' : 'NIP');

                        $subText = '';
                        if ($signerId !== '') {
                            $subText = preg_match('/^(NIP|NIM)\b/i', $signerId) ? $signerId : ($idLabel . ': ' . $signerId);
                        } elseif ($signerPos !== '') {
                            $subText = $signerPos;
                        }

                        // Alokasi batas tinggi vertikal untuk blok identitas (mencegah overflow keluar kotak slot)
                        $minScribbleH = $isQrOnly ? 0.0 : min(max(3.0, $availH * 0.34), $availH * 0.50);
                        $maxIdentityH = max(3.2, $availH - $minScribbleH);
                        $maxNameLines = ($maxIdentityH >= 8.5) ? 3 : (($maxIdentityH >= 5.8) ? 2 : 1);

                        // Nama: kecilkan font hingga muat, bungkus hingga $maxNameLines baris, dan cegah overflow lebar
                        $nameLines = [];
                        $nameFont  = 6.5;
                        $nameLH    = 2.6;
                        if ($signerName !== '') {
                            $pdf->SetFont('Helvetica', 'B', $nameFont);
                            while ($nameFont > 4.2 && $pdf->GetStringWidth($signerName) > $leftW) {
                                $nameFont -= 0.3;
                                $pdf->SetFont('Helvetica', 'B', $nameFont);
                            }
                            $nameLines = self::wrapAndFitLines($pdf, $signerName, $leftW, $maxNameLines);
                            $nameLH    = $nameFont * 0.3528 * 1.22;
                        }

                        // Teks NIP/NIM: kecilkan font hingga muat pada kolom kiri dan potong aman bila ekstrem
                        $subFont = 5.5;
                        $subLH   = 2.2;
                        if ($subText !== '') {
                            $pdf->SetFont('Helvetica', '', $subFont);
                            while ($subFont > 3.6 && $pdf->GetStringWidth($subText) > $leftW) {
                                $subFont -= 0.3;
                                $pdf->SetFont('Helvetica', '', $subFont);
                            }
                            $subText = self::fitTextToWidth($pdf, $subText, $leftW);
                            $subLH   = $subFont * 0.3528 * 1.28;
                        }

                        // Hitung tinggi blok identitas & skalakan bila melebihi $maxIdentityH (anti vertical-overflow)
                        $lineGap   = (!empty($nameLines) && $subText !== '') ? 0.65 : 0.0;
                        $identityH = count($nameLines) * $nameLH + $lineGap + ($subText !== '' ? $subLH : 0.0);

                        if ($identityH > $maxIdentityH && $identityH > 0.0) {
                            $vScale    = $maxIdentityH / $identityH;
                            $nameFont  = max(3.5, $nameFont * $vScale);
                            $subFont   = max(3.2, $subFont * $vScale);
                            $nameLH   *= $vScale;
                            $subLH    *= $vScale;
                            $lineGap  *= $vScale;
                            $identityH = $maxIdentityH;
                        }

                        // 1. Jika Format QR + TTD: Gambar coretan tanda tangan (fit-contain BUG-07) di area atas kolom kiri
                        if (!$isQrOnly) {
                            $scribbleH  = max(2.5, $availH - $identityH);
                            $sigImgPath = !empty($sig['signature_image_path']) ? ($storageDir . '/' . ltrim($sig['signature_image_path'], '/\\')) : '';
                            if (!empty($sigImgPath) && file_exists($sigImgPath) && is_readable($sigImgPath)) {
                                $imgInfo = @getimagesize($sigImgPath);
                                $origW   = $imgInfo[0] ?? 0;
                                $origH   = $imgInfo[1] ?? 0;
                                if ($origW > 0 && $origH > 0) {
                                    $scale   = min($leftW / $origW, $scribbleH / $origH);
                                    $renderW = $origW * $scale;
                                    $renderH = $origH * $scale;
                                    $renderX = $leftX + ($leftW - $renderW) / 2.0;
                                    $renderY = $yPt + $pad + ($scribbleH - $renderH) / 2.0;
                                    $pdf->Image($sigImgPath, $renderX, $renderY, $renderW, $renderH);
                                } else {
                                    $pdf->Image($sigImgPath, $leftX, $yPt + $pad, $leftW, $scribbleH);
                                }
                            }
                            $textY = $yPt + $pad + $scribbleH;
                        } else {
                            // Format Hanya QR: tempatkan blok identitas secara proporsional di tengah-bawah kolom kiri
                            $textY = $yPt + $pad + max(0.0, ($availH - $identityH) * 0.55);
                        }

                        // 2. Cetak Nama (tebal) → garis pemisah → NIP/NIM di kolom kiri
                        if (!empty($nameLines)) {
                            $pdf->SetFont('Helvetica', 'B', $nameFont);
                            $pdf->SetTextColor(15, 23, 42); // slate-900
                            foreach ($nameLines as $nl) {
                                $pdf->SetXY($leftX, $textY);
                                $pdf->Cell($leftW, $nameLH, $nl, 0, 0, 'C');
                                $textY += $nameLH;
                            }
                        }
                        if (!empty($nameLines) && $subText !== '') {
                            $pdf->SetDrawColor(15, 23, 42);
                            $pdf->SetLineWidth(0.2);
                            $lineY = $textY + $lineGap / 2.0;
                            $pdf->Line($leftX, $lineY, $leftX + $leftW, $lineY);
                            $textY += $lineGap;
                        }
                        if ($subText !== '') {
                            $pdf->SetFont('Helvetica', '', $subFont);
                            $pdf->SetTextColor(71, 85, 105); // slate-600
                            $pdf->SetXY($leftX, $textY);
                            $pdf->Cell($leftW, $subLH, $subText, 0, 0, 'C');
                        }

                        // 3. Cetak QR Code verifikasi di kolom kanan (di dalam kotak slot)
                        if (!empty($sig['verification_token'])) {
                            $token = $sig['verification_token'];
                            // Nama file temp unik per proses (mencegah tabrakan antar request konkuren)
                            $qrTempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                                . 'sigma_qr_' . $token . '_' . bin2hex(random_bytes(4)) . '.png';
                            $verifyUrl  = $verifyBaseUrl . $token;

                            try {
                                $qrGen->render($verifyUrl, $qrTempFile);

                                if (file_exists($qrTempFile)) {
                                    $pdf->Image($qrTempFile, $qrX, $qrY, $qrDim, $qrDim);

                                    // Label teks stempel resmi tepat di bawah lebar QR code (tidak memakan ruang gap kolom kiri)
                                    $capText = 'SIGMA PNJ Verified';
                                    $capFont = 4.5;
                                    $pdf->SetFont('Helvetica', '', $capFont);
                                    while ($capFont > 3.2 && $pdf->GetStringWidth($capText) > $qrDim) {
                                        $capFont -= 0.25;
                                        $pdf->SetFont('Helvetica', '', $capFont);
                                    }
                                    if ($pdf->GetStringWidth($capText) > $qrDim) {
                                        $capText = 'SIGMA Verified';
                                        while ($capFont > 2.8 && $pdf->GetStringWidth($capText) > $qrDim) {
                                            $capFont -= 0.2;
                                            $pdf->SetFont('Helvetica', '', $capFont);
                                        }
                                    }
                                    $pdf->SetTextColor(6, 78, 59); // PNJ Green
                                    $captionY = min($pageH - 2.5, $qrY + $qrDim + 0.15);
                                    $pdf->SetXY($qrX, $captionY);
                                    $pdf->Cell($qrDim, $capH, self::fitTextToWidth($pdf, $capText, $qrDim), 0, 0, 'C');
                                }
                            } catch (\Throwable $qe) {
                                // Fallback jika rendering QR code bermasalah
                                error_log("Gagal membuat QR Code untuk token {$token}: " . $qe->getMessage());
                            } finally {
                                if (file_exists($qrTempFile)) {
                                    @unlink($qrTempFile);
                                }
                            }
                        }
                    }
                }
            }

            // Simpan berkas PDF final ke storage privat secara ATOMik:
            // tulis ke berkas sementara lalu rename agar proses konkuren
            // tidak meninggalkan berkas setengah tertulis (anti race condition).
            $tmpAbsPath = $finalAbsPath . '.tmp';
            $pdf->Output($tmpAbsPath, 'F');

            if (!file_exists($tmpAbsPath) || filesize($tmpAbsPath) === 0) {
                @unlink($tmpAbsPath);
                return ['success' => false, 'message' => 'Gagal menulis berkas PDF final ke disk penyimpanan.'];
            }

            if (!@rename($tmpAbsPath, $finalAbsPath)) {
                if (!@copy($tmpAbsPath, $finalAbsPath)) {
                    @unlink($tmpAbsPath);
                    return ['success' => false, 'message' => 'Gagal memindahkan berkas PDF final ke lokasi penyimpanan.'];
                }
                @unlink($tmpAbsPath);
            }

            if (!file_exists($finalAbsPath) || filesize($finalAbsPath) === 0) {
                return ['success' => false, 'message' => 'Gagal menulis berkas PDF final ke disk penyimpanan.'];
            }

            // 3. Kalkulasi Nilai SHA-256 Hash Dokumen Final (NFR-3)
            $finalFileHash = hash_file('sha256', $finalAbsPath);

            // 4. Perbarui data tabel documents
            $stmt = $conn->prepare(
                "UPDATE documents 
                 SET final_file_path = :final_path, 
                     final_file_hash = :final_hash, 
                     updated_at = NOW() 
                 WHERE id = :id"
            );
            $stmt->execute([
                ':final_path' => $finalRelPath,
                ':final_hash' => $finalFileHash,
                ':id'         => $documentId
            ]);

            // 5. Catat jejak audit pengesahan final
            $actorId = $actorUserId ?: ($doc['uploader_id'] ?? null);
            if ($actorId) {
                $auditModel->log($actorId, 'document.final_generated', [
                    'document_id'     => $documentId,
                    'title'           => $doc['title'],
                    'final_file_path' => $finalRelPath,
                    'final_file_hash' => $finalFileHash,
                    'signatures_count'=> count($signatures)
                ], $documentId);
            }

            return [
                'success'         => true,
                'final_file_path' => $finalRelPath,
                'final_file_hash' => $finalFileHash,
                'message'         => 'Dokumen PDF final berstempel berhasil digenerate.'
            ];

        } catch (\Throwable $e) {
            error_log("Kesalahan fatal saat stamping PDF [{$documentId}]: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses stamping berkas PDF: ' . $e->getMessage()
            ];
        } finally {
            if ($normalizedTempPath !== null && file_exists($normalizedTempPath)) {
                @unlink($normalizedTempPath);
            }
        }
    }

    /**
     * Mengonversi teks UTF-8 ke Windows-1252 agar dapat dirender oleh font inti FPDF (Helvetica).
     * Karakter di luar jangkauan ditransliterasi/dibuang sehingga tidak menghasilkan teks rusak.
     */
    private static function toLatin1(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        }
        return $converted;
    }

    /**
     * Memastikan satu baris teks (Latin-1) tidak pernah melebihi batas lebar maksimum ($maxWidth mm).
     * Bila melebihi, dipotong secara presisi dan diberi elipsis ('...') agar tidak menabrak QR Code.
     */
    private static function fitTextToWidth(Fpdi $pdf, string $text, float $maxWidth): string
    {
        if ($text === '' || $maxWidth <= 0.0) {
            return '';
        }
        if ($pdf->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }
        $ellipsis = '..';
        $targetW  = max(1.0, $maxWidth - $pdf->GetStringWidth($ellipsis));
        $len      = strlen($text);
        while ($len > 1 && $pdf->GetStringWidth(substr($text, 0, $len)) > $targetW) {
            $len--;
        }
        return rtrim(substr($text, 0, max(1, $len))) . $ellipsis;
    }

    /**
     * Membungkus teks nama menjadi maksimal $maxLines baris yang masing-masing dijamin <= $maxWidth mm.
     */
    private static function wrapAndFitLines(Fpdi $pdf, string $text, float $maxWidth, int $maxLines = 3): array
    {
        if ($text === '' || $maxWidth <= 0.0) {
            return [];
        }
        $maxLines = max(1, $maxLines);
        if ($maxLines === 1 || $pdf->GetStringWidth($text) <= $maxWidth) {
            return [self::fitTextToWidth($pdf, $text, $maxWidth)];
        }

        $words = preg_split('/\s+/', $text) ?: [$text];
        $lines = [];
        $cur   = '';

        foreach ($words as $word) {
            $try = ($cur === '') ? $word : ($cur . ' ' . $word);
            if ($pdf->GetStringWidth($try) <= $maxWidth) {
                $cur = $try;
            } else {
                if ($cur !== '') {
                    $lines[] = self::fitTextToWidth($pdf, $cur, $maxWidth);
                    $cur     = $word;
                } else {
                    // Kata tunggal yang sangat panjang
                    $lines[] = self::fitTextToWidth($pdf, $word, $maxWidth);
                    $cur     = '';
                }
            }
        }
        if ($cur !== '') {
            $lines[] = self::fitTextToWidth($pdf, $cur, $maxWidth);
        }

        if (count($lines) > $maxLines) {
            $overflow = implode(' ', array_slice($lines, $maxLines - 1));
            $lines    = array_slice($lines, 0, $maxLines - 1);
            $lines[]  = self::fitTextToWidth($pdf, $overflow, $maxWidth);
        }

        return $lines;
    }

    /**
     * Menentukan base URL halaman verifikasi publik yang di-encode ke QR Code.
     *
     * KEAMANAN (Host Header Injection): JANGAN memercayai $_SERVER['HTTP_HOST']
     * secara langsung — header Host dikendalikan penuh oleh client. Tanpa
     * validasi, penyerang dapat menyuntikkan domain sendiri ke QR Code dokumen
     * resmi, sehingga pemindai QR diarahkan ke situs palsu (phishing verifikasi).
     * Host hanya dipakai bila lolos pola ketat DAN terdaftar di allowlist.
     *
     * Prioritas:
     *   1. Konstanta konfigurasi eksplisit SIGMA_VERIFY_BASE_URL (opsional).
     *   2. Host request yang lolos validasi + allowlist tepercaya.
     *   3. Fallback default pengembangan lokal (composer serve).
     */
    private static function resolveVerifyBaseUrl(): string
    {
        // 1. Konfigurasi eksplisit (prioritas tertinggi, bebas Host header)
        if (defined('SIGMA_VERIFY_BASE_URL') && is_string(SIGMA_VERIFY_BASE_URL) && SIGMA_VERIFY_BASE_URL !== '') {
            return rtrim(SIGMA_VERIFY_BASE_URL, '/') . '/verify?token=';
        }

        // 2. Host request — wajib lolos pola ketat + allowlist domain tepercaya
        $host         = $_SERVER['HTTP_HOST'] ?? '';
        $allowedHosts = ['localhost', '127.0.0.1', 'sigma.local', 'tik.pnj.ac.id', 'www.tik.pnj.ac.id'];
        $baseUrlPart  = defined('BASE_URL') ? BASE_URL : '';

        if (preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host)) {
            $hostPart = strtolower((string) preg_replace('/:\d+$/', '', $host));
            if (in_array($hostPart, $allowedHosts, true)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                return rtrim($protocol . $host . $baseUrlPart, '/') . '/verify?token=';
            }
        }

        // 3. Fallback default pengembangan lokal
        return 'http://localhost:5000' . $baseUrlPart . '/verify?token=';
    }
}

