<?php
$ytMute = !empty($isPreview) ? '1' : '0';
?>
<?php if (!empty($embedSrc)): ?>
    <div class="video-responsive">
        <iframe id="youtube-player"
            src="<?= $e($embedSrc) ?>?autoplay=1&mute=<?= $ytMute ?>&controls=0&playsinline=1&rel=0&modestbranding=1&enablejsapi=1"
            allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
            referrerpolicy="strict-origin-when-cross-origin">
        </iframe>
    </div>
<?php else: ?>
    <div class="video-responsive video-empty-state">
        <div class="video-empty-inner">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <polygon points="23 7 16 12 23 17 23 7"/>
                <rect x="1" y="5" width="15" height="14" rx="2"/>
            </svg>
            <div class="video-empty-title">Video / Siaran Langsung</div>
            <div class="video-empty-sub">Belum ada tautan video yang diatur</div>
        </div>
    </div>
<?php endif; ?>

<script>
    var videoSource = <?= json_encode((string) ($embedSrc ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    var videoMute = <?= json_encode($ytMute) ?>;

    function nextVideo() {
        var player = document.getElementById("youtube-player");
        if (!player || !videoSource) return;
        player.src = videoSource + "?autoplay=1&mute=" + videoMute + "&controls=0&playsinline=1&rel=0&modestbranding=1&enablejsapi=1";
    }
</script>
