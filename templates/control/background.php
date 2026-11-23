<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Change Background</title>
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/background.css">
</head>

<body>
    <?php
    $backgrounds = ['bg1.png', 'bg2.jpg', 'bg3.jpeg', 'bg4.jpg', 'bg5.jpg', 'bg6.jpg'];
    $currentBg = (string) ($currentBackground ?? '');
    ?>
    <div class="container">
        <h3>Change Background</h3>
        <p>Pilih Background yang ingin ditampilkan:</p>

        <form action="<?= $e($basePath) ?>/control/background" method="POST">
            <div class="grid-container">
                <?php foreach ($backgrounds as $bg): ?>
                    <label>
                        <input type="radio" name="background" value="<?= $e($bg) ?>" <?= $currentBg === $bg ? 'checked' : '' ?> required>
                        <img src="<?= $e($basePath) ?>/assets/img/background/<?= $e($bg) ?>" class="bg-item" alt="<?= $e($bg) ?>">
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn-submit">Save</button>
        </form>
    </div>

</body>

</html>
