<?php if (empty($row)): ?>
    <tr><td colspan="4" style="text-align:center;">Belum ada laporan kas</td></tr>
<?php else: ?>
    <?php
    $formatMoney = static function (mixed $amount): string {
        return $amount === null ? '-' : 'Rp ' . number_format((float) $amount, 0, ',', '.');
    };
    $formatDate = static function (mixed $date) use ($e): string {
        return empty($date) ? '-' : $e($date);
    };
    ?>
    <tr>
        <td>Saldo Awal</td>
        <td><?= $formatDate($row['opening_date'] ?? null) ?></td>
        <td><?= $formatMoney($row['opening_umat'] ?? null) ?></td>
        <td><?= $formatMoney($row['opening_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td>Pemasukan</td>
        <td><?= $formatDate($row['period_start'] ?? null) ?></td>
        <td><?= $formatMoney($row['income_umat'] ?? null) ?></td>
        <td><?= $formatMoney($row['income_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td>Posisi kas</td>
        <td><?= $formatDate($row['position_date'] ?? null) ?></td>
        <td><?= $formatMoney($row['position_umat'] ?? null) ?></td>
        <td><?= $formatMoney($row['position_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td>Pengeluaran</td>
        <td><?= $formatDate($row['expense_date'] ?? null) ?></td>
        <td><?= $formatMoney($row['expense_umat'] ?? null) ?></td>
        <td><?= $formatMoney($row['expense_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td><strong>Saldo akhir</strong></td>
        <td><strong><?= $formatDate($row['closing_date'] ?? null) ?></strong></td>
        <td><strong><?= $formatMoney($row['closing_umat'] ?? null) ?></strong></td>
        <td><strong><?= $formatMoney($row['closing_masjid'] ?? null) ?></strong></td>
    </tr>
    <tr>
        <td colspan="4" class="kas-total-summary-cell">
            <div class="kas-total-summary">
                TOTAL KAS MASJID POSISI <?= $formatDate($row['closing_date'] ?? null) ?> ||
                <span class="kas-total-summary-value"><?= $formatMoney((float) ($row['closing_umat'] ?? 0) + (float) ($row['closing_masjid'] ?? 0)) ?></span>
            </div>
        </td>
    </tr>
<?php endif; ?>
