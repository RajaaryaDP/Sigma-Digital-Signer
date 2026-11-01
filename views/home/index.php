<?php
/**
 * View: Halaman Pengenalan (Landing Page)
 * Path: views/home/index.php
 *
 * Halaman utama pengenalan aplikasi SIGMA (Sistem Tanda Tangan Digital)
 * Politeknik Negeri Jakarta (PNJ) untuk seluruh Civitas Akademika & Publik.
 */
require_once __DIR__ . '/../layout/header.php';
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>

<!-- ==========================================================================
     1. HERO SECTION (INSTITUTIONAL BANNER & DOCUMENT PREVIEW)
     ========================================================================== -->
<section class="landing-hero">
    <div class="row align-items-center g-4">
        <!-- Kolom Kiri: Teks Pengenalan & Call to Action -->
        <div class="col-lg-7">
            <div class="landing-badge">
                <i class="bi bi-shield-check"></i>
                <span>Platform Resmi Politeknik Negeri Jakarta</span>
            </div>

            <h1 class="landing-title">
                SIGMA — Sistem Informasi & <span>Tanda Tangan Digital</span>
            </h1>

            <p class="landing-desc">
                Layanan pengesahan dokumen akademik dan administratif elektronik terpadu berbasis tanda tangan digital kriptografis, verifikasi QR Code unik, dan rantai persetujuan berjenjang untuk seluruh Civitas Akademika PNJ.
            </p>

            <!-- Action Buttons -->
            <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= BASE_URL ?>/documents" class="btn-landing-primary">
                        <i class="bi bi-folder2-open"></i>
                        <span>Buka Dashboard Dokumen</span>
                    </a>
                    <a href="<?= BASE_URL ?>/verify" class="btn-landing-secondary">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Verifikasi Dokumen</span>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth" class="btn-landing-primary">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Masuk / Login Civitas</span>
                    </a>
                    <a href="<?= BASE_URL ?>/verify" class="btn-landing-secondary">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Verifikasi Keaslian Dokumen</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Quick Trust Badges -->
            <div class="d-flex flex-wrap gap-2 pt-2">
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-2.5 py-1.5 rounded-pill small fw-normal">
                    <i class="bi bi-shield-lock-fill text-warning me-1"></i> Standar Kriptografi SHA-256
                </span>
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-2.5 py-1.5 rounded-pill small fw-normal">
                    <i class="bi bi-diagram-3-fill text-info me-1"></i> Rantai Persetujuan Berjenjang
                </span>
                <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-2.5 py-1.5 rounded-pill small fw-normal">
                    <i class="bi bi-patch-check-fill text-success me-1"></i> Validasi QR Code Publik
                </span>
            </div>
        </div>

        <!-- Kolom Kanan: Visual Mockup Dokumen Digital Terverifikasi -->
        <div class="col-lg-5">
            <div class="doc-mockup-card">
                <!-- Header Dokumen Mockup -->
                <div class="doc-mockup-header">
                    <div>
                        <div class="fw-bold text-dark small mb-0 d-flex align-items-center gap-1.5">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                            <span>LEMBAR PENGESAHAN AKADEMIK</span>
                        </div>
                        <div class="text-muted" style="font-size: 0.7rem;">Jurusan Teknik Informatika dan Komputer (TIK) PNJ</div>
                    </div>
                    <div class="doc-stamp-valid">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>SAH / RESMI</span>
                    </div>
                </div>

                <!-- Info Ringkas Berkas -->
                <div class="mb-3 p-2 rounded bg-light" style="font-size: 0.78rem;">
                    <div class="fw-semibold text-truncate text-dark">Laporan Tugas Akhir / Sidang Skripsi Periode 2025/2026</div>
                    <div class="text-muted d-flex justify-content-between mt-1">
                        <span>Kode Berkas: <code>DOC-PNJ-2026-081</code></span>
                        <span class="text-success fw-semibold"><i class="bi bi-check-all"></i> Terbit Resmi</span>
                    </div>
                </div>

                <!-- Visual Rantai Penandatangan (Sequential Approval) -->
                <div class="mb-3">
                    <div class="text-muted fw-bold mb-1.5" style="font-size: 0.72rem; letter-spacing: 0.5px;">ALUR PENANDATANGANAN BERJENJANG:</div>
                    
                    <!-- Step 1: Mahasiswa -->
                    <div class="doc-flow-step completed">
                        <div class="doc-flow-icon bg-success text-white">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Mahasiswa (Pengaju)</div>
                            <div class="text-muted" style="font-size: 0.7rem;">NIM: 2107411001 &bull; Diajukan</div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Selesai</span>
                    </div>

                    <!-- Step 2: Dosen Pembimbing -->
                    <div class="doc-flow-step completed">
                        <div class="doc-flow-icon bg-success text-white">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Dosen Pembimbing</div>
                            <div class="text-muted" style="font-size: 0.7rem;">NIP: 197505102000031002 &bull; Bertanda Tangan</div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Step 1 Disahkan</span>
                    </div>

                    <!-- Step 3: Ketua Jurusan -->
                    <div class="doc-flow-step completed">
                        <div class="doc-flow-icon bg-success text-white">
                            <i class="bi bi-check"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-dark">Ketua Jurusan TIK (Kajur)</div>
                            <div class="text-muted" style="font-size: 0.7rem;">NIP: 196803151993031001 &bull; Otorisasi Final</div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Step 2 Selesai</span>
                    </div>
                </div>

                <!-- Footer Mockup: QR & Hash Anti-Tamper -->
                <div class="pt-2 border-top d-flex align-items-center justify-content-between" style="font-size: 0.72rem;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-1 rounded bg-dark text-white text-center" style="width: 32px; height: 32px; font-size: 1.1rem; line-height: 1;">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <div>
                            <div class="fw-semibold text-dark">Token UUID Valid</div>
                            <div class="text-muted font-monospace" style="font-size: 0.68rem;">550e8400-e29b-41d4...</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-bold"><i class="bi bi-shield-check"></i> Integritas Utuh</div>
                        <div class="text-muted" style="font-size: 0.68rem;">SHA-256 Valid</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     2. 4 PILAR UTAMA (HIGHLIGHT VALUES)
     ========================================================================== -->
<section class="mb-5">
    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <div class="card-clean p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Multi-Tier Approval</div>
                        <div class="text-muted small">Persetujuan Berjenjang</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Alur pengesahan teratur dari mahasiswa, dosen pembimbing, hingga pimpinan jurusan.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card-clean p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-info-subtle text-info">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Verifikasi Publik</div>
                        <div class="text-muted small">Tanpa Perlu Login</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Mitra industri, perguruan tinggi, dan publik dapat memindai QR code untuk validasi keaslian.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card-clean p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Anti-Tampering</div>
                        <div class="text-muted small">Kriptografi SHA-256</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Mendeteksi seketika setiap modifikasi atau pemalsuan berkas PDF setelah ditandatangani.</p>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card-clean p-3 h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Audit Trail Penuh</div>
                        <div class="text-muted small">Transparansi Aktivitas</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Pencatatan stempel waktu (timestamp) dan identitas resmi pelaksana pada seluruh aksi dokumen.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     3. TENTANG SIGMA (LATAR BELAKANG & TUJUAN)
     ========================================================================== -->
<section id="tentang" class="mb-5 pt-3">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <span class="section-tag"><i class="bi bi-info-circle me-1"></i> Tentang Sistem</span>
            <h2 class="section-title">Transformasi Pengesahan Dokumen di Politeknik Negeri Jakarta</h2>
            <p class="text-secondary leading-relaxed mb-3">
                <strong>SIGMA (Sistem Informasi dan Manajemen Tanda Tangan Digital)</strong> dirancang untuk menggantikan proses pengesahan dokumen konvensional berbasis kertas dengan tanda tangan elektronik yang legal, tersertifikasi, dan aman di lingkungan <strong>Politeknik Negeri Jakarta</strong>, khususnya pada <strong>Jurusan Teknik Informatika dan Komputer (TIK)</strong>.
            </p>
            <p class="text-secondary leading-relaxed mb-4">
                Dengan SIGMA, Civitas Akademika tidak lagi menghadapi kendala birokrasi manual seperti antrean tatap muka, risiko dokumen basah tercecer, atau penundaan tanda tangan saat dosen maupun pimpinan sedang bertugas di luar kampus.
            </p>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="d-flex align-items-start gap-2.5">
                        <i class="bi bi-check2-circle text-success fs-5"></i>
                        <div>
                            <div class="fw-bold text-dark small">Paperless & Cepat</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Memangkas waktu birokrasi dari hitungan hari ke hitungan menit.</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-start gap-2.5">
                        <i class="bi bi-check2-circle text-success fs-5"></i>
                        <div>
                            <div class="fw-bold text-dark small">Kepastian Hukum</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Memenuhi kaidah integritas berkas elektronik UU ITE.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-clean p-4 border shadow-sm" style="border-radius: var(--radius-lg);">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <div class="pnj-brand-icon" style="width: 48px; height: 48px; font-size: 1.5rem; background: var(--pnj-green-800); color: #a7f3d0;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Politeknik Negeri Jakarta</h5>
                        <small class="text-muted">Jurusan Teknik Informatika dan Komputer (TIK)</small>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2">Dokumen Akademik yang Didukung:</h6>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2" style="font-size: 0.88rem;">
                    <li class="d-flex align-items-center gap-2 text-secondary">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Lembar Pengesahan Sidang Skripsi & Tugas Akhir</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 text-secondary">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Surat Permohonan & Pengantar Kerja Praktik (KP) / Magang</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 text-secondary">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Surat Keterangan Bebas Pustaka & Laboratorium</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 text-secondary">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Surat Rekomendasi Beasiswa & Studi Lanjut</span>
                    </li>
                    <li class="d-flex align-items-center gap-2 text-secondary">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Berita Acara Rapat & Otorisasi Dinas Internal Jurusan</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     4. FITUR UTAMA & KEUNGGULAN SISTEM
     ========================================================================== -->
<section id="fitur" class="mb-5 pt-3">
    <div class="text-center">
        <span class="section-tag"><i class="bi bi-stars me-1"></i> Keunggulan Teknologi</span>
        <h2 class="section-title">Fitur Komprehensif untuk Pengesahan Digital</h2>
        <p class="section-subtitle">
            Dirancang dengan standar rekayasa perangkat lunak modern untuk menjamin kemudahan, kecepatan, dan keamanan berkas akademik.
        </p>
    </div>

    <div class="row g-4">
        <!-- Fitur 1 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-pen"></i>
                </div>
                <h5 class="feature-title">Tanda Tangan Visual & QR Code</h5>
                <p class="feature-desc">
                    Penempatan stempel tanda tangan visual presisi pada lembar PDF yang dilengkapi kode QR dinamis berisi tautan token unik verifikasi.
                </p>
            </div>
        </div>

        <!-- Fitur 2 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h5 class="feature-title">Rantai Persetujuan Berjenjang</h5>
                <p class="feature-desc">
                    Alur tanda tangan sekuensial otomatis; penandatangan tahap berikutnya baru dapat memproses setelah tahap sebelumnya resmi disahkan.
                </p>
            </div>
        </div>

        <!-- Fitur 3 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <h5 class="feature-title">Verifikasi Terbuka 24 Jam</h5>
                <p class="feature-desc">
                    Halaman publik yang dapat diakses siapa saja untuk mengonfirmasi keaslian dokumen cukup dengan memindai kode QR atau memasukkan token UUID.
                </p>
            </div>
        </div>

        <!-- Fitur 4 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h5 class="feature-title">Integritas Hash Anti-Tamper</h5>
                <p class="feature-desc">
                    Menggunakan sidik jari kriptografi SHA-256 untuk memvalidasi isi berkas PDF, memastikan dokumen tidak mengalami perubahan pasca-penandatanganan.
                </p>
            </div>
        </div>

        <!-- Fitur 5 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-journal-text"></i>
                </div>
                <h5 class="feature-title">Audit Trail Transparan</h5>
                <p class="feature-desc">
                    Seluruh riwayat aksi mulai dari unggah berkas, peninjauan, pembubuhan ttd, hingga penolakan tercatat secara permanen dengan stempel waktu resmi.
                </p>
            </div>
        </div>

        <!-- Fitur 6 -->
        <div class="col-md-6 col-lg-4">
            <div class="feature-card">
                <div class="feature-icon-box">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h5 class="feature-title">Identitas Resmi Civitas PNJ</h5>
                <p class="feature-desc">
                    Terikat langsung dengan nomor identitas institusi (NIM mahasiswa, NIP dosen dan tendik) beserta gelar dan jabatan struktural yang sah.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     5. ALUR KERJA PENGESAHAN (WORKFLOW 4 LANGKAH)
     ========================================================================== -->
<section id="alur" class="mb-5 pt-3">
    <div class="text-center">
        <span class="section-tag"><i class="bi bi-signpost-split me-1"></i> Cara Kerja</span>
        <h2 class="section-title">4 Langkah Mudah Pengesahan Dokumen</h2>
        <p class="section-subtitle">
            Proses terstruktur yang menjamin keteraturan birokrasi kampus dan transparansi setiap tahapan.
        </p>
    </div>

    <div class="row g-4">
        <!-- Langkah 1 -->
        <div class="col-md-6 col-lg-3">
            <div class="workflow-card">
                <span class="workflow-number">01</span>
                <div class="stat-icon bg-success-subtle text-success mb-3">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Unggah Berkas PDF</h5>
                <p class="text-muted small mb-0">
                    Pengaju mengunggah dokumen PDF dan menata posisi slot tanda tangan pada halaman target secara visual dan interaktif.
                </p>
            </div>
        </div>

        <!-- Langkah 2 -->
        <div class="col-md-6 col-lg-3">
            <div class="workflow-card">
                <span class="workflow-number">02</span>
                <div class="stat-icon bg-info-subtle text-info mb-3">
                    <i class="bi bi-people"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Tentukan Urutan TTD</h5>
                <p class="text-muted small mb-0">
                    Pilih daftar pejabat atau dosen penandatangan beserta urutan jenjangnya (misal: Dosen Pembimbing lalu Ketua Jurusan).
                </p>
            </div>
        </div>

        <!-- Langkah 3 -->
        <div class="col-md-6 col-lg-3">
            <div class="workflow-card">
                <span class="workflow-number">03</span>
                <div class="stat-icon bg-warning-subtle text-warning mb-3">
                    <i class="bi bi-pen-fill"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Penandatanganan</h5>
                <p class="text-muted small mb-0">
                    Penandatangan meninjau isi draf dan membubuhkan tanda tangan elektronik sesuai giliran tanpa perlu bertatap muka fisik.
                </p>
            </div>
        </div>

        <!-- Langkah 4 -->
        <div class="col-md-6 col-lg-3">
            <div class="workflow-card">
                <span class="workflow-number">04</span>
                <div class="stat-icon bg-primary-subtle text-primary mb-3">
                    <i class="bi bi-patch-check"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Penerbitan & QR</h5>
                <p class="text-muted small mb-0">
                    Server menyematkan stempel visual, kode QR verifikasi, dan menerbitkan berkas final sah yang dapat diunduh dan divalidasi.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     6. CEK CEPAT DOKUMEN PUBLIK (QUICK VERIFICATION WIDGET)
     ========================================================================== -->
<section class="quick-verify-banner">
    <div class="row align-items-center g-4">
        <div class="col-lg-6">
            <div class="d-flex align-items-center gap-2 text-success fw-bold small text-uppercase mb-1">
                <i class="bi bi-qr-code-scan"></i>
                <span>Akses Publik Langsung</span>
            </div>
            <h3 class="fw-bold text-dark mb-2">Punya Dokumen Bertanda Tangan SIGMA?</h3>
            <p class="text-secondary small mb-0">
                Periksa keabsahan lembar dokumen Anda sekarang. Masukkan token verifikasi 36-karakter yang tertera di samping QR Code dokumen, atau buka portal pemindaian kamera.
            </p>
        </div>

        <div class="col-lg-6">
            <form action="<?= BASE_URL ?>/verify" method="GET" class="d-flex flex-column flex-sm-row gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted border-end-0">
                        <i class="bi bi-key"></i>
                    </span>
                    <input type="text" name="token" class="form-control border-start-0" 
                           placeholder="Contoh: 550e8400-e29b-41d4-a716-446655440000" 
                           pattern="[a-fA-F0-9-]{36}" title="Masukkan 36 karakter UUID token" required>
                </div>
                <button type="submit" class="btn btn-success fw-semibold px-4 text-nowrap d-flex align-items-center justify-content-center gap-1.5" style="border-radius: var(--radius-sm);">
                    <i class="bi bi-search"></i>
                    <span>Cek Dokumen</span>
                </button>
            </form>
            <div class="mt-2 text-muted" style="font-size: 0.76rem;">
                <i class="bi bi-info-circle me-1"></i> Tanpa perlu login. Terbuka bagi mahasiswa, dosen, maupun mitra eksternal PNJ.
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     7. PERAN CIVITAS AKADEMIKA DI SIGMA
     ========================================================================== -->
<section class="mb-5">
    <div class="text-center">
        <span class="section-tag"><i class="bi bi-people me-1"></i> Ekosistem Pengguna</span>
        <h2 class="section-title">Peran Terintegrasi Seluruh Civitas Akademika</h2>
        <p class="section-subtitle">Setiap entitas kampus memiliki hak akses dan peran spesifik dalam ekosistem SIGMA.</p>
    </div>

    <div class="row g-3">
        <!-- Mahasiswa -->
        <div class="col-md-6 col-lg-3">
            <div class="role-badge-card">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="bi bi-backpack4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Mahasiswa</h5>
                <span class="badge bg-light text-muted border mb-2.5">Pengaju Berkas</span>
                <p class="text-muted small mb-0">Mengunggah PDF lembar pengesahan skripsi, tugas akhir, surat izin magang, dan memantau status secara live.</p>
            </div>
        </div>

        <!-- Dosen -->
        <div class="col-md-6 col-lg-3">
            <div class="role-badge-card">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Dosen & Pembimbing</h5>
                <span class="badge bg-light text-muted border mb-2.5">Penandatangan Step 1</span>
                <p class="text-muted small mb-0">Memeriksa kelayakan draf bimbingan, menyetujui, dan membubuhkan tanda tangan elektronik tersimpan.</p>
            </div>
        </div>

        <!-- Ketua Jurusan -->
        <div class="col-md-6 col-lg-3">
            <div class="role-badge-card">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="bi bi-award"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Ketua Jurusan</h5>
                <span class="badge bg-light text-muted border mb-2.5">Otorisasi Final Step 2</span>
                <p class="text-muted small mb-0">Memberikan pengesahan akhir tingkat jurusan untuk lembar kelulusan dan dokumen formal institusi.</p>
            </div>
        </div>

        <!-- Tendik / Admin -->
        <div class="col-md-6 col-lg-3">
            <div class="role-badge-card">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-info-subtle text-info" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Tendik & Admin</h5>
                <span class="badge bg-light text-muted border mb-2.5">Pengelola Sistem</span>
                <p class="text-muted small mb-0">Manajemen master data civitas, pemeliharaan akun resmi, dan pemantauan jejak audit keamanan sistem.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==========================================================================
     8. AKUN PENGUJIAN DEMO (UNTUK EVALUATOR / DOSEN / PENGUJI)
     ========================================================================== -->
<section class="mb-5">
    <div class="card-clean p-4 border" style="border-radius: var(--radius-md); background: #ffffff;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-key-fill text-warning fs-5"></i>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Akun Pengujian Demo (Seed Evaluation)</h5>
                    <small class="text-muted">Gunakan akun berikut untuk menguji berbagai peran dan alur persetujuan berjenjang.</small>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/auth" class="btn btn-sm btn-outline-success fw-semibold px-3 py-1.5" style="border-radius: var(--radius-sm);">
                <i class="bi bi-box-arrow-in-right me-1"></i> Buka Halaman Login
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th>Peran (Role)</th>
                        <th>Email Institusi</th>
                        <th>NIP / NIM</th>
                        <th>Keterangan Alur</th>
                        <th class="text-end">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Administrator</span></td>
                        <td><code>admin@sigma.local</code></td>
                        <td>198501012010121001</td>
                        <td class="text-muted">Master data civitas & log audit</td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/auth?email=admin%40sigma.local" class="btn btn-xs btn-outline-secondary py-0.5 px-2">Login Admin</a>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle">Mahasiswa</span></td>
                        <td><code>mahasiswa@sigma.local</code></td>
                        <td>2107411001</td>
                        <td class="text-muted">Pengaju dokumen PDF & penataan slot</td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/auth?email=mahasiswa%40sigma.local" class="btn btn-xs btn-outline-secondary py-0.5 px-2">Login Mahasiswa</a>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Dosen Pembimbing</span></td>
                        <td><code>dosen@sigma.local</code></td>
                        <td>197505102000031002</td>
                        <td class="text-muted">Penandatangan urutan pertama (Step 1)</td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/auth?email=dosen%40sigma.local" class="btn btn-xs btn-outline-secondary py-0.5 px-2">Login Dosen</a>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle">Ketua Jurusan</span></td>
                        <td><code>kajur@sigma.local</code></td>
                        <td>196803151993031001</td>
                        <td class="text-muted">Penandatangan urutan kedua (Step 2 final)</td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/auth?email=kajur%40sigma.local" class="btn btn-xs btn-outline-secondary py-0.5 px-2">Login Kajur</a>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle">Tenaga Kependidikan</span></td>
                        <td><code>tendik@sigma.local</code></td>
                        <td>198002202005012003</td>
                        <td class="text-muted">Staf administrasi berkas akademik</td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/auth?email=tendik%40sigma.local" class="btn btn-xs btn-outline-secondary py-0.5 px-2">Login Tendik</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-2.5 text-muted" style="font-size: 0.75rem;">
            * Password standar seluruh akun pengujian adalah <code>password</code>.
        </div>
    </div>
</section>

<!-- ==========================================================================
     9. CALL TO ACTION (CTA BANNER BOTTOM)
     ========================================================================== -->
<section class="landing-cta">
    <div style="max-width: 640px; margin: 0 auto;">
        <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1 rounded-pill bg-white bg-opacity-15 text-white small mb-3 border border-white border-opacity-20">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            <span>Beralih ke Pengesahan Digital Sekarang</span>
        </div>
        <h2 class="fw-bold mb-3">Siap Menggunakan SIGMA PNJ?</h2>
        <p class="text-light opacity-90 mb-4" style="font-size: 0.98rem;">
            Tinggalkan proses pengesahan manual berbasis kertas. Masuk dengan akun institusi Anda atau periksa keabsahan berkas resmi Politeknik Negeri Jakarta secara instan.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php if ($isLoggedIn): ?>
                <a href="<?= BASE_URL ?>/documents" class="btn-landing-primary">
                    <i class="bi bi-folder2-open"></i>
                    <span>Menuju Dashboard Saya</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth" class="btn-landing-primary">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Login Civitas PNJ</span>
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/verify" class="btn-landing-secondary">
                <i class="bi bi-qr-code-scan"></i>
                <span>Verifikasi Dokumen Publik</span>
            </a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>

