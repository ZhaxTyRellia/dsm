<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Banner Khotbah Jum'at</h2>
                    <p class="card-subtitle">Atur khatib, tanggal Jum'at, dan foto untuk tampilan overlay khotbah</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
        <div class="card-body">
            <form action="control/khotbah-day" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 1) ?>">

                <div style="text-align:center; margin-bottom:22px;">
                    <label for="avatar" style="cursor:pointer; display:inline-block;">
                        <img id="avatar-preview" src="<?= $e($uploadUrl('khotbah', $p['avatar'] ?? null)) ?>"
                            class="preview-circle" style="width:132px; height:132px;" alt="Avatar Khatib">
                        <span class="badge badge-gold">Klik foto untuk ganti avatar khatib</span>
                    </label>
                    <input id="avatar" type="file" name="avatar" accept="image/png, image/jpeg" style="display:none;" data-preview-id="avatar-preview">
                </div>

                <div class="form-group">
                    <label class="form-label">Khatib Saat Ini</label>
                    <div class="form-control" style="background:var(--emerald-50); font-weight:800; color:var(--emerald-900);">
                        <?= $e($p['name'] ?? '') ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Pilih Khatib (Total: <?= (int) ($totalRows ?? 0) ?>)</label>
                        <select name="speaker" class="form-control">
                            <option value="<?= $e($p['name'] ?? '') ?>">-- Pertahankan / Pilih Imam Khotbah --</option>
                            <?php foreach ($speakers as $row): ?>
                                <option value="<?= $e($row['identity']) ?>">
                                    <?= $e($row['identity']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Hari dan Tanggal Ibadah (Jum'at)</label>
                        <select name="title" class="form-control">
                            <?php foreach ($fridays as $formatted): ?>
                                <option value="<?= $e($formatted) ?>" <?= (($p['title'] ?? '') === $formatted) ? 'selected' : '' ?>>
                                    <?= $e($formatted) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                    <a href="" class="btn btn-outline">Batal</a>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Perbarui Banner Khotbah</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
