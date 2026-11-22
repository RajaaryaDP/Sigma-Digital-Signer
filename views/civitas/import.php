<?php
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Header & Breadcrumb -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1 small">
            <a href="<?= BASE_URL ?>/civitas" class="text-decoration-none" style="color: var(--text-muted);">
                <i class="bi bi-arrow-left me-1"></i>Master Data Civitas
            </a>
            <span style="color: var(--text-muted);">/</span>
            <span class="fw-semibold" style="color: var(--sigma-purple-600);">Import Massal CSV</span>
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading);">Import Massal Master Data Civitas (CSV)</h4>
        <p class="small mb-0" style="color: var(--text-muted);">
            Unggah berkas CSV resmi untuk menambahkan banyak data Dosen, Mahasiswa, dan Tenaga Kependidikan sekaligus dengan pratinjau terverifikasi.
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= BASE_URL ?>/civitas/template" class="btn-sigma-navy text-decoration-none">
            <i class="bi bi-download"></i>
            <span>Unduh Template Resmi (.csv)</span>
        </a>
        <a href="<?= BASE_URL ?>/civitas" class="btn-sigma-outline text-decoration-none">
            Kembali
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert" style="background: var(--sigma-danger-bg); color: var(--sigma-danger-text); border-left: 4px solid #dc2626 !important;">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-octagon-fill fs-5 mt-1"></i>
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
        <div class="card-sigma h-100">
            <div class="card-sigma-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold" style="color: var(--text-heading);">
                    <i class="bi bi-cloud-arrow-up me-2 text-sigma-purple"></i>1. Unggah Berkas CSV untuk Pratinjau
                </span>
                <span class="badge-sigma badge-sigma-neutral">Maks. 5 MB (.csv)</span>
            </div>
            <div class="card-sigma-body p-4">
                <form method="POST" action="<?= BASE_URL ?>/civitas/import" enctype="multipart/form-data" id="csvUploadForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="stage" value="preview">
                    <div class="mb-3">
                        <label for="csv_file" class="sigma-form-label">
                            Pilih Berkas CSV Master Civitas <span class="text-danger">*</span>
                        </label>
                        <input type="file"
                               class="form-control sigma-input"
                               id="csv_file"
                               name="csv_file"
                               accept=".csv,text/csv,text/plain"
                               required>
                        <div class="form-text small mt-1" style="color: var(--text-muted);">
                            Hanya menerima berkas teks <code>.csv</code> (delimiter koma <code>,</code> atau titik-koma <code>;</code>) dengan ukuran maksimal 5 MB.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2">
                        <div class="small" style="color: var(--text-muted);">
                            <i class="bi bi-shield-check text-sigma-purple me-1"></i>
                            Data belum akan disimpan ke database sebelum Anda menekan tombol konfirmasi pada tahap pratinjau.
                        </div>
                        <button type="submit" class="btn-sigma-navy" id="btnPreviewCsv">
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
        <div class="card-sigma h-100">
            <div class="card-sigma-header">
                <span class="fw-semibold" style="color: var(--text-heading);">
                    <i class="bi bi-table me-2 text-sigma-purple"></i>Struktur Header Wajib (Anti-Wrong-File)
                </span>
            </div>
            <div class="card-sigma-body p-4">
                <p class="small mb-2" style="color: var(--text-muted);">
                    Baris pertama berkas CSV wajib memuat 7 nama kolom berikut secara lengkap:
                </p>
                <div class="p-2 rounded-2 mb-3 font-mono-hash small" style="background: var(--sigma-tint-50); border: 1px solid var(--border-subtle); color: var(--text-heading); word-break: break-all;">
                    identifier,name,email,type,department,position,phone
                </div>
                <ul class="small mb-0 ps-3" style="line-height: 1.6; color: var(--text-muted);">
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
    <div class="card-sigma sigma-card-active mb-4">
        <div class="card-sigma-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" style="background: var(--sigma-tint-50);">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge-sigma badge-sigma-purple">TAHAP PRATINJAU</span>
                <span class="fw-bold" style="color: var(--text-heading);">
                    Hasil Pembacaan Berkas: <code><?= htmlspecialchars($preview['filename'] ?? 'civitas.csv') ?></code>
                </span>
            </div>
            <div class="small" style="color: var(--text-muted);">
                Waktu baca: <?= htmlspecialchars($preview['uploaded_at'] ?? '-') ?>
                &bull; Delimiter: <code><?= htmlspecialchars($preview['delimiter'] ?? ',') ?></code>
            </div>
        </div>

        <div class="card-sigma-body p-4">
            <!-- Ringkasan Statistik Baris -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: var(--sigma-bg-page); border-color: var(--border-subtle) !important;">
                        <div class="small" style="color: var(--text-muted);">Total Baris Data</div>
                        <div class="fs-4 fw-bold" style="color: var(--text-heading);"><?= $totalRows ?></div>
                        <div class="small" style="color: var(--text-muted);">Baris terdeteksi</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: var(--sigma-success-bg); border-color: #a7f3d0 !important;">
                        <div class="small" style="color: var(--sigma-success-text);">Siap Diimpor (Valid)</div>
                        <div class="fs-4 fw-bold" style="color: var(--sigma-success-text);"><?= $validCount ?></div>
                        <div class="small" style="color: var(--sigma-success-text);">Akan disimpan ke DB</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: var(--sigma-warning-bg); border-color: #fde68a !important;">
                        <div class="small" style="color: var(--sigma-warning-text);">Duplikat (Dilewati)</div>
                        <div class="fs-4 fw-bold" style="color: var(--sigma-warning-text);"><?= $duplicateCount ?></div>
                        <div class="small" style="color: var(--sigma-warning-text);">NIM/NIP atau Email sudah ada</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 border" style="background: var(--sigma-danger-bg); border-color: #fecaca !important;">
                        <div class="small" style="color: var(--sigma-danger-text);">Format Tidak Valid</div>
                        <div class="fs-4 fw-bold" style="color: var(--sigma-danger-text);"><?= $invalidCount ?></div>
                        <div class="small" style="color: var(--sigma-danger-text);">Kolom kosong / salah format</div>
                    </div>
                </div>
            </div>

            <!-- Tabel Sampel 10 Baris Teratas -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h6 class="fw-bold mb-0" style="color: var(--text-heading);">
                    Sampel Pratinjau Baris Teratas (Menampilkan <?= count($sampleRows) ?> dari <?= $totalRows ?> baris)
                </h6>
                <span class="small" style="color: var(--text-muted);">Periksa kesesuaian pemetaan kolom sebelum konfirmasi simpan</span>
            </div>

            <div class="table-responsive border rounded-3 mb-4" style="border-color: var(--border-subtle) !important;">
                <table class="table table-sigma mb-0">
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
                                    $stClass = 'badge-sigma-success';
                                    $stIcon  = 'bi-check-circle-fill';
                                } elseif ($st === 'duplicate') {
                                    $stClass = 'badge-sigma-warning';
                                    $stIcon  = 'bi-exclamation-triangle-fill';
                                } else {
                                    $stClass = 'badge-sigma-danger';
                                    $stIcon  = 'bi-x-circle-fill';
                                }
                            ?>
                            <tr>
                                <td class="small" style="color: var(--text-muted);">#<?= (int) $row['line'] ?></td>
                                <td>
                                    <code class="font-mono-hash fw-semibold px-2 py-1 rounded" style="background: var(--sigma-tint-50); color: var(--text-heading); border: 1px solid var(--border-subtle);">
                                        <?= htmlspecialchars($row['identifier'] !== '' ? $row['identifier'] : '-') ?>
                                    </code>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--text-heading);"><?= htmlspecialchars($row['name'] !== '' ? $row['name'] : '-') ?></div>
                                    <div class="small" style="color: var(--text-muted);"><?= htmlspecialchars($row['position'] !== '' ? $row['position'] : '-') ?></div>
                                </td>
                                <td>
                                    <span class="badge-sigma badge-sigma-neutral text-uppercase"><?= htmlspecialchars($row['type'] !== '' ? $row['type'] : '-') ?></span>
                                </td>
                                <td class="small" style="color: var(--text-body);"><?= htmlspecialchars($row['department'] !== '' ? $row['department'] : '-') ?></td>
                                <td>
                                    <div class="small" style="color: var(--text-heading);"><?= htmlspecialchars($row['email'] !== '' ? $row['email'] : '-') ?></div>
                                    <?php if (!empty($row['phone'])): ?>
                                        <div class="small" style="color: var(--text-muted);"><?= htmlspecialchars($row['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-sigma <?= $stClass ?>">
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
                <div class="p-3 rounded-3 mb-4" style="background: var(--sigma-warning-bg); border: 1px solid #fde68a;">
                    <div class="fw-semibold small mb-2" style="color: var(--sigma-warning-text);">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Rincian Baris yang Akan Dilewati Otomatis (Duplikat / Tidak Valid):
                    </div>
                    <ul class="small mb-0 ps-3" style="color: var(--sigma-warning-text);">
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
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                <form method="POST" action="<?= BASE_URL ?>/civitas/import">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="stage" value="cancel">
                    <button type="submit" class="btn-sigma-outline text-danger" style="border-color: #fecaca;">
                        <i class="bi bi-x-circle"></i>
                        <span>Batalkan Pratinjau</span>
                    </button>
                </form>

                <form method="POST" action="<?= BASE_URL ?>/civitas/import" id="csvCommitForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="stage" value="commit">
                    <button type="submit"
                            class="btn-sigma-navy px-4 py-2"
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
