<?php
$isEmbedded = !empty($embeddedSection);
$info = $systemInfo ?? [];
$isActivated = !empty($info['isActivated']);
$deviceId = (string) ($info['deviceId'] ?? '');
$activateKey = trim((string) ($info['activateKey'] ?? ''));
$maskedKey = '';
if ($activateKey !== '') {
    $keyLen = strlen($activateKey);
    $maskedKey = $keyLen > 16
        ? substr($activateKey, 0, 8) . str_repeat('•', 12) . substr($activateKey, -6)
        : str_repeat('•', $keyLen);
}
?>
<?php if (!$isEmbedded): ?><div class="control-wrap"><?php endif; ?>
    <div class="info-grid">
        <div class="section-card">
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    </div>
                    <div>
                        <h2 class="card-title">Activation Status</h2>
                        <p class="card-subtitle">Device license and activation details for this unit</p>
                    </div>
                </div>
                <span class="audio-status-pill <?= $isActivated ? 'info-status-active' : 'info-status-inactive' ?>">
                    <?= $isActivated ? 'Activated' : 'Not Activated' ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Device ID</div>
                        <div class="info-value-wrap">
                            <span class="info-mono" id="infoDeviceId"><?= $e($deviceId !== '' ? $deviceId : 'Not Detected') ?></span>
                            <?php if ($deviceId !== ''): ?>
                                <button type="button"
                                    class="btn btn-outline btn-sm"
                                    data-copy-text="<?= $e($deviceId) ?>"
                                    data-copy-label="Device ID copied.">
                                    Copy
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Activation Key</div>
                        <div class="info-value-wrap">
                            <?php if ($activateKey !== ''): ?>
                                <span class="info-mono"
                                    id="infoActivationKeyText"
                                    data-masked="<?= $e($maskedKey) ?>"
                                    data-full="<?= $e($activateKey) ?>"
                                    data-revealed="0"><?= $e($maskedKey) ?></span>
                                <div class="info-actions-inline">
                                    <button type="button" class="btn btn-outline btn-sm" id="toggleActivationKeyBtn">Show</button>
                                    <button type="button"
                                        class="btn btn-outline btn-sm"
                                        data-copy-text="<?= $e($activateKey) ?>"
                                        data-copy-label="Activation key copied.">
                                        Copy
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="info-muted">Not set yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-badge-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    </div>
                    <div>
                        <h2 class="card-title">About &amp; Developer</h2>
                        <p class="card-subtitle">Application creator, official brand, and website</p>
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
                <div class="info-brand-banner">
                    <svg class="info-brand-emblem" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" aria-hidden="true">
                        <rect width="64" height="64" rx="16" fill="#062c24"/>
                        <rect x="3" y="3" width="58" height="58" rx="14" fill="none" stroke="#d4af37" stroke-width="2" opacity="0.7"/>
                        <path d="M34.5 11.5a6 6 0 1 0 5.7 7.9 4.7 4.7 0 1 1-5.7-7.9z" fill="#d4af37"/>
                        <path d="M32 20c-6.5 4.2-10 8.8-10 14v2h20v-2c0-5.2-3.5-9.8-10-14z" fill="#d4af37"/>
                        <rect x="14" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                        <rect x="45" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                        <rect x="19" y="36" width="26" height="14" fill="#d4af37"/>
                        <path d="M28 50v-6.5a4 4 0 0 1 8 0V50z" fill="#062c24"/>
                        <rect x="11" y="49" width="42" height="3.5" rx="1.75" fill="#d4af37"/>
                    </svg>
                    <div>
                        <div class="info-brand-name"><?= $e($info['brand'] ?? 'Gautavaris DigiSoft HUB') ?></div>
                        <div class="info-brand-tagline">Clean, stable software crafted for masjid displays</div>
                    </div>
                </div>

                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label">Application</div>
                        <div class="info-value"><?= $e($info['appName'] ?? 'Masjid Display') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Developer</div>
                        <div class="info-value"><?= $e($info['developer'] ?? 'Zhax Ty Rellia') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Brand</div>
                        <div class="info-value"><?= $e($info['brand'] ?? 'Gautavaris DigiSoft HUB') ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Website</div>
                        <div class="info-value">
                            <a href="<?= $e($info['website'] ?? 'https://gautavaris.web.id/') ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="info-link">
                                <span><?= $e($info['website'] ?? 'https://gautavaris.web.id/') ?></span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if (!$isEmbedded): ?></div><?php endif; ?>
