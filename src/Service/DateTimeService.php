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

    private const HIJRI_MONTHS = [
        1 => 'Muharram',
        2 => 'Safar',
        3 => "Rabi'ul Awal",
        4 => "Rabi'ul Akhir",
        5 => 'Jumadil Awal',
        6 => 'Jumadil Akhir',
        7 => 'Rajab',
        8 => "Sya'ban",
        9 => 'Ramadhan',
        10 => 'Syawal',
        11 => "Dzulqa'dah",
        12 => 'Dzulhijjah',
    ];

    public function formatHijriDate(?DateTimeInterface $now = null): string
    {
        $now = $now ?? new DateTimeImmutable();
        $y = (int) $now->format('Y');
        $m = (int) $now->format('n');
        $d = (int) $now->format('j');

        $a = intdiv(14 - $m, 12);
        $y2 = $y + 4800 - $a;
        $m2 = $m + 12 * $a - 3;
        $jd = $d + intdiv(153 * $m2 + 2, 5) + 365 * $y2 + intdiv($y2, 4) - intdiv($y2, 100) + intdiv($y2, 400) - 32045;

        $l = $jd - 1948440 + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - 10631 * $n + 354;
        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
        $l = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
        $hMonth = intdiv(24 * $l, 709);
        $hDay = $l - intdiv(709 * $hMonth, 24);
        $hYear = 30 * $n + $j - 30;

        $monthName = self::HIJRI_MONTHS[$hMonth] ?? 'Muharram';

        return "{$hDay} {$monthName} {$hYear} H";
    }

    public function formatCurrentDateHtml(?DateTimeInterface $now = null): string
    {
        $now = $now ?? new DateTimeImmutable();
        $dayIndex = (int) $now->format('w');
        $monthIndex = (int) $now->format('n');

        $hari = self::DAYS[$dayIndex] ?? 'Tidak diketahui';
        $bulan = self::MONTHS[$monthIndex] ?? 'Tidak diketahui';
        $tanggal = $now->format('d') . ' ' . $bulan . ' ' . $now->format('Y');
        $hijri = $this->formatHijriDate($now);

        return '<span class="date-gregorian"><b>' . $hari . '</b>, ' . $tanggal . '</span>'
            . '<span class="date-sep">&bull;</span>'
            . '<span class="date-hijri">' . $hijri . '</span>';
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
