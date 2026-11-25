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
        var sectionTriggers = Array.prototype.slice.call(
            document.querySelectorAll('.sidebar-brand[data-section], .sidebar-nav .nav-item[data-section], [data-goto-section]')
        );

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

            if (target === 'background' && typeof window.refreshDisplayPreviewLayout === 'function') {
                requestAnimationFrame(function () {
                    window.refreshDisplayPreviewLayout(true);
                });
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
        document.getElementById('edit-rawatib-preview').src = button.dataset.profile || 'assets/img/default.svg';
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
        var toggleInput = document.getElementById('audioEnabledToggle');
        var statusPill = document.getElementById('audioStatusPill');

        if (toggleInput) {
            toggleInput.addEventListener('change', function () {
                var wantEnabled = this.checked ? '1' : '0';
                if (statusPill) {
                    statusPill.textContent = this.checked ? 'Active' : 'Off';
                    statusPill.classList.toggle('status-pill-on', this.checked);
                    statusPill.classList.toggle('status-pill-off', !this.checked);
                }

                var body = new URLSearchParams();
                body.append('audio_enabled', wantEnabled);

                fetch('control/audio/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: body.toString()
                })
                    .then(function (resp) {
                        if (!resp.ok) throw new Error('Unable to update background music status.');
                        return resp.json();
                    })
                    .then(function (data) {
                        if (!data || !data.success) {
                            throw new Error((data && data.message) || 'Unable to update background music status.');
                        }
                        window.showToast('success', data.message);
                    })
                    .catch(function (err) {
                        toggleInput.checked = wantEnabled !== '1';
                        if (statusPill) {
                            statusPill.textContent = toggleInput.checked ? 'Active' : 'Off';
                            statusPill.classList.toggle('status-pill-on', toggleInput.checked);
                            statusPill.classList.toggle('status-pill-off', !toggleInput.checked);
                        }
                        window.showToast('error', err.message || 'Unable to update background music status.');
                    });
            });
        }

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
                window.showToast('error', 'Please choose an audio file first!');
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

    function getToastContainer() {
        var container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.id = 'toastContainer';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    function bindToastLifecycle(toast) {
        if (!toast || toast.dataset.toastBound === '1') return;
        toast.dataset.toastBound = '1';
        var progress = toast.querySelector('.toast-progress');
        if (progress) {
            progress.style.width = '100%';
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    progress.style.width = '0%';
                });
            });
        }
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 320);
        }, 4500);
    }

    window.showToast = function (type, message) {
        var toastType = type === 'error' ? 'error' : 'success';
        var container = getToastContainer();
        var toast = document.createElement('div');
        toast.className = 'toast-banner toast-' + toastType + ' toast-entering';
        toast.setAttribute('role', 'status');

        var iconSvg = toastType === 'error'
            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';

        var iconEl = document.createElement('div');
        iconEl.className = 'toast-icon';
        iconEl.innerHTML = iconSvg;

        var bodyEl = document.createElement('div');
        bodyEl.className = 'toast-body';
        var titleEl = document.createElement('div');
        titleEl.className = 'toast-title';
        titleEl.textContent = toastType === 'error' ? 'Error' : 'Success';
        var msgEl = document.createElement('div');
        msgEl.className = 'toast-message';
        msgEl.textContent = String(message || '');
        bodyEl.appendChild(titleEl);
        bodyEl.appendChild(msgEl);

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Close notification');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        });

        var progressEl = document.createElement('div');
        progressEl.className = 'toast-progress';

        toast.appendChild(iconEl);
        toast.appendChild(bodyEl);
        toast.appendChild(closeBtn);
        toast.appendChild(progressEl);
        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.remove('toast-entering');
        });
        bindToastLifecycle(toast);
    };

    function initFlashToast() {
        document.querySelectorAll('#toastContainer .toast-banner, #flashToast').forEach(function (toast) {
            bindToastLifecycle(toast);
        });
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

    function initDisplayPreview() {
        var panel = document.getElementById('displayPreviewPanel');
        var stage = document.getElementById('previewStage');
        var frameBox = document.getElementById('previewDeviceFrame');
        var viewport = document.getElementById('previewViewport');
        var iframe = document.getElementById('displayPreviewFrame');
        if (!panel || !stage || !frameBox || !viewport || !iframe) return;

        var resBadge = document.getElementById('previewResolutionBadge');
        var sceneSelect = document.getElementById('previewSceneSelect');
        var reloadBtn = document.getElementById('reloadPreviewBtn');
        var openFullLink = document.getElementById('openFullPreviewLink');
        var modeBtns = Array.prototype.slice.call(panel.querySelectorAll('[data-preview-mode]'));
        var bgRadios = Array.prototype.slice.call(document.querySelectorAll('#displaySettingsForm input[name="background"]'));
        var modeRadios = Array.prototype.slice.call(document.querySelectorAll('#displaySettingsForm input[name="display_mode"]'));

        var baseUrl = panel.getAttribute('data-base-url') || 'display';
        var currentBg = panel.getAttribute('data-initial-bg') || 'emerald-gold.svg';
        var currentMode = panel.getAttribute('data-initial-mode') === 'landscape' ? 'landscape' : 'portrait';
        var currentScene = sceneSelect ? sceneSelect.value : '0';
        var iframeLoadedOnce = false;

        function buildUrl(isPreview) {
            var params = ['mode=' + encodeURIComponent(currentMode), 'bg=' + encodeURIComponent(currentBg)];
            if (isPreview) {
                params.unshift('preview=1');
                if (currentScene !== 'auto' && currentScene !== '') {
                    params.push('scene=' + encodeURIComponent(currentScene));
                }
            }
            return baseUrl + '?' + params.join('&');
        }

        function postPreviewMessage(payload) {
            if (!iframe.contentWindow) return;
            try {
                iframe.contentWindow.postMessage(payload, window.location.origin);
            } catch (e) {}
        }

        function updateUiState() {
            modeBtns.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-preview-mode') === currentMode);
            });
            modeRadios.forEach(function (radio) {
                radio.checked = radio.value === currentMode;
            });
            if (resBadge) {
                resBadge.textContent = currentMode === 'landscape'
                    ? 'Landscape Screen'
                    : 'Portrait Screen';
            }
            if (openFullLink) {
                openFullLink.href = buildUrl(false);
            }
        }

        function isPanelVisible() {
            return panel.offsetParent !== null && stage.clientWidth > 0;
        }

        function ensureIframeLoaded(forceReload) {
            if (!isPanelVisible()) return;
            if (!iframeLoadedOnce || forceReload) {
                iframeLoadedOnce = true;
                iframe.src = buildUrl(true);
            }
        }

        function rescaleStage(ensureLoad) {
            if (!isPanelVisible()) return;
            if (ensureLoad) {
                ensureIframeLoaded(false);
            }

            var virtualW = currentMode === 'landscape' ? 1920 : 1080;
            var virtualH = currentMode === 'landscape' ? 1080 : 1920;

            var availW = Math.max(220, stage.clientWidth - 60);
            var maxH = currentMode === 'landscape' ? 460 : 620;
            var scale = Math.min(availW / virtualW, maxH / virtualH);
            if (!isFinite(scale) || scale <= 0) {
                scale = 0.25;
            }

            var scaledW = Math.round(virtualW * scale);
            var scaledH = Math.round(virtualH * scale);

            viewport.style.width = scaledW + 'px';
            viewport.style.height = scaledH + 'px';
            iframe.style.width = virtualW + 'px';
            iframe.style.height = virtualH + 'px';
            iframe.style.transform = 'scale(' + scale + ')';
            frameBox.style.width = (scaledW + 24) + 'px';
        }

        window.refreshDisplayPreviewLayout = function (ensureLoad) {
            rescaleStage(Boolean(ensureLoad));
        };

        bgRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!this.checked) return;
                currentBg = this.value;
                updateUiState();
                postPreviewMessage({ type: 'dsm:preview-bg', bg: currentBg });
            });
        });

        modeRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!this.checked) return;
                currentMode = this.value === 'landscape' ? 'landscape' : 'portrait';
                updateUiState();
                rescaleStage(true);
                postPreviewMessage({ type: 'dsm:preview-mode', mode: currentMode });
            });
        });

        modeBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var chosen = this.getAttribute('data-preview-mode') === 'landscape' ? 'landscape' : 'portrait';
                if (chosen === currentMode) return;
                currentMode = chosen;
                updateUiState();
                rescaleStage(true);
                postPreviewMessage({ type: 'dsm:preview-mode', mode: currentMode });
            });
        });

        if (sceneSelect) {
            sceneSelect.addEventListener('change', function () {
                currentScene = this.value;
                postPreviewMessage({ type: 'dsm:preview-scene', scene: currentScene });
            });
        }

        if (reloadBtn) {
            reloadBtn.addEventListener('click', function () {
                ensureIframeLoaded(true);
            });
        }

        iframe.addEventListener('load', function () {
            postPreviewMessage({ type: 'dsm:preview-bg', bg: currentBg });
            postPreviewMessage({ type: 'dsm:preview-mode', mode: currentMode });
            postPreviewMessage({ type: 'dsm:preview-scene', scene: currentScene });
        });

        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(function () {
                rescaleStage(true);
            });
            ro.observe(stage);
        }
        window.addEventListener('resize', function () {
            rescaleStage(false);
        });

        updateUiState();
        rescaleStage(true);
    }

    function initInformationControls() {
        document.querySelectorAll('[data-copy-text]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var val = this.getAttribute('data-copy-text') || '';
                var msg = this.getAttribute('data-copy-label') || 'Copied.';
                if (!val || !navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                    return;
                }
                navigator.clipboard.writeText(val).then(function () {
                    window.showToast('success', msg);
                }).catch(function () {
                    window.showToast('error', 'Unable to copy to clipboard.');
                });
            });
        });

        var toggleBtn = document.getElementById('toggleActivationKeyBtn');
        var keyText = document.getElementById('infoActivationKeyText');
        if (toggleBtn && keyText) {
            toggleBtn.addEventListener('click', function () {
                var isRevealed = keyText.getAttribute('data-revealed') === '1';
                keyText.textContent = isRevealed
                    ? (keyText.getAttribute('data-masked') || '')
                    : (keyText.getAttribute('data-full') || '');
                keyText.setAttribute('data-revealed', isRevealed ? '0' : '1');
                this.textContent = isRevealed ? 'Show' : 'Hide';
            });
        }
    }

    function initTime24hPickers() {
        document.querySelectorAll('.time-24h-picker').forEach(function (picker) {
            var hiddenInput = picker.querySelector('.time-24h-value');
            var hhSel = picker.querySelector('[data-part="hh"]');
            var mmSel = picker.querySelector('[data-part="mm"]');
            var ssSel = picker.querySelector('[data-part="ss"]');
            if (!hiddenInput || !hhSel || !mmSel || !ssSel) return;

            var sync = function () {
                hiddenInput.value = hhSel.value + ':' + mmSel.value + ':' + ssSel.value;
            };

            hhSel.addEventListener('change', sync);
            mmSel.addEventListener('change', sync);
            ssSel.addEventListener('change', sync);
            sync();
        });
    }

    initSectionNavigation();

    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        updateLiveClock();
        setInterval(updateLiveClock, 1000);
        initFilePreviews();
        initCashCalculation();
        initAudioPreview();
        initFlashToast();
        initDisplayPreview();
        initInformationControls();
        initTime24hPickers();
    });
})();
