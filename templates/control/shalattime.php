<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Change Shalat Time</title>
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/shalattime.css">
</head>

<body>

    <div class="container">
        <header class="page-header">
            <h3>Update Waktu Shalat</h3>
            <p>Atur jadwal waktu ibadah harian</p>
            <div class="header-divider"></div>
        </header>
        <form action="<?= $e($basePath) ?>/control/shalat-time" method="POST">
            <div class="form-group">
                <label>Subuh</label>
                <input type="time" name="shalat[subuh]" step="1" value="<?= $e($data['subuh'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Dzuhur</label>
                <input type="time" name="shalat[dzuhur]" step="1" value="<?= $e($data['dzuhur'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Jum'at</label>
                <input type="time" name="shalat[jumat]" step="1" value="<?= $e($data['jumat'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Ashar</label>
                <input type="time" name="shalat[ashar]" step="1" value="<?= $e($data['ashar'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Maghrib</label>
                <input type="time" name="shalat[maghrib]" step="1" value="<?= $e($data['maghrib'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Isya</label>
                <input type="time" name="shalat[isya]" step="1" value="<?= $e($data['isya'] ?? '') ?>" required>
            </div>
            <button type="submit" class="btn-submit">Save</button>
        </form>
    </div>

</body>

</html>
