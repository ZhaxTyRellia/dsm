<style>
    .video-responsive {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
    }

    .video-responsive iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    .video-responsive::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px;
        background: transparent;
        z-index: 1;
        pointer-events: none;
    }
</style>

<?php if (!empty($embedSrc)): ?>
    <div class="video-responsive">
        <iframe id="youtube-player"
            src="<?= $e($embedSrc) ?>?autoplay=1&mute=0&controls=0&playsinline=1&rel=0&modestbranding=1&enablejsapi=1"
            allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
            referrerpolicy="strict-origin-when-cross-origin">
        </iframe>
    </div>
<?php else: ?>
    <div class="video-responsive" style="display:flex;align-items:center;justify-content:center;background:#111;color:#fff;font-size:18px;">
        Live YouTube belum tersedia / URL tidak valid
    </div>
<?php endif; ?>

<script>
    var videoSource = <?= json_encode((string) ($embedSrc ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function nextVideo() {
        var player = document.getElementById("youtube-player");
        if (!player || !videoSource) return;
        player.src = videoSource + "?autoplay=1&mute=0&controls=0&playsinline=1&rel=0&modestbranding=1&enablejsapi=1";
    }
</script>
