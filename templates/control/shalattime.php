<?php
$isEmbedded = !empty($embeddedSection);
$shalatRow = $shalatTimeData ?? $data ?? [];

$prayerSlots = [
    'subuh' => 'Subuh',
    'dzuhur' => 'Dzuhur',
    'jumat' => "Jum'at",
    'ashar' => 'Ashar',
    'maghrib' => 'Maghrib',
    'isya' => 'Isya',
];

$parse24h = static function (string $raw): array {
    $parts = explode(':', trim($raw));
    $hh = max(0, min(23, (int) ($parts[0] ?? 0)));
    $mm = max(0, min(59, (int) ($parts[1] ?? 0)));
    $ss = max(0, min(59, (int) ($parts[2] ?? 0)));

    return [
        str_pad((string) $hh, 2, '0', STR_PAD_LEFT),
        str_pad((string) $mm, 2, '0', STR_PAD_LEFT),
        str_pad((string) $ss, 2, '0', STR_PAD_LEFT),
    ];
};
?>
<?php if (!$isEmbedded): ?><div class="control-wrap"><?php endif; ?>
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Jadwal Waktu Shalat</h2>
                    <p class="card-subtitle">Set daily and Friday prayer times for the on-screen reminder (24-hour format)</p>
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
                    <?php foreach ($prayerSlots as $slotKey => $slotLabel): ?>
                        <?php
                        [$hh, $mm, $ss] = $parse24h((string) ($shalatRow[$slotKey] ?? '00:00:00'));
                        $formatted24h = "{$hh}:{$mm}:{$ss}";
                        ?>
                        <div class="shalat-item">
                            <label class="form-label"><?= $e($slotLabel) ?></label>
                            <div class="time-24h-picker" data-time-slot="<?= $e($slotKey) ?>">
                                <input type="hidden" name="shalat[<?= $e($slotKey) ?>]" value="<?= $e($formatted24h) ?>" class="time-24h-value">
                                <select class="time-24h-select" data-part="hh" aria-label="<?= $e($slotLabel) ?> Hour">
                                    <?php for ($i = 0; $i < 24; $i++): ?>
                                        <?php $val = str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?>
                                        <option value="<?= $val ?>" <?= $hh === $val ? 'selected' : '' ?>><?= $val ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="time-24h-sep">:</span>
                                <select class="time-24h-select" data-part="mm" aria-label="<?= $e($slotLabel) ?> Minute">
                                    <?php for ($i = 0; $i < 60; $i++): ?>
                                        <?php $val = str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?>
                                        <option value="<?= $val ?>" <?= $mm === $val ? 'selected' : '' ?>><?= $val ?></option>
                                    <?php endfor; ?>
                                </select>
                                <span class="time-24h-sep">:</span>
                                <select class="time-24h-select" data-part="ss" aria-label="<?= $e($slotLabel) ?> Second">
                                    <?php for ($i = 0; $i < 60; $i++): ?>
                                        <?php $val = str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?>
                                        <option value="<?= $val ?>" <?= $ss === $val ? 'selected' : '' ?>><?= $val ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
