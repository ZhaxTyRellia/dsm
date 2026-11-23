<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/dashboard.css">
</head>

<body>

    <div id="loader" class="center">
    </div>
    <!--- HEADER --->
    <div class="header_btn">
        <div class="menu" onclick="openNav()" title="Setting">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
        </div>
        <div id="myNav" class="overlay">
            <div class="overlay-content">
                <div class="block-navbar">
                    <a href="javascript:void(0)" onclick="closeNav()">&times;</a>
                    <a href="<?= $e($basePath) ?>/control/identity" title="Change Your Company">IDENTITY</a>
                    <a href="<?= $e($basePath) ?>/control/background" title="Change Background">BACKGROUND</a>
                    <!--<a href="<?= $e($basePath) ?>/control/audio" title="Change Audio">AUDIO</a>-->
                    <a href="<?= $e($basePath) ?>/control/shalat-time" title="Change Shalat Time">SHALAT TIME</a>
                    <a href="<?= $e($basePath) ?>/control/khotbah-day" title="Change Banner Khotbah">BANNER KHOTBAH</a>
                    <a href="<?= $e($basePath) ?>/logout">LOGOUT</a>
                    <a href="<?= $e($basePath) ?>/control/shutdown">SHUTDOWN PC</a>
                </div>
            </div>
        </div>

        <div class="dropdown">
            <button>
                <a href="<?= $e($basePath) ?>/potrait" class="dropbtn display" title="View Potrait">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="7" y="3" width="10" height="18" rx="2" ry="2"></rect>
                    </svg>
                </a>
            </button>
        </div>
    </div>

    <hr>

    <!--- VIDEO BANNER --->
    <div class="section-card" style="margin-top: 50px;">
        <div class="section-title">Video Banner</div>
        <div class="container-all card-modern">
            <div class="form-group">
                <label for="youtube_url" class="form-label">YouTube URL:</label>
                <form action="<?= $e($basePath) ?>/youtube" method="POST" class="youtube-form">
                    <input type="text" id="youtube_url" name="youtube_url"
                        value="<?= $e($currentUrl) ?>"
                        placeholder="https://youtube.com/..."
                        required autocomplete="url"
                        style="width: 100%;">
                </form>
            </div>
            <br />
            <div class="card-modern">
                <div class="tbl-header">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Video</th>
                                <th>URL</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="tbl-content">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <tbody>
                            <?php if (!empty($currentUrl)): ?>
                                <tr>
                                    <td style="width:50px">1</td>
                                    <td>
                                        <iframe width="200" height="113" src="https://www.youtube.com/embed/<?= $e($currentVideoId) ?>" frameborder="0" allowfullscreen></iframe>
                                    </td>
                                    <td><?= $e($currentUrl) ?></td>
                                    <td>Aktif</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <hr class="gradient-hr">

    <!--- DATA KEUANGAN --->
    <div class="section-card">
        <div class="section-title">Data Keuangan</div>
        <form action="<?= $e($basePath) ?>/money" method="POST" style="width: 100%;">
            <input type="hidden" name="action" value="update">
            <div class="cash-report-form">
                <table>
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Tanggal</th>
                            <th>Kemaslahatan Umat</th>
                            <th>Kemakmuran Masjid</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Saldo Awal</td>
                            <td><input type="text" name="opening_date" placeholder="Contoh: 10.09.2026" value="<?= $e($latestCashReport['opening_date'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="opening_umat" step="0.01" value="<?= $e($latestCashReport['opening_umat'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="opening_masjid" step="0.01" value="<?= $e($latestCashReport['opening_masjid'] ?? '') ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Pemasukan</td>
                            <td><input type="text" name="period_start" placeholder="Contoh: 11.09.2026 s/d 17.09.2026" value="<?= $e($latestCashReport['period_start'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="income_umat" step="0.01" value="<?= $e($latestCashReport['income_umat'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="income_masjid" step="0.01" value="<?= $e($latestCashReport['income_masjid'] ?? '') ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Posisi kas</td>
                            <td><input type="text" name="position_date" placeholder="Contoh: 17.09.2026" value="<?= $e($latestCashReport['position_date'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="position_umat" step="0.01" value="<?= $e($latestCashReport['position_umat'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="position_masjid" step="0.01" value="<?= $e($latestCashReport['position_masjid'] ?? '') ?>"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Pengeluaran</td>
                            <td><input type="text" name="expense_date" placeholder="Contoh: 11.09.2026 s/d 17.09.2026" value="<?= $e($latestCashReport['expense_date'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="expense_umat" step="0.01" value="<?= $e($latestCashReport['expense_umat'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="expense_masjid" step="0.01" value="<?= $e($latestCashReport['expense_masjid'] ?? '') ?>"></td>
                            <td></td>
                        </tr>
                        <tr class="cash-calculated-row">
                            <td>Saldo akhir</td>
                            <td><input type="text" name="closing_date" placeholder="Contoh: 17.09.2026" value="<?= $e($latestCashReport['closing_date'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="closing_umat" step="0.01" value="<?= $e($latestCashReport['closing_umat'] ?? '') ?>"></td>
                            <td><input class="cash-input" type="number" name="closing_masjid" step="0.01" value="<?= $e($latestCashReport['closing_masjid'] ?? '') ?>"></td>
                            <td><input class="cash-total-input" type="number" name="closing_total" step="0.01" value="<?= $e(number_format($latestClosingTotal, 2, '.', '')) ?>" readonly></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" class="btn">Simpan Laporan Kas</button>
            </div>
        </form>
    </div>

    <hr class="gradient-hr">

    <!--- DATA IMAM RAWATIB --->
    <div class="section-card">
        <div class="section-title">Daftar Imam Rawatib</div>
        <button type="button" class="btn-modern" onclick="openModal('dataRawatib')">
            + Tambah Data
        </button>

        <!-- Modal Form Tambah Data -->
        <div id="dataRawatib" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Input Data Imam Rawatib</h4>
                        <button type="button" class="close" onclick="closeModal('dataRawatib')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= $e($basePath) ?>/rawatib" method="POST" enctype="multipart/form-data" style="width: 100%;">
                            <input type="hidden" name="action" value="insert">
                            <div class="input-group">
                                <select name="data_shalat" required>
                                    <option value="" disabled selected>-- Pilih Kategori --</option>
                                    <option value="ISYA">ISYA</option>
                                    <option value="SUBUH">SUBUH</option>
                                    <option value="DZUHUR">DZUHUR</option>
                                    <option value="ASHAR">ASHAR</option>
                                    <option value="MAGHRIB">MAGHRIB</option>
                                    <option value="BADAL">BADAL IMAM</option>
                                </select>

                                <input type="text" name="hari" placeholder="Hari (Contoh: SENIN - RABU / -)" required style="margin-top: 10px;">

                                <input type="text" name="name" placeholder="Nama Imam" required style="margin-top: 10px;">

                                <div style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
                                    <label for="profile" style="display: block; font-size: 14px; margin-bottom: 5px;">Foto Profile:</label>
                                    <input type="file" name="profile" id="profile" accept="image/*" required>
                                </div>

                                <button type="submit" class="btn" style="margin-top: 10px;">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="editRawatib" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Data Imam Rawatib</h4>
                        <button type="button" class="close" onclick="closeModal('editRawatib')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= $e($basePath) ?>/rawatib/update" method="POST" enctype="multipart/form-data" style="width: 100%;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" id="edit-rawatib-id">
                            <div class="input-group">
                                <select name="data_shalat" id="edit-rawatib-shalat" required>
                                    <option value="ISYA">ISYA</option>
                                    <option value="SUBUH">SUBUH</option>
                                    <option value="DZUHUR">DZUHUR</option>
                                    <option value="ASHAR">ASHAR</option>
                                    <option value="MAGHRIB">MAGHRIB</option>
                                    <option value="BADAL">BADAL IMAM</option>
                                </select>
                                <input type="text" name="hari" id="edit-rawatib-hari" placeholder="Hari" required style="margin-top: 10px;">
                                <input type="text" name="name" id="edit-rawatib-name" placeholder="Nama Imam" required style="margin-top: 10px;">
                                <div style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
                                    <label for="edit-rawatib-profile" style="display: block; font-size: 14px; margin-bottom: 5px;">Ganti Foto Profile:</label>
                                    <img id="edit-rawatib-preview" alt="Foto Profil" style="display: block; width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 10px;">
                                    <input type="file" name="profile" id="edit-rawatib-profile" accept="image/png, image/jpeg" data-preview-id="edit-rawatib-preview">
                                </div>
                                <button type="submit" class="btn" style="margin-top: 10px;">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <br>

        <!-- Tabel Data Imam -->
        <div class="card-modern">
            <div class="tbl-header">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th style="width:120px;">Sholat</th>
                            <th style="width:140px;">Hari</th>
                            <th style="width:100px;">Foto</th>
                            <th>Nama Imam</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="tbl-content">
                <table>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($rawatibList as $row): ?>
                            <?php $profileFile = !empty($row['profile']) ? $row['profile'] : 'default.png'; ?>
                            <tr>
                                <td style="width:50px;"><?= $no++ ?></td>
                                <td style="width:120px;"><strong><?= $e($row['data_shalat']) ?></strong></td>
                                <td style="width:140px;"><?= $e(!empty($row['hari']) ? $row['hari'] : '-') ?></td>
                                <td style="width:100px;">
                                    <img src="<?= $e($basePath) ?>/assets/img/pictures/<?= $e($profileFile) ?>" alt="Foto Profil" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                </td>
                                <td><?= $e($row['name']) ?></td>
                                <td style="width:80px;">
                                    <button type="button"
                                        onclick="editRawatib(this)"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-shalat="<?= $e($row['data_shalat']) ?>"
                                        data-hari="<?= $e($row['hari']) ?>"
                                        data-name="<?= $e($row['name']) ?>"
                                        data-profile="<?= $e($basePath) ?>/assets/img/pictures/<?= $e($profileFile) ?>"
                                        style="color: blue; cursor: pointer;">Edit</button>
                                    |
                                    <a href="<?= $e($basePath) ?>/rawatib/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        style="color: red; text-decoration: none; font-weight: bold;">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <hr class="gradient-hr">

    <!--- DATA RUTINITAS BULANAN --->
    <div class="section-card">
        <div class="section-title">Aktivitas Rutinitas Bulanan</div>
        <button type="button" class="btn-modern" onclick="openModal('dataRutinitas')">
            + Tambah Data
        </button>

        <div id="dataRutinitas" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Input Data Rutinitas Bulanan</h4>
                        <button type="button" class="close" onclick="closeModal('dataRutinitas')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= $e($basePath) ?>/activity" method="POST" style="width: 100%;">
                            <input type="hidden" name="action" value="insert">
                            <div class="input-group">
                                <select name="id_desc" id="keterangan" onchange="autoFillDesc()" required>
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
                                <input type="text" name="time" placeholder="Waktu" required>
                                <input type="text" name="speaker" placeholder="Nama Pengisi" required>
                                <button type="submit" class="btn">Simpan Data</button>
                            </div>
                            <script>
                                function autoFillDesc() {
                                    const select = document.getElementById('keterangan');
                                    const hiddenDesc = document.getElementById('desc_input');
                                    const selectedText = select.options[select.selectedIndex].text;
                                    hiddenDesc.value = select.value !== "" ? selectedText : "";
                                }
                            </script>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <br>

        <div class="card-modern">
            <div class="tbl-header">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Keterangan</th>
                            <th>Waktu</th>
                            <th>Pengisi</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="tbl-content">
                <table>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($activityList as $row): ?>
                            <tr>
                                <td style="width:50px;"><?= $no++ ?></td>
                                <td><?= $e($row['desc']) ?></td>
                                <td><?= $e($row['time']) ?></td>
                                <td><?= $e($row['speaker']) ?></td>
                                <td style="width:80px;">
                                    <a href="<?= $e($basePath) ?>/activity/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        style="color: red; text-decoration: none; font-weight: bold;">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <hr class="gradient-hr">

    <!--- DAFTAR KHOTBAH JUM'AT --->
    <div class="section-card">
        <div class="section-title">Daftar Khotbah Ju'mat</div>
        <button type="button" class="btn-modern" onclick="openModal('khotbahJumat')">
            + Tambah Data
        </button>

        <div id="khotbahJumat" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Input Data Khotbah Jum'at</h4>
                        <button type="button" class="close" onclick="closeModal('khotbahJumat')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= $e($basePath) ?>/khotbah-list" method="POST" style="width: 100%;">
                            <input type="hidden" name="action" value="insert">
                            <div class="input-group">
                                <select name="moon" required>
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
                                <input type="text" name="date" placeholder="Waktu" required>
                                <input type="text" name="identity" placeholder="Nama Imam/Khotib" required>
                                <button type="submit" class="btn">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <br>

        <div class="card-modern">
            <div class="tbl-header">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Bulan</th>
                            <th>Waktu</th>
                            <th>Nama Imam/Khotib</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="tbl-content">
                <table>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($khotbahList as $row): ?>
                            <tr>
                                <td style="width:50px;"><?= $no++ ?></td>
                                <td><?= $e($row['moon']) ?></td>
                                <td><?= $e($row['date']) ?></td>
                                <td><?= $e($row['identity']) ?></td>
                                <td style="width:150px;">
                                    <button type="button"
                                        onclick="editData(this)"
                                        data-id="<?= (int) $row['id'] ?>"
                                        data-moon="<?= $e($row['moon']) ?>"
                                        data-date="<?= $e($row['date']) ?>"
                                        data-identity="<?= $e($row['identity']) ?>"
                                        style="color: blue; cursor: pointer;">Edit</button>
                                    |
                                    <a href="<?= $e($basePath) ?>/khotbah-list/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        style="color: red; text-decoration: none; font-weight: bold;">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form id="editForm" action="<?= $e($basePath) ?>/khotbah-list/update" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="moon" id="editMoon">
                    <input type="hidden" name="date" id="editDate">
                    <input type="hidden" name="identity" id="editIdentity">
                </form>

                <script>
                    function editData(button) {
                        let newMoon = prompt("Edit Bulan:", button.dataset.moon);
                        let newDate = prompt("Edit Tanggal:", button.dataset.date);
                        let newIdentity = prompt("Edit Nama/Identitas:", button.dataset.identity);

                        if (newMoon && newDate && newIdentity) {
                            document.getElementById('editId').value = button.dataset.id;
                            document.getElementById('editMoon').value = newMoon;
                            document.getElementById('editDate').value = newDate;
                            document.getElementById('editIdentity').value = newIdentity;
                            document.getElementById('editForm').submit();
                        }
                    }
                </script>
            </div>
        </div>
    </div>

    <hr class="gradient-hr">

    <!--- SUSUNAN PENGURUS --->
    <div class="section-card">
        <div class="section-title">Data Susunan Pengurus</div>
        <button type="button" class="btn-modern" onclick="openModal('staff')">
            + Tambah Data
        </button>

        <div id="staff" class="modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Input Data Susunan Pengurus</h4>
                        <button type="button" class="close" onclick="closeModal('staff')">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="<?= $e($basePath) ?>/staff" method="POST">
                            <input type="hidden" name="action" value="insert">

                            <div class="input-group">
                                <select name="tier" required>
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

                                <input type="text" name="identity" placeholder="Nama Lengkap" required>

                                <button type="submit" class="btn">Simpan Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <br>

        <div class="card-modern">
            <div class="tbl-header">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Jabatan</th>
                            <th>Nama</th>
                            <th style="width:80px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="tbl-content">
                <table>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($staffList as $row): ?>
                            <tr>
                                <td style="width:50px;"><?= $no++ ?></td>
                                <td><?= $e($row['tier']) ?></td>
                                <td><?= $e($row['identity']) ?></td>
                                <td style="width:80px;">
                                    <a href="<?= $e($basePath) ?>/staff/<?= (int) $row['id'] ?>/delete"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')"
                                        style="color: red; text-decoration: none; font-weight: bold;">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <hr class="gradient-hr">

    <!--- BANNER IMAGE FADE --->
    <div class="section-card">
        <div class="section-title">Image Banner</div>
        <div class="form-group">
            <label class="form-label">Banner Active: </label>
            <form action="<?= $e($basePath) ?>/banner/slides" method="POST">
                <input type="hidden" name="action" value="update_slides">
                <select name="active_slides" onchange="this.form.submit()">
                    <?php for ($n = 1; $n <= 9; $n++): ?>
                        <option value="<?= $n ?>" <?= ($n === (int) $limit) ? 'selected' : '' ?>>
                            Slide <?= $n ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="container">
            <?php for ($i = 1; $i <= (int) $limit; $i++): ?>
                <?php
                $pictKey = 'pict' . $i;
                $previewId = $pictKey . '_preview';
                $currentImage = !empty($bannerData[$pictKey]) ? $bannerData[$pictKey] : 'default.png';
                ?>
                <div class="hero">
                    <p class="text">Slider : <?= $i ?></p>
                    <form action="<?= $e($basePath) ?>/banner/upload" method="post" enctype="multipart/form-data">
                        <center>
                            <label for="<?= $e($pictKey) ?>">
                                <img src="<?= $e($basePath) ?>/assets/img/pictures/<?= $e($currentImage) ?>"
                                    class="pictures" id="<?= $e($previewId) ?>" alt="NO IMAGE">
                            </label>
                        </center>
                        <div class="image-upload">
                            <input id="<?= $e($pictKey) ?>" type="file" name="<?= $e($pictKey) ?>" accept="image/*" data-preview-id="<?= $e($previewId) ?>">
                        </div>
                        <div class="input-group">
                            <button type="submit" value="save" name="<?= $e($pictKey) ?>" class="btn">Upload</button>
                        </div>
                    </form>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!--- SCRIPTS --->
    <script type="text/javascript" src="<?= $e($basePath) ?>/assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="<?= $e($basePath) ?>/assets/js/bootstrap.min.js"></script>
    <!--- Modal -->
    <script>
        function openModal(id) {
            var modal = document.getElementById(id);
            if (modal) {
                modal.style.display = "block";
                modal.classList.add("in");
            }
        }

        function closeModal(id) {
            var modal = document.getElementById(id);
            if (modal) {
                modal.style.display = "none";
                modal.classList.remove("in");
            }
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }
    </script>
    <!-- Modal Preloader -->
    <script>
        document.onreadystatechange = function() {
            if (document.readyState !== "complete") {
                document.querySelector("body").style.visibility = "hidden";
                document.querySelector("#loader").style.visibility = "visible";
            } else {
                document.querySelector("#loader").style.display = "none";
                document.querySelector("body").style.visibility = "visible";
            }
        };
    </script>
    <script>
        function openNav() {
            document.getElementById("myNav").style.height = "100%";
        }

        function closeNav() {
            document.getElementById("myNav").style.height = "0%";
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportForm = document.querySelector('.cash-report-form');
            if (!reportForm) {
                return;
            }

            const closingUmat = reportForm.querySelector('[name="closing_umat"]');
            const closingMasjid = reportForm.querySelector('[name="closing_masjid"]');
            const closingTotal = reportForm.querySelector('[name="closing_total"]');

            const updateClosingTotal = function() {
                closingTotal.value = ((Number(closingUmat.value) || 0) + (Number(closingMasjid.value) || 0)).toFixed(2);
            };

            closingUmat.addEventListener('input', updateClosingTotal);
            closingMasjid.addEventListener('input', updateClosingTotal);
            updateClosingTotal();
        });
    </script>
    <script>
        function editRawatib(button) {
            document.getElementById('edit-rawatib-id').value = button.dataset.id;
            document.getElementById('edit-rawatib-shalat').value = button.dataset.shalat;
            document.getElementById('edit-rawatib-hari').value = button.dataset.hari;
            document.getElementById('edit-rawatib-name').value = button.dataset.name;
            document.getElementById('edit-rawatib-preview').src = button.dataset.profile;
            document.getElementById('edit-rawatib-profile').value = '';
            openModal('editRawatib');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="file"][data-preview-id]').forEach(function(input) {
                input.addEventListener('change', function() {
                    const file = input.files[0];
                    const preview = document.getElementById(input.dataset.previewId);

                    if (file && preview && file.type.startsWith('image/')) {
                        preview.src = URL.createObjectURL(file);
                    }
                });
            });
        });
    </script>
</body>

</html>
