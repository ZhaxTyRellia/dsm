<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <base href="<?= $e($baseUrl) ?>">
    <title><?= $e($pageTitle ?? 'Dashboard') ?> | Digital Signage Masjid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" type="text/css" href="assets/css/admin.css">
</head>

<body>
    <?php
    $brandLogo = !empty($brandIdentity['logo']) ? $brandIdentity['logo'] : 'default.png';
    $brandTitle = !empty($brandIdentity['tittle']) ? $brandIdentity['tittle'] : 'Digital Signage Masjid';
    $brandSub = !empty($brandIdentity['name']) ? $brandIdentity['name'] : 'Executive Control';
    $currentNav = (string) ($activeNav ?? 'dashboard');
    ?>
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar" aria-label="Navigasi Utama">
            <a href="" class="sidebar-brand">
                <img src="<?= $e($uploadUrl('identity', $brandLogo)) ?>" alt="Logo" class="sidebar-logo">
                <div class="sidebar-brand-text">
                    <div class="sidebar-brand-title"><?= $e($brandTitle) ?></div>
                    <div class="sidebar-brand-sub"><?= $e($brandSub) ?></div>
                </div>
            </a>

            <nav class="sidebar-nav">
                <div class="nav-group">
                    <div class="nav-group-label">Panel Utama</div>
                    <a href="" class="nav-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        <span>Ringkasan Dashboard</span>
                    </a>
                    <a href="#sec-video" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                        <span>Video Banner</span>
                    </a>
                    <a href="#sec-keuangan" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>Laporan Keuangan</span>
                    </a>
                    <a href="#sec-rawatib" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Imam Rawatib</span>
                    </a>
                    <a href="#sec-rutinitas" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>Rutinitas Bulanan</span>
                    </a>
                    <a href="#sec-khotbah" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span>Daftar Khotbah Jum'at</span>
                    </a>
                    <a href="#sec-pengurus" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h7l-5.5 4.2L18.5 20 12 15.8 5.5 20l2-6.8L2 9h7z"/></svg>
                        <span>Susunan Pengurus</span>
                    </a>
                    <a href="#sec-banner" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Image Banner</span>
                    </a>
                </div>

                <div class="nav-group">
                    <div class="nav-group-label">Pengaturan Display</div>
                    <a href="control/identity" class="nav-item <?= $currentNav === 'control-identity' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                        <span>Identitas Masjid</span>
                    </a>
                    <a href="control/background" class="nav-item <?= $currentNav === 'control-background' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>
                        <span>Latar Belakang</span>
                    </a>
                    <a href="control/audio" class="nav-item <?= $currentNav === 'control-audio' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                        <span>Audio Latar</span>
                    </a>
                    <a href="control/shalat-time" class="nav-item <?= $currentNav === 'control-shalat' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Waktu Shalat</span>
                    </a>
                    <a href="control/khotbah-day" class="nav-item <?= $currentNav === 'control-khotbah' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                        <span>Banner Khotbah</span>
                    </a>
                </div>

                <div class="nav-group">
                    <div class="nav-group-label">Aksi &amp; Sistem</div>
                    <a href="potrait" class="nav-item nav-cta" title="Tampilkan Layar Signage Portrait">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        <span>Buka Display Portrait</span>
                    </a>
                    <a href="logout" class="nav-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Keluar (Logout)</span>
                    </a>
                    <button type="button" class="nav-item" onclick="openModal('factoryResetModal')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                        <span>Reset Factory</span>
                    </button>
                    <button type="button" class="nav-item nav-danger" onclick="openModal('shutdownConfirmModal')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                        <span>Matikan PC</span>
                    </button>
                </div>
            </nav>
        </aside>

        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="main-wrap">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="icon-btn" id="sidebarToggleBtn" aria-label="Toggle Sidebar" title="Toggle Sidebar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="topbar-title-wrap">
                        <div class="topbar-kicker">Manajemen Signage Masjid</div>
                        <h1 class="topbar-title"><?= $e($pageTitle ?? 'Dashboard') ?></h1>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="live-clock-pill">
                        <span class="live-dot"></span>
                        <span id="topbarLiveClock">Memuat waktu...</span>
                    </div>
                    <a href="potrait" class="btn btn-gold topbar-display-btn" title="Lihat Display TV">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="2" width="12" height="20" rx="2"></rect>
                        </svg>
                        <span>Layar Signage</span>
                    </a>
                </div>
            </header>

            <main class="page-content">
                <?= $content ?>
            </main>
        </div>
    </div>

    <?php if (!empty($flash) && !empty($flash['message'])): ?>
        <div class="toast-banner" id="flashToast">
            <span><?= $e($flash['message']) ?></span>
            <button type="button" class="modal-close" onclick="this.parentElement.remove()" style="width:26px;height:26px;font-size:16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Factory Reset Confirmation Modal -->
    <div id="factoryResetModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Reset Pengaturan Pabrik</h4>
                    <button type="button" class="modal-close" onclick="closeModal('factoryResetModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 18px; color: var(--text-muted); font-size: 14px;">
                        Seluruh data masjid, laporan kas, daftar imam, jadwal, dan file unggahan akan dikembalikan ke data awal bawaan pabrik (akun login kembali ke <strong>admin / admin123</strong>). Lisensi perangkat tetap dipertahankan.
                    </p>
                    <form action="control/factory-reset" method="POST" style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('factoryResetModal')">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            <span>Ya, Reset Factory</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shutdown Confirmation Modal -->
    <div id="shutdownConfirmModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Konfirmasi Matikan PC</h4>
                    <button type="button" class="modal-close" onclick="closeModal('shutdownConfirmModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 18px; color: var(--text-muted); font-size: 14px;">
                        Apakah Anda yakin ingin mematikan komputer display signage sekarang?
                    </p>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="closeModal('shutdownConfirmModal')">Batal</button>
                        <a href="control/shutdown" class="btn btn-danger">Ya, Matikan PC</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>

</html>
