(function () {
    'use strict';

    var SECTION_STORAGE_KEY = 'dsm_active_section';
    var DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    function normalizeSection(raw) {
        if (!raw) return null;
        var clean = String(raw).replace(/^#/, '').trim();
        if (clean.indexOf('sec-') === 0) {
            clean = clean.slice(4);
        }
        return clean || null;
    }

    function initSectionNavigation() {
        var sections = Array.prototype.slice.call(document.querySelectorAll('.dashboard-section[data-section]'));
        var topbarTitleEl = document.getElementById('topbarPageTitle');
        var navLinks = Array.prototype.slice.call(document.querySelectorAll('.sidebar-nav .nav-item[data-section]'));
        var sectionTriggers = Array.prototype.slice.call(document.querySelectorAll('[data-section], [data-goto-section]'));

        if (sections.length === 0) {
            sectionTriggers.forEach(function (el) {
                el.addEventListener('click', function () {
                    var target = normalizeSection(el.getAttribute('data-section') || el.getAttribute('data-goto-section'));
                    if (target) {
                        try {
                            localStorage.setItem(SECTION_STORAGE_KEY, target);
                        } catch (e) {}
                    }
                });
            });
            return;
        }

        var sectionMap = {};
        sections.forEach(function (sec) {
            var id = sec.getAttribute('data-section');
            if (id) {
                sectionMap[id] = sec;
            }
        });

        var fallbackId = sectionMap.overview ? 'overview' : sections[0].getAttribute('data-section');

        function activateSection(sectionId, syncHash) {
            var target = sectionMap[sectionId] ? sectionId : fallbackId;
            var activeSection = sectionMap[target];

            sections.forEach(function (sec) {
                sec.classList.toggle('is-active', sec.getAttribute('data-section') === target);
            });

            navLinks.forEach(function (link) {
                link.classList.toggle('active', link.getAttribute('data-section') === target);
            });

            var title = (activeSection && activeSection.getAttribute('data-section-title')) || 'Overview';
            if (topbarTitleEl) {
                topbarTitleEl.textContent = title;
            }
            document.title = title + ' | Masjid Display';

            try {
                localStorage.setItem(SECTION_STORAGE_KEY, target);
            } catch (e) {}

            if (syncHash) {
                try {
                    if (window.history && typeof window.history.replaceState === 'function') {
                        window.history.replaceState(null, '', '#' + target);
                    } else {
                        window.location.hash = target;
                    }
                } catch (e) {}
            }
        }

        var fromHash = normalizeSection(window.location.hash);
        var fromStorage = null;
        try {
            fromStorage = normalizeSection(localStorage.getItem(SECTION_STORAGE_KEY));
        } catch (e) {}

        var initialSection = (fromHash && sectionMap[fromHash])
            ? fromHash
            : ((fromStorage && sectionMap[fromStorage]) ? fromStorage : fallbackId);

        activateSection(initialSection, Boolean(fromHash && sectionMap[fromHash]));

        sectionTriggers.forEach(function (el) {
            el.addEventListener('click', function (event) {
                var target = normalizeSection(el.getAttribute('data-section') || el.getAttribute('data-goto-section'));
                if (!target || !sectionMap[target]) return;
                event.preventDefault();
                activateSection(target, true);
                document.body.classList.remove('sidebar-open');
                window.scrollTo({ top: 0, behavior: 'auto' });
            });
        });

        window.addEventListener('hashchange', function () {
            var hashId = normalizeSection(window.location.hash);
            if (hashId && sectionMap[hashId]) {
                activateSection(hashId, false);
            }
        });
    }

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function updateLiveClock() {
        var el = document.getElementById('topbarLiveClock');
        if (!el) return;
        var now = new Date();
        var text = DAYS[now.getDay()] + ', ' + pad2(now.getDate()) + ' ' + MONTHS[now.getMonth()] + ' '
            + now.getFullYear() + ' • ' + pad2(now.getHours()) + ':' + pad2(now.getMinutes()) + ':' + pad2(now.getSeconds());
        el.textContent = text;
    }

    function initSidebar() {
        var body = document.body;
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        var backdrop = document.getElementById('sidebarBackdrop');

        try {
            if (window.innerWidth > 1024 && localStorage.getItem('dsm_sidebar_collapsed') === '1') {
                body.classList.add('sidebar-collapsed');
            }
        } catch (e) {}

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    body.classList.toggle('sidebar-open');
                } else {
                    body.classList.toggle('sidebar-collapsed');
                    try {
                        localStorage.setItem('dsm_sidebar_collapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
                    } catch (e) {}
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        }

        document.querySelectorAll('.sidebar-nav a.nav-item').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    body.classList.remove('sidebar-open');
                }
            });
        });
    }

    window.openModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('in');
        }
    };

    window.closeModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('in');
        }
    };

    window.editRawatib = function (button) {
        document.getElementById('edit-rawatib-id').value = button.dataset.id || '';
        document.getElementById('edit-rawatib-shalat').value = button.dataset.shalat || '';
        document.getElementById('edit-rawatib-hari').value = button.dataset.hari || '';
        document.getElementById('edit-rawatib-name').value = button.dataset.name || '';
        document.getElementById('edit-rawatib-preview').src = button.dataset.profile || 'assets/img/default.png';
        document.getElementById('edit-rawatib-profile').value = '';
        window.openModal('editRawatib');
    };

    window.editKhotbah = function (button) {
        document.getElementById('edit-khotbah-id').value = button.dataset.id || '';
        document.getElementById('edit-khotbah-moon').value = button.dataset.moon || 'Januari';
        document.getElementById('edit-khotbah-date').value = button.dataset.date || '';
        document.getElementById('edit-khotbah-identity').value = button.dataset.identity || '';
        window.openModal('editKhotbahModal');
    };

    window.autoFillDesc = function () {
        var select = document.getElementById('keterangan');
        var hiddenDesc = document.getElementById('desc_input');
        if (!select || !hiddenDesc) return;
        hiddenDesc.value = select.value !== '' ? select.options[select.selectedIndex].text : '';
    };

    function initFilePreviews() {
        document.querySelectorAll('input[type="file"][data-preview-id]').forEach(function (input) {
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                var preview = document.getElementById(input.dataset.previewId);
                if (file && preview && file.type.startsWith('image/')) {
                    preview.src = URL.createObjectURL(file);
                }
            });
        });
    }

    function initCashCalculation() {
        var form = document.querySelector('.cash-report-form');
        if (!form) return;

        var closingUmat = form.querySelector('[name="closing_umat"]');
        var closingMasjid = form.querySelector('[name="closing_masjid"]');
        var closingTotal = form.querySelector('[name="closing_total"]');
        var formattedEl = document.getElementById('cashTotalFormatted');

        if (!closingUmat || !closingMasjid || !closingTotal) return;

        var update = function () {
            var total = (Number(closingUmat.value) || 0) + (Number(closingMasjid.value) || 0);
            closingTotal.value = total.toFixed(2);
            if (formattedEl) {
                formattedEl.textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
            }
        };

        closingUmat.addEventListener('input', update);
        closingMasjid.addEventListener('input', update);
        update();
    }

    function initAudioPreview() {
        var fileInput = document.getElementById('audio_file');
        var audio = document.getElementById('audioElement');
        var playBtn = document.getElementById('playBtn');
        var progressBar = document.getElementById('progressBar');
        var progressContainer = document.getElementById('progressContainer');
        var currentTimeEl = document.getElementById('currentTime');
        var durationEl = document.getElementById('duration');

        if (!fileInput || !audio || !playBtn) return;

        var formatTime = function (seconds) {
            if (!isFinite(seconds)) return '0:00';
            var min = Math.floor(seconds / 60);
            var sec = Math.floor(seconds % 60);
            return min + ':' + (sec < 10 ? '0' : '') + sec;
        };

        var setPlayLabel = function (text) {
            var span = playBtn.querySelector('span');
            if (span) {
                span.textContent = text;
            } else {
                playBtn.textContent = text;
            }
        };

        fileInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (file) {
                audio.src = URL.createObjectURL(file);
                audio.load();
                setPlayLabel('Play Preview');
            }
        });

        playBtn.addEventListener('click', function () {
            if (!audio.src) {
                alert('Please choose an audio file first!');
                return;
            }
            if (audio.paused) {
                audio.play();
                setPlayLabel('Stop Preview');
            } else {
                audio.pause();
                setPlayLabel('Play Preview');
            }
        });

        audio.addEventListener('loadedmetadata', function () {
            if (durationEl) durationEl.textContent = formatTime(audio.duration);
        });

        audio.addEventListener('timeupdate', function () {
            if (progressBar && audio.duration) {
                progressBar.style.width = ((audio.currentTime / audio.duration) * 100) + '%';
            }
            if (currentTimeEl) currentTimeEl.textContent = formatTime(audio.currentTime);
        });

        audio.addEventListener('ended', function () {
            setPlayLabel('Play Preview');
            if (progressBar) progressBar.style.width = '0%';
            if (currentTimeEl) currentTimeEl.textContent = '0:00';
        });

        if (progressContainer) {
            progressContainer.addEventListener('click', function (e) {
                if (!audio.duration) return;
                audio.currentTime = (e.offsetX / this.clientWidth) * audio.duration;
            });
        }
    }

    function initFlashToast() {
        var toast = document.getElementById('flashToast');
        if (!toast) return;
        setTimeout(function () {
            toast.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 350);
        }, 4200);
    }

    document.addEventListener('click', function (event) {
        if (event.target && event.target.classList && event.target.classList.contains('modal')) {
            event.target.classList.remove('in');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.body.classList.remove('sidebar-open');
            document.querySelectorAll('.modal.in').forEach(function (m) {
                m.classList.remove('in');
            });
        }
    });

    initSectionNavigation();

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        updateLiveClock();
        setInterval(updateLiveClock, 1000);
        initFilePreviews();
        initCashCalculation();
        initAudioPreview();
        initFlashToast();
    });
})();
