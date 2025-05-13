<?php

namespace App\Helpers;

use \Carbon\Carbon;

final class DateUtil
{
    const NW_PUBLISHER_DATE = 'Y-m-d';
    const BR_DATE = 'd/m/Y';
    const BR_DATE_TIME = 'd/m/Y H:i:s';
    const STANDARD_DATE = 'Y-m-d';
    const STANDARD_DATE_TIME = 'Y-m-d H:i:s';
    const STANDARD_DATE_TIME_TIMEZONE = 'Y-m-d H:i:s T';


    public static function convertDate(
        string $date,
        string $inFormat,
        string $outFormat,
    ): string {
        return Carbon::createFromFormat($inFormat, $date)->format($outFormat);
    }

    public static function getBrMonthShort(string $ymdDate): ?string
    {
        $monthNbr = Carbon::createFromFormat(self::STANDARD_DATE, $ymdDate)->format('n');
        $brMonths = [
            1 => 'Jan',
            2 => 'Fev',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Out',
            11 => 'Nov',
            12 => 'Dez',
        ];

        return $brMonths[$monthNbr] ?? null;
    }

    public static function getDateDay(string $ymdDate): ?string
    {
        return Carbon::createFromFormat(self::STANDARD_DATE, $ymdDate)->format('j');
    }

    public static function getDateYear(string $ymdDate): ?string
    {
        return Carbon::createFromFormat(self::STANDARD_DATE, $ymdDate)->format('Y');
    }
}