<?php $isEmbedded = !empty($embeddedSection); ?>
<?php if (!$isEmbedded): ?><div class="control-wrap"><?php endif; ?>
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Jadwal Waktu Shalat</h2>
                    <p class="card-subtitle">Set daily and Friday prayer times for the on-screen reminder</p>
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
            <form action="control/shalat-time" method="POST">
                <div class="shalat-grid">
                    <div class="shalat-item">
                        <label class="form-label">Subuh</label>
                        <input type="time" class="form-control" name="shalat[subuh]" step="1" value="<?= $e($data['subuh'] ?? '') ?>" required>
                    </div>
                    <div class="shalat-item">
                        <label class="form-label">Dzuhur</label>
                        <input type="time" class="form-control" name="shalat[dzuhur]" step="1" value="<?= $e($data['dzuhur'] ?? '') ?>" required>
                    </div>
                    <div class="shalat-item">
                        <label class="form-label">Jum'at</label>
                        <input type="time" class="form-control" name="shalat[jumat]" step="1" value="<?= $e($data['jumat'] ?? '') ?>" required>
                    </div>
                    <div class="shalat-item">
                        <label class="form-label">Ashar</label>
                        <input type="time" class="form-control" name="shalat[ashar]" step="1" value="<?= $e($data['ashar'] ?? '') ?>" required>
                    </div>
                    <div class="shalat-item">
                        <label class="form-label">Maghrib</label>
                        <input type="time" class="form-control" name="shalat[maghrib]" step="1" value="<?= $e($data['maghrib'] ?? '') ?>" required>
                    </div>
                    <div class="shalat-item">
                        <label class="form-label">Isya</label>
                        <input type="time" class="form-control" name="shalat[isya]" step="1" value="<?= $e($data['isya'] ?? '') ?>" required>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <?php if (!$isEmbedded): ?>
                        <a href="" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Save Prayer Times</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php if (!$isEmbedded): ?></div><?php endif; ?>
