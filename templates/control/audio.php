<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Change Audio</title>
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/audio.css">
</head>

<body>
    <div class="container">
        <h3>Change Audio</h3>
        <p>Pilih file audio untuk diunggah:</p>

        <form action="<?= $e($basePath) ?>/control/audio" method="POST" enctype="multipart/form-data">

            <div class="audio-preview-card">
                <div class="audio-info">
                    <span id="currentTime">0:00</span>
                    <div class="progress-container" id="progressContainer">
                        <div class="progress-bar" id="progressBar"></div>
                    </div>
                    <span id="duration">0:00</span>
                </div>
                <audio id="audioElement"></audio>
                <button type="button" id="playBtn" class="play-btn">Play Preview</button>
            </div>

            <div class="form-group">
                <label for="audio_file">Pilih Audio (MP3/WAV):</label>
                <input type="file" name="audio_file" id="audio_file" accept="audio/*" required>
            </div>

            <button type="submit" class="btn-submit" name="submit">Save</button>
        </form>
    </div>

    <!--- Progress Bar --->
    <script>
        const fileInput = document.getElementById('audio_file');
        const audio = document.getElementById('audioElement');
        const playBtn = document.getElementById('playBtn');
        const progressBar = document.getElementById('progressBar');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                audio.src = url;
                audio.load();
                playBtn.textContent = 'Play Preview';
                playBtn.classList.remove('playing');
            }
        });

        playBtn.addEventListener('click', function() {
            if (!audio.src) {
                alert("Silakan pilih file audio terlebih dahulu!");
                return;
            }

            if (audio.paused) {
                audio.play();
                playBtn.textContent = 'Stop Preview';
                playBtn.classList.add('playing');
            } else {
                audio.pause();
                playBtn.textContent = 'Play Preview';
                playBtn.classList.remove('playing');
            }
        });

        audio.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audio.duration);
        });

        audio.addEventListener('timeupdate', () => {
            const progress = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = `${progress}%`;
            currentTimeEl.textContent = formatTime(audio.currentTime);
        });

        function formatTime(seconds) {
            const min = Math.floor(seconds / 60);
            const sec = Math.floor(seconds % 60);
            return `${min}:${sec < 10 ? '0' : ''}${sec}`;
        }

        audio.addEventListener('ended', () => {
            playBtn.textContent = 'Play Preview';
            playBtn.classList.remove('playing');
            progressBar.style.width = '0%';
            currentTimeEl.textContent = '0:00';
        });

        document.getElementById('progressContainer').addEventListener('click', function(e) {
            const width = this.clientWidth;
            const clickX = e.offsetX;
            const duration = audio.duration;
            audio.currentTime = (clickX / width) * duration;
        });
    </script>

</body>

</html>
