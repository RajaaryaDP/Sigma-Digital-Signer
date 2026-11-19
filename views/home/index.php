<?php
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
$activeTab = $activeTab ?? 'beranda';
if ($activeTab === 'panduan') {
    $activeTab = 'guide';
}
if (!in_array($activeTab, ['beranda', 'fitur', 'tentang', 'guide'], true)) {
    $activeTab = 'beranda';
}
$pageTitle = $title ?? 'SIGMA | Tanda Tangan Digital Politeknik Negeri Jakarta';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link href="<?= BASE_URL ?>/assets/css/sigma-footer.css" rel="stylesheet">
    <style>
        /* Reading this as: institutional digital-signature introduction page for Politeknik Negeri Jakarta civitas, in the supplied navy-and-lavender Figma identity. The shared blue image fills the footer's wave and base at 40% opacity; ENERGY 2 / RHYTHM 2 / MOTION 1. */
        /* Palette: deep navy (#1d2748, #151d39) and lavender (#c3b5d9, #f6f5fb) unify the introduction shell with login.php while keeping body contrast above 8.2:1. */
        /* Typography: Georgia serif on the SIGMA wordmark echoes an academic seal; system sans-serif keeps headings and body copy crisp at every viewport width. */
        :root {
            color-scheme: light;
            --navy: #1d2748;
            --navy-deep: #151d39;
            --navy-cta: #1d233b;
            --navy-cta-hover: #2b3457;
            --lavender-pill: #c3b5d9;
            --lavender-pill-hover: #d3c7e6;
            --lavender-pill-active: #b3a2cc;
            --surface-tint: #f6f5fb;
            --surface-white: #ffffff;
            --icon-Tint: #eeebf8;
            --icon-ink: #352c66;
            --ink: #1d233b;
            --muted: #4a4759;
            --line: #cbc8d8;
            --focus: #6246d0;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-width: 320px;
            min-height: 100vh;
            min-height: 100svh;
            background: var(--surface-white);
            color: var(--ink);
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        a[href],
        button,
        [role="tab"] {
            cursor: pointer;
        }

        button:disabled {
            cursor: not-allowed;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        a:focus-visible,
        button:focus-visible,
        [role="tab"]:focus-visible {
            outline: 3px solid var(--focus);
            outline-offset: 3px;
        }

        .skip-link {
            position: absolute;
            top: -100px;
            left: 16px;
            z-index: 100;
            padding: 10px 16px;
            border-radius: 6px;
            background: var(--surface-white);
            color: var(--navy-deep);
            font-weight: 700;
        }

        .skip-link:focus {
            top: 12px;
        }

        /* Header uses subtle smoky radial layers to frame the brand lockup and tab bar against the light content canvas. */
        .sigma-header {
            position: relative;
            background:
                radial-gradient(ellipse at 18% 20%, rgba(96, 114, 166, 0.28), transparent 45%),
                radial-gradient(ellipse at 52% 85%, rgba(82, 99, 148, 0.24), transparent 50%),
                radial-gradient(ellipse at 85% 30%, rgba(102, 118, 168, 0.22), transparent 42%),
                linear-gradient(180deg, var(--navy) 0%, var(--navy-deep) 100%);
            color: var(--surface-white);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sigma-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 14px 36px;
            min-height: 86px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 24px;
        }

        .sigma-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            width: fit-content;
            min-height: 48px;
        }

        .sigma-brand-logo {
            display: block;
            width: 54px;
            height: 54px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .sigma-brand-title {
            display: block;
            margin: 0;
            color: var(--surface-white);
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1.15;
        }

        .sigma-brand-sub {
            display: block;
            margin-top: 2px;
            color: #e3e1ee;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.2;
        }

        .sigma-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(28px, 5vw, 76px);
        }

        .sigma-tab {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 8px 6px;
            border: 0;
            background: transparent;
            color: #e3e1ee;
            font: inherit;
            font-size: 0.98rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            transition: color 140ms ease;
        }

        .sigma-tab:hover {
            color: var(--surface-white);
        }

        .sigma-tab:active {
            color: var(--lavender-pill);
        }

        .sigma-tab[aria-selected="true"] {
            color: var(--surface-white);
            font-weight: 600;
        }

        /* Angled underline motif mirrors the Figma active-tab stroke and repeats across sections as the identity marker. */
        .sigma-tab[aria-selected="true"]::after {
            content: "";
            position: absolute;
            left: -2px;
            right: -2px;
            bottom: 4px;
            height: 2px;
            border-radius: 2px;
            background: var(--surface-white);
            transform: rotate(-1.5deg);
        }

        .sigma-header-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Pill radius is reserved for top-level entry CTAs so primary actions stand apart from rectangular content cards. */
        .btn-taskbar-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 106px;
            min-height: 44px;
            padding: 8px 26px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: var(--lavender-pill);
            color: var(--navy-deep);
            font-size: 0.95rem;
            font-weight: 700;
            transition: background-color 140ms ease, transform 140ms ease;
        }

        .btn-taskbar-login:hover {
            background: var(--lavender-pill-hover);
            transform: translateY(-1px);
        }

        .btn-taskbar-login:active {
            background: var(--lavender-pill-active);
            transform: translateY(0);
        }

        .sigma-main {
            flex: 1 0 auto;
            display: flex;
            flex-direction: column;
            background: var(--surface-tint);
        }

        .tab-panel {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: clamp(36px, 5vw, 68px) clamp(20px, 4vw, 48px) clamp(44px, 6vw, 76px);
        }

        .tab-panel[hidden] {
            display: none !important;
        }

        /* Beranda uses an asymmetric two-column split so the headline leads on the left and the signing illustration anchors the right. */
        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(0, 0.88fr);
            align-items: center;
            gap: clamp(28px, 5vw, 64px);
            min-height: 390px;
        }

        .hero-copy {
            max-width: 540px;
        }

        .hero-title {
            margin: 0 0 18px;
            color: var(--ink);
            font-size: clamp(1.85rem, 3.3vw, 2.65rem);
            font-weight: 800;
            line-height: 1.22;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            margin: 0 0 34px;
            max-width: 440px;
            color: var(--muted);
            font-size: clamp(0.95rem, 1.2vw, 1.06rem);
            line-height: 1.55;
        }

        .hero-cta-row {
            display: flex;
            justify-content: flex-end;
            max-width: 490px;
        }

        /* The directional arrow appears only on this primary CTA to cue forward movement into the sign-in flow. */
        .btn-hero-start {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 46px;
            padding: 11px 26px;
            border-radius: 999px;
            border: 1px solid var(--navy-cta);
            background: var(--navy-cta);
            color: var(--surface-white);
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 140ms ease, transform 140ms ease;
        }

        .btn-hero-start:hover {
            background: var(--navy-cta-hover);
            border-color: var(--navy-cta-hover);
            transform: translateY(-1px);
        }

        .btn-hero-start:active {
            background: var(--navy-deep);
            transform: translateY(0);
        }

        /* Elevation is applied to the hero illustration card and catalog cards to separate interactive white surfaces from the tinted canvas. */
        .hero-illustration-card {
            width: 100%;
            max-width: 410px;
            margin-left: auto;
            padding: clamp(22px, 3.2vw, 34px);
            border-radius: 22px;
            border: 1px solid rgba(29, 39, 72, 0.08);
            background: var(--surface-white);
            box-shadow: 0 12px 30px rgba(29, 39, 72, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-illustration-svg {
            display: block;
            width: 100%;
            height: auto;
            max-width: 340px;
        }

        /* Fitur section shifts rhythm to a centered header and two-column catalog so users can scan all eight system capabilities. */
        .section-header {
            text-align: center;
            max-width: 560px;
            margin: 0 auto clamp(28px, 4vw, 44px);
        }

        .section-heading {
            margin: 0 0 14px;
            color: var(--ink);
            font-size: clamp(1.5rem, 2.4vw, 1.95rem);
            font-weight: 800;
            line-height: 1.25;
        }

        .section-lead {
            margin: 0;
            color: var(--muted);
            font-size: clamp(0.88rem, 1.1vw, 0.96rem);
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px 44px;
            max-width: 960px;
            margin: 0 auto;
        }

        /* Feature cards use a 12px radius; primary verification card is directly actionable so public visitors can jump straight to QR check. */
        .feature-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 132px;
            padding: 20px 24px;
            border-radius: 12px;
            border: 1px solid rgba(29, 39, 72, 0.1);
            background: var(--surface-white);
            box-shadow: 0 4px 12px rgba(29, 39, 72, 0.07);
            transition: border-color 140ms ease, transform 140ms ease;
        }

        a.feature-card-link:hover {
            border-color: var(--focus);
            transform: translateY(-2px);
        }

        a.feature-card-link:active {
            transform: translateY(0);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--icon-Tint);
            color: var(--icon-ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .feature-title {
            margin: 0 0 6px;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .feature-desc {
            margin: 0;
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.48;
            max-width: 360px;
        }

        /* Tentang combines two narrative blocks with a ruled role matrix to separate institutional purpose from actor responsibilities. */
        .about-wrap {
            max-width: 780px;
            margin: 0 auto;
            text-align: center;
        }

        .about-block {
            max-width: 580px;
            margin: 0 auto 30px;
        }

        .about-subheading {
            margin: 0 0 12px;
            color: var(--ink);
            font-size: clamp(1.15rem, 1.8vw, 1.35rem);
            font-weight: 800;
            line-height: 1.3;
        }

        .about-text {
            margin: 0;
            color: var(--muted);
            font-size: clamp(0.9rem, 1.15vw, 0.98rem);
            line-height: 1.6;
        }

        .users-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin: 38px auto 30px;
            max-width: 680px;
        }

        .users-divider::before,
        .users-divider::after {
            content: "";
            flex: 1 1 auto;
            height: 1px;
            background: #7c798b;
        }

        .users-divider-label {
            margin: 0;
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 280px));
            justify-content: center;
            gap: 28px 64px;
            margin: 0 auto;
        }

        .user-role-card {
            min-height: 124px;
            padding: 22px 22px;
            border-radius: 14px;
            border: 1px solid rgba(29, 39, 72, 0.09);
            background: var(--surface-white);
            box-shadow: 0 5px 14px rgba(29, 39, 72, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .user-role-title {
            margin: 0 0 10px;
            color: var(--ink);
            font-size: 0.98rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .user-role-desc {
            margin: 0;
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.48;
        }

        /* Guide / Panduan section matching Figma layout in media_1795073220502_1f2c7842.png */
        .guide-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .guide-badge-pill {
            display: inline-flex;
            align-items: center;
            padding: 5px 14px;
            border-radius: 999px;
            background: #ece7f8;
            color: #553bb8;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .guide-hero-title {
            margin: 0 0 14px;
            color: var(--ink);
            font-size: clamp(1.95rem, 3.2vw, 2.75rem);
            font-weight: 800;
            line-height: 1.18;
            letter-spacing: -0.02em;
        }

        .guide-hero-subtitle {
            margin: 0 0 38px;
            color: #555268;
            font-size: clamp(0.92rem, 1.15vw, 1.02rem);
            line-height: 1.55;
            max-width: 680px;
        }

        .guide-steps-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .guide-step-card {
            background: var(--surface-white);
            border: 1px solid rgba(29, 39, 72, 0.08);
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(29, 39, 72, 0.04);
            padding: clamp(24px, 3.2vw, 34px);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
            gap: clamp(24px, 3.5vw, 38px);
            align-items: center;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .guide-step-card:hover {
            border-color: rgba(98, 70, 208, 0.28);
            box-shadow: 0 8px 24px rgba(29, 39, 72, 0.07);
        }

        .guide-step-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .guide-step-tag {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eeebf8;
            color: #5236b8;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .guide-step-title {
            margin: 0 0 8px;
            color: var(--ink);
            font-size: clamp(1.22rem, 1.6vw, 1.4rem);
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.01em;
        }

        .guide-step-desc {
            margin: 0 0 14px;
            color: #555268;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .guide-step-list {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .guide-step-list li {
            color: #2b283d;
            font-size: 0.86rem;
            line-height: 1.52;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .guide-step-list li .bullet-dot {
            color: #5236b8;
            font-size: 1.15rem;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .guide-step-list li .bullet-num {
            color: #5236b8;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 0.86rem;
            min-width: 16px;
        }

        .guide-step-note {
            margin: 16px 0 0;
            padding-top: 12px;
            border-top: 1px dashed #e6e2f0;
            color: #553bb8;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.5;
            width: 100%;
        }

        .guide-step-note a {
            color: #553bb8;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .guide-step-note a:hover {
            color: var(--focus);
        }

        .guide-step-visual {
            background: #f8f7fc;
            border: 1px solid rgba(29, 39, 72, 0.09);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 250px;
            cursor: pointer;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.6);
        }

        .guide-step-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
            display: block;
            transition: transform 220ms ease;
        }

        .guide-step-visual.is-contain .guide-step-img {
            object-fit: contain;
            background: #f7f6fb;
        }

        .guide-step-visual:hover .guide-step-img {
            transform: scale(1.025);
        }

        .guide-zoom-hint {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(29, 39, 72, 0.8);
            backdrop-filter: blur(4px);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            opacity: 0;
            transform: translateY(4px);
            transition: opacity 160ms ease, transform 160ms ease;
            pointer-events: none;
        }

        .guide-step-visual:hover .guide-zoom-hint {
            opacity: 1;
            transform: translateY(0);
        }

        /* Lightbox modal */
        .guide-lightbox {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .guide-lightbox[hidden] {
            display: none !important;
        }

        .guide-lightbox-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 20, 38, 0.84);
            backdrop-filter: blur(6px);
        }

        .guide-lightbox-content {
            position: relative;
            z-index: 2;
            max-width: 1080px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: guideZoomIn 180ms ease;
        }

        @keyframes guideZoomIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .guide-lightbox-close {
            position: absolute;
            top: -44px;
            right: 0;
            background: rgba(255, 255, 255, 0.2);
            border: 0;
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 140ms ease;
        }

        .guide-lightbox-close:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        .guide-lightbox-img-wrap {
            max-width: 100%;
            max-height: calc(85vh - 50px);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
            background: #ffffff;
        }

        .guide-lightbox-img {
            display: block;
            max-width: 100%;
            max-height: calc(85vh - 50px);
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .guide-lightbox-caption {
            color: #e3e1ee;
            font-size: 0.88rem;
            margin-top: 12px;
            text-align: center;
            font-weight: 600;
        }

        @media (max-width: 920px) {
            .guide-step-card {
                grid-template-columns: minmax(0, 1fr);
                gap: 20px;
            }
            .guide-step-visual {
                height: 220px;
            }
        }

        /* White spacer band mirrors the clean break above the curved footer wave in all three Figma frames. */
        .pre-footer-band {
            height: clamp(32px, 5vw, 64px);
            background: var(--surface-white);
        }

        /* Tablet breakpoint stacks the hero split cleanly while keeping two-column grids readable. */
        @media (max-width: 920px) {
            .sigma-header-inner {
                grid-template-columns: 1fr auto;
                padding: 14px 24px;
                row-gap: 10px;
            }

            .sigma-nav {
                grid-column: 1 / -1;
                order: 3;
                justify-content: flex-start;
                gap: 28px;
                padding-top: 6px;
                border-top: 1px solid rgba(255, 255, 255, 0.12);
            }

            .hero-grid {
                grid-template-columns: minmax(0, 1fr);
                min-height: auto;
                gap: 32px;
            }

            .hero-copy {
                max-width: 100%;
            }

            .hero-cta-row {
                justify-content: flex-start;
                max-width: 100%;
            }

            .hero-illustration-card {
                margin: 0 auto;
                max-width: 420px;
            }

            .features-grid {
                gap: 18px 24px;
            }

            .users-grid {
                gap: 22px 32px;
            }
        }

        /* Phone breakpoint collapses multi-column card grids to a single column and adjusts padding for thumb reach. */
        @media (max-width: 620px) {
            .sigma-header-inner {
                padding: 12px 16px;
            }

            .sigma-brand-logo {
                width: 44px;
                height: 44px;
            }

            .sigma-brand-title {
                font-size: 1.1rem;
            }

            .sigma-brand-sub {
                font-size: 0.76rem;
            }

            .btn-taskbar-login {
                min-width: 88px;
                padding: 8px 18px;
                font-size: 0.88rem;
            }

            .sigma-nav {
                justify-content: space-between;
                gap: 12px;
            }

            .tab-panel {
                padding: 28px 16px 36px;
            }

            .features-grid,
            .users-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 16px;
            }

        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <header class="sigma-header">
        <div class="sigma-header-inner">
            <a href="<?= BASE_URL ?>/" class="sigma-brand">
                <img
                    src="<?= BASE_URL ?>/assets/img/logo.png"
                    alt="Logo SIGMA"
                    class="sigma-brand-logo"
                    width="54"
                    height="54"
                    fetchpriority="high">
                <span>
                    <span class="sigma-brand-title">SIGMA</span>
                    <span class="sigma-brand-sub">Tanda Tangan Digital</span>
                </span>
            </a>

            <nav class="sigma-nav" role="tablist" aria-label="Bagian halaman perkenalan">
                <button
                    type="button"
                    role="tab"
                    id="tab-beranda"
                    class="sigma-tab"
                    aria-selected="<?= $activeTab === 'beranda' ? 'true' : 'false' ?>"
                    aria-controls="panel-beranda"
                    tabindex="<?= $activeTab === 'beranda' ? '0' : '-1' ?>"
                    data-tab="beranda">Beranda</button>
                <button
                    type="button"
                    role="tab"
                    id="tab-fitur"
                    class="sigma-tab"
                    aria-selected="<?= $activeTab === 'fitur' ? 'true' : 'false' ?>"
                    aria-controls="panel-fitur"
                    tabindex="<?= $activeTab === 'fitur' ? '0' : '-1' ?>"
                    data-tab="fitur">Fitur</button>
                <button
                    type="button"
                    role="tab"
                    id="tab-tentang"
                    class="sigma-tab"
                    aria-selected="<?= $activeTab === 'tentang' ? 'true' : 'false' ?>"
                    aria-controls="panel-tentang"
                    tabindex="<?= $activeTab === 'tentang' ? '0' : '-1' ?>"
                    data-tab="tentang">Tentang</button>
                <button
                    type="button"
                    role="tab"
                    id="tab-guide"
                    class="sigma-tab"
                    aria-selected="<?= $activeTab === 'guide' ? 'true' : 'false' ?>"
                    aria-controls="panel-guide"
                    tabindex="<?= $activeTab === 'guide' ? '0' : '-1' ?>"
                    data-tab="guide">Guide</button>
            </nav>

            <div class="sigma-header-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= BASE_URL ?>/documents" class="btn-taskbar-login">Dashboard</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth" class="btn-taskbar-login">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="sigma-main" id="main-content">
        <section
            id="panel-beranda"
            class="tab-panel"
            role="tabpanel"
            aria-labelledby="tab-beranda"
            <?= $activeTab === 'beranda' ? '' : 'hidden' ?>>
            <div class="hero-grid">
                <div class="hero-copy">
                    <h1 class="hero-title">Tanda Tangan Digital, Lebih Mudah Menggunakan SIGMA</h1>
                    <p class="hero-subtitle">
                        Platform tanda tangan digital untuk memudahkan pengelolaan dokumen secara cepat, aman, dan praktis.
                    </p>
                    <div class="hero-cta-row">
                        <a href="<?= $isLoggedIn ? (BASE_URL . '/documents') : (BASE_URL . '/auth') ?>" class="btn-hero-start">
                            <span>Mulai Sekarang</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>

                <div class="hero-illustration-card">
                    <!-- Custom vector illustration of a laptop and signed document directly depicts SIGMA's digital signing workflow. -->
                    <svg
                        class="hero-illustration-svg"
                        viewBox="0 0 360 280"
                        width="340"
                        height="264"
                        role="img"
                        aria-label="Ilustrasi penandatanganan dokumen digital pada laptop">
                        <path
                            d="M66 184C32 162 28 112 58 88C84 67 116 82 144 62C174 40 206 18 242 36C278 54 282 108 262 146C242 184 198 206 150 204C112 202 88 198 66 184Z"
                            fill="#dbd6f2" />
                        <g stroke="#ffffff" stroke-width="1.5" fill="none" opacity="0.85">
                            <circle cx="196" cy="70" r="24" />
                            <ellipse cx="196" cy="70" rx="12" ry="24" />
                            <line x1="172" y1="70" x2="220" y2="70" />
                            <line x1="176" y1="59" x2="216" y2="59" />
                            <line x1="176" y1="81" x2="216" y2="81" />
                        </g>
                        <polygon points="62,92 224,92 248,196 86,196" fill="#ffffff" stroke="#232946" stroke-width="2" stroke-linejoin="round" />
                        <polygon points="70,99 218,99 239,190 91,190" fill="#4636c6" />
                        <line x1="94" y1="109" x2="196" y2="109" stroke="#1d233b" stroke-width="4" stroke-linecap="round" />
                        <path
                            d="M104 109H190C198 109 204 115 208 126L230 192C233 201 241 207 250 207H156C144 207 135 199 131 188L104 109Z"
                            fill="#ffffff"
                            stroke="#cfd3e6"
                            stroke-width="1.5" />
                        <line x1="126" y1="127" x2="190" y2="127" stroke="#30344c" stroke-width="2" stroke-linecap="round" />
                        <line x1="131" y1="141" x2="195" y2="141" stroke="#30344c" stroke-width="2" stroke-linecap="round" />
                        <line x1="136" y1="155" x2="200" y2="155" stroke="#30344c" stroke-width="2" stroke-linecap="round" />
                        <line x1="141" y1="169" x2="192" y2="169" stroke="#30344c" stroke-width="2" stroke-linecap="round" />
                        <path
                            d="M182 192C185 184 191 183 192 190C193 195 187 197 190 193C194 189 201 189 204 193C207 196 212 191 217 192"
                            fill="none"
                            stroke="#232946"
                            stroke-width="1.8"
                            stroke-linecap="round" />
                        <polygon points="86,196 248,196 296,204 134,204" fill="#d9ddf0" stroke="#232946" stroke-width="2" stroke-linejoin="round" />
                        <polygon points="134,204 296,204 292,211 128,211" fill="#1d233b" />
                        <polygon points="86,196 134,204 128,211 82,201" fill="#1d233b" />
                        <g>
                            <path
                                d="M268 150C258 147 246 146 238 153C232 158 228 166 221 169C217 171 216 176 221 178C228 180 236 175 241 172L236 181C234 185 238 189 243 187L250 183C250 187 255 189 260 186L275 178L298 175L290 144L268 150Z"
                                fill="#f29d7e" />
                            <line x1="259" y1="115" x2="213" y2="185" stroke="#1d233b" stroke-width="6" stroke-linecap="round" />
                            <line x1="254" y1="123" x2="245" y2="136" stroke="#d9ddf0" stroke-width="2" stroke-linecap="round" />
                            <polygon points="210,189 216,184 212,181" fill="#1d233b" />
                            <path
                                d="M238 154C231 159 225 168 219 170C215 171 215 176 220 177C226 178 235 173 243 166"
                                fill="#f29d7e" />
                            <polygon points="285,141 296,139 306,177 295,179" fill="#ffffff" stroke="#d5d8e8" stroke-width="1" />
                            <polygon points="294,136 328,128 338,174 303,181" fill="#1d233b" />
                        </g>
                    </svg>
                </div>
            </div>
        </section>

        <section
            id="panel-fitur"
            class="tab-panel"
            role="tabpanel"
            aria-labelledby="tab-fitur"
            <?= $activeTab === 'fitur' ? '' : 'hidden' ?>>
            <div class="section-header">
                <h2 class="section-heading">Fitur SIGMA</h2>
                <p class="section-lead">
                    Berbagai fitur untuk membantu pengelolaan dan penandatanganan dokumen secara mudah, aman, dan praktis.
                </p>
            </div>

            <div class="features-grid">
                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="11" width="14" height="10" rx="2" />
                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                            <circle cx="12" cy="16" r="1" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Autentikasi Pengguna</h3>
                    <p class="feature-desc">Pengguna dapat masuk ke SIGMA menggunakan email dan password.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <circle cx="9" cy="8" r="3" />
                            <path d="M3 19v-1a5 5 0 0 1 5-5h2a5 5 0 0 1 5 5v1" />
                            <circle cx="17" cy="9" r="2.5" />
                            <path d="M21 19v-1a4 4 0 0 0-3-3.87" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Akses Berdasarkan Role</h3>
                    <p class="feature-desc">Menampilkan menu dan akses sesuai dengan peran pengguna.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 18a4.6 4.4 0 0 1 0-9 5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1" />
                            <polyline points="9 15 12 12 15 15" />
                            <line x1="12" y1="12" x2="12" y2="21" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Unggah Dokumen</h3>
                    <p class="feature-desc">Mengunggah dokumen dengan pemeriksaan format dan ukuran file.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                            <path d="m15 5 4 4" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Pengelolaan Dokumen &amp; Tanda Tangan</h3>
                    <p class="feature-desc">Mengelola dokumen dan tanda tangan digital yang telah diproses.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                            <circle cx="12" cy="12" r="3" />
                            <path d="m16 19 2 2 4-4" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Pengelolaan &amp; Monitoring Dokumen</h3>
                    <p class="feature-desc">Admin dapat mengelola dan memantau dokumen yang diproses dalam sistem.</p>
                </article>

                <a href="<?= BASE_URL ?>/verify" class="feature-card feature-card-link">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Verifikasi Keaslian Dokumen</h3>
                    <p class="feature-desc">Memastikan keaslian dokumen melalui watermark verifikasi.</p>
                </a>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M7 18a4.6 4.4 0 0 1 0-9 5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-1" />
                            <polyline points="9 17 12 20 15 17" />
                            <line x1="12" y1="12" x2="12" y2="20" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Unduh Dokumen Terverifikasi</h3>
                    <p class="feature-desc">Mengunduh dokumen yang sudah terverifikasi.</p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
                            <polyline points="3 3 3 8 8 8" />
                            <polyline points="12 7 12 12 15 14" />
                        </svg>
                    </span>
                    <h3 class="feature-title">Riwayat Dokumen</h3>
                    <p class="feature-desc">Melihat riwayat dan aktivitas dokumen dalam sistem.</p>
                </article>
            </div>
        </section>

        <section
            id="panel-tentang"
            class="tab-panel"
            role="tabpanel"
            aria-labelledby="tab-tentang"
            <?= $activeTab === 'tentang' ? '' : 'hidden' ?>>
            <div class="about-wrap">
                <div class="about-block">
                    <h2 class="section-heading">Tentang SIGMA</h2>
                    <p class="about-text">
                        SIGMA merupakan platform penandatanganan digital yang menyediakan layanan untuk memudahkan dalam mengelola dan menandatangani sebuah dokumen akademik.
                    </p>
                </div>

                <div class="about-block">
                    <h3 class="about-subheading">Tujuan SIGMA</h3>
                    <p class="about-text">
                        Memudahkan proses penandatanganan dokumen secara digital, sehingga pengelolaan dokumen menjadi lebih praktis dan terstruktur.
                    </p>
                </div>

                <div class="users-divider" role="presentation">
                    <p class="users-divider-label">Pengguna SIGMA</p>
                </div>

                <div class="users-grid">
                    <article class="user-role-card">
                        <h4 class="user-role-title">Mahasiswa</h4>
                        <p class="user-role-desc">Menggunakan SIGMA untuk kebutuhan dokumen akademik.</p>
                    </article>

                    <article class="user-role-card">
                        <h4 class="user-role-title">Dosen</h4>
                        <p class="user-role-desc">Mengelola dan menandatangani dokumen sesuai kewenangan.</p>
                    </article>

                    <article class="user-role-card">
                        <h4 class="user-role-title">Admin</h4>
                        <p class="user-role-desc">Mengelola dan memantau dokumen dalam sistem.</p>
                    </article>

                    <article class="user-role-card">
                        <h4 class="user-role-title">Kaprodi/Kajur</h4>
                        <p class="user-role-desc">Terlibat dalam proses persetujuan dokumen sesuai kebutuhan.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Panel Panduan / GUIDE -->
        <section
            id="panel-guide"
            class="tab-panel"
            role="tabpanel"
            aria-labelledby="tab-guide"
            <?= $activeTab === 'guide' ? '' : 'hidden' ?>>
            <div class="guide-container">
                <div class="guide-hero">
                    <span class="guide-badge-pill">Panduan Penggunaan Website</span>
                    <h1 class="guide-hero-title">Dari unggah dokumen<br>hingga tanda tangan digital.</h1>
                    <p class="guide-hero-subtitle">
                        Ikuti 5 langkah berikut untuk dapat mendisposisikan PDF, dan menandatangani serta memvalidasi dokumen yang panjang di SIGMA.
                    </p>
                </div>

                <div class="guide-steps-list">
                    <!-- Langkah 01 -->
                    <article class="guide-step-card" id="panduan-langkah-01">
                        <div class="guide-step-content">
                            <span class="guide-step-tag">Langkah 01</span>
                            <h2 class="guide-step-title">Masuk ke akun SIGMA</h2>
                            <p class="guide-step-desc">Masuk akun Anda sebelum mulai mengelola dokumen.</p>
                            <ol class="guide-step-list">
                                <li>
                                    <span class="bullet-num">1.</span>
                                    <span>Masukkan email dan password akun.</span>
                                </li>
                                <li>
                                    <span class="bullet-num">2.</span>
                                    <span>Masukkan 5 karakter captcha yang ditampilkan.</span>
                                </li>
                                <li>
                                    <span class="bullet-num">3.</span>
                                    <span>Klik &quot;Masuk&quot; untuk menuju ke Dashboard.</span>
                                </li>
                            </ol>
                            <div class="guide-step-note">
                                Belum memiliki akun? Gunakan tautan <a href="<?= BASE_URL ?>/register">&quot;Registrasi Akun Civitas&quot;</a>.
                            </div>
                        </div>
                        <div class="guide-step-visual" data-lightbox="<?= BASE_URL ?>/assets/img/guide/step1-login.jpg" data-caption="Langkah 01: Masuk ke Akun SIGMA">
                            <img src="<?= BASE_URL ?>/assets/img/guide/step1-login.jpg" alt="Tampilan Halaman Masuk ke SIGMA" class="guide-step-img" loading="lazy">
                            <span class="guide-zoom-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                Perbesar
                            </span>
                        </div>
                    </article>

                    <!-- Langkah 02 -->
                    <article class="guide-step-card" id="panduan-langkah-02">
                        <div class="guide-step-content">
                            <span class="guide-step-tag">Langkah 02</span>
                            <h2 class="guide-step-title">Kenali dashboard Anda</h2>
                            <p class="guide-step-desc">Semua aktivitas dokumen Anda ada di satu ringkasan.</p>
                            <ul class="guide-step-list">
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Ketahui peran yang perlu ditandatangani, menunggu giliran, masih antrean, atau sudah selesai.</span>
                                </li>
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Klik &quot;Ajukan dokumen baru&quot; untuk memulai pengajuan.</span>
                                </li>
                            </ul>
                            <div class="guide-step-note">
                                Pantau antrean dan berkas terbaru melalui menu Dokumen Saya.
                            </div>
                        </div>
                        <div class="guide-step-visual" data-lightbox="<?= BASE_URL ?>/assets/img/guide/step2-dashboard.png" data-caption="Langkah 02: Kenali Dashboard Anda">
                            <img src="<?= BASE_URL ?>/assets/img/guide/step2-dashboard.png" alt="Tampilan Dashboard Dokumen SIGMA" class="guide-step-img" loading="lazy">
                            <span class="guide-zoom-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                Perbesar
                            </span>
                        </div>
                    </article>

                    <!-- Langkah 03 -->
                    <article class="guide-step-card" id="panduan-langkah-03">
                        <div class="guide-step-content">
                            <span class="guide-step-tag">Langkah 03</span>
                            <h2 class="guide-step-title">Ajukan dokumen baru</h2>
                            <p class="guide-step-desc">Isi data dokumen PDF dan tentukan penandatangan.</p>
                            <ol class="guide-step-list">
                                <li>
                                    <span class="bullet-num">1.</span>
                                    <span>Isi formulir nama dan informasi.</span>
                                </li>
                                <li>
                                    <span class="bullet-num">2.</span>
                                    <span>Pilih tipe alur tanda tangan, lalu unggah berkas dokumen PDF.</span>
                                </li>
                                <li>
                                    <span class="bullet-num">3.</span>
                                    <span>Tambahkan penandatangan dan peran responsibilitas yang dibutuhkan.</span>
                                </li>
                                <li>
                                    <span class="bullet-num">4.</span>
                                    <span>Klik &quot;Unggah &amp; Atur Slot Tanda Tangan&quot;.</span>
                                </li>
                            </ol>
                            <div class="guide-step-note">
                                Dokumen berjenjang memerlukan persetujuan Admin terlebih dahulu.
                            </div>
                        </div>
                        <div class="guide-step-visual is-contain" data-lightbox="<?= BASE_URL ?>/assets/img/guide/step3-ajukan.png" data-caption="Langkah 03: Ajukan Dokumen Baru">
                            <img src="<?= BASE_URL ?>/assets/img/guide/step3-ajukan.png" alt="Formulir Ajukan Dokumen Baru SIGMA" class="guide-step-img" loading="lazy">
                            <span class="guide-zoom-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                Perbesar
                            </span>
                        </div>
                    </article>

                    <!-- Langkah 04 -->
                    <article class="guide-step-card" id="panduan-langkah-04">
                        <div class="guide-step-content">
                            <span class="guide-step-tag">Langkah 04</span>
                            <h2 class="guide-step-title">Atur posisi tanda tangan</h2>
                            <p class="guide-step-desc">Tentukan titik pada halaman dokumen yang tepat.</p>
                            <ul class="guide-step-list">
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Tentukan posisi slot tanda tangan ke halaman yang tepat dan geser kotak slot sesuai posisi tanda tangan.</span>
                                </li>
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Klik &quot;Simpan &amp; Ajukan ke Administrator&quot; setelah posisi sudah sesuai.</span>
                                </li>
                            </ul>
                            <div class="guide-step-note">
                                <strong>Penting:</strong> posisi slot tidak dapat diubah lagi setelah dokumen diajukan ke admin.
                            </div>
                        </div>
                        <div class="guide-step-visual" data-lightbox="<?= BASE_URL ?>/assets/img/guide/step4-slot.png" data-caption="Langkah 04: Atur Posisi Tanda Tangan">
                            <img src="<?= BASE_URL ?>/assets/img/guide/step4-slot.png" alt="Tata Letak Slot Tanda Tangan SIGMA" class="guide-step-img" loading="lazy">
                            <span class="guide-zoom-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                Perbesar
                            </span>
                        </div>
                    </article>

                    <!-- Langkah 05 -->
                    <article class="guide-step-card" id="panduan-langkah-05">
                        <div class="guide-step-content">
                            <span class="guide-step-tag">Langkah 05</span>
                            <h2 class="guide-step-title">Pantau proses pengajuan</h2>
                            <p class="guide-step-desc">Verifikasi dokumen serta tanda tangan akan berjalan berurutan.</p>
                            <ul class="guide-step-list">
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Periksa status pada tahapan alur berkas dokumen yang diajukan.</span>
                                </li>
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Lakukan penandatanganan sesuai giliran Anda.</span>
                                </li>
                                <li>
                                    <span class="bullet-dot">&bull;</span>
                                    <span>Dokumen yang telah selesai dapat langsung diunduh dan dipastikan keasliannya.</span>
                                </li>
                            </ul>
                            <div class="guide-step-note">
                                Status &quot;Menunggu Review Admin&quot; berarti dokumen Anda sedang ditinjau kelayakannya.
                            </div>
                        </div>
                        <div class="guide-step-visual" data-lightbox="<?= BASE_URL ?>/assets/img/guide/step5-pantau.png" data-caption="Langkah 05: Pantau Proses Pengajuan">
                            <img src="<?= BASE_URL ?>/assets/img/guide/step5-pantau.png" alt="Detail dan Pratinjau Dokumen SIGMA" class="guide-step-img" loading="lazy">
                            <span class="guide-zoom-hint">
                                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                Perbesar
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <div class="pre-footer-band" aria-hidden="true"></div>
    </main>

    <!-- Lightbox Modal untuk Memperbesar Gambar Panduan -->
    <div id="guide-lightbox" class="guide-lightbox" hidden aria-modal="true" role="dialog" aria-label="Pratinjau Screenshot Panduan">
        <div class="guide-lightbox-backdrop" id="guide-lightbox-backdrop"></div>
        <div class="guide-lightbox-content">
            <button type="button" class="guide-lightbox-close" id="guide-lightbox-close" aria-label="Tutup pratinjau">&times;</button>
            <div class="guide-lightbox-img-wrap">
                <img src="" alt="" id="guide-lightbox-img" class="guide-lightbox-img">
            </div>
            <div id="guide-lightbox-caption" class="guide-lightbox-caption"></div>
        </div>
    </div>

    <?php require __DIR__ . '/../layout/sigma-footer.php'; ?>

    <script>
        (function () {
            const tabs = Array.from(document.querySelectorAll('.sigma-tab[role="tab"]'));
            const validTabs = ['beranda', 'fitur', 'tentang', 'guide'];

            function activateTab(tabName, updateHash, focusTab) {
                if (tabName === 'panduan') {
                    tabName = 'guide';
                }
                const target = validTabs.includes(tabName) ? tabName : 'beranda';

                tabs.forEach(function (tab) {
                    const isSelected = tab.dataset.tab === target;
                    const panel = document.getElementById('panel-' + tab.dataset.tab);

                    tab.setAttribute('aria-selected', String(isSelected));
                    tab.tabIndex = isSelected ? 0 : -1;

                    if (panel) {
                        panel.hidden = !isSelected;
                    }

                    if (isSelected && focusTab) {
                        tab.focus();
                    }
                });

                if (updateHash) {
                    const newHash = target === 'beranda' ? window.location.pathname : '#' + target;
                    history.replaceState(null, '', newHash);
                }
            }

            tabs.forEach(function (tab, index) {
                tab.addEventListener('click', function () {
                    activateTab(tab.dataset.tab, true, false);
                });

                tab.addEventListener('keydown', function (event) {
                    let nextIndex = null;
                    if (event.key === 'ArrowRight') {
                        nextIndex = (index + 1) % tabs.length;
                    } else if (event.key === 'ArrowLeft') {
                        nextIndex = (index - 1 + tabs.length) % tabs.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = tabs.length - 1;
                    }

                    if (nextIndex !== null) {
                        event.preventDefault();
                        activateTab(tabs[nextIndex].dataset.tab, true, true);
                    }
                });
            });

            function syncFromLocation() {
                let hash = window.location.hash.replace('#', '').toLowerCase();
                if (hash === 'panduan') hash = 'guide';
                if (validTabs.includes(hash)) {
                    activateTab(hash, false, false);
                }
            }

            window.addEventListener('hashchange', syncFromLocation);
            syncFromLocation();

            // Lightbox functionality
            const lightbox = document.getElementById('guide-lightbox');
            const lightboxImg = document.getElementById('guide-lightbox-img');
            const lightboxCaption = document.getElementById('guide-lightbox-caption');
            const lightboxClose = document.getElementById('guide-lightbox-close');
            const lightboxBackdrop = document.getElementById('guide-lightbox-backdrop');

            function openLightbox(src, caption) {
                if (!lightbox || !lightboxImg) return;
                lightboxImg.src = src;
                lightboxImg.alt = caption || 'Pratinjau Screenshot';
                if (lightboxCaption) {
                    lightboxCaption.textContent = caption || '';
                }
                lightbox.hidden = false;
                document.body.style.overflow = 'hidden';
            }

            function closeLightbox() {
                if (!lightbox) return;
                lightbox.hidden = true;
                if (lightboxImg) lightboxImg.src = '';
                document.body.style.overflow = '';
            }

            document.querySelectorAll('.guide-step-visual[data-lightbox]').forEach(function (card) {
                card.addEventListener('click', function () {
                    const src = this.getAttribute('data-lightbox');
                    const caption = this.getAttribute('data-caption');
                    if (src) openLightbox(src, caption);
                });
            });

            if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
            if (lightboxBackdrop) lightboxBackdrop.addEventListener('click', closeLightbox);
            window.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && lightbox && !lightbox.hidden) {
                    closeLightbox();
                }
            });
        })();
    </script>
</body>
</html>
