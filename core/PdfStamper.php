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

                        // 1. Tempelkan Goresan Tanda Tangan Fisik (Prinsip fit-contain BUG-07)
                        $sigImgPath = $storageDir . '/' . ltrim($sig['signature_image_path'], '/\\');
                        if (file_exists($sigImgPath) && is_readable($sigImgPath)) {
                            list($origW, $origH) = @getimagesize($sigImgPath);
                            if ($origW > 0 && $origH > 0) {
                                $scale   = min($wPt / $origW, $hPt / $origH);
                                $renderW = $origW * $scale;
                                $renderH = $origH * $scale;
                                $renderX = $xPt + ($wPt - $renderW) / 2;
                                $renderY = $yPt + ($hPt - $renderH) / 2;
                                $pdf->Image($sigImgPath, $renderX, $renderY, $renderW, $renderH);
                            } else {
                                $pdf->Image($sigImgPath, $xPt, $yPt, $wPt, $hPt);
                            }
                        }

                        // 2. Tempelkan Stempel QR Code Verifikasi Civitas
                        if (!empty($sig['verification_token'])) {
                            $token = $sig['verification_token'];
                            // Nama file temp unik per proses (mencegah tabrakan antar request konkuren)
                            $qrTempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                                . 'sigma_qr_' . $token . '_' . bin2hex(random_bytes(4)) . '.png';
                            $verifyUrl  = $verifyBaseUrl . $token;

                            try {
                                $qrGen->render($verifyUrl, $qrTempFile);

                                if (file_exists($qrTempFile)) {
                                    // Ukuran QR code: proporsional terhadap kotak slot (max 20mm, min 14mm)
                                    $qrDim = min(20.0, max(14.0, $wPt * 0.35));

                                    // Penempatan Cerdas (Smart Boundary Placement):
                                    // Pastikan QR code tidak hilang jika slot diletakkan di tepi bawah dokumen
                                    if ($yPt + $hPt + $qrDim + 4.0 <= $pageH - 6.0) {
                                        // Opsi A: Tepat di bawah slot tanda tangan (rata tengah)
                                        $qrX = $xPt + ($wPt - $qrDim) / 2.0;
                                        $qrY = $yPt + $hPt + 1.5;
                                    } elseif ($xPt + $wPt + $qrDim + 2.0 <= $pageW) {
                                        // Opsi B: Di sebelah kanan slot jika bagian bawah mepet batas lembar
                                        $qrX = $xPt + $wPt + 2.0;
                                        $qrY = max(2.0, min($pageH - $qrDim - 8.0, $yPt + ($hPt - $qrDim) / 2.0));
                                    } elseif ($xPt - $qrDim - 2.0 >= 2.0) {
                                        // Opsi C: Di sebelah kiri slot jika sisi kanan tidak cukup
                                        $qrX = $xPt - $qrDim - 2.0;
                                        $qrY = max(2.0, min($pageH - $qrDim - 8.0, $yPt + ($hPt - $qrDim) / 2.0));
                                    } else {
                                        // Opsi D: Fallback batas aman lembar bawah
                                        $qrX = max(2.0, min($pageW - $qrDim - 2.0, $xPt + ($wPt - $qrDim) / 2.0));
                                        $qrY = max(2.0, $pageH - $qrDim - 9.0);
                                    }

                                    $pdf->Image($qrTempFile, $qrX, $qrY, $qrDim, $qrDim);

                                    // Label teks stempel resmi di bawah QR code
                                    $pdf->SetFont('Helvetica', '', 5);
                                    $pdf->SetTextColor(6, 78, 59); // PNJ Green
                                    $captionX = max(0.0, $qrX - 4.0);
                                    $captionY = min($pageH - 2.5, $qrY + $qrDim + 0.3);
                                    $captionW = min($pageW - $captionX, $qrDim + 8.0);
                                    $pdf->SetXY($captionX, $captionY);
                                    $pdf->Cell($captionW, 2.5, 'SIGMA PNJ Verified', 0, 0, 'C');
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

