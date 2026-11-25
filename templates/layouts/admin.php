<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <base href="<?= $e($baseUrl) ?>">
    <title><?= $e($pageTitle ?? 'Overview') ?> | Masjid Display</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" type="text/css" href="assets/css/admin.css?v=6">
</head>

<body>
    <?php
    $brandLogo = !empty($brandIdentity['logo']) ? $brandIdentity['logo'] : 'default.png';
    $brandTitle = !empty($brandIdentity['tittle']) ? $brandIdentity['tittle'] : 'Masjid Display';
    $brandSub = !empty($brandIdentity['name']) ? $brandIdentity['name'] : 'Management Panel';
    $currentNav = (string) ($activeNav ?? 'dashboard');
    ?>
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar" aria-label="Main Navigation">
            <a href="#overview" class="sidebar-brand" data-section="overview" data-section-title="Overview">
                <img src="<?= $e($uploadUrl('identity', $brandLogo)) ?>" alt="Logo" class="sidebar-logo">
                <div class="sidebar-brand-text">
                    <div class="sidebar-brand-title"><?= $e($brandTitle) ?></div>
                    <div class="sidebar-brand-sub"><?= $e($brandSub) ?></div>
                </div>
            </a>

            <nav class="sidebar-nav">
                <div class="nav-group">
                    <div class="nav-group-label">Main Menu</div>
                    <a href="#overview" class="nav-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>" data-section="overview" data-section-title="Overview">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <span>Overview</span>
                    </a>
                    <a href="#video" class="nav-item" data-section="video" data-section-title="Video Ads">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        <span>Video Ads</span>
                    </a>
                    <a href="#keuangan" class="nav-item" data-section="keuangan" data-section-title="Laporan Kas Masjid">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>Laporan Kas Masjid</span>
                    </a>
                    <a href="#rawatib" class="nav-item" data-section="rawatib" data-section-title="Daftar Imam Rawatib">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Imam Rawatib</span>
                    </a>
                    <a href="#rutinitas" class="nav-item" data-section="rutinitas" data-section-title="Jadwal Rutinitas Bulanan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Rutinitas Bulanan</span>
                    </a>
                    <a href="#khotbah" class="nav-item" data-section="khotbah" data-section-title="Daftar Khotbah Jum'at">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span>Daftar Khotbah Jum'at</span>
                    </a>
                    <a href="#pengurus" class="nav-item" data-section="pengurus" data-section-title="Susunan Pengurus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h7l-5.5 4.2L18.5 20 12 15.8 5.5 20l2-6.8L2 9h7z"/></svg>
                        <span>Susunan Pengurus</span>
                    </a>
                    <a href="#banner" class="nav-item" data-section="banner" data-section-title="Image Banner">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Image Banner</span>
                    </a>
                </div>

                <div class="nav-group">
                    <div class="nav-group-label">Display Settings</div>
                    <a href="#identity" class="nav-item <?= $currentNav === 'control-identity' ? 'active' : '' ?>" data-section="identity" data-section-title="Masjid Profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                        <span>Masjid Profile</span>
                    </a>
                    <a href="#background" class="nav-item <?= $currentNav === 'control-background' ? 'active' : '' ?>" data-section="background" data-section-title="Display Theme">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>
                        <span>Display Theme</span>
                    </a>
                    <a href="#audio" class="nav-item <?= $currentNav === 'control-audio' ? 'active' : '' ?>" data-section="audio" data-section-title="Background Music">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        <span>Background Music</span>
                    </a>
                    <a href="#shalat" class="nav-item <?= $currentNav === 'control-shalat' ? 'active' : '' ?>" data-section="shalat" data-section-title="Jadwal Waktu Shalat">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Waktu Shalat</span>
                    </a>
                    <a href="#khotbah-day" class="nav-item <?= $currentNav === 'control-khotbah' ? 'active' : '' ?>" data-section="khotbah-day" data-section-title="Banner Khotbah Jum'at">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                        <span>Banner Khotbah Jum'at</span>
                    </a>
                </div>

                <div class="nav-group">
                    <div class="nav-group-label">System</div>
                    <a href="display" class="nav-item nav-cta" title="Open Live Display">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        <span>Open Live Display</span>
                    </a>
                    <a href="logout" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Sign Out</span>
                    </a>
                    <button type="button" class="nav-item" onclick="openModal('factoryResetModal')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <span>Restore Defaults</span>
                    </button>
                    <button type="button" class="nav-item nav-danger" onclick="openModal('shutdownConfirmModal')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                        <span>Shut Down Computer</span>
                    </button>
                </div>
            </nav>
        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="main-wrap">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="icon-btn" id="sidebarToggleBtn" aria-label="Toggle Menu" title="Toggle Menu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="topbar-title-wrap">
                        <div class="topbar-kicker">Masjid Management</div>
                        <h1 class="topbar-title" id="topbarPageTitle"><?= $e($pageTitle ?? 'Overview') ?></h1>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="live-clock-pill">
                        <span class="live-dot"></span>
                        <span id="topbarLiveClock">Loading time...</span>
                    </div>
                    <a href="display" class="btn btn-gold topbar-display-btn" title="Open Live Display">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="2" width="12" height="20" rx="2"></rect>
                        </svg>
                        <span>Live Display</span>
                    </a>
                </div>
            </header>

            <main class="page-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <div class="toast-container" id="toastContainer" aria-live="polite">
        <?php if (!empty($flash) && !empty($flash['message'])): ?>
            <?php
            $toastType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success';
            $toastTitle = $toastType === 'error' ? 'Error' : 'Success';
            ?>
            <div class="toast-banner toast-<?= $toastType ?>" id="flashToast" role="status">
                <div class="toast-icon">
                    <?php if ($toastType === 'error'): ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <?php else: ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php endif; ?>
                </div>
                <div class="toast-body">
                    <div class="toast-title"><?= $toastTitle ?></div>
                    <div class="toast-message"><?= $e($flash['message']) ?></div>
                </div>
                <button type="button" class="toast-close" onclick="this.closest('.toast-banner').remove()" aria-label="Close notification">&times;</button>
                <div class="toast-progress"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Restore Defaults Confirmation Dialog -->
    <div id="factoryResetModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Restore Default Settings</h4>
                    <button type="button" class="modal-close" onclick="closeModal('factoryResetModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 18px; color: var(--text-muted); font-size: 14px;">
                        All masjid details, cash reports, schedules, and uploaded files will be restored to their starting defaults (the sign-in account will return to <strong>admin / admin123</strong>). Your device activation will stay active.
                    </p>
                    <form action="control/factory-reset" method="POST" style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('factoryResetModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            <span>Yes, Restore Defaults</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shut Down Confirmation Dialog -->
    <div id="shutdownConfirmModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirm Shut Down</h4>
                    <button type="button" class="modal-close" onclick="closeModal('shutdownConfirmModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 18px; color: var(--text-muted); font-size: 14px;">
                        Are you sure you want to shut down the display computer now?
                    </p>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('shutdownConfirmModal')">Cancel</button>
                        <a href="control/shutdown" class="btn btn-danger">Yes, Shut Down</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js?v=6"></script>
</body>

</html>
