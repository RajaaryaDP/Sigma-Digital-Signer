<?php
global $page;
$currentUser = $_SESSION['user'] ?? null;
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$civitasType = $currentUser['civitas_type'] ?? 'civitas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SIGMA | Politeknik Negeri Jakarta') ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- PNJ Institutional Stylesheet -->
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/style.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>

    <!-- PNJ Top Navigation Bar (Taskbar) -->
    <header class="pnj-navbar">
        <div class="pnj-navbar-inner">
            <!-- Brand Identity -->
            <a href="<?= BASE_URL ?>/" class="pnj-brand" title="Halaman Utama SIGMA PNJ">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="SIGMA" class="pnj-brand-logo" width="38" height="38" style="object-fit: contain;">
                <div class="pnj-brand-text">
                    <span class="pnj-brand-title">SIGMA</span>
                    <span class="pnj-brand-sub">Politeknik Negeri Jakarta (PNJ)</span>
                </div>
            </a>

            <!-- Horizontal Navigation Links -->
            <nav class="pnj-nav-container">
                <ul class="pnj-nav-menu">

                    <?php if ($currentUser): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/documents" class="pnj-nav-link <?= ($page === 'documents') ? 'active' : '' ?>">
                                <i class="bi bi-folder2-open"></i>
                                <span><?= $isAdmin ? 'Dashboard Dokumen' : 'Dokumen Saya' ?></span>
                            </a>
                        </li>

                        <?php if ($isAdmin): ?>
                            <li>
                                <a href="<?= BASE_URL ?>/civitas" class="pnj-nav-link <?= ($page === 'civitas') ? 'active' : '' ?>">
                                    <i class="bi bi-person-vcard"></i>
                                    <span>Master Data Civitas</span>
                                </a>
                            </li>
                            <li>
                                <a href="<?= BASE_URL ?>/departments" class="pnj-nav-link <?= in_array($page, ['departments', 'prodi'], true) ? 'active' : '' ?>">
                                    <i class="bi bi-building"></i>
                                    <span>Jurusan &amp; Prodi</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li>
                            <a href="<?= BASE_URL ?>/user-signatures" class="pnj-nav-link <?= ($page === 'user-signatures') ? 'active' : '' ?>">
                                <i class="bi bi-pen"></i>
                                <span>Template TTD</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= BASE_URL ?>/#fitur" class="pnj-nav-link">
                                <i class="bi bi-grid"></i>
                                <span>Fitur</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= BASE_URL ?>/#tentang" class="pnj-nav-link">
                                <i class="bi bi-info-circle"></i>
                                <span>Tentang</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li>
                        <a href="<?= BASE_URL ?>/verify" class="pnj-nav-link <?= ($page === 'verify') ? 'active' : '' ?>">
                            <i class="bi bi-qr-code-scan"></i>
                            <span>Verifikasi Publik</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile & Action (Taskbar Login / User Widget) -->
            <?php if ($currentUser): ?>
                <div class="pnj-user-widget">
                    <div class="pnj-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="pnj-user-info d-none d-sm-block">
                        <div class="pnj-user-name text-truncate" style="max-width: 170px;">
                            <?= htmlspecialchars($currentUser['name']) ?>
                        </div>
                        <div class="pnj-user-badge">
                            <?= $isAdmin ? 'Administrator' : ucfirst($civitasType) ?>
                            <?php if (!empty($currentUser['identifier'])): ?>
                                &bull; <?= htmlspecialchars($currentUser['identifier']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/auth/logout" class="btn-pnj-logout" title="Keluar dari sistem">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="d-none d-md-inline">Keluar</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="pnj-user-widget">
                    <a href="<?= BASE_URL ?>/auth" class="btn-pnj-login-taskbar" title="Masuk ke Akun Civitas SIGMA PNJ">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Login Civitas</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="main-container">

        <!-- Flash Message Notification -->
        <?php if (!empty($_SESSION['flash'])): ?>
            <?php 
                $flash = $_SESSION['flash'];
                $flashType = htmlspecialchars($flash['type'] ?? 'info');
                $flashIcons = [
                    'success' => 'bi-check-circle-fill',
                    'danger'  => 'bi-exclamation-triangle-fill',
                    'warning' => 'bi-exclamation-circle-fill',
                    'info'    => 'bi-info-circle-fill',
                ];
                $flashIcon = $flashIcons[$flashType] ?? 'bi-info-circle-fill';
                unset($_SESSION['flash']);
            ?>
            <div class="alert alert-<?= $flashType ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: var(--radius-md);">
                <i class="bi <?= $flashIcon ?>"></i>
                <div class="small fw-medium flex-grow-1"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
