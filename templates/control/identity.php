<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Identitas &amp; Running Text Masjid</h2>
                    <p class="card-subtitle">Atur logo, nama masjid, alamat, dan teks berjalan di layar signage</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">Kembali ke Dashboard</a>
        </div>
        <div class="card-body">
            <?php foreach ($identities as $data): ?>
                <?php $logoFile = !empty($data['logo']) ? $data['logo'] : 'default.png'; ?>
                <form action="control/identity" method="post" enctype="multipart/form-data">
                    <div style="text-align:center; margin-bottom:22px;">
                        <label for="logo" style="cursor:pointer; display:inline-block;">
                            <img id="logo-preview" src="assets/img/pictures/<?= $e($logoFile) ?>"
                                class="preview-circle" style="width:120px; height:120px;" alt="Logo Masjid">
                            <span class="badge badge-gold">Klik gambar untuk ganti logo</span>
                        </label>
                        <input id="logo" type="file" name="logo" accept="image/*" style="display:none;" data-preview-id="logo-preview">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nama Masjid / Instansi</label>
                            <input type="text" name="tittle" class="form-control" value="<?= $e($data['tittle'] ?? '') ?>" placeholder="Masukkan nama masjid">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat / Lokasi</label>
                            <input type="text" name="name" class="form-control" value="<?= $e($data['name'] ?? '') ?>" placeholder="Masukkan alamat lengkap">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teks Berjalan (Marquee Footer)</label>
                        <input type="text" name="marquee" class="form-control" value="<?= $e($data['marquee'] ?? '') ?>" placeholder="Masukkan pengumuman teks berjalan">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                        <a href="" class="btn btn-outline">Batal</a>
                        <button type="submit" value="save" name="submit" class="btn btn-primary">Simpan Identitas</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>
