<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Latar Belakang Display</h2>
                    <p class="card-subtitle">Pilih tema latar belakang untuk tampilan signage portrait</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">Kembali ke Dashboard</a>
        </div>
        <div class="card-body">
            <?php
            $backgrounds = ['bg1.png', 'bg2.jpg', 'bg3.jpeg', 'bg4.jpg', 'bg5.jpg', 'bg6.jpg'];
            $currentBg = (string) ($currentBackground ?? '');
            ?>
            <form action="control/background" method="POST">
                <div class="bg-gallery">
                    <?php foreach ($backgrounds as $index => $bg): ?>
                        <label class="bg-option">
                            <input type="radio" name="background" value="<?= $e($bg) ?>" <?= $currentBg === $bg ? 'checked' : '' ?> required>
                            <img src="assets/img/background/<?= $e($bg) ?>" class="bg-thumb" alt="Tema <?= $index + 1 ?>">
                            <div class="bg-caption">Tema #<?= $index + 1 ?> (<?= $e($bg) ?>)</div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <a href="" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Latar Belakang</button>
                </div>
            </form>
        </div>
    </div>
</div>
