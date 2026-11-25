<?php
$isEmbedded = !empty($embeddedSection);
$themeList = $themes ?? \App\Support\DisplayThemeCatalog::all();
$currentBg = \App\Support\DisplayThemeCatalog::normalize((string) ($currentBackground ?? ''));
$currentMode = strtolower(trim((string) ($currentDisplayMode ?? 'portrait')));
if (!in_array($currentMode, ['portrait', 'landscape'], true)) {
    $currentMode = 'portrait';
}
?>
<?php if (!$isEmbedded): ?><div class="control-wrap control-wrap-wide"><?php endif; ?>
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Display Theme &amp; Orientation</h2>
                    <p class="card-subtitle">Choose a theme and screen orientation, then preview how it looks on the TV</p>
                </div>
            </div>
            <?php if (!$isEmbedded): ?>
                <a href="" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    <span>Back to Overview</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="bg-studio-layout">
                <div class="bg-studio-controls">
                    <form action="control/background" method="POST" id="displaySettingsForm">
                        <div class="form-group">
                            <span class="form-label">Screen Orientation</span>
                            <div class="orientation-picker">
                                <label class="orientation-option">
                                    <input type="radio" name="display_mode" value="portrait" <?= $currentMode === 'portrait' ? 'checked' : '' ?> required>
                                    <div class="orientation-card">
                                        <span class="orientation-icon orientation-icon-portrait" aria-hidden="true"></span>
                                        <span class="orientation-meta">
                                            <strong>Portrait</strong>
                                            <small>Vertical TV screen</small>
                                        </span>
                                    </div>
                                </label>
                                <label class="orientation-option">
                                    <input type="radio" name="display_mode" value="landscape" <?= $currentMode === 'landscape' ? 'checked' : '' ?> required>
                                    <div class="orientation-card">
                                        <span class="orientation-icon orientation-icon-landscape" aria-hidden="true"></span>
                                        <span class="orientation-meta">
                                            <strong>Landscape</strong>
                                            <small>Horizontal TV screen</small>
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <span class="form-label">Choose Theme</span>
                            <div class="bg-gallery">
                                <?php foreach ($themeList as $themeItem): ?>
                                    <?php
                                    $tFile = (string) $themeItem['file'];
                                    $tId = (string) $themeItem['id'];
                                    $tName = (string) $themeItem['name'];
                                    $tSub = (string) $themeItem['subtitle'];
                                    $tTone = (string) $themeItem['tone'];
                                    ?>
                                    <label class="bg-option">
                                        <input type="radio"
                                            name="background"
                                            value="<?= $e($tFile) ?>"
                                            data-theme-id="<?= $e($tId) ?>"
                                            data-theme-tone="<?= $e($tTone) ?>"
                                            <?= $currentBg === $tFile ? 'checked' : '' ?>
                                            required>
                                        <div class="bg-thumb-card">
                                            <img src="assets/img/background/<?= $e($tFile) ?>" class="bg-thumb" alt="<?= $e($tName) ?>">
                                            <div class="theme-card-meta">
                                                <div class="theme-card-row">
                                                    <span class="theme-card-name"><?= $e($tName) ?></span>
                                                    <span class="theme-tone-pill tone-<?= $e($tTone) ?>"><?= $e(ucfirst($tTone)) ?></span>
                                                </div>
                                                <div class="theme-card-sub"><?= $e($tSub) ?></div>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bg-studio-actions">
                            <?php if (!$isEmbedded): ?>
                                <a href="" class="btn btn-outline">Cancel</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>Save Display Settings</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-studio-preview" id="displayPreviewPanel"
                    data-base-url="display"
                    data-initial-bg="<?= $e($currentBg) ?>"
                    data-initial-mode="<?= $e($currentMode) ?>">
                    <div class="preview-toolbar">
                        <div class="preview-toolbar-left">
                            <span class="preview-live-badge">
                                <span class="live-dot"></span>
                                <span>Live Preview</span>
                            </span>
                            <span class="preview-res-badge" id="previewResolutionBadge">
                                <?= $currentMode === 'landscape' ? 'Landscape Screen' : 'Portrait Screen' ?>
                            </span>
                        </div>
                        <div class="preview-toolbar-right">
                            <div class="preview-mode-switch" role="group" aria-label="Preview Orientation">
                                <button type="button" class="preview-mode-btn <?= $currentMode === 'portrait' ? 'is-active' : '' ?>" data-preview-mode="portrait">
                                    Portrait
                                </button>
                                <button type="button" class="preview-mode-btn <?= $currentMode === 'landscape' ? 'is-active' : '' ?>" data-preview-mode="landscape">
                                    Landscape
                                </button>
                            </div>
                            <select id="previewSceneSelect" class="preview-scene-select" aria-label="Preview Page">
                                <option value="auto">Auto Rotate</option>
                                <option value="0" selected>Home: Video &amp; Kas</option>
                                <option value="1">Banner Slides</option>
                                <option value="2">Rutinitas Bulanan</option>
                                <option value="3">Khotbah Jum'at</option>
                                <option value="4">Susunan Pengurus</option>
                                <option value="5">Imam Rawatib</option>
                            </select>
                            <button type="button" class="btn btn-outline btn-sm" id="reloadPreviewBtn" title="Reload Live Preview">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                <span>Reload</span>
                            </button>
                            <a href="display?mode=<?= $e($currentMode) ?>&amp;bg=<?= $e($currentBg) ?>"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-gold btn-sm"
                                id="openFullPreviewLink"
                                title="Open Full Screen Preview">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                <span>Open Full</span>
                            </a>
                        </div>
                    </div>

                    <div class="preview-stage" id="previewStage">
                        <div class="preview-device-frame" id="previewDeviceFrame">
                            <div class="preview-viewport" id="previewViewport">
                                <iframe id="displayPreviewFrame"
                                    title="Live Masjid Display Preview"
                                    loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if (!$isEmbedded): ?></div><?php endif; ?>
