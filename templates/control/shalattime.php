<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Pengaturan Waktu Shalat</h2>
                    <p class="card-subtitle">Atur jadwal masuk waktu shalat harian dan Jum'at untuk modal pengingat</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">Kembali ke Dashboard</a>
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
                    <a href="" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal Shalat</button>
                </div>
            </form>
        </div>
    </div>
</div>
