<div class="control-wrap">
    <div class="section-card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-badge-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                </div>
                <div>
                    <h2 class="card-title">Audio Latar Display</h2>
                    <p class="card-subtitle">Unggah dan pratinjau file audio MP3/WAV untuk pemutaran latar</p>
                </div>
            </div>
            <a href="" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
        <div class="card-body">
            <form action="control/audio" method="POST" enctype="multipart/form-data">
                <div class="audio-preview-card">
                    <div class="audio-info">
                        <span id="currentTime">0:00</span>
                        <div class="progress-container" id="progressContainer">
                            <div class="progress-bar" id="progressBar"></div>
                        </div>
                        <span id="duration">0:00</span>
                    </div>
                    <audio id="audioElement"></audio>
                    <button type="button" id="playBtn" class="btn btn-gold">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Play Preview</span>
                    </button>
                </div>

                <div class="form-group">
                    <label for="audio_file" class="form-label">Pilih File Audio (MP3 / WAV)</label>
                    <input type="file" name="audio_file" id="audio_file" class="form-control" accept="audio/*" required>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <a href="" class="btn btn-outline">Batal</a>
                    <button type="submit" class="btn btn-primary" name="submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Simpan Audio</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
