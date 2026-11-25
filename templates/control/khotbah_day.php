<?php $isEmbedded = !empty($embeddedSection); ?>
<?php if (!$isEmbedded): ?><div class="control-wrap"><?php endif; ?>
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Banner Khotbah Jum'at</h2>
                    <p class="card-subtitle">Choose the Friday speaker, date, and photo for the full-screen sermon banner</p>
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
            <form action="control/khotbah-day" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 1) ?>">

                <div style="text-align:center; margin-bottom:22px;">
                    <label for="avatar" style="cursor:pointer; display:inline-block;">
                        <img id="avatar-preview" src="<?= $e($uploadUrl('khotbah', $p['avatar'] ?? null)) ?>"
                            class="preview-circle" style="width:132px; height:132px;" alt="Speaker Photo">
                        <span class="badge badge-gold">Click photo to change speaker photo</span>
                    </label>
                    <input id="avatar" type="file" name="avatar" accept="image/png, image/jpeg" style="display:none;" data-preview-id="avatar-preview">
                </div>

                <div class="form-group">
                    <label class="form-label">Current Khotib</label>
                    <div class="form-control" style="background:var(--emerald-50); font-weight:800; color:var(--emerald-900);">
                        <?= $e($p['name'] ?? '') ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Select Khotib (Total: <?= (int) ($totalRows ?? 0) ?>)</label>
                        <select name="speaker" class="form-control">
                            <option value="<?= $e($p['name'] ?? '') ?>">-- Keep Current / Choose Khotib --</option>
                            <?php foreach ($speakers as $row): ?>
                                <option value="<?= $e($row['identity']) ?>">
                                    <?= $e($row['identity']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Friday Date</label>
                        <select name="title" class="form-control">
                            <?php foreach ($fridays as $formatted): ?>
                                <option value="<?= $e($formatted) ?>" <?= (($p['title'] ?? '') === $formatted) ? 'selected' : '' ?>>
                                    <?= $e($formatted) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; margin-top:8px;">
                    <?php if (!$isEmbedded): ?>
                        <a href="" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-gold" data-test-modal-type="jumat" data-test-duration="10">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Test Banner (10s)</span>
                    </button>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Save Khotbah Banner</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php if (!$isEmbedded): ?></div><?php endif; ?>
