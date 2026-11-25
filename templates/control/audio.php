<?php
$isEmbedded = !empty($embeddedSection);
$isAudioOn = !empty($audioEnabled);
$savedAudioPath = (string) ($currentAudioPath ?? 'uploads/audio/audio.mp3');
?>
<?php if (!$isEmbedded): ?><div class="control-wrap"><?php endif; ?>
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Background Music</h2>
                    <p class="card-subtitle">Turn background audio on or off and choose the audio played on the display screen</p>
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
            <form action="control/audio" method="POST" enctype="multipart/form-data" id="audioSettingsForm">
                <div class="audio-toggle-card">
                    <div class="audio-toggle-meta">
                        <div class="audio-toggle-title">Background Music on Display</div>
                        <div class="audio-toggle-desc">
                            Play soft background audio (such as murottal or instrumental) on the masjid screen. Automatically pauses during prayer time.
                        </div>
                    </div>
                    <div class="audio-toggle-controls">
                        <span id="audioStatusPill" class="audio-status-pill <?= $isAudioOn ? 'status-pill-on' : 'status-pill-off' ?>">
                            <?= $isAudioOn ? 'Active' : 'Off' ?>
                        </span>
                        <input type="hidden" name="audio_enabled" value="0">
                        <label class="toggle-switch" title="Turn Background Music On or Off">
                            <input type="checkbox"
                                id="audioEnabledToggle"
                                name="audio_enabled"
                                value="1"
                                <?= $isAudioOn ? 'checked' : '' ?>
                                aria-label="Enable Background Music">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="audio-preview-card">
                    <div class="audio-info">
                        <span id="currentTime">0:00</span>
                        <div class="progress-container" id="progressContainer">
                            <div class="progress-bar" id="progressBar"></div>
                        </div>
                        <span id="duration">0:00</span>
                    </div>
                    <audio id="audioElement" src="<?= $e($savedAudioPath) ?>" preload="metadata"></audio>
                    <button type="button" id="playBtn" class="btn btn-gold">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Play Preview</span>
                    </button>
                </div>

                <div class="form-group">
                    <label for="audio_file" class="form-label">Change Audio Track (Optional)</label>
                    <input type="file" name="audio_file" id="audio_file" class="form-control" accept="audio/*">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <?php if (!$isEmbedded): ?>
                        <a href="" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary" name="submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Save Audio Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php if (!$isEmbedded): ?></div><?php endif; ?>
