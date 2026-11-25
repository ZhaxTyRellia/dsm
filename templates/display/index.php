<!DOCTYPE HTML>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <base href="<?= $e($baseUrl) ?>">
    <title><?= $e(!empty($identity['tittle']) ? $identity['tittle'] : 'Masjid Display') ?></title>
    <meta name="description" content="Masjid Display">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="assets/css/show.css?v=5">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
</head>

<?php
$initialBodyMode = ($displayMode ?? 'auto') === 'landscape' ? 'landscape' : 'portrait';
?>
<body class="display-mode-<?= $e($initialBodyMode) ?><?= !empty($isPreview) ? ' is-preview-mode' : '' ?>"
    data-theme="<?= $e($theme['id'] ?? 'emerald-gold') ?>"
    data-tone="<?= $e($theme['tone'] ?? 'dark') ?>"
    data-configured-mode="<?= $e($displayMode ?? 'auto') ?>"
    style="background-image: url('assets/img/background/<?= $e($bgImage) ?>?v=<?= time() ?>');">

    <div id="display-stage">
    <!-- MODAL PENGINGAT WAKTU SHALAT -->
    <div id="prayerModal" class="prayer-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="prayerTimeLabel">
        <div class="prayer-modal-card">
            <img src="assets/img/frame.svg" class="corner top-left" alt="">
            <img src="assets/img/frame.svg" class="corner top-right" alt="">
            <img src="assets/img/frame.svg" class="corner bottom-left" alt="">
            <img src="assets/img/frame.svg" class="corner bottom-right" alt="">

            <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid" class="prayer-logo">
            <div class="prayer-arabic" dir="rtl" lang="ar">حَيَّ عَلَى الصَّلَاةِ</div>
            <div class="prayer-divider"></div>
            <p class="prayer-heading">Telah Tiba Waktu Shalat</p>
            <div class="prayer-name-badge" id="prayerTimeLabel">DZUHUR</div>
            <p class="prayer-subtext">Mari luruskan dan rapatkan shaf untuk melaksanakan shalat berjamaah.</p>
            <div class="prayer-remaining-pill" id="prayerRemainingTimer">00:00</div>
        </div>
    </div>

    <!-- MODAL KHOTBAH JUM'AT -->
    <div id="jumatModal" class="prayer-modal-overlay" role="dialog" aria-modal="true">
        <div class="full-screen-container">
            <img src="assets/img/frame.svg" class="corner top-left" alt="">
            <img src="assets/img/frame.svg" class="corner top-right" alt="">
            <img src="assets/img/frame.svg" class="corner bottom-left" alt="">
            <img src="assets/img/frame.svg" class="corner bottom-right" alt="">

            <div class="bismillah" dir="rtl" lang="ar">بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ</div>

            <div class="title jumat-title"><?= $e(!empty($view['title']) ? $view['title'] : "Khotbah Jum'at") ?></div>

            <div class="avatar-frame">
                <img class="jumat-avatar" src="<?= $e($uploadUrl('khotbah', $view['avatar'] ?? null)) ?>" alt="Khotib">
            </div>

            <div class="khotib-wrapper">
                <div class="khotib-label">Khotib &amp; Imam Jum'at</div>
                <div class="khotib-name jumat-name"><?= $e(!empty($view['name']) ? $view['name'] : '-') ?></div>
            </div>

            <div class="lokasi-box">
                <div class="lokasi-title jumat-lokasi-title">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    <span class="jumat-lokasi-title-text"><?= $e($profil['tittle'] ?? '') ?></span>
                </div>
                <div class="lokasi-detail jumat-lokasi-detail">
                    <?= $e($profil['name'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN SCENE VIEWPORT -->
    <main id="scene-container">

        <!-- SCENE 0: HOME (HEADER + JADWAL SHALAT + VIDEO + KAS MASJID) -->
        <section class="scene active" data-slide-key="home" data-has-content="1">
            <div class="scene1-top">
                <header class="masjid-header">
                    <div class="masjid-logo-box">
                        <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid">
                    </div>
                    <div class="masjid-title-group">
                        <h1 class="tittle-sign"><?= $e($identity['tittle'] ?? '') ?></h1>
                        <p class="name-sign"><?= $e($identity['name'] ?? '') ?></p>
                    </div>
                </header>

                <div class="date-pill" id="date-container">
                    <?= $currentDateHtml ?>
                </div>
            </div>

            <!-- LIVE PRAYER TIMES STRIP -->
            <div class="prayer-bar-wrap" aria-label="Jadwal Waktu Shalat Hari Ini">
                <?php
                $stripFormat = static function (mixed $raw): string {
                    $clean = trim((string) ($raw ?? ''));
                    if ($clean === '') {
                        return '--:--';
                    }
                    $parts = explode(':', $clean);
                    return str_pad((string) ((int) ($parts[0] ?? 0)), 2, '0', STR_PAD_LEFT)
                        . ':' . str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);
                };
                $isFridayToday = ((int) date('N')) === 5;
                $middayKey = $isFridayToday ? 'jumat' : 'dzuhur';
                $middayLabel = $isFridayToday ? "Jum'at" : 'Dzuhur';
                $stripSlots = [
                    ['slot' => 'subuh', 'label' => 'Subuh', 'time' => $stripFormat($shalatTimes['subuh'] ?? '')],
                    ['slot' => $middayKey, 'label' => $middayLabel, 'time' => $stripFormat($shalatTimes[$middayKey] ?? ($shalatTimes['dzuhur'] ?? ''))],
                    ['slot' => 'ashar', 'label' => 'Ashar', 'time' => $stripFormat($shalatTimes['ashar'] ?? '')],
                    ['slot' => 'maghrib', 'label' => 'Maghrib', 'time' => $stripFormat($shalatTimes['maghrib'] ?? '')],
                    ['slot' => 'isya', 'label' => 'Isya', 'time' => $stripFormat($shalatTimes['isya'] ?? '')],
                ];
                ?>
                <div class="prayer-strip" id="prayerStrip">
                    <?php foreach ($stripSlots as $slotItem): ?>
                        <div class="prayer-chip" data-prayer-slot="<?= $e($slotItem['slot']) ?>">
                            <span class="prayer-chip-label"><?= $e($slotItem['label']) ?></span>
                            <span class="prayer-chip-time"><?= $e($slotItem['time']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="prayer-countdown-pill" id="prayerCountdownPill">
                    <span class="countdown-dot"></span>
                    <span id="prayerCountdownText">Memuat jadwal shalat...</span>
                </div>
            </div>

            <div class="scene1-body">
                <div class="scene1-media">
                    <?php require __DIR__ . '/partials/video.php'; ?>
                </div>

                <div class="data-keuangan-wrapper">
                    <div class="table-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>LAPORAN KAS MASJID</span>
                    </div>
                    <div class="kas-table-scroll">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Keterangan</th>
                                    <th>Tanggal</th>
                                    <th>Kemaslahatan Umat</th>
                                    <th>Kemakmuran Masjid</th>
                                </tr>
                            </thead>
                            <tbody id="kas-table-body">
                                <tr>
                                    <td colspan="4" class="kas-empty">Memuat laporan kas masjid...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- SCENE 1: BANNER PENGUMUMAN -->
        <section class="scene scene-slides" data-slide-key="banner" data-has-content="0">
            <div class="scene-plus-container" id="scenePlusWrapper"></div>
            <div class="scene-empty-card scene-slides-empty" id="scenePlusEmpty">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <h2>Banner Pengumuman</h2>
                <p>Belum ada gambar banner pengumuman yang diunggah.</p>
            </div>
        </section>

        <!-- SCENE 2: JADWAL RUTINITAS BULANAN -->
        <section class="scene" data-slide-key="rutinitas" data-has-content="<?= !empty($monthlyRows) ? '1' : '0' ?>">
            <header class="slide-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid">
                </div>
                <div class="block-tittle-slide">
                    <h2 class="slide-text">Jadwal Rutinitas Bulanan</h2>
                </div>
            </header>
            <div class="scene-body">
                <?php if (empty($monthlyRows)): ?>
                    <div class="scene-empty-card">
                        <h2>Jadwal Rutinitas Bulanan</h2>
                        <p>Belum ada jadwal kegiatan rutin yang ditambahkan.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <?php $renderedIds = []; ?>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>Waktu</th>
                                    <th>Pengisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyRows as $row): ?>
                                    <tr>
                                        <?php if (!in_array($row['id_desc'], $renderedIds, true)): ?>
                                            <td rowspan="<?= (int) ($monthlyCounts[(string) $row['id_desc']] ?? 1) ?>" class="cell-group-title">
                                                <?= $e($row['desc']) ?>
                                            </td>
                                            <?php $renderedIds[] = $row['id_desc']; ?>
                                        <?php endif; ?>
                                        <td><span class="time-badge"><?= $e($row['time']) ?></span></td>
                                        <td><?= $e($row['speaker']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SCENE 3: DAFTAR KHOTBAH JUM'AT -->
        <section class="scene" data-slide-key="khotbah" data-has-content="<?= !empty($khotbahGrouped) ? '1' : '0' ?>">
            <header class="slide-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid">
                </div>
                <div class="block-tittle-slide">
                    <h2 class="slide-text">Daftar Khotbah Jum'at</h2>
                </div>
            </header>
            <div class="scene-body">
                <?php if (empty($khotbahGrouped)): ?>
                    <div class="scene-empty-card">
                        <h2>Daftar Khotbah Jum'at</h2>
                        <p>Belum ada jadwal khotbah Jum'at yang ditambahkan.</p>
                    </div>
                <?php else: ?>
                    <div class="grid-container">
                        <?php foreach ($months as $m): ?>
                            <div class="month-card">
                                <div class="month-title"><?= $e($m) ?></div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>TGL</th>
                                            <th>NAMA KHATIB / IMAM</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($khotbahGrouped[$m])): ?>
                                            <?php foreach ($khotbahGrouped[$m] as $row): ?>
                                                <tr>
                                                    <td class="td-tgl"><?= $e($row['date']) ?></td>
                                                    <td><?= $e($row['identity']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="2" class="td-empty">Belum ada jadwal</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SCENE 4: SUSUNAN PENGURUS -->
        <section class="scene" data-slide-key="pengurus" data-has-content="<?= !empty($staffRows) ? '1' : '0' ?>">
            <header class="slide-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid">
                </div>
                <div class="block-tittle-slide">
                    <h2 class="slide-text">Susunan Pengurus</h2>
                </div>
            </header>
            <div class="scene-body">
                <?php if (empty($staffRows)): ?>
                    <div class="scene-empty-card">
                        <h2>Susunan Pengurus</h2>
                        <p>Belum ada daftar susunan pengurus yang ditambahkan.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="modern-table-4">
                            <thead>
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Nama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $renderedTiers = [];
                                $currentTier = '';
                                $counter = 1;
                                foreach ($staffRows as $row):
                                    if ($row['tier'] !== $currentTier) {
                                        $counter = 1;
                                        $currentTier = (string) $row['tier'];
                                    }
                                ?>
                                    <tr>
                                        <?php if (!in_array($row['tier'], $renderedTiers, true)): ?>
                                            <td rowspan="<?= (int) ($staffCounts[(string) $row['tier']] ?? 1) ?>" class="cell-group-title">
                                                <?= $e($row['tier']) ?>
                                            </td>
                                            <?php $renderedTiers[] = $row['tier']; ?>
                                        <?php endif; ?>
                                        <td><?= $counter++ ?>. <?= $e($row['identity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- SCENE 5: IMAM RAWATIB -->
        <section class="scene" data-slide-key="rawatib" data-has-content="<?= !empty($rawatibGrouped) ? '1' : '0' ?>">
            <header class="slide-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo Masjid">
                </div>
                <div class="block-tittle-slide">
                    <h2 class="slide-text">Imam Rawatib</h2>
                </div>
            </header>
            <div class="scene-body">
                <?php if (empty($rawatibGrouped)): ?>
                    <div class="scene-empty-card">
                        <h2>Imam Rawatib</h2>
                        <p>Belum ada jadwal imam rawatib yang ditambahkan.</p>
                    </div>
                <?php else: ?>
                    <div class="jadwal-grid">
                        <?php foreach ($rawatibGrouped as $namaSholat => $daftarJadwal): ?>
                            <div class="card-sholat">
                                <div class="card-header">
                                    <?= $e($namaSholat === 'BADAL' ? 'BADAL IMAM' : $namaSholat) ?>
                                </div>
                                <table class="table-jadwal">
                                    <tbody>
                                        <?php foreach ($daftarJadwal as $item): ?>
                                            <tr>
                                                <td class="td-hari"><?= $e($item['hari']) ?></td>
                                                <td class="td-imam">
                                                    <div class="imam-wrapper">
                                                        <img src="<?= $e($uploadUrl('rawatib', $item['profil'] ?? null)) ?>"
                                                            alt="Foto Imam" class="img-avatar">
                                                        <span class="nama-imam"><?= $e($item['nama']) ?></span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- AUDIO LATAR -->
    <audio id="bgAudioPlayer"
        class="audio-player"
        loop
        preload="auto"<?= (!empty($audioEnabled) && empty($isPreview)) ? ' src="' . $e($audioPath) . '"' : '' ?>></audio>

    <!-- FOOTER RUNNING TEXT & JAM 24H -->
    <?php
    $marqueeText = trim((string) ($identity['marquee'] ?? ''));
    if ($marqueeText === '') {
        $marqueeText = 'Selamat datang di ' . (!empty($identity['tittle']) ? (string) $identity['tittle'] : 'Masjid');
    }
    ?>
    <footer class="display-footer">
        <div class="ticker-viewport">
            <div class="ticker-track">
                <span class="ticker-item"><?= $e($marqueeText) ?></span>
                <span class="ticker-sep" aria-hidden="true">&#10022;</span>
                <span class="ticker-item" aria-hidden="true"><?= $e($marqueeText) ?></span>
                <span class="ticker-sep" aria-hidden="true">&#10022;</span>
            </div>
        </div>
        <button type="button" class="footer-clock-box" id="fullscreen-trigger" onclick="toggleFullscreen()" title="Layar Penuh">
            <span class="clock" id="clock">00:00:00</span>
            <svg class="fullscreen-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
            </svg>
        </button>
    </footer>
    </div><!-- /#display-stage -->

    <!-- REFRESH TANGGAL MASEHI + HIJRIAH -->
    <script>
        function refreshDate() {
            fetch('api/time', { cache: 'no-store' })
                .then(function(response) { return response.ok ? response.text() : ''; })
                .then(function(data) {
                    if (data) {
                        document.getElementById('date-container').innerHTML = data;
                    }
                })
                .catch(function() {});
        }
        setInterval(refreshDate, 30000);
    </script>

    <!-- JAM 24 JAM -->
    <script>
        function checkTime(i) {
            return i < 10 ? "0" + i : String(i);
        }

        function showTime() {
            var today = new Date();
            var el = document.getElementById('clock');
            if (!el) return;
            el.textContent = checkTime(today.getHours()) + ":" + checkTime(today.getMinutes()) + ":" + checkTime(today.getSeconds());
        }
        showTime();
        setInterval(showTime, 500);
    </script>

    <!-- REFRESH LAPORAN KAS MASJID -->
    <script>
        function refreshKasTable() {
            fetch('api/money?v=' + new Date().getTime(), { cache: 'no-store' })
                .then(function(response) {
                    if (!response.ok) throw new Error('Unable to load cash report');
                    return response.text();
                })
                .then(function(data) {
                    var tbody = document.getElementById('kas-table-body');
                    if (tbody) tbody.innerHTML = data;
                })
                .catch(function(error) { console.error('Cash report refresh error:', error); });
        }

        document.addEventListener("DOMContentLoaded", function() {
            refreshKasTable();
            setInterval(refreshKasTable, 4000);
        });
    </script>

    <!-- FULLSCREEN -->
    <script>
        function toggleFullscreen() {
            var elem = document.documentElement;
            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) elem.requestFullscreen();
                else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
            }
        }
    </script>

    <!-- JADWAL SHALAT BAR, HITUNG MUNDUR, PENGINGAT SHALAT & JUM'AT -->
    <script>
        let shalatSchedule = <?= json_encode($shalatTimes ?: null) ?>;
        let prayerEndTime = 0;
        const prayerDurations = {
            subuh: 25,
            dzuhur: 30,
            ashar: 30,
            maghrib: 35,
            isya: 30,
            jumat: 95
        };

        function muteYouTubeVideo() {
            const iframe = document.getElementById("youtube-player");
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage('{"event":"command","func":"mute","args":""}', '*');
            }
        }

        function unmuteYouTubeVideo() {
            const iframe = document.getElementById("youtube-player");
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage('{"event":"command","func":"unMute","args":""}', '*');
            }
        }

        function parseSeconds(raw) {
            if (!raw || typeof raw !== 'string') return null;
            const parts = raw.trim().split(':');
            if (parts.length < 2) return null;
            const h = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            const s = parseInt(parts[2] || '0', 10);
            if (isNaN(h) || isNaN(m) || isNaN(s)) return null;
            return h * 3600 + m * 60 + s;
        }

        function formatHhMm(raw) {
            if (!raw || typeof raw !== 'string') return '--:--';
            const parts = raw.trim().split(':');
            if (parts.length < 2) return '--:--';
            return String(parts[0]).padStart(2, '0') + ':' + String(parts[1]).padStart(2, '0');
        }

        function formatCountdown(diffSeconds) {
            const sec = Math.max(0, Math.floor(diffSeconds));
            const h = Math.floor(sec / 3600);
            const m = Math.floor((sec % 3600) / 60);
            const s = sec % 60;
            return checkTime(h) + ':' + checkTime(m) + ':' + checkTime(s);
        }

        function updatePrayerStripUI() {
            const now = new Date();
            const isFriday = now.getDay() === 5;
            const currentTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
            const strip = document.getElementById('prayerStrip');
            const countdownEl = document.getElementById('prayerCountdownText');

            if (!shalatSchedule || !strip) return;

            const midKey = isFriday ? 'jumat' : 'dzuhur';
            const midLabel = isFriday ? "Jum'at" : 'Dzuhur';
            const ordered = [
                { key: 'subuh', label: 'Subuh' },
                { key: midKey, label: midLabel },
                { key: 'ashar', label: 'Ashar' },
                { key: 'maghrib', label: 'Maghrib' },
                { key: 'isya', label: 'Isya' }
            ];

            const chips = strip.querySelectorAll('.prayer-chip');
            let nextPrayer = null;
            let minDiff = Infinity;

            ordered.forEach(function(item, idx) {
                const chip = chips[idx];
                if (!chip) return;
                chip.setAttribute('data-prayer-slot', item.key);
                const labelEl = chip.querySelector('.prayer-chip-label');
                const timeEl = chip.querySelector('.prayer-chip-time');
                if (labelEl) labelEl.textContent = item.label;
                if (timeEl) timeEl.textContent = formatHhMm(shalatSchedule[item.key]);
                chip.classList.remove('is-next', 'is-active-prayer');

                const startSec = parseSeconds(shalatSchedule[item.key]);
                if (startSec !== null) {
                    const dur = (prayerDurations[item.key] || 20) * 60;
                    if (currentTime >= startSec && currentTime < startSec + dur) {
                        chip.classList.add('is-active-prayer');
                    }
                    let diff = startSec - currentTime;
                    if (diff < 0) diff += 86400;
                    if (diff < minDiff) {
                        minDiff = diff;
                        nextPrayer = { idx: idx, label: item.label, diff: diff };
                    }
                }
            });

            if (prayerEndTime > currentTime) {
                if (countdownEl) {
                    countdownEl.textContent = 'Sedang berlangsung waktu shalat';
                }
            } else if (nextPrayer) {
                if (chips[nextPrayer.idx]) {
                    chips[nextPrayer.idx].classList.add('is-next');
                }
                if (countdownEl) {
                    countdownEl.textContent = 'Menuju ' + nextPrayer.label + ' • ' + formatCountdown(nextPrayer.diff);
                }
            }
        }

        function checkPrayerTime() {
            const now = new Date();
            const day = now.getDay();
            const currentTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();

            const modalReguler = document.getElementById("prayerModal");
            const modalJumat = document.getElementById("jumatModal");
            const label = document.getElementById("prayerTimeLabel");
            const remainEl = document.getElementById("prayerRemainingTimer");

            if (prayerEndTime !== 0 && currentTime < prayerEndTime && remainEl) {
                remainEl.textContent = 'Sisa waktu ' + formatCountdown(prayerEndTime - currentTime);
            }

            if (currentTime >= prayerEndTime && prayerEndTime !== 0) {
                if (modalReguler) modalReguler.style.display = 'none';
                if (modalJumat) modalJumat.style.display = 'none';

                unmuteYouTubeVideo();
                prayerEndTime = 0;
                if (typeof window.syncBgAudioPlayback === 'function') {
                    window.syncBgAudioPlayback();
                }
            }

            updatePrayerStripUI();

            if (prayerEndTime > 0 || !shalatSchedule) return;

            for (let key in shalatSchedule) {
                if (key === 'id') continue;
                if (day === 5 && key === 'dzuhur') continue;
                if (day !== 5 && key === 'jumat') continue;
                if (!shalatSchedule[key]) continue;

                const startTime = parseSeconds(shalatSchedule[key]);
                if (startTime === null) continue;
                const duration = (prayerDurations[key] || 2) * 60;
                const endTime = startTime + duration;

                if (currentTime >= startTime && currentTime < endTime) {
                    prayerEndTime = endTime;
                    muteYouTubeVideo();
                    if (typeof window.syncBgAudioPlayback === 'function') {
                        window.syncBgAudioPlayback();
                    }
                    if (remainEl) {
                        remainEl.textContent = 'Sisa waktu ' + formatCountdown(endTime - currentTime);
                    }

                    if (key === 'jumat') {
                        if (modalJumat) modalJumat.style.display = 'flex';
                    } else {
                        if (label) label.textContent = key.toUpperCase();
                        if (modalReguler) modalReguler.style.display = 'flex';
                    }
                    break;
                }
            }
        }

        function fetchShalatSchedule() {
            fetch('api/shalat-time', { cache: 'no-store' })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data) {
                        shalatSchedule = data;
                        updatePrayerStripUI();
                    }
                })
                .catch(function(error) { console.error('Unable to load prayer times:', error); });
        }

        document.addEventListener("DOMContentLoaded", function() {
            checkPrayerTime();
            setInterval(checkPrayerTime, 1000);
            setInterval(fetchShalatSchedule, 5000);
        });
    </script>

    <!-- AUTO ORIENTATION, UNIVERSAL RESOLUTION SCALING, ROTASI HALAMAN & LIVE PREVIEW -->
    <script>
        (function() {
            const themeMap = <?= json_encode(
                array_column($allowedThemes ?? [], null, 'file'),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) ?>;
            const isPreviewMode = <?= !empty($isPreview) ? 'true' : 'false' ?>;
            const initialSceneParam = <?= $initialScene !== null ? (int) $initialScene : 'null' ?>;
            let configuredMode = <?= json_encode((string) ($displayMode ?? 'auto')) ?>;
            let slideConfig = <?= json_encode($slideConfig ?? \App\Support\DisplaySlideConfig::defaults(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            let manualPreviewScene = initialSceneParam;
            let scenes = [];
            let currentScene = 0;
            let sceneTimer = null;

            function resolveEffectiveMode() {
                if (configuredMode === 'landscape' || configuredMode === 'portrait') {
                    return configuredMode;
                }
                return window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
            }

            function applyDisplayLayout() {
                const mode = resolveEffectiveMode();
                const isLandscape = mode === 'landscape';
                document.body.classList.toggle('display-mode-landscape', isLandscape);
                document.body.classList.toggle('display-mode-portrait', !isLandscape);

                const stage = document.getElementById('display-stage');
                if (!stage) return;

                const vw = Math.max(320, window.innerWidth || document.documentElement.clientWidth || (isLandscape ? 1920 : 1080));
                const vh = Math.max(240, window.innerHeight || document.documentElement.clientHeight || (isLandscape ? 1080 : 1920));
                const baseW = isLandscape ? 1920 : 1080;
                const baseH = isLandscape ? 1080 : 1920;
                const scale = Math.min(vw / baseW, vh / baseH);

                if (isFinite(scale) && scale > 0) {
                    stage.style.width = (vw / scale) + 'px';
                    stage.style.height = (vh / scale) + 'px';
                    stage.style.transform = 'scale(' + scale + ')';
                }
            }

            function isSceneEnabled(sc, idx) {
                const key = sc.getAttribute('data-slide-key') || 'home';
                const cfg = slideConfig && slideConfig[key] ? slideConfig[key] : null;
                if (cfg && cfg.enabled === false) {
                    return false;
                }
                return idx === 0 || sc.getAttribute('data-has-content') === '1';
            }

            function getSlideDurationMs(sc) {
                const key = sc ? (sc.getAttribute('data-slide-key') || 'home') : 'home';
                const cfg = slideConfig && slideConfig[key] ? slideConfig[key] : null;
                const sec = cfg && typeof cfg.duration === 'number' ? cfg.duration : 60;
                return Math.max(5, Math.min(3600, sec)) * 1000;
            }

            function getEligibleIndices() {
                const indices = [];
                scenes.forEach(function(sc, idx) {
                    if (isSceneEnabled(sc, idx)) {
                        indices.push(idx);
                    }
                });
                return indices.length ? indices : [0];
            }

            function activateSceneIndex(idx) {
                scenes.forEach(function(sc, i) {
                    sc.classList.toggle('active', i === idx);
                });
                currentScene = idx;
            }

            function stopSceneAuto() {
                if (sceneTimer) {
                    clearTimeout(sceneTimer);
                    sceneTimer = null;
                }
            }

            function scheduleNextRotation() {
                stopSceneAuto();
                if (!scenes.length || manualPreviewScene !== null) return;

                const currentEl = scenes[currentScene] || scenes[0];
                const delayMs = getSlideDurationMs(currentEl);

                sceneTimer = setTimeout(function() {
                    const eligible = getEligibleIndices();
                    if (eligible.length <= 1) {
                        if (currentScene !== eligible[0]) {
                            activateSceneIndex(eligible[0]);
                        }
                    } else {
                        const pos = eligible.indexOf(currentScene);
                        const nextIdx = pos === -1 ? eligible[0] : eligible[(pos + 1) % eligible.length];
                        activateSceneIndex(nextIdx);
                    }
                    scheduleNextRotation();
                }, delayMs);
            }

            function startSceneAuto() {
                manualPreviewScene = null;
                const eligible = getEligibleIndices();
                if (eligible.indexOf(currentScene) === -1) {
                    activateSceneIndex(eligible[0]);
                }
                scheduleNextRotation();
            }

            window.dsmSetScene = function(sceneVal) {
                if (!scenes.length) return;
                if (sceneVal === 'auto' || sceneVal === null || sceneVal === undefined || sceneVal === '') {
                    startSceneAuto();
                    return;
                }
                const idx = parseInt(sceneVal, 10);
                if (isNaN(idx) || idx < 0 || idx >= scenes.length) return;
                manualPreviewScene = idx;
                stopSceneAuto();
                activateSceneIndex(idx);
            };

            function pollDisplayConfig() {
                if (isPreviewMode) return;
                fetch('api/display-config', { cache: 'no-store' })
                    .then(function(resp) { return resp.ok ? resp.json() : null; })
                    .then(function(cfg) {
                        if (!cfg) return;
                        if (cfg.mode && cfg.mode !== configuredMode) {
                            configuredMode = cfg.mode;
                            applyDisplayLayout();
                        }
                        if (cfg.background && Object.prototype.hasOwnProperty.call(themeMap, cfg.background)) {
                            const meta = themeMap[cfg.background];
                            const targetImg = "url('assets/img/background/" + meta.file + "')";
                            if (document.body.getAttribute('data-theme') !== meta.id) {
                                document.body.style.backgroundImage = targetImg;
                                document.body.setAttribute('data-theme', meta.id);
                                document.body.setAttribute('data-tone', meta.tone);
                            }
                        }
                        if (cfg.slides && typeof cfg.slides === 'object') {
                            const prevJson = JSON.stringify(slideConfig);
                            const nextJson = JSON.stringify(cfg.slides);
                            if (prevJson !== nextJson) {
                                slideConfig = cfg.slides;
                                if (manualPreviewScene === null) {
                                    startSceneAuto();
                                }
                            }
                        }
                    })
                    .catch(function() {});
            }

            applyDisplayLayout();
            window.addEventListener('resize', applyDisplayLayout);
            window.addEventListener('orientationchange', applyDisplayLayout);

            document.addEventListener("DOMContentLoaded", function() {
                applyDisplayLayout();
                scenes = Array.prototype.slice.call(document.querySelectorAll('#scene-container > .scene'));
                if (initialSceneParam !== null) {
                    window.dsmSetScene(initialSceneParam);
                } else {
                    startSceneAuto();
                }
                if (!isPreviewMode) {
                    setInterval(pollDisplayConfig, 10000);
                }
            });

            window.addEventListener('message', function(event) {
                if (event.origin !== window.location.origin) return;
                const data = event.data;
                if (!data || typeof data !== 'object') return;

                if (data.type === 'dsm:preview-bg' && Object.prototype.hasOwnProperty.call(themeMap, data.bg)) {
                    const meta = themeMap[data.bg];
                    document.body.style.backgroundImage = "url('assets/img/background/" + meta.file + "')";
                    document.body.setAttribute('data-theme', meta.id);
                    document.body.setAttribute('data-tone', meta.tone);
                } else if (data.type === 'dsm:preview-mode') {
                    configuredMode = (data.mode === 'landscape' || data.mode === 'portrait') ? data.mode : 'auto';
                    applyDisplayLayout();
                } else if (data.type === 'dsm:preview-scene') {
                    window.dsmSetScene(data.scene);
                }
            });
        })();
    </script>

    <!-- KHOTBAH JUM'AT POLLING -->
    <script>
        function refreshJumatData() {
            fetch('api/khotbah', { cache: 'no-store' })
                .then(function(response) {
                    if (!response.ok) throw new Error('Unable to load Friday sermon data');
                    return response.json();
                })
                .then(function(data) {
                    document.querySelectorAll('.jumat-title').forEach(function(el) {
                        el.textContent = (data.title && data.title.trim() !== '') ? data.title : "Khotbah Jum'at";
                    });
                    document.querySelectorAll('.jumat-name').forEach(function(el) {
                        el.textContent = (data.name && data.name.trim() !== '') ? data.name : '-';
                    });
                    document.querySelectorAll('.jumat-lokasi-title-text').forEach(function(el) {
                        el.textContent = data.tittle || '';
                    });
                    document.querySelectorAll('.jumat-lokasi-detail').forEach(function(el) {
                        el.textContent = data.profil_name || '';
                    });

                    document.querySelectorAll('.jumat-avatar').forEach(function(img) {
                        if (data.avatar_url) {
                            img.src = data.avatar_url;
                        } else {
                            var avatarFile = (data.avatar && data.avatar.trim() !== '') ? data.avatar : 'default.svg';
                            img.src = (avatarFile.toLowerCase() === 'default.png' || avatarFile.toLowerCase() === 'default.svg')
                                ? 'assets/img/default.svg'
                                : 'uploads/khotbah/' + avatarFile;
                        }
                    });
                })
                .catch(function(error) { console.error('Friday sermon refresh error:', error); });
        }

        document.addEventListener("DOMContentLoaded", function() {
            refreshJumatData();
            setInterval(refreshJumatData, 5000);
        });
    </script>

    <!-- BANNER SLIDES AUTO UPDATE & AUTO-SKIP -->
    <script>
        let currentScenePlusImages = [];
        let currentPlusIndex = 0;
        let scenePlusInterval = null;

        function fetchScenePlusData() {
            fetch('api/scene-plus', { cache: 'no-store' })
                .then(function(response) {
                    if (!response.ok) throw new Error('Unable to load announcement slides');
                    return response.json();
                })
                .then(function(data) {
                    const newImages = data.image_urls || data.images || [];
                    if (JSON.stringify(newImages) !== JSON.stringify(currentScenePlusImages)) {
                        currentScenePlusImages = newImages;
                        renderScenePlusImages(newImages);
                    }
                })
                .catch(function(error) { console.error('Announcement slides refresh error:', error); });
        }

        function renderScenePlusImages(images) {
            const wrapper = document.getElementById('scenePlusWrapper');
            const emptyCard = document.getElementById('scenePlusEmpty');
            const slidesScene = wrapper ? wrapper.closest('.scene') : null;
            if (!wrapper) return;

            if (scenePlusInterval) clearInterval(scenePlusInterval);
            wrapper.innerHTML = '';
            currentPlusIndex = 0;

            if (slidesScene) {
                slidesScene.setAttribute('data-has-content', images.length > 0 ? '1' : '0');
            }
            if (emptyCard) {
                emptyCard.style.display = images.length > 0 ? 'none' : 'flex';
            }

            if (images.length === 0) return;

            const cacheBuster = new Date().getTime();
            images.forEach(function(imgPath, index) {
                const img = document.createElement('img');
                img.className = 'scene-plus-img' + (index === 0 ? ' active' : '');
                if (imgPath.indexOf('/') !== -1) {
                    img.src = imgPath + '?v=' + cacheBuster;
                } else if (imgPath.toLowerCase() === 'default.png' || imgPath.toLowerCase() === 'default.svg') {
                    img.src = 'assets/img/default.svg?v=' + cacheBuster;
                } else {
                    img.src = 'uploads/banner/' + imgPath + '?v=' + cacheBuster;
                }
                img.alt = 'Banner ' + (index + 1);
                wrapper.appendChild(img);
            });

            const elements = wrapper.querySelectorAll('.scene-plus-img');
            if (elements.length > 1) {
                scenePlusInterval = setInterval(function() {
                    elements[currentPlusIndex].classList.remove('active');
                    currentPlusIndex = (currentPlusIndex + 1) % elements.length;
                    elements[currentPlusIndex].classList.add('active');
                }, 6000);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            fetchScenePlusData();
            setInterval(fetchScenePlusData, 6000);
        });
    </script>

    <!-- NETWORK MONITOR -->
    <script>
        window.addEventListener('online', function() {
            setTimeout(function() {
                location.reload();
            }, 3000);
        });
    </script>

    <?php if (empty($isPreview)): ?>
    <!-- BACKGROUND AUDIO CONTROLLER -->
    <script>
        (function() {
            const player = document.getElementById('bgAudioPlayer');
            if (!player) return;

            let bgAudioEnabled = <?= !empty($audioEnabled) ? 'true' : 'false' ?>;
            let bgAudioSrc = <?= json_encode((string) ($audioPath ?? 'uploads/audio/audio.mp3'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

            window.syncBgAudioPlayback = function() {
                if (!bgAudioEnabled || !bgAudioSrc || prayerEndTime > 0) {
                    if (!player.paused) {
                        player.pause();
                    }
                    return;
                }
                if (player.getAttribute('src') !== bgAudioSrc) {
                    player.setAttribute('src', bgAudioSrc);
                    player.load();
                }
                const playPromise = player.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(function() {});
                }
            };

            function pollAudioConfig() {
                fetch('api/audio', { cache: 'no-store' })
                    .then(function(r) { return r.ok ? r.json() : null; })
                    .then(function(data) {
                        if (!data) return;
                        bgAudioEnabled = Boolean(data.enabled);
                        if (data.src) {
                            bgAudioSrc = data.src;
                        }
                        window.syncBgAudioPlayback();
                    })
                    .catch(function() {});
            }

            document.addEventListener('DOMContentLoaded', function() {
                window.syncBgAudioPlayback();
                setInterval(pollAudioConfig, 5000);
            });
            document.addEventListener('click', function() {
                window.syncBgAudioPlayback();
            }, { once: true });
        })();
    </script>
    <?php endif; ?>
</body>

</html>
