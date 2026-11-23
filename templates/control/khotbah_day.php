<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Change Khotbah View</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/khotbah.css">
</head>

<body>
    <div class="container">
        <p class="text">Identity Imam/Khotbah</p>
        <form action="<?= $e($basePath) ?>/control/khotbah-day" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 1) ?>">

            <center>
                <label for="avatar">
                    <img id="avatar-preview" src="<?= $e($basePath) ?>/assets/img/pictures/<?= $e(!empty($p['avatar']) ? $p['avatar'] : 'default.png') ?>" class="pictures" alt="NO IMAGE">
                </label>
            </center>
            <br />
            <div class="image-upload">
                <input id="avatar" type="file" name="avatar" accept="image/png, image/jpeg" style="display: none;">
            </div>

            <div class="input-group">
                <label>Speaker Saat Ini:</label>
                <div style="background: #e5e7eb; padding: 0.75rem; border-radius: 8px; font-weight: 600; color: #374151; text-align: center; font-size: 1.6rem;">
                    <?= $e($p['name'] ?? '') ?>
                </div>
            </div>

            <div class="input-group">
                <label>Daftar Imam (Total: <?= (int) ($totalRows ?? 0) ?>)</label>
                <select name="speaker">
                    <option value="<?= $e($p['name'] ?? '') ?>">-- Pilih Imam Khotbah --</option>
                    <?php foreach ($speakers as $row): ?>
                        <option value="<?= $e($row['identity']) ?>">
                            <?= $e($row['identity']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label>Hari dan Tanggal Ibadah (Jumat):</label>
                <select name="title">
                    <?php foreach ($fridays as $formatted): ?>
                        <option value="<?= $e($formatted) ?>" <?= (($p['title'] ?? '') === $formatted) ? 'selected' : '' ?>>
                            <?= $e($formatted) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <button type="submit" name="submit" class="btn">Update</button>
            </div>
        </form>
    </div>
    <script>
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatar-preview');

        avatarInput.addEventListener('change', function() {
            const selectedFile = this.files[0];

            if (selectedFile && selectedFile.type.startsWith('image/')) {
                avatarPreview.src = URL.createObjectURL(selectedFile);
            }
        });
    </script>
</body>

</html>
