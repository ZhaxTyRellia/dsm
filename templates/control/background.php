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
            <a href="" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
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
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Simpan Latar Belakang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
