<?php
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Header & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?= BASE_URL ?>/civitas" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i>Master Data Civitas
            </a>
            <span class="text-muted small">/</span>
            <span class="text-dark small fw-semibold">Import Massal CSV</span>
        </div>
        <h4 class="fw-bold text-dark mb-1">Import Massal Master Data Civitas (CSV)</h4>
        <p class="text-muted small mb-0">
            Unggah berkas CSV resmi untuk menambahkan banyak data Dosen, Mahasiswa, dan Tenaga Kependidikan sekaligus dengan pratinjau terverifikasi.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>/civitas/template" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-download"></i>
            <span>Unduh Template Resmi (.csv)</span>
        </a>
        <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle">
            Kembali
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-5 mt-0.5"></i>
            <div>
                <div class="fw-bold mb-1">Gagal Memproses Berkas CSV</div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Kolom Kiri: Form Upload CSV -->
    <div class="col-lg-7">
        <div class="card-clean h-100">
            <div class="card-clean-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark">
                    <i class="bi bi-cloud-arrow-up me-2 text-success"></i>1. Unggah Berkas CSV untuk Pratinjau
                </span>
                <span class="badge bg-light text-secondary border">Maks. 5 MB (.csv)</span>
            </div>
            <div class="card-clean-body p-4">
                <form method="POST" action="<?= BASE_URL ?>/civitas/import" enctype="multipart/form-data" id="csvUploadForm">
                    <input type="hidden" name="stage" value="preview">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label fw-semibold small text-dark">
                            Pilih Berkas CSV Master Civitas <span class="text-danger">*</span>
                        </label>
                        <input type="file"
                               class="form-control"
                               id="csv_file"
                               name="csv_file"
                               accept=".csv,text/csv,text/plain"
                               required>
                        <div class="form-text small text-muted mt-1">
                            Hanya menerima berkas teks <code>.csv</code> (delimiter koma <code>,</code> atau titik-koma <code>;</code>) dengan ukuran maksimal 5 MB.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2">
                        <div class="small text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            Data belum akan disimpan ke database sebelum Anda menekan tombol konfirmasi pada tahap pratinjau.
                        </div>
                        <button type="submit" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2" id="btnPreviewCsv">
                            <i class="bi bi-eye"></i>
                            <span>Baca &amp; Tampilkan Pratinjau</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Spesifikasi Kolom Wajib -->
    <div class="col-lg-5">
        <div class="card-clean h-100">
            <div class="card-clean-header">
                <span class="fw-semibold text-dark">
                    <i class="bi bi-table me-2 text-success"></i>Struktur Header Wajib (Anti-Wrong-File)
                </span>
            </div>
            <div class="card-clean-body p-4">
                <p class="small text-muted mb-2">
                    Baris pertama berkas CSV wajib memuat 7 nama kolom berikut secara lengkap:
                </p>
                <div class="p-2.5 rounded-2 mb-3 font-monospace small" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; word-break: break-all;">
                    identifier,name,email,type,department,position,phone
                </div>
                <ul class="small text-muted mb-0 ps-3" style="line-height: 1.6;">
                    <li><code>identifier</code>: NIM Mahasiswa atau NIP/NIDN Pegawai (Unik).</li>
                    <li><code>name</code>: Nama lengkap beserta gelar akademik resmi.</li>
                    <li><code>email</code>: Email institusi resmi (Unik, cth: <code>@stu.pnj.ac.id</code> / <code>@pnj.ac.id</code>).</li>
                    <li><code>type</code>: Wajib bernilai <code>mahasiswa</code>, <code>dosen</code>, atau <code>tendik</code>.</li>
                    <li><code>department</code>: Nama Jurusan / Program Studi / Unit Kerja.</li>
                    <li><code>position</code>: Jabatan / Peran resmi civitas.</li>
                    <li><code>phone</code>: Nomor telepon aktif (opsional).</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($preview) && is_array($preview)): ?>
    <?php
        $totalRows      = (int) ($preview['total_rows'] ?? 0);
        $validCount     = (int) ($preview['valid_count'] ?? 0);
        $duplicateCount = (int) ($preview['duplicate_count'] ?? 0);
        $invalidCount   = (int) ($preview['invalid_count'] ?? 0);
        $sampleRows     = $preview['sample_rows'] ?? [];
        $issueRows      = $preview['issue_rows'] ?? [];
    ?>
    <!-- Tahap 2: Pratinjau Interaktif Sebelum Konfirmasi Simpan -->
    <div class="card-clean mb-4" style="border: 2px solid #006837;">
        <div class="card-clean-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" style="background: #f0fdf4;">
            <div>
                <span class="badge bg-success me-2">TAHAP PRATINJAU</span>
                <span class="fw-bold text-dark">
                    Hasil Pembacaan Berkas: <code><?= htmlspecialchars($preview['filename'] ?? 'civitas.csv') ?></code>
                </span>
            </div>
            <div class="small text-muted">
                Waktu baca: <?= htmlspecialchars($preview['uploaded_at'] ?? '-') ?>
                &bull; Delimiter: <code><?= htmlspecialchars($preview['delimiter'] ?? ',') ?></code>
            </div>
        </div>

        <div class="card-clean-body p-4">
            <!-- Ringkasan Statistik Baris -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="small text-muted">Total Baris Data</div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalRows ?></div>
                        <div class="small text-secondary">Baris terdeteksi</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: #ecfdf5; border-color: #a7f3d0 !important;">
                        <div class="small" style="color: #065f46;">Siap Diimpor (Valid)</div>
                        <div class="fs-4 fw-bold" style="color: #047857;"><?= $validCount ?></div>
                        <div class="small" style="color: #065f46;">Akan disimpan ke DB</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: #fffbeb; border-color: #fde68a !important;">
                        <div class="small" style="color: #92400e;">Duplikat (Dilewati)</div>
                        <div class="fs-4 fw-bold" style="color: #b45309;"><?= $duplicateCount ?></div>
                        <div class="small" style="color: #92400e;">NIM/NIP atau Email sudah ada</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: #fef2f2; border-color: #fecaca !important;">
                        <div class="small" style="color: #991b1b;">Format Tidak Valid</div>
                        <div class="fs-4 fw-bold" style="color: #dc2626;"><?= $invalidCount ?></div>
                        <div class="small" style="color: #991b1b;">Kolom kosong / salah format</div>
                    </div>
                </div>
            </div>

            <!-- Tabel Sampel 10 Baris Teratas -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">
                    Sampel Pratinjau Baris Teratas (Menampilkan <?= count($sampleRows) ?> dari <?= $totalRows ?> baris)
                </h6>
                <span class="small text-muted">Periksa kesesuaian pemetaan kolom sebelum konfirmasi simpan</span>
            </div>

            <div class="table-responsive border rounded-3 mb-4">
                <table class="table table-minimal mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Baris</th>
                            <th>NIM / NIP</th>
                            <th>Nama &amp; Jabatan</th>
                            <th>Kategori</th>
                            <th>Jurusan / Unit</th>
                            <th>Email &amp; Telepon</th>
                            <th>Status Pratinjau</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sampleRows as $row): ?>
                            <?php
                                $st = $row['status'] ?? 'valid';
                                if ($st === 'valid') {
                                    $stBadge = 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;';
                                    $stIcon  = 'bi-check-circle-fill';
                                } elseif ($st === 'duplicate') {
                                    $stBadge = 'background: #fffbeb; color: #92400e; border: 1px solid #fde68a;';
                                    $stIcon  = 'bi-exclamation-triangle-fill';
                                } else {
                                    $stBadge = 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;';
                                    $stIcon  = 'bi-x-circle-fill';
                                }
                            ?>
                            <tr>
                                <td class="text-muted small">#<?= (int) $row['line'] ?></td>
                                <td>
                                    <code class="fw-semibold px-2 py-0.5 rounded" style="background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0;">
                                        <?= htmlspecialchars($row['identifier'] !== '' ? $row['identifier'] : '-') ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['name'] !== '' ? $row['name'] : '-') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($row['position'] !== '' ? $row['position'] : '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($row['type'] !== '' ? $row['type'] : '-') ?></span>
                                </td>
                                <td class="small text-secondary"><?= htmlspecialchars($row['department'] !== '' ? $row['department'] : '-') ?></td>
                                <td>
                                    <div class="small text-dark"><?= htmlspecialchars($row['email'] !== '' ? $row['email'] : '-') ?></div>
                                    <?php if (!empty($row['phone'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($row['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1" style="<?= $stBadge ?>; font-size: 0.74rem;">
                                        <i class="bi <?= $stIcon ?>"></i>
                                        <span><?= htmlspecialchars($row['reason'] ?? '') ?></span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($issueRows)): ?>
                <div class="p-3 rounded-3 mb-4" style="background: #fffbeb; border: 1px solid #fde68a;">
                    <div class="fw-semibold small mb-2" style="color: #92400e;">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Rincian Baris yang Akan Dilewati Otomatis (Duplikat / Tidak Valid):
                    </div>
                    <ul class="small mb-0 ps-3" style="color: #78350f;">
                        <?php foreach ($issueRows as $ir): ?>
                            <li>
                                <strong>Baris #<?= (int) $ir['line'] ?></strong>
                                (<code><?= htmlspecialchars($ir['identifier'] ?: '-') ?></code> &mdash; <?= htmlspecialchars($ir['email'] ?: '-') ?>):
                                <?= htmlspecialchars($ir['reason']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Tombol Konfirmasi Simpan Atomik atau Batal -->
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pt-3 border-top">
                <form method="POST" action="<?= BASE_URL ?>/civitas/import">
                    <input type="hidden" name="stage" value="cancel">
                    <button type="submit" class="btn btn-subtle text-danger d-inline-flex align-items-center gap-2">
                        <i class="bi bi-x-circle"></i>
                        <span>Batalkan Pratinjau</span>
                    </button>
                </form>

                <form method="POST" action="<?= BASE_URL ?>/civitas/import" id="csvCommitForm">
                    <input type="hidden" name="stage" value="commit">
                    <button type="submit"
                            class="btn btn-pnj-primary px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                            id="btnCommitCsv"
                            <?= $validCount <= 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-database-fill-check"></i>
                        <span>Konfirmasi &amp; Simpan <?= $validCount ?> Data Civitas ke Database</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

