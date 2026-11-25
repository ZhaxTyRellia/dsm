<?php if (empty($row)): ?>
    <tr><td colspan="4" class="kas-empty">Belum ada laporan kas masjid</td></tr>
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
        <td class="kas-label">Saldo Awal</td>
        <td class="kas-date"><?= $formatDate($row['opening_date'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['opening_umat'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['opening_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td class="kas-label">Pemasukan</td>
        <td class="kas-date"><?= $formatDate($row['period_start'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['income_umat'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['income_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td class="kas-label">Posisi Kas</td>
        <td class="kas-date"><?= $formatDate($row['position_date'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['position_umat'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['position_masjid'] ?? null) ?></td>
    </tr>
    <tr>
        <td class="kas-label">Pengeluaran</td>
        <td class="kas-date"><?= $formatDate($row['expense_date'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['expense_umat'] ?? null) ?></td>
        <td class="kas-amount"><?= $formatMoney($row['expense_masjid'] ?? null) ?></td>
    </tr>
    <tr class="kas-row-closing">
        <td class="kas-label"><strong>Saldo Akhir</strong></td>
        <td class="kas-date"><strong><?= $formatDate($row['closing_date'] ?? null) ?></strong></td>
        <td class="kas-amount"><strong><?= $formatMoney($row['closing_umat'] ?? null) ?></strong></td>
        <td class="kas-amount"><strong><?= $formatMoney($row['closing_masjid'] ?? null) ?></strong></td>
    </tr>
    <tr>
        <td colspan="4" class="kas-total-summary-cell">
            <div class="kas-total-summary">
                <span class="kas-total-label">TOTAL KAS MASJID (<?= $formatDate($row['closing_date'] ?? null) ?>)</span>
                <span class="kas-total-summary-value"><?= $formatMoney((float) ($row['closing_umat'] ?? 0) + (float) ($row['closing_masjid'] ?? 0)) ?></span>
            </div>
        </td>
    </tr>
<?php endif; ?>
