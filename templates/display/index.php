<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <base href="<?= $e($baseUrl) ?>">
    <title>Masjid Display</title>
    <meta name="description" content="Masjid Display">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="assets/css/show.css?v=3">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
</head>

<body class="display-mode-<?= $e($displayMode ?? 'portrait') ?><?= !empty($isPreview) ? ' is-preview-mode' : '' ?>"
    data-theme="<?= $e($theme['id'] ?? 'emerald-gold') ?>"
    data-tone="<?= $e($theme['tone'] ?? 'dark') ?>"
    style="background-image: url('assets/img/background/<?= $e($bgImage) ?>?v=<?= time() ?>');">
    <!--- MODAL SHALAT TIME & JUMAT --->
    <div id="prayerModal" class="prayer-modal-overlay">
        <div class="prayer-modal-content">
            <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo" class="prayer-logo">
            <h2 id="prayerName" style="font-size: 80px; margin: 10px 0; font-family: 'Times New Roman', serif;">حيا الشلاء</h2>
            <div class="divider"></div>
            <p style="font-size: 24px; margin-top: 10px;">Telah tiba waktu Shalat <span id="prayerTimeLabel">...</span></p>
            <p style="font-size: 16px; color: white; margin-top: 5px;">
                Mari segerakan shalat berjamaah.
            </p>
        </div>
    </div>
    <div id="jumatModal" class="prayer-modal-overlay">
        <div class="full-screen-container">
            <img src="assets/img/frame.svg" class="corner top-left">
            <img src="assets/img/frame.svg" class="corner top-right">
            <img src="assets/img/frame.svg" class="corner bottom-left">
            <img src="assets/img/frame.svg" class="corner bottom-right">

            <div class="bismillah">بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ</div>

            <div class="title jumat-title"><?= $e($view['title'] ?? '') ?></div>

            <div class="avatar-frame">
                <img class="jumat-avatar" src="<?= $e($uploadUrl('khotbah', $view['avatar'] ?? null)) ?>" alt="Khotib">
            </div>

            <div class="khotib-wrapper">
                <div class="khotib-label">Khotib :</div>
                <div class="khotib-name jumat-name"><?= $e($view['name'] ?? '') ?></div>
            </div>

            <div class="lokasi-box">
                <div class="lokasi-title jumat-lokasi-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
                    <span class="jumat-lokasi-title-text"><?= $e($profil['tittle'] ?? '') ?></span>
                </div>
                <div class="lokasi-detail jumat-lokasi-detail">
                    <?= $e($profil['name'] ?? '') ?>
                </div>
            </div>
        </div>
    </div>

    <!--- SCENE --->
    <div id="scene-container">

        <!--- SCENE 1 --->
        <div class="scene active">
            <div class="scene1-top">
                <!--- HEADER --->
                <div class="container-header">
                    <div class="logo-header">
                        <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo">
                    </div>
                    <div class="block-tittle">
                        <br class="portrait-only-br"><br class="portrait-only-br"><br class="portrait-only-br">
                        <p class="tittle-sign">
                            <?= $e($identity['tittle'] ?? '') ?>
                        </p>
                        <p class="name-sign">
                            <?= $e($identity['name'] ?? '') ?>
                        </p>
                    </div>
                </div>

                <!--- DATE --->
                <div class="date" id="date-container">
                    <?= $currentDateHtml ?>
                </div>
            </div>

            <script>
                function refreshDate() {
                    fetch('api/time')
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('date-container').innerHTML = data;
                        });
                }

                setInterval(refreshDate, 10000);
            </script>
            <br class="portrait-only-br">

            <div class="scene1-body">
                <!--- MEDIA --->
                <div class="scene1-media">
                    <?php require __DIR__ . '/partials/video.php'; ?>
                </div>

                <!--- DATA KEUANGAN --->
                <div class="data-keuangan-wrapper">
                    <div class="table-title">LAPORAN KAS MASJID</div>
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
                                <td colspan="4" style="text-align:center;">Memuat laporan kas masjid...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!--- ANNOUNCEMENT SLIDES --->
        <div class="scene">
            <style>
                .scene-plus-container {
                    width: 100%;
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                    z-index: 9999;
                }

                .scene-plus-img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 1.5s ease-in-out;
                }

                .scene-plus-img.active {
                    opacity: 1;
                    visibility: visible;
                }
            </style>
            <div class="scene-plus-container" id="scenePlusWrapper">
            </div>
        </div>

        <!--- SCENE 2 --->
        <div class="scene">
            <!--- HEADER --->
            <div class="container-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo" style="width: 160px; height: auto;">
                </div>
                <div class="block-tittle-slide">
                    <div class="slide-text">Jadwal Rutinitas Bulanan</div>
                </div>
            </div>
            <br>
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
                                    <td rowspan="<?= (int) ($monthlyCounts[(string) $row['id_desc']] ?? 1) ?>" style="vertical-align: middle; text-align: center; font-weight: bold;">
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
        </div>

        <!--- SCENE 3 --->
        <div class="scene">
            <!--- HEADER --->
            <div class="container-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo" style="width: 160px; height: auto;">
                </div>
                <div class="block-tittle-slide">
                    <div class="slide-text">Daftar Khotbah Jum'at</div>
                </div>
            </div>
            <br>
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
                                <?php if (isset($khotbahGrouped[$m])): ?>
                                    <?php foreach ($khotbahGrouped[$m] as $row): ?>
                                        <tr>
                                            <td style="text-align: center;"><?= $e($row['date']) ?></td>
                                            <td><?= $e($row['identity']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center;">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!--- SCENE 4 --->
        <div class="scene">
            <!--- HEADER --->
            <div class="container-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo" style="width: 160px; height: auto;">
                </div>
                <div class="block-tittle-slide">
                    <div class="slide-text">Susunan Pengurus</div>
                </div>
            </div>
            <br>
            <div class="table-container">
                <table class="modern-table-4">
                    <thead>
                        <tr>
                            <th style="padding-left:50px;">Jabatan</th>
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
                                    <td rowspan="<?= (int) ($staffCounts[(string) $row['tier']] ?? 1) ?>" style="padding-left:50px; vertical-align: middle; font-weight: bold;">
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
        </div>

        <!--- SCENE 5 --->
        <div class="scene">
            <!--- HEADER --->
            <div class="container-header">
                <div class="logo-header-slide">
                    <img src="<?= $e($uploadUrl('identity', $identity['logo'] ?? null)) ?>" alt="Logo" style="width: 160px; height: auto;">
                </div>
                <div class="block-tittle-slide">
                    <div class="slide-text">Imam Rawatib</div>
                </div>
            </div>
            <br>

            <section class="signage-container">
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
                                            <td class="td-hari">
                                                <?= $e($item['hari']) ?>
                                            </td>
                                            <td class="td-imam">
                                                <div class="imam-wrapper">
                                                    <img src="<?= $e($uploadUrl('rawatib', $item['profil'] ?? null)) ?>"
                                                        alt="Imam Photo" class="img-avatar">
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
            </section>
        </div>
    </div>

    <!--- FOOTER --->
    <audio id="bgAudioPlayer"
        class="audio-player"
        loop
        preload="auto"<?= (!empty($audioEnabled) && empty($isPreview)) ? ' src="' . $e($audioPath) . '"' : '' ?>></audio>
    <div class="marquee-wrapper">
        <marquee behavior="scroll" direction="left" scrollamount="10" onmouseover="this.stop();"
            onmouseleave="this.start();">
            <?= $e($identity['marquee'] ?? '') ?>
        </marquee>
        <div class="right-foot" id="fullscreen-trigger" onclick="toggleFullscreen()">
            <div class="clock" id="clock"></div>
        </div>
    </div>

    <!--- Slideshow --->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const slides = document.querySelectorAll('#sliderWrapper img.DS');
            const limit = <?= (int) $limit ?>;

            if (limit > 1 && slides.length > 0) {
                let currentIndex = 0;
                slides[0].classList.add('active');

                setInterval(() => {
                    slides[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex + 1) % slides.length;
                    slides[currentIndex].classList.add('active');
                }, 10000);
            }
        });
    </script>
    <!--- Clock --->
    <script>
        function showTime() {
            var a_p = "";
            var today = new Date();
            var curr_hour = today.getHours();
            var curr_minute = today.getMinutes();
            var curr_second = today.getSeconds();
            if (curr_hour == 0) {
                curr_hour = 24;
            }
            if (curr_hour > 24) {
                curr_hour = curr_hour - 24;
            }
            curr_hour = checkTime(curr_hour);
            curr_minute = checkTime(curr_minute);
            curr_second = checkTime(curr_second);
            document.getElementById('clock').innerHTML = curr_hour + ":" + curr_minute + ":" + curr_second + " " + a_p;
        }

        function checkTime(i) {
            if (i < 10) {
                i = "0" + i;
            }
            return i;
        }
        setInterval(showTime, 500);
    </script>
    <!--- Cash Report Refresh --->
    <script>
        function refreshKasTable() {
            fetch('api/money?v=' + new Date().getTime(), {
                    cache: 'no-store'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Unable to load cash report');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('kas-table-body').innerHTML = data;
                })
                .catch(error => console.error('Cash report refresh error:', error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            refreshKasTable();
            setInterval(refreshKasTable, 2000);
        });
    </script>
    <!--- Fullscreen --->
    <script>
        function toggleFullscreen() {
            let elem = document.documentElement;

            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        }
    </script>
    <!--- PRAYER & FRIDAY REMINDER + YOUTUBE SOUND CONTROL --->
    <script>
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

        function checkPrayerTime() {
            const now = new Date();
            const day = now.getDay();
            const currentTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();

            const modalReguler = document.getElementById("prayerModal");
            const modalJumat = document.getElementById("jumatModal");
            const label = document.getElementById("prayerTimeLabel");

            if (currentTime >= prayerEndTime && prayerEndTime !== 0) {
                if (modalReguler) modalReguler.style.display = 'none';
                if (modalJumat) modalJumat.style.display = 'none';

                unmuteYouTubeVideo();
                prayerEndTime = 0;
                if (typeof window.syncBgAudioPlayback === 'function') {
                    window.syncBgAudioPlayback();
                }
            }

            if (prayerEndTime > 0 || typeof shalatSchedule === 'undefined' || !shalatSchedule) return;

            for (let key in shalatSchedule) {
                if (key === 'id') continue;

                if (day === 5 && key === 'dzuhur') continue;
                if (day !== 5 && key === 'jumat') continue;

                if (!shalatSchedule[key]) continue;

                const [h, m, s] = shalatSchedule[key].split(':');
                const startTime = parseInt(h) * 3600 + parseInt(m) * 60 + parseInt(s);
                const duration = (prayerDurations[key] || 2) * 60;
                const endTime = startTime + duration;

                if (currentTime >= startTime && currentTime < endTime) {
                    prayerEndTime = endTime;
                    muteYouTubeVideo();
                    if (typeof window.syncBgAudioPlayback === 'function') {
                        window.syncBgAudioPlayback();
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

        setInterval(checkPrayerTime, 1000);
    </script>
    <!--- Fade Content & Live Preview Messaging --->
    <script>
        (function() {
            const themeMap = <?= json_encode(
                array_column($allowedThemes ?? [], null, 'file'),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) ?>;
            const initialSceneParam = <?= $initialScene !== null ? (int) $initialScene : 'null' ?>;
            const sceneIntervalMs = 59000;
            let scenes = [];
            let currentScene = 0;
            let sceneTimer = null;

            function stopSceneAuto() {
                if (sceneTimer) {
                    clearInterval(sceneTimer);
                    sceneTimer = null;
                }
            }

            function startSceneAuto() {
                stopSceneAuto();
                if (!scenes.length) return;
                sceneTimer = setInterval(function() {
                    scenes[currentScene].classList.remove('active');
                    currentScene = (currentScene + 1) % scenes.length;
                    scenes[currentScene].classList.add('active');
                }, sceneIntervalMs);
            }

            window.dsmSetScene = function(sceneVal) {
                if (!scenes.length) return;
                if (sceneVal === 'auto' || sceneVal === null || sceneVal === undefined || sceneVal === '') {
                    startSceneAuto();
                    return;
                }
                const idx = parseInt(sceneVal, 10);
                if (isNaN(idx) || idx < 0 || idx >= scenes.length) return;
                stopSceneAuto();
                scenes.forEach(function(sc, i) {
                    sc.classList.toggle('active', i === idx);
                });
                currentScene = idx;
            };

            document.addEventListener("DOMContentLoaded", function() {
                scenes = Array.prototype.slice.call(document.querySelectorAll('.scene'));
                if (initialSceneParam !== null) {
                    window.dsmSetScene(initialSceneParam);
                } else {
                    startSceneAuto();
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
                    const isLandscape = data.mode === 'landscape';
                    document.body.classList.toggle('display-mode-landscape', isLandscape);
                    document.body.classList.toggle('display-mode-portrait', !isLandscape);
                } else if (data.type === 'dsm:preview-scene') {
                    window.dsmSetScene(data.scene);
                }
            });
        })();
    </script>
    <!--- Shalat Time --->
    <script>
        let shalatSchedule = <?= json_encode($shalatTimes ?: null) ?>;

        function fetchShalatSchedule() {
            fetch('api/shalat-time')
                .then(response => response.json())
                .then(data => {
                    shalatSchedule = data;
                })
                .catch(error => console.error('Unable to load prayer times:', error));
        }

        setInterval(fetchShalatSchedule, 3000);
    </script>
    <!--- Khotbah Jum'at --->
    <script>
        function refreshJumatData() {
            fetch('api/khotbah')
                .then(response => {
                    if (!response.ok) throw new Error('Unable to load Friday sermon data');
                    return response.json();
                })
                .then(data => {
                    document.querySelectorAll('.jumat-title').forEach(el => el.textContent = data.title);
                    document.querySelectorAll('.jumat-name').forEach(el => el.textContent = data.name);
                    document.querySelectorAll('.jumat-lokasi-title-text').forEach(el => el.textContent = data.tittle);
                    document.querySelectorAll('.jumat-lokasi-detail').forEach(el => el.textContent = data.profil_name);

                    document.querySelectorAll('.jumat-avatar').forEach(img => {
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
                .catch(error => console.error('Friday sermon refresh error:', error));
        }

        document.addEventListener("DOMContentLoaded", function() {
            refreshJumatData();
            setInterval(refreshJumatData, 2500);
        });
    </script>
    <!--- Announcement Slideshow & Auto Update --->
    <script>
        let currentScenePlusImages = [];
        let currentPlusIndex = 0;
        let scenePlusInterval = null;

        function fetchScenePlusData() {
            fetch('api/scene-plus')
                .then(response => {
                    if (!response.ok) throw new Error('Unable to load announcement slides');
                    return response.json();
                })
                .then(data => {
                    const newImages = data.image_urls || data.images || [];

                    if (JSON.stringify(newImages) !== JSON.stringify(currentScenePlusImages)) {
                        currentScenePlusImages = newImages;
                        renderScenePlusImages(newImages);
                    }
                })
                .catch(error => console.error('Announcement slides refresh error:', error));
        }

        function renderScenePlusImages(images) {
            const wrapper = document.getElementById('scenePlusWrapper');
            if (!wrapper) return;

            if (scenePlusInterval) clearInterval(scenePlusInterval);
            wrapper.innerHTML = '';
            currentPlusIndex = 0;

            if (images.length === 0) return;

            const cacheBuster = new Date().getTime();

            images.forEach((imgPath, index) => {
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
                scenePlusInterval = setInterval(() => {
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
    <!--- Network Connection Monitor --->
    <script>
        window.addEventListener('offline', function(e) {
            console.warn("Connection lost. Showing the last loaded screen.");
        });

        window.addEventListener('online', function(e) {
            console.log("Connection restored. Refreshing display...");
            setTimeout(function() {
                location.reload();
            }, 3000);
        });
    </script>
    <?php if (empty($isPreview)): ?>
    <!--- Background Music Controller --->
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
