<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Masjid Profile &amp; Running Text</h2>
                    <p class="card-subtitle">Update the masjid logo, name, address, and running text shown on the display</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Back to Overview</span>
            </a>
        </div>
        <div class="card-body">
            <?php foreach ($identities as $data): ?>
                <?php $logoUrl = $uploadUrl('identity', $data['logo'] ?? null); ?>
                <form action="control/identity" method="post" enctype="multipart/form-data">
                    <div style="text-align:center; margin-bottom:22px;">
                        <label for="logo" style="cursor:pointer; display:inline-block;">
                            <img id="logo-preview" src="<?= $e($logoUrl) ?>"
                                class="preview-circle" style="width:120px; height:120px;" alt="Masjid Logo">
                            <span class="badge badge-gold">Click image to change logo</span>
                        </label>
                        <input id="logo" type="file" name="logo" accept="image/*" style="display:none;" data-preview-id="logo-preview">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Masjid Name</label>
                            <input type="text" name="tittle" class="form-control" value="<?= $e($data['tittle'] ?? '') ?>" placeholder="Enter masjid name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address / Location</label>
                            <input type="text" name="name" class="form-control" value="<?= $e($data['name'] ?? '') ?>" placeholder="Enter full address">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Running Text</label>
                        <input type="text" name="marquee" class="form-control" value="<?= $e($data['marquee'] ?? '') ?>" placeholder="Enter scrolling announcement text">
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                        <a href="" class="btn btn-outline">Cancel</a>
                        <button type="submit" value="save" name="submit" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            <span>Save Profile</span>
                        </button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>
