<?php
/**
 * Partial: Left Sidebar Navigasi Workspace SIGMA (204px, #2d2b4b)
 * Hanya menampilkan rute yang memiliki implementasi backend nyata & patuh ADR-02.
 */
$currentStatus = $_GET['status'] ?? '';
$currentView   = $_GET['view'] ?? '';
$currentAction = $action ?? 'index';
$userInitial   = strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1, 'UTF-8'));
$roleLabel     = $isAdmin
    ? 'Administrator'
    : (!empty($currentUser['position'])
        ? $currentUser['position']
        : (!empty($currentUser['civitas_type']) ? ucfirst((string) $currentUser['civitas_type']) : 'Civitas'));
?>
<aside class="sigma-sidebar" id="sigmaSidebar" aria-label="Navigasi Ruang Kerja SIGMA">
    <a href="<?= BASE_URL ?>/documents" class="sigma-sidebar-brand" title="Dashboard SIGMA PNJ">
        <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Logo SIGMA" width="34" height="34" style="object-fit: contain; flex-shrink: 0;">
        <div class="d-flex flex-column min-w-0">
            <span class="sigma-sidebar-brand-title">SIGMA</span>
            <span class="sigma-sidebar-brand-sub text-truncate">PNJ Digital Sign</span>
        </div>
    </a>

    <div class="sigma-sidebar-section-label">Menu Utama</div>
    <ul class="sigma-sidebar-nav">
        <?php if ($isAdmin): ?>
            <li>
                <a href="<?= BASE_URL ?>/documents"
                   class="sigma-sidebar-link <?= ($page === 'documents' && $currentAction === 'index' && $currentStatus !== 'pending_review') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Ringkasan Dokumen</span>
                    </span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/documents?status=pending_review"
                   class="sigma-sidebar-link <?= ($page === 'documents' && $currentStatus === 'pending_review') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-inbox"></i>
                        <span>Antrean Review</span>
                    </span>
                    <?php if (($pendingQueueCount ?? 0) > 0): ?>
                        <span class="sigma-nav-count"><?= (int) $pendingQueueCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/civitas"
                   class="sigma-sidebar-link <?= ($page === 'civitas') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-people"></i>
                        <span>Master Data Civitas</span>
                    </span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/departments"
                   class="sigma-sidebar-link <?= in_array($page, ['departments', 'prodi'], true) ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-building"></i>
                        <span>Jurusan &amp; Prodi</span>
                    </span>
                </a>
            </li>
        <?php else: ?>
            <li>
                <a href="<?= BASE_URL ?>/documents"
                   class="sigma-sidebar-link <?= ($page === 'documents' && $currentAction === 'index' && $currentView !== 'queue') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/documents/create"
                   class="sigma-sidebar-link <?= ($page === 'documents' && $currentAction === 'create') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-file-earmark-plus"></i>
                        <span>Ajukan Dokumen</span>
                    </span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/documents?view=queue"
                   class="sigma-sidebar-link <?= ($page === 'documents' && ($currentView === 'queue' || $currentAction === 'sign')) ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-pen"></i>
                        <span>Tanda Tangan</span>
                    </span>
                    <?php if (($pendingQueueCount ?? 0) > 0): ?>
                        <span class="sigma-nav-count"><?= (int) $pendingQueueCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="sigma-sidebar-section-label">Pustaka &amp; Alat</div>
    <ul class="sigma-sidebar-nav">
        <?php if (!$isAdmin): ?>
            <li>
                <a href="<?= BASE_URL ?>/user-signatures"
                   class="sigma-sidebar-link <?= ($page === 'user-signatures') ? 'active' : '' ?>">
                    <span class="sigma-sidebar-link-main">
                        <i class="bi bi-vector-pen"></i>
                        <span>Template TTD</span>
                    </span>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?= BASE_URL ?>/document-templates"
               class="sigma-sidebar-link <?= ($page === 'document-templates') ? 'active' : '' ?>">
                <span class="sigma-sidebar-link-main">
                    <i class="bi bi-file-earmark-word"></i>
                    <span>Template Dokumen</span>
                </span>
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>/verify"
               class="sigma-sidebar-link <?= ($page === 'verify') ? 'active' : '' ?>">
                <span class="sigma-sidebar-link-main">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Verifikasi Publik</span>
                </span>
            </a>
        </li>
    </ul>

    <div class="sigma-sidebar-footer">
        <div class="sigma-sidebar-user">
            <div class="sigma-avatar-sm"><?= htmlspecialchars($userInitial) ?></div>
            <div class="min-w-0 flex-grow-1">
                <div class="text-white fw-semibold text-truncate" style="font-size: 0.8rem;">
                    <?= htmlspecialchars($currentUser['name'] ?? 'Pengguna') ?>
                </div>
                <div class="text-truncate" style="font-size: 0.68rem; color: var(--sigma-lavender-light);">
                    <?= htmlspecialchars($roleLabel) ?>
                </div>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout"
           class="btn btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
           style="background: rgba(255,255,255,0.1); color: #ffffff; border: 1px solid rgba(255,255,255,0.16); border-radius: 7px; font-size: 0.78rem;">
            <i class="bi bi-box-arrow-right"></i>
            <span>Keluar</span>
        </a>
    </div>
</aside>

