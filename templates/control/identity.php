<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Change Identity</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/identity.css">
</head>

<body>
    <div class="container">
        <p class="text">Your Identity Company</p><br>
        <?php foreach ($identities as $data): ?>
            <?php $logoFile = !empty($data['logo']) ? $data['logo'] : 'default.png'; ?>
            <form action="<?= $e($basePath) ?>/control/identity" method="post" enctype="multipart/form-data">
                <center>
                    <label for="logo">
                        <img id="logo-preview" src="<?= $e($basePath) ?>/assets/img/pictures/<?= $e($logoFile) ?>" class="pictures" alt="NO IMAGE">
                    </label>
                </center>
                <br />
                <div class="image-upload">
                    <input id="logo" type="file" name="logo" accept="image/*">
                </div>
                <div class="input-group">
                    <input type="text" name="tittle" value="<?= $e($data['tittle'] ?? '') ?>" placeholder="Input Your Tittle">
                </div>
                <div class="input-group">
                    <input type="text" name="name" value="<?= $e($data['name'] ?? '') ?>" placeholder="Input Your Address">
                </div>
                <div class="input-group">
                    <input type="text" name="marquee" value="<?= $e($data['marquee'] ?? '') ?>" placeholder="Input Your Marquee">
                </div>
                <div class="input-group">
                    <button type="submit" value="save" name="submit" class="btn">Update</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
    <script>
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logo-preview');
        if (logoInput && logoPreview) {
            logoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file && file.type.startsWith('image/')) {
                    logoPreview.src = URL.createObjectURL(file);
                }
            });
        }
    </script>
</body>

</html>
