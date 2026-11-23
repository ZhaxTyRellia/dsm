<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use DateTimeInterface;

final class DateTimeService
{
    private const DAYS = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
    ];

    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function formatCurrentDateHtml(?DateTimeInterface $now = null): string
    {
        $now = $now ?? new DateTimeImmutable();
        $dayIndex = (int) $now->format('w');
        $monthIndex = (int) $now->format('n');

        $hari = self::DAYS[$dayIndex] ?? 'Tidak diketahui';
        $bulan = self::MONTHS[$monthIndex] ?? 'Tidak diketahui';
        $tanggal = $now->format('d') . ' ' . $bulan . ' ' . $now->format('Y');

        return '<b>' . $hari . '</b>, ' . $tanggal;
    }

    /**
     * @return string[]
     */
    public function upcomingFridays(int $weeks = 8, ?DateTimeInterface $now = null): array
    {
        $base = $now !== null ? DateTimeImmutable::createFromInterface($now) : new DateTimeImmutable();
        $jumatTerdekat = ((int) $base->format('N') === 5)
            ? $base
            : $base->modify('next friday');

        $result = [];
        for ($i = 0; $i < $weeks; $i++) {
            $date = $i === 0 ? $jumatTerdekat : $jumatTerdekat->modify("+{$i} weeks");
            $tgl = $date->format('j');
            $namaBulan = self::MONTHS[(int) $date->format('n')] ?? '';
            $tahun = $date->format('Y');
            $result[] = "Jumat, {$tgl} {$namaBulan} {$tahun}";
        }

        return $result;
    }
}
