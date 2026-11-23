(function () {
    'use strict';

    var DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

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
