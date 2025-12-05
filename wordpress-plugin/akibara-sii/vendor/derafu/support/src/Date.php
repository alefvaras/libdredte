<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Support;

use Carbon\Carbon;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * Date and time manipulation utilities.
 *
 * This class extends Carbon's functionality with additional features for working
 * with dates, including working days calculations, period handling, and Spanish
 * date formatting.
 */
final class Date
{
    /**
     * Max year for periods.
     *
     * @var int
     */
    private const YEAR_MIN = 2000;

    /**
     * Min year for periods.
     *
     * @var int
     */
    private const YEAR_MAX = 2100;

    /**
     * Spanish day names.
     *
     * @var array<int,string>
     */
    private const DAYS_SPANISH = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * Spanish month names.
     *
     * @var array<int,string>
     */
    private const MONTHS_SPANISH = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * Formats a date in Spanish format with optional day name.
     *
     * @param Carbon|string $date The date to format.
     * @param bool $includeDay Whether to include the day name.
     * @return string Formatted date string.
     */
    public static function formatSpanish(
        Carbon|string $date,
        bool $includeDay = true
    ): string {
        $date = self::ensureCarbon($date);

        $format = $includeDay ? self::DAYS_SPANISH[$date->dayOfWeek] . ', ' : '';
        $format .= $date->day
            . ' de ' . self::MONTHS_SPANISH[$date->month]
            . ' del ' . $date->year
        ;

        return $format;
    }

    /**
     * Formats a period (YYYYMM) in Spanish.
     *
     * @param int $period Period in YYYYMM format.
     * @return string Formatted period string.
     * @throws InvalidArgumentException If period format is invalid.
     */
    public static function formatPeriodSpanish(int $period): string
    {
        $year = (int)substr((string)$period, 0, 4);
        $month = (int)substr((string)$period, 4, 2);

        if (!isset(self::MONTHS_SPANISH[$month])) {
            throw new InvalidArgumentException(
                "Invalid month in period: {$period}"
            );
        }

        return self::MONTHS_SPANISH[$month] . ' de ' . $year;
    }

    /**
     * Calculates how much time has passed since a date and returns it as a
     * string.
     *
     * @param Carbon|string $datetime Date to calculate from.
     * @param bool $full Whether to show the full string or just the most
     * significant parts.
     * @return string Human-readable time difference.
     */
    public static function agoSpanish(
        Carbon|string $datetime,
        bool $full = false
    ): string {
        $datetime = self::ensureCarbon($datetime);
        $now = Carbon::now();
        $diff = $now->diff($datetime);

        // Prepare the initial strings array with existing interval properties.
        $string = [
            'y' => 'año',
            'm' => 'mes',
            'd' => 'día',
            'h' => 'hora',
            'i' => 'minuto',
            's' => 'segundo',
        ];

        // Add weeks if we want to show them.
        $weeks = (int)floor($diff->d / 7);
        $remainingDays = $diff->d % 7;

        if ($weeks > 0) {
            $string = array_merge(
                array_slice($string, 0, 2),  // años y meses.
                ['w' => 'semana'],           // semanas.
                array_slice($string, 2)      // días en adelante.
            );
        }

        $parts = [];
        foreach ($string as $k => $v) {
            $value = $k === 'w' ? $weeks : ($k === 'd' ? $remainingDays : $diff->$k);

            if ($value > 0) {
                $plural = $value > 1;
                if ($k === 'm' && $plural) {
                    $parts[$k] = $value . ' ' . $v . 'es';
                } else {
                    $parts[$k] = $value . ' ' . $v . ($plural ? 's' : '');
                }
            }
        }

        if (!$full) {
            $parts = array_slice($parts, 0, count($parts) >= 2 ? 2 : 1);
        }

        return $parts ? 'hace ' . implode(', ', $parts) : 'recién';
    }

    /**
     * Creates a Carbon instance from a date string.
     *
     * @param string $date Date string in any format Carbon understands.
     * @return Carbon
     * @throws InvalidArgumentException If the date string is invalid.
     */
    public static function create(string $date): Carbon
    {
        try {
            return Carbon::parse($date);
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                "Invalid date string: {$date}. {$e->getMessage()}"
            );
        }
    }

    /**
     * Ensures a date is a Carbon instance.
     *
     * @param Carbon|string $date Date to convert.
     * @return Carbon Carbon instance.
     */
    public static function ensureCarbon(Carbon|string $date): Carbon
    {
        return $date instanceof Carbon ? $date : self::create($date);
    }

    /**
     * Gets a date from an Excel serial number.
     *
     * Excel uses a different epoch date system:
     *
     *   - Dates before March 1, 1900 are counted from January 1, 1900.
     *   - The system includes a special case for the non-existent February 29, 1900.
     *
     * @param int $n Excel serial number.
     * @return Carbon
     */
    public static function fromSerialNumber(int $n): Carbon
    {
        // Special case for 1970-01-01 (Unix epoch).
        if ($n === 25569) {
            return Carbon::createFromTimestamp(0);
        }

        return Carbon::create(1900, 1, 1)->addDays($n - 1);
    }

    /**
     * Validates if a date string matches a specific format.
     *
     * @param string $date Date string to validate.
     * @param string $format Expected format (default: Y-m-d).
     * @return bool True if the date is valid and matches the format.
     */
    public static function validate(string $date, string $format = 'Y-m-d'): bool
    {
        $dt = DateTime::createFromFormat($format, $date);

        return $dt !== false && $dt->getLastErrors() === false;
    }

    /**
     * Validates if a date is in Y-m-d format and converts it to a new format.
     *
     * @param string $date The date to validate and convert.
     * @param string $format The target format.
     * @return string|null Converted date or null if invalid.
     */
    public static function validateAndConvert(
        string $date,
        string $format = 'd/m/Y'
    ): ?string {
        try {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $date);

            if (!$carbonDate instanceof Carbon ||
                $carbonDate->format('Y-m-d') !== $date ||
                $carbonDate->getLastErrors()['error_count'] > 0) {
                return null;
            }

            return $carbonDate->format($format);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Calculates age from a date.
     *
     * @param Carbon|string $date Birth date.
     * @return int Age in years.
     */
    public static function calculateAge(Carbon|string $date): int
    {
        return self::ensureCarbon($date)->age;
    }

    /**
     * Calculates days between two dates.
     *
     * @param Carbon|string $from Start date.
     * @param Carbon|string|null $to End date (defaults to now).
     * @return int Number of days between dates.
     */
    public static function countDays(
        Carbon|string $from,
        Carbon|string|null $to = null
    ): int {
        $from = self::ensureCarbon($from);
        $to = $to ? self::ensureCarbon($to) : Carbon::now();

        return (int) $from->diffInDays($to);
    }

    /**
     * Counts how many specific days exist in a date range.
     *
     * @param Carbon|string $from Start date.
     * @param Carbon|string $to End date.
     * @param array $days Array of dates to count in Y-m-d format.
     * @param bool $excludeWeekend Whether to skip weekends.
     * @return int Number of matching days found.
     */
    public static function countDaysMatch(
        Carbon|string $from,
        Carbon|string $to,
        array $days,
        bool $excludeWeekend = false
    ): int {
        $from = self::ensureCarbon($from);
        $to = self::ensureCarbon($to);
        $count = 0;

        $current = $from->copy();
        while ($current->lte($to)) {
            if ($excludeWeekend && $current->isWeekend()) {
                $current->addDay();
                continue;
            }

            if (in_array($current->format('Y-m-d'), $days)) {
                $count++;
            }

            $current->addDay();
        }

        return $count;
    }

    /**
     * Counts months between two dates.
     *
     * @param Carbon|string|int $from Start date or period (YYYYMM).
     * @param Carbon|string|int|null $to End date or period (YYYYMM), `null` for
     * current date.
     * @return int Number of months between dates.
     */
    public static function countMonths(
        Carbon|string|int $from,
        Carbon|string|int|null $to = null
    ): int {
        // Handle period format.
        if (is_int($from)) {
            $from = self::periodToCarbon((int)$from);
        }
        if (is_int($to)) {
            $to = self::periodToCarbon((int)$to);
        }

        $from = self::ensureCarbon($from);
        $to = $to ? self::ensureCarbon($to) : Carbon::now();

        return ($to->year - $from->year) * 12 + ($to->month - $from->month);
    }

    /**
     * Gets the first day of the week for a given date.
     *
     * @param Carbon|string|null $date Reference date (null for current date).
     * @return Carbon First day of the week.
     */
    public static function firstDayWeek(Carbon|string|null $date = null): Carbon
    {
        $date = $date ? self::ensureCarbon($date) : Carbon::now();

        return $date->copy()->startOfWeek();
    }

    /**
     * Gets the last day of the week for a given date.
     *
     * @param Carbon|string|null $date Reference date (null for current date).
     * @return Carbon Last day of the week.
     */
    public static function lastDayWeek(Carbon|string|null $date = null): Carbon
    {
        $date = $date ? self::ensureCarbon($date) : Carbon::now();

        return $date->copy()->endOfWeek();
    }

    /**
     * Generates an array of consecutive years.
     *
     * @param int $totalYears Number of years to generate.
     * @param int|null $from Starting year (null for current year).
     * @return array Array of years in descending order.
     */
    public static function generateYears(int $totalYears, ?int $from = null): array
    {
        $startYear = $from ?? (int)Carbon::now()->format('Y');
        $endYear = $startYear - $totalYears + 1;

        return range($startYear, $endYear);
    }

    /**
     * Converts a period (YYYYMM) to a Carbon instance.
     *
     * @param int $period Period in YYYYMM format.
     * @return Carbon Carbon instance set to first day of the period.
     * @throws InvalidArgumentException If period format is invalid.
     */
    public static function periodToCarbon(int $period): Carbon
    {
        $year = (int)substr((string)$period, 0, 4);
        $month = (int)substr((string)$period, 4, 2);

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException(
                "Invalid month in period: {$period}"
            );
        }

        return Carbon::createFromDate($year, $month, 1);
    }

    /**
     * Validates a period in YYYY format.
     *
     * @param int $period Period to validate.
     * @param int $yearFrom Start year of valid range.
     * @param int $yearTo End year of valid range.
     * @return bool True if period is valid.
     */
    public static function validPeriod4(
        int $period,
        int $yearFrom = self::YEAR_MIN,
        int $yearTo = self::YEAR_MAX
    ): bool {
        return self::validPeriod($period, $yearFrom, $yearTo, 4);
    }

    /**
     * Validates a period in YYYYMM format.
     *
     * @param int $period Period to validate.
     * @param int $yearFrom Start year of valid range.
     * @param int $yearTo End year of valid range.
     * @return bool True if period is valid.
     */
    public static function validPeriod6(
        int $period,
        int $yearFrom = self::YEAR_MIN,
        int $yearTo = self::YEAR_MAX
    ): bool {
        return self::validPeriod($period, $yearFrom, $yearTo, 6);
    }

    /**
     * Validates a period in YYYY or YYYYMM format within a year range.
     *
     * @param int $period Period to validate.
     * @param int $yearFrom Start year of valid range.
     * @param int $yearTo End year of valid range.
     * @param int|null $length Expected length (4 or 6, null for any).
     * @return bool True if period is valid.
     */
    public static function validPeriod(
        int $period,
        int $yearFrom = self::YEAR_MIN,
        int $yearTo = self::YEAR_MAX,
        ?int $length = null
    ): bool {
        $periodStr = (string)(int)$period;
        $periodLength = strlen($periodStr);

        if ($length !== null && $length !== $periodLength) {
            return false;
        }

        if ($periodLength === 4) {
            return $period >= $yearFrom && $period <= $yearTo;
        }

        if ($periodLength === 6) {
            $year = (int)substr($periodStr, 0, 4);
            $month = (int)substr($periodStr, 4, 2);
            return $year >= $yearFrom
                && $year <= $yearTo
                && $month >= 1
                && $month <= 12
            ;
        }

        return false;
    }

    /**
     * Gets the number of days in a month for a given period.
     *
     * @param int $period Period in YYYYMM format.
     * @return int Number of days in the month.
     */
    public static function daysInPeriod(int $period): int
    {
        $date = self::periodToCarbon($period);

        return $date->daysInMonth;
    }

    /**
     * Gets the last day of a period.
     *
     * @param int|null $period Period in YYYYMM format (null for current month).
     * @return string Last day of the period in Y-m-d format.
     */
    public static function lastDayPeriod(?int $period = null): string
    {
        if ($period === null) {
            $date = Carbon::now();
        } else {
            $date = self::periodToCarbon($period);
        }

        return $date->endOfMonth()->format('Y-m-d');
    }

    /**
     * Gets the next period (YYYYMM) after a given period.
     *
     * @param int|null $period Current period (null for current month).
     * @param int $steps Number of periods to move forward.
     * @return int Next period.
     */
    public static function nextPeriod(?int $period = null, int $steps = 1): int
    {
        if ($period === null) {
            $date = Carbon::now();
        } else {
            $date = self::periodToCarbon($period);
        }

        return (int)$date->addMonths($steps)->format('Ym');
    }

    /**
     * Gets the previous period (YYYYMM) before a given period.
     *
     * @param int|null $period Current period (null for current month).
     * @param int $steps Number of periods to move backward.
     * @return int Previous period.
     */
    public static function previousPeriod(?int $period = null, int $steps = 1): int
    {
        if ($period === null) {
            $date = Carbon::now();
        } else {
            $date = self::periodToCarbon($period);
        }

        return (int)$date->subMonths($steps)->format('Ym');
    }

    /**
     * Gets the next date based on a specific time unit.
     *
     * Available time units:
     *
     *   - 'D': Days.
     *   - 'W': Weeks.
     *   - 'M': Months.
     *   - 'Q': Quarters.
     *   - 'S': Semesters.
     *   - 'Y': Years.
     *
     * @param Carbon|string|null $date Starting date (null for current date).
     * @param string $unit Time unit (D, W, M, Q, S, Y).
     * @param int $steps Number of units to move forward.
     * @return Carbon The resulting date.
     * @throws InvalidArgumentException If the time unit is invalid.
     */
    public static function nextDate(
        Carbon|string|null $date = null,
        string $unit = 'M',
        int $steps = 1
    ): Carbon {
        $date = $date ? self::ensureCarbon($date) : Carbon::now();

        return match (strtoupper($unit)) {
            'D' => $date->addDays($steps),
            'W' => $date->addWeeks($steps),
            'M' => $date->addMonths($steps),
            'Q' => $date->addQuarters($steps),
            'S' => $date->addMonths($steps * 6),
            'Y' => $date->addYears($steps),
            default => throw new InvalidArgumentException(
                "Invalid time unit: {$unit}"
            )
        };
    }

    /**
     * Gets the previous date based on a specific time unit.
     *
     * Available time units:
     *
     *   - 'D': Days.
     *   - 'W': Weeks.
     *   - 'M': Months.
     *   - 'Q': Quarters.
     *   - 'S': Semesters.
     *   - 'Y': Years.
     *
     * @param Carbon|string|null $date Starting date (null for current date).
     * @param string $unit Time unit (D, W, M, Q, S, Y).
     * @param int $steps Number of units to move backward.
     * @return Carbon The resulting date.
     * @throws InvalidArgumentException If the time unit is invalid.
     */
    public static function previousDate(
        Carbon|string|null $date = null,
        string $unit = 'M',
        int $steps = 1
    ): Carbon {
        $date = $date ? self::ensureCarbon($date) : Carbon::now();

        return match (strtoupper($unit)) {
            'D' => $date->subDays($steps),
            'W' => $date->subWeeks($steps),
            'M' => $date->subMonths($steps),
            'Q' => $date->subQuarters($steps),
            'S' => $date->subMonths($steps * 6),
            'Y' => $date->subYears($steps),
            default => throw new InvalidArgumentException(
                "Invalid time unit: {$unit}"
            )
        };
    }

    /**
     * Gets the date of a specific working day in a month.
     *
     * @param int $year Year to look in.
     * @param int $month Month to look in.
     * @param int $workingDay Working day number to find.
     * @param array $holidays Array of holiday dates to exclude.
     * @return Carbon|false The date found or false if invalid.
     */
    public static function getWorkingDay(
        int $year,
        int $month,
        int $workingDay,
        array $holidays = []
    ): Carbon|false {
        // Start with first day of month.
        $date = Carbon::create($year, $month, 1);

        // Get first working day.
        $firstWorkingDay = self::addWorkingDays($date, 0, $holidays);

        // Get the target working day.
        $targetDate = self::addWorkingDays(
            $firstWorkingDay,
            $workingDay - 1,
            $holidays
        );

        // Validate the result is in the same month.
        if ($targetDate->year !== $year || $targetDate->month !== $month) {
            return false;
        }

        return $targetDate;
    }

    /**
     * Gets the working day number of the month for a specific date.
     *
     * @param Carbon|string $date The date to check.
     * @param array $holidays Array of holiday dates in Y-m-d format.
     * @return int|false Working day number or false if not a working day.
     */
    public static function getWorkingDayNumber(
        Carbon|string $date,
        array $holidays = []
    ): int|false {
        $date = self::ensureCarbon($date);

        if (!$date->isWeekday() || in_array($date->format('Y-m-d'), $holidays)) {
            return false;
        }

        $firstDay = $date->copy()->startOfMonth();
        $workingDays = 0;

        while ($firstDay->lte($date)) {
            if (
                $firstDay->isWeekday()
                && !in_array($firstDay->format('Y-m-d'), $holidays)
            ) {
                $workingDays++;
            }
            $firstDay->addDay();
        }

        return $workingDays;
    }

    /**
     * Checks if a date is the last working day of its month.
     *
     * @param Carbon|string $date Date to check.
     * @param array $holidays Array of holiday dates to exclude.
     * @return bool True if it's the last working day.
     */
    public static function isLastWorkingDay(
        Carbon|string $date,
        array $holidays = []
    ): bool {
        $date = self::ensureCarbon($date);

        // If it's not a working day, return false.
        if (!self::getWorkingDayNumber($date, $holidays)) {
            return false;
        }

        // Get next working day.
        $nextWorkingDay = self::addWorkingDays($date, 1, $holidays);

        // If next working day is in the same month, this is not the last
        // working day.
        return $nextWorkingDay->month !== $date->month;
    }

    /**
     * Adds working days to a date, skipping weekends and holidays.
     *
     * @param Carbon|string $date Starting date.
     * @param int $days Number of working days to add.
     * @param array $holidays Array of holiday dates in Y-m-d format.
     * @return Carbon Resulting date.
     */
    public static function addWorkingDays(
        Carbon|string $date,
        int $days,
        array $holidays = []
    ): Carbon {
        $date = self::ensureCarbon($date);
        $workingDate = $date->copy();

        while ($days > 0) {
            $workingDate->addDay();
            if (
                $workingDate->isWeekday()
                && !in_array($workingDate->format('Y-m-d'), $holidays)
            ) {
                $days--;
            }
        }

        return $workingDate;
    }

    /**
     * Subtracts working days from a date, skipping weekends and holidays.
     *
     * @param Carbon|string $date Starting date.
     * @param int $days Number of working days to subtract.
     * @param array $holidays Array of holiday dates in Y-m-d format.
     * @return Carbon Resulting date.
     */
    public static function subtractWorkingDays(
        Carbon|string $date,
        int $days,
        array $holidays = []
    ): Carbon {
        $date = self::ensureCarbon($date);
        $workingDate = $date->copy();

        while ($days > 0) {
            $workingDate->subDay();
            if (
                $workingDate->isWeekday()
                && !in_array($workingDate->format('Y-m-d'), $holidays)
            ) {
                $days--;
            }
        }

        return $workingDate;
    }
}
