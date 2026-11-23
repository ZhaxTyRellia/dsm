<!-- KPI Summary Cards -->
<div class="kpi-grid">
    <div class="kpi-card kpi-featured">
        <div>
            <div class="kpi-label">Total Saldo Kas</div>
            <div class="kpi-value">Rp <?= number_format((float) ($kpi['closingTotal'] ?? 0), 0, ',', '.') ?></div>
            <div class="kpi-sub">Posisi: <?= $e($kpi['closingDate'] ?? '-') ?></div>
        </div>
        <div class="kpi-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
        </div>
    </div>

    <div class="kpi-card">
        <div>
            <div class="kpi-label">Imam Rawatib</div>
            <div class="kpi-value"><?= (int) ($kpi['rawatibCount'] ?? 0) ?></div>
            <div class="kpi-sub">Jadwal Imam 5 Waktu &amp; Badal</div>
        </div>
        <div class="kpi-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
    </div>

    <div class="kpi-card">
        <div>
            <div class="kpi-label">Agenda &amp; Khotbah</div>
            <div class="kpi-value"><?= (int) ($kpi['activityCount'] ?? 0) ?> / <?= (int) ($kpi['khotbahCount'] ?? 0) ?></div>
            <div class="kpi-sub">Rutinitas Bulanan &amp; Khotbah Jum'at</div>
        </div>
        <div class="kpi-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
    </div>

    <div class="kpi-card">
        <div>
            <div class="kpi-label">Pengurus &amp; Slide</div>
            <div class="kpi-value"><?= (int) ($kpi['staffCount'] ?? 0) ?> / <?= (int) ($kpi['activeSlides'] ?? 1) ?></div>
            <div class="kpi-sub">Personel DKM &amp; Banner Aktif</div>
        </div>
        <div class="kpi-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
    </div>
</div>

<!-- VIDEO BANNER -->
<section class="section-card" id="sec-video">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
            </div>
            <div>
                <h2 class="card-title">Video Banner (YouTube)</h2>
                <p class="card-subtitle">Atur tautan video atau live stream YouTube pada layar utama</p>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form action="youtube" method="POST" class="inline-form-row">
            <div class="form-group">
                <label for="youtube_url" class="form-label">URL YouTube</label>
                <input type="text" id="youtube_url" name="youtube_url" class="form-control"
                    value="<?= $e($currentUrl) ?>" placeholder="https://www.youtube.com/watch?v=..." autocomplete="url">
            </div>
            <button type="submit" class="btn btn-primary">Simpan Video</button>
        </form>

        <div class="video-preview-row table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="width:240px;">Pratinjau</th>
                        <th>URL Terpasang</th>
                        <th style="width:120px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($currentUrl) && !empty($currentVideoId)): ?>
                        <tr>
                            <td>1</td>
                            <td>
                                <iframe class="video-thumb" src="https://www.youtube.com/embed/<?= $e($currentVideoId) ?>" frameborder="0" allowfullscreen></iframe>
                            </td>
                            <td><?= $e($currentUrl) ?></td>
                            <td><span class="badge">Aktif</span></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-cell">Belum ada URL video YouTube yang dikonfigurasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- DATA KEUANGAN -->
<section class="section-card" id="sec-keuangan">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div>
                <h2 class="card-title">Laporan Kas Masjid</h2>
                <p class="card-subtitle">Kelola saldo awal, pemasukan, posisi kas, pengeluaran, dan saldo akhir</p>
            </div>
        </div>
        <span class="badge badge-gold" id="cashTotalFormatted">Rp <?= number_format((float) $latestClosingTotal, 0, ',', '.') ?></span>
    </div>
    <div class="card-body">
        <form action="money" method="POST" class="cash-report-form">
            <input type="hidden" name="action" value="update">
            <div class="table-wrap" style="margin-bottom: 16px;">
                <table class="data-table" style="min-width: 820px;">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Tanggal / Periode</th>
                            <th>Kemaslahatan Umat</th>
                            <th>Kemakmuran Masjid</th>
                            <th>Total Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Saldo Awal</strong></td>
                            <td><input type="text" class="form-control" name="opening_date" placeholder="Contoh: 10.09.2026" value="<?= $e($latestCashReport['opening_date'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="opening_umat" step="0.01" value="<?= $e($latestCashReport['opening_umat'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="opening_masjid" step="0.01" value="<?= $e($latestCashReport['opening_masjid'] ?? '') ?>"></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><strong>Pemasukan</strong></td>
                            <td><input type="text" class="form-control" name="period_start" placeholder="Contoh: 11.09.2026 s/d 17.09.2026" value="<?= $e($latestCashReport['period_start'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="income_umat" step="0.01" value="<?= $e($latestCashReport['income_umat'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="income_masjid" step="0.01" value="<?= $e($latestCashReport['income_masjid'] ?? '') ?>"></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><strong>Posisi Kas</strong></td>
                            <td><input type="text" class="form-control" name="position_date" placeholder="Contoh: 17.09.2026" value="<?= $e($latestCashReport['position_date'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="position_umat" step="0.01" value="<?= $e($latestCashReport['position_umat'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="position_masjid" step="0.01" value="<?= $e($latestCashReport['position_masjid'] ?? '') ?>"></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><strong>Pengeluaran</strong></td>
                            <td><input type="text" class="form-control" name="expense_date" placeholder="Contoh: 11.09.2026 s/d 17.09.2026" value="<?= $e($latestCashReport['expense_date'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="expense_umat" step="0.01" value="<?= $e($latestCashReport['expense_umat'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="expense_masjid" step="0.01" value="<?= $e($latestCashReport['expense_masjid'] ?? '') ?>"></td>
                            <td>-</td>
                        </tr>
                        <tr class="row-highlight">
                            <td><strong>Saldo Akhir</strong></td>
                            <td><input type="text" class="form-control" name="closing_date" placeholder="Contoh: 17.09.2026" value="<?= $e($latestCashReport['closing_date'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="closing_umat" step="0.01" value="<?= $e($latestCashReport['closing_umat'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="closing_masjid" step="0.01" value="<?= $e($latestCashReport['closing_masjid'] ?? '') ?>"></td>
                            <td><input type="number" class="form-control" name="closing_total" step="0.01" value="<?= $e(number_format((float) $latestClosingTotal, 2, '.', '')) ?>" readonly></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Simpan Laporan Kas</button>
            </div>
        </form>
    </div>
</section>

<!-- DATA IMAM RAWATIB -->
<section class="section-card" id="sec-rawatib">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div>
                <h2 class="card-title">Daftar Imam Rawatib</h2>
                <p class="card-subtitle">Atur jadwal imam shalat wajib dan badal imam beserta foto profil</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('dataRawatib')">+ Tambah Imam</button>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="width:130px;">Sholat</th>
                        <th style="width:160px;">Hari</th>
                        <th style="width:80px;">Foto</th>
                        <th>Nama Imam</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rawatibList)): ?>
                        <tr><td colspan="6" class="empty-cell">Belum ada data imam rawatib.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($rawatibList as $row): ?>
                            <?php $profileFile = !empty($row['profile']) ? $row['profile'] : 'default.png'; ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge"><?= $e($row['data_shalat']) ?></span></td>
                                <td><?= $e(!empty($row['hari']) ? $row['hari'] : '-') ?></td>
                                <td>
                                    <img src="assets/img/pictures/<?= $e($profileFile) ?>" alt="Foto Profil" class="table-avatar">
                                </td>
                                <td><strong><?= $e($row['name']) ?></strong></td>
                                <td>
                                    <div class="action-group">
                                        <button type="button"
                                            class="btn btn-sm btn-action-edit"
                                            onclick="editRawatib(this)"
                                            data-id="<?= (int) $row['id'] ?>"
                                            data-shalat="<?= $e($row['data_shalat']) ?>"
                                            data-hari="<?= $e($row['hari']) ?>"
                                            data-name="<?= $e($row['name']) ?>"
                                            data-profile="assets/img/pictures/<?= $e($profileFile) ?>">Edit</button>
                                        <a href="rawatib/<?= (int) $row['id'] ?>/delete"
                                            onclick="return confirm('Yakin ingin menghapus data ini?')"
                                            class="btn btn-sm btn-action-delete">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- DATA RUTINITAS BULANAN -->
<section class="section-card" id="sec-rutinitas">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div>
                <h2 class="card-title">Aktivitas Rutinitas Bulanan</h2>
                <p class="card-subtitle">Jadwal kajian rutin, pengajian, dan kegiatan masjid bulanan</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('dataRutinitas')">+ Tambah Agenda</button>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Keterangan Kegiatan</th>
                        <th>Waktu</th>
                        <th>Pengisi / Ustadz</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activityList)): ?>
                        <tr><td colspan="5" class="empty-cell">Belum ada data aktivitas rutinitas bulanan.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($activityList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= $e($row['desc']) ?></strong></td>
                                <td><span class="badge badge-gold"><?= $e($row['time']) ?></span></td>
                                <td><?= $e($row['speaker']) ?></td>
                                <td>
                                    <a href="activity/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        class="btn btn-sm btn-action-delete">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- DAFTAR KHOTBAH JUM'AT -->
<section class="section-card" id="sec-khotbah">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
            <div>
                <h2 class="card-title">Daftar Khotbah Jum'at</h2>
                <p class="card-subtitle">Jadwal khatib dan imam shalat Jum'at sepanjang tahun</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('khotbahJumat')">+ Tambah Jadwal</button>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th style="width:140px;">Bulan</th>
                        <th style="width:140px;">Tanggal</th>
                        <th>Nama Imam / Khatib</th>
                        <th style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($khotbahList)): ?>
                        <tr><td colspan="5" class="empty-cell">Belum ada jadwal khotbah Jum'at.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($khotbahList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge"><?= $e($row['moon']) ?></span></td>
                                <td><?= $e($row['date']) ?></td>
                                <td><strong><?= $e($row['identity']) ?></strong></td>
                                <td>
                                    <div class="action-group">
                                        <button type="button"
                                            class="btn btn-sm btn-action-edit"
                                            onclick="editKhotbah(this)"
                                            data-id="<?= (int) $row['id'] ?>"
                                            data-moon="<?= $e($row['moon']) ?>"
                                            data-date="<?= $e($row['date']) ?>"
                                            data-identity="<?= $e($row['identity']) ?>">Edit</button>
                                        <a href="khotbah-list/<?= (int) $row['id'] ?>/delete"
                                            onclick="return confirm('Yakin ingin menghapus data ini?')"
                                            class="btn btn-sm btn-action-delete">Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- SUSUNAN PENGURUS -->
<section class="section-card" id="sec-pengurus">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7h7l-5.5 4.2L18.5 20 12 15.8 5.5 20l2-6.8L2 9h7z"/></svg>
            </div>
            <div>
                <h2 class="card-title">Data Susunan Pengurus DKM</h2>
                <p class="card-subtitle">Struktur jabatan dan anggota kepengurusan masjid</p>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openModal('staff')">+ Tambah Pengurus</button>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Jabatan</th>
                        <th>Nama Lengkap</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staffList)): ?>
                        <tr><td colspan="4" class="empty-cell">Belum ada data susunan pengurus.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; ?>
                        <?php foreach ($staffList as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span class="badge"><?= $e($row['tier']) ?></span></td>
                                <td><strong><?= $e($row['identity']) ?></strong></td>
                                <td>
                                    <a href="staff/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        class="btn btn-sm btn-action-delete">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- IMAGE BANNER -->
<section class="section-card" id="sec-banner">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-badge-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div>
                <h2 class="card-title">Image Banner Slideshow</h2>
                <p class="card-subtitle">Kelola jumlah slide dan gambar pengumuman layar penuh</p>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="banner-toolbar">
            <div>
                <strong style="font-size:14px; color:var(--emerald-900);">Jumlah Slide Aktif</strong>
                <p style="font-size:12px; color:var(--text-muted);">Pilih 1 sampai 9 slide yang ditampilkan bergantian pada layar signage</p>
            </div>
            <form action="banner/slides" method="POST">
                <input type="hidden" name="action" value="update_slides">
                <select name="active_slides" class="form-control" style="min-width:160px;" onchange="this.form.submit()">
                    <?php for ($n = 1; $n <= 9; $n++): ?>
                        <option value="<?= $n ?>" <?= ($n === (int) $limit) ? 'selected' : '' ?>>
                            Slide <?= $n ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="banner-grid">
            <?php for ($i = 1; $i <= (int) $limit; $i++): ?>
                <?php
                $pictKey = 'pict' . $i;
                $previewId = $pictKey . '_preview';
                $currentImage = !empty($bannerData[$pictKey]) ? $bannerData[$pictKey] : 'default.png';
                ?>
                <div class="banner-card">
                    <div class="banner-card-title">
                        <span>Slider #<?= $i ?></span>
                        <span class="badge">Klik gambar</span>
                    </div>
                    <form action="banner/upload" method="post" enctype="multipart/form-data">
                        <label for="<?= $e($pictKey) ?>" class="banner-preview-label">
                            <img src="assets/img/pictures/<?= $e($currentImage) ?>" id="<?= $e($previewId) ?>" alt="Banner Slide <?= $i ?>">
                        </label>
                        <input id="<?= $e($pictKey) ?>" type="file" name="<?= $e($pictKey) ?>" accept="image/*" class="banner-file-input" data-preview-id="<?= $e($previewId) ?>">
                        <button type="submit" value="save" name="<?= $e($pictKey) ?>" class="btn btn-primary" style="width:100%; margin-top:12px;">Upload Slide <?= $i ?></button>
                    </form>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- MODALS -->
<div id="dataRawatib" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Imam Rawatib</h4>
                <button type="button" class="modal-close" onclick="closeModal('dataRawatib')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="rawatib" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="insert">
                    <div class="form-group">
                        <label class="form-label">Kategori Shalat</label>
                        <select name="data_shalat" class="form-control" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <option value="ISYA">ISYA</option>
                            <option value="SUBUH">SUBUH</option>
                            <option value="DZUHUR">DZUHUR</option>
                            <option value="ASHAR">ASHAR</option>
                            <option value="MAGHRIB">MAGHRIB</option>
                            <option value="BADAL">BADAL IMAM</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hari</label>
                        <input type="text" name="hari" class="form-control" placeholder="Contoh: SENIN - RABU / -" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Imam</label>
                        <input type="text" name="name" class="form-control" placeholder="Nama lengkap imam" required>
                    </div>
                    <div class="form-group">
                        <label for="profile" class="form-label">Foto Profil</label>
                        <input type="file" name="profile" id="profile" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Data Imam</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="editRawatib" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Imam Rawatib</h4>
                <button type="button" class="modal-close" onclick="closeModal('editRawatib')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="rawatib/update" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-rawatib-id">
                    <img id="edit-rawatib-preview" src="assets/img/pictures/default.png" alt="Foto Profil" class="preview-circle">
                    <div class="form-group">
                        <label class="form-label">Kategori Shalat</label>
                        <select name="data_shalat" id="edit-rawatib-shalat" class="form-control" required>
                            <option value="ISYA">ISYA</option>
                            <option value="SUBUH">SUBUH</option>
                            <option value="DZUHUR">DZUHUR</option>
                            <option value="ASHAR">ASHAR</option>
                            <option value="MAGHRIB">MAGHRIB</option>
                            <option value="BADAL">BADAL IMAM</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hari</label>
                        <input type="text" name="hari" id="edit-rawatib-hari" class="form-control" placeholder="Hari" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Imam</label>
                        <input type="text" name="name" id="edit-rawatib-name" class="form-control" placeholder="Nama Imam" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-rawatib-profile" class="form-label">Ganti Foto Profil (Opsional)</label>
                        <input type="file" name="profile" id="edit-rawatib-profile" class="form-control" accept="image/png, image/jpeg" data-preview-id="edit-rawatib-preview">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="dataRutinitas" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Rutinitas Bulanan</h4>
                <button type="button" class="modal-close" onclick="closeModal('dataRutinitas')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="activity" method="POST">
                    <input type="hidden" name="action" value="insert">
                    <div class="form-group">
                        <label class="form-label">Kategori Kajian / Kegiatan</label>
                        <select name="id_desc" id="keterangan" class="form-control" onchange="autoFillDesc()" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="1">KAJIAN HADITS "BULUGHUL MAROM"</option>
                            <option value="2">KAJIAN UMUM "TASAWUF"</option>
                            <option value="3">KHOTMIL QUR'AN</option>
                            <option value="4">KAJIAN HADITS "AHKAM"</option>
                            <option value="5">KAJIAN FIQIH "SAFINATUN NAJAH"</option>
                            <option value="6">BACA SOROGAN KITAB KUNING</option>
                            <option value="7">SHOLAT HAJAT, TASBIH &amp; ISTIGHOSAH</option>
                            <option value="8">TARTIL QUR'AN</option>
                            <option value="9">SHOLAWAT DIBA'</option>
                            <option value="10">KAJIAN UMUM "IBADAH"</option>
                            <option value="11">KAJIAN HADITS "AHKAM"</option>
                            <option value="12">KAJIAN IBADAH "IRSYADUL IBAD"</option>
                            <option value="13">KAJIAN FIQIH KONTEMPORER</option>
                            <option value="14">KAJIAN AL QUR'AN</option>
                            <option value="15">KEGIATAN BANSOS</option>
                            <option value="16">PENGAJIAN MAJLIS TA'LIM AN-NISA</option>
                            <option value="17">KHITANAN MASSAL</option>
                        </select>
                        <input type="hidden" name="desc" id="desc_input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Pelaksanaan</label>
                        <input type="text" name="time" class="form-control" placeholder="Contoh: Setiap Malam Ahad Ba'da Maghrib" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Pengisi</label>
                        <input type="text" name="speaker" class="form-control" placeholder="Nama Ustadz / Pembicara" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Agenda</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="khotbahJumat" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Jadwal Khotbah Jum'at</h4>
                <button type="button" class="modal-close" onclick="closeModal('khotbahJumat')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="khotbah-list" method="POST">
                    <input type="hidden" name="action" value="insert">
                    <div class="form-group">
                        <label class="form-label">Bulan</label>
                        <select name="moon" class="form-control" required>
                            <option value="">-- Pilih Bulan --</option>
                            <option value="Januari">Januari</option>
                            <option value="Februari">Februari</option>
                            <option value="Maret">Maret</option>
                            <option value="April">April</option>
                            <option value="Mei">Mei</option>
                            <option value="Juni">Juni</option>
                            <option value="Juli">Juli</option>
                            <option value="Agustus">Agustus</option>
                            <option value="September">September</option>
                            <option value="Oktober">Oktober</option>
                            <option value="November">November</option>
                            <option value="Desember">Desember</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="text" name="date" class="form-control" placeholder="Contoh: 04" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Imam / Khatib</label>
                        <input type="text" name="identity" class="form-control" placeholder="Nama Khatib" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Jadwal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="editKhotbahModal" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Jadwal Khotbah Jum'at</h4>
                <button type="button" class="modal-close" onclick="closeModal('editKhotbahModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="khotbah-list/update" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-khotbah-id">
                    <div class="form-group">
                        <label class="form-label">Bulan</label>
                        <select name="moon" id="edit-khotbah-moon" class="form-control" required>
                            <option value="Januari">Januari</option>
                            <option value="Februari">Februari</option>
                            <option value="Maret">Maret</option>
                            <option value="April">April</option>
                            <option value="Mei">Mei</option>
                            <option value="Juni">Juni</option>
                            <option value="Juli">Juli</option>
                            <option value="Agustus">Agustus</option>
                            <option value="September">September</option>
                            <option value="Oktober">Oktober</option>
                            <option value="November">November</option>
                            <option value="Desember">Desember</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <input type="text" name="date" id="edit-khotbah-date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Imam / Khatib</label>
                        <input type="text" name="identity" id="edit-khotbah-identity" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="staff" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Susunan Pengurus</h4>
                <button type="button" class="modal-close" onclick="closeModal('staff')">&times;</button>
            </div>
            <div class="modal-body">
                <form action="staff" method="POST">
                    <input type="hidden" name="action" value="insert">
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <select name="tier" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <option value="PENASEHAT">PENASEHAT</option>
                            <option value="KETUA">KETUA</option>
                            <option value="WAKIL KETUA">WAKIL KETUA</option>
                            <option value="SEKRETARIS">SEKRETARIS</option>
                            <option value="BENDAHARA">BENDAHARA</option>
                            <option value="HUMAS, SOSIAL &amp; PHBI">HUMAS, SOSIAL &amp; PHBI</option>
                            <option value="PERIBADATAN/DAKWAH">PERIBADATAN/DAKWAH</option>
                            <option value="PENDIDIKAN">PENDIDIKAN</option>
                            <option value="REMAJA MASJID">REMAJA MASJID</option>
                            <option value="PEMBANGUNAN &amp; PEMELIHARAAN KEBERSIHAN">PEMBANGUNAN &amp; PEMELIHARAAN KEBERSIHAN</option>
                            <option value="KELISTRIKAN DAN TEKNIS">KELISTRIKAN DAN TEKNIS</option>
                            <option value="KEAMANAN">KEAMANAN</option>
                            <option value="PERGUDANGAN">PERGUDANGAN</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="identity" class="form-control" placeholder="Nama Lengkap Pengurus" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Simpan Data Pengurus</button>
                </form>
            </div>
        </div>
    </div>
</div>
