<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Date;

use Carbon\Carbon;
use Derafu\Support\Date;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Date::class)]
class Date2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2024, 1, 15));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('provideValidationData')]
    public function shouldValidateDates(string $date, string $format, bool $expected): void
    {
        $result = Date::validate($date, $format);
        $this->assertSame($expected, $result);
    }

    public static function provideValidationData(): array
    {
        return [
            'valid date standard format' => [
                '2024-01-15',
                'Y-m-d',
                true,
            ],
            'valid date custom format' => [
                '15/01/2024',
                'd/m/Y',
                true,
            ],
            'invalid day' => [
                '2024-02-30',
                'Y-m-d',
                false,
            ],
            'invalid month' => [
                '2024-13-01',
                'Y-m-d',
                false,
            ],
            'invalid format' => [
                '2024-01-15',
                'd/m/Y',
                false,
            ],
            'non-date string' => [
                'not a date',
                'Y-m-d',
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideValidateAndConvertData')]
    public function shouldValidateAndConvertDate(
        string $date,
        string $format,
        ?string $expected
    ): void {
        $result = Date::validateAndConvert($date, $format);
        $this->assertSame($expected, $result);
    }

    public static function provideValidateAndConvertData(): array
    {
        return [
            'valid date' => [
                '2024-01-15',
                'd/m/Y',
                '15/01/2024',
            ],
            'invalid format' => [
                '2024-13-45',
                'd/m/Y',
                null,
            ],
            'invalid date string' => [
                'not-a-date',
                'd/m/Y',
                null,
            ],
            'custom format' => [
                '2024-01-15',
                'Y.m.d',
                '2024.01.15',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideWorkingDayData')]
    public function shouldFindWorkingDay(
        int $year,
        int $month,
        int $workingDay,
        array $holidays,
        string|false $expected
    ): void {
        $result = Date::getWorkingDay($year, $month, $workingDay, $holidays);

        if ($expected === false) {
            $this->assertFalse($result);
        } else {
            $this->assertInstanceOf(Carbon::class, $result);
            $this->assertSame($expected, $result->format('Y-m-d'));
        }
    }

    public static function provideWorkingDayData(): array
    {
        return [
            'first working day' => [
                2024,
                1,
                1,
                [],
                '2024-01-01',
            ],
            'with weekend' => [
                2024,
                1,
                2,
                [],
                '2024-01-02',
            ],
            'with holiday' => [
                2024,
                1,
                2,
                ['2024-01-02'],
                '2024-01-03',
            ],
            'invalid working day' => [
                2024,
                1,
                50,  // Too many working days for the month.
                [],
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideWorkingDaysData')]
    public function shouldAddWorkingDays(
        string $startDate,
        int $days,
        array $holidays,
        string $expected
    ): void {
        $result = Date::addWorkingDays($startDate, $days, $holidays);
        $this->assertSame($expected, $result->format('Y-m-d'));
    }

    public static function provideWorkingDaysData(): array
    {
        return [
            'add one day' => [
                '2024-01-15',  // Monday.
                1,
                [],
                '2024-01-16',   // Tuesday.
            ],
            'skip weekend' => [
                '2024-01-19',  // Friday.
                1,
                [],
                '2024-01-22',   // Monday.
            ],
            'skip holiday' => [
                '2024-01-15',
                1,
                ['2024-01-16'],
                '2024-01-17',
            ],
            'skip multiple holidays' => [
                '2024-01-15',
                2,
                ['2024-01-16', '2024-01-17'],
                '2024-01-19',
            ],
            'skip multiple holidays and weekend' => [
                '2024-01-16',
                2,
                ['2024-01-17', '2024-01-18'],
                '2024-01-22',
            ],
            'no days to add' => [
                '2024-01-15',
                0,
                [],
                '2024-01-15',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideLastWorkingDayData')]
    public function shouldCheckLastWorkingDay(
        string $date,
        array $holidays,
        bool $expected
    ): void {
        $result = Date::isLastWorkingDay($date, $holidays);
        $this->assertSame($expected, $result);
    }

    public static function provideLastWorkingDayData(): array
    {
        return [
            'last day is working' => [
                '2024-01-31',
                [],
                true,
            ],
            'last day is weekend' => [
                '2024-02-29',  // February 2024 ends on a Thursday.
                [],
                true,
            ],
            'not last working day' => [
                '2024-01-15',
                [],
                false,
            ],
            'last working day with holiday' => [
                '2024-01-30',
                ['2024-01-31'],
                true,
            ],
            'weekend day' => [
                '2024-01-27',  // Saturday.
                [],
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideCountDaysMatchData')]
    public function shouldCountMatchingDays(
        string $from,
        string $to,
        array $days,
        bool $excludeWeekend,
        int $expected
    ): void {
        $result = Date::countDaysMatch($from, $to, $days, $excludeWeekend);
        $this->assertSame($expected, $result);
    }

    public static function provideCountDaysMatchData(): array
    {
        return [
            'simple range' => [
                '2024-01-01',
                '2024-01-05',
                ['2024-01-02', '2024-01-04'],
                false,
                2,
            ],
            'exclude weekends' => [
                '2024-01-01',
                '2024-01-07',
                ['2024-01-06', '2024-01-07'],  // Saturday and Sunday.
                true,
                0,
            ],
            'no matches' => [
                '2024-01-01',
                '2024-01-05',
                ['2024-01-10'],
                false,
                0,
            ],
            'single day' => [
                '2024-01-01',
                '2024-01-01',
                ['2024-01-01'],
                false,
                1,
            ],
            'with weekends included' => [
                '2024-01-05',
                '2024-01-08',
                ['2024-01-06', '2024-01-07'],
                false,
                2,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideAgoData')]
    public function shouldFormatTimeAgo(
        string $datetime,
        bool $full,
        string $expected
    ): void {
        // Fix the "now" time for testing.
        Carbon::setTestNow('2024-01-15 12:00:00');

        $result = Date::agoSpanish($datetime, $full);
        $this->assertSame($expected, $result);

        Carbon::setTestNow(); // Reset time.
    }

    public static function provideAgoData(): array
    {
        return [
            'just now' => [
                '2024-01-15 12:00:00',
                false,
                'recién',
            ],
            'minutes ago' => [
                '2024-01-15 11:45:00',
                false,
                'hace 15 minutos',
            ],
            'hours and minutes' => [
                '2024-01-15 10:45:00',
                true,
                'hace 1 hora, 15 minutos',
            ],
            'one day' => [
                '2024-01-14 12:00:00',
                false,
                'hace 1 día',
            ],
            'days and hours' => [
                '2024-01-13 10:00:00',
                true,
                'hace 2 días, 2 horas',
            ],
            'one week' => [
                '2024-01-08 12:00:00',
                false,
                'hace 1 semana',
            ],
            'one month' => [
                '2023-12-15 12:00:00',
                false,
                'hace 1 mes',
            ],
            'multiple months' => [
                '2023-11-15 12:00:00',
                false,
                'hace 2 meses',
            ],
            'one year' => [
                '2023-01-15 12:00:00',
                false,
                'hace 1 año',
            ],
            'full details' => [
                '2023-01-10 10:30:00',
                true,
                'hace 1 año, 5 días, 1 hora, 30 minutos',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideCountData')]
    public function shouldCountDays(
        string $from,
        ?string $to,
        int $expected
    ): void {
        if ($to === null) {
            Carbon::setTestNow('2024-01-15');
        }

        $result = Date::countDays($from, $to);
        $this->assertSame($expected, $result);

        if ($to === null) {
            Carbon::setTestNow();
        }
    }

    public static function provideCountData(): array
    {
        return [
            'specific range' => [
                '2024-01-01',
                '2024-01-15',
                14,
            ],
            'to today' => [
                '2024-01-01',
                null,
                14,
            ],
            'same day' => [
                '2024-01-15',
                '2024-01-15',
                0,
            ],
            'one day' => [
                '2024-01-14',
                '2024-01-15',
                1,
            ],
            'across months' => [
                '2023-12-15',
                '2024-01-15',
                31,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideSerialNumberData')]
    public function shouldConvertFromSerialNumber(
        int $serialNumber,
        string $expected
    ): void {
        $result = Date::fromSerialNumber($serialNumber);
        $this->assertSame($expected, $result->format('Y-m-d'));
    }

    public static function provideSerialNumberData(): array
    {
        return [
            'regular date' => [
                44926,  // 2023-01-01
                '2023-01-01',
            ],
            'leap year date' => [
                45291,  // 2024-01-01
                '2024-01-01',
            ],
            'end of month' => [
                45321,  // 2024-01-31
                '2024-01-31',
            ],
            'beginning of excel' => [
                25569,  // 1970-01-01
                '1970-01-01',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providePeriodValidationData')]
    public function shouldValidatePeriod(
        int $period,
        int $yearFrom,
        int $yearTo,
        ?int $length,
        bool $expected
    ): void {
        $result = Date::validPeriod($period, $yearFrom, $yearTo, $length);
        $this->assertSame($expected, $result);
    }

    public static function providePeriodValidationData(): array
    {
        return [
            'valid year' => [
                2024,
                2000,
                2100,
                4,
                true,
            ],
            'valid month' => [
                202401,
                2000,
                2100,
                6,
                true,
            ],
            'invalid year range' => [
                1999,
                2000,
                2100,
                4,
                false,
            ],
            'invalid month' => [
                202413,
                2000,
                2100,
                6,
                false,
            ],
            'invalid length' => [
                2024,
                2000,
                2100,
                6,
                false,
            ],
            'any length year' => [
                2024,
                2000,
                2100,
                null,
                true,
            ],
            'any length month' => [
                202401,
                2000,
                2100,
                null,
                true,
            ],
        ];
    }

    #[Test]
    #[DataProvider('providePeriod4Data')]
    public function shouldValidatePeriod4(
        int $period,
        int $yearFrom,
        int $yearTo,
        bool $expected
    ): void {
        $result = Date::validPeriod4($period, $yearFrom, $yearTo);
        $this->assertSame($expected, $result);
    }

    public static function providePeriod4Data(): array
    {
        return [
            'valid year' => [
                2024,
                2000,
                2100,
                true,
            ],
            'year too early' => [
                1999,
                2000,
                2100,
                false,
            ],
            'year too late' => [
                2101,
                2000,
                2100,
                false,
            ],
            'invalid format' => [
                202401,
                2000,
                2100,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('providePeriod6Data')]
    public function shouldValidatePeriod6(
        int $period,
        int $yearFrom,
        int $yearTo,
        bool $expected
    ): void {
        $result = Date::validPeriod6($period, $yearFrom, $yearTo);
        $this->assertSame($expected, $result);
    }

    public static function providePeriod6Data(): array
    {
        return [
            'valid period' => [
                202401,
                2000,
                2100,
                true,
            ],
            'invalid month' => [
                202413,
                2000,
                2100,
                false,
            ],
            'year too early' => [
                199912,
                2000,
                2100,
                false,
            ],
            'year too late' => [
                210101,
                2000,
                2100,
                false,
            ],
            'invalid format' => [
                2024,
                2000,
                2100,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideCountMonthsData')]
    public function shouldCountMonths(
        Carbon|string|int $from,
        Carbon|string|int|null $to,
        int $expected
    ): void {
        if ($to === null) {
            Carbon::setTestNow('2024-01-15');
        }

        $result = Date::countMonths($from, $to);
        $this->assertSame($expected, $result);

        if ($to === null) {
            Carbon::setTestNow();
        }
    }

    public static function provideCountMonthsData(): array
    {
        return [
            'string dates' => [
                '2023-01-15',
                '2024-01-15',
                12,
            ],
            'period format' => [
                202301,
                202401,
                12,
            ],
            'mixed formats' => [
                202301,
                '2024-01-15',
                12,
            ],
            'to current date' => [
                '2023-01-15',
                null,
                12,
            ],
            'same month' => [
                '2024-01-01',
                '2024-01-31',
                0,
            ],
            'partial month' => [
                '2024-01-15',
                '2024-02-14',
                1,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideWeekBoundariesData')]
    public function shouldGetWeekBoundaries(
        ?string $date,
        string $expectedFirst,
        string $expectedLast
    ): void {
        if ($date === null) {
            Carbon::setTestNow('2024-01-15');
        }

        $firstDay = Date::firstDayWeek($date);
        $lastDay = Date::lastDayWeek($date);

        $this->assertSame($expectedFirst, $firstDay->format('Y-m-d'));
        $this->assertSame($expectedLast, $lastDay->format('Y-m-d'));

        if ($date === null) {
            Carbon::setTestNow();
        }
    }

    public static function provideWeekBoundariesData(): array
    {
        return [
            'mid week' => [
                '2024-01-15',  // Monday.
                '2024-01-15',  // Monday.
                '2024-01-21',   // Sunday.
            ],
            'start of week' => [
                '2024-01-15',  // Monday.
                '2024-01-15',  // Monday.
                '2024-01-21',   // Sunday.
            ],
            'end of week' => [
                '2024-01-21',  // Sunday.
                '2024-01-15',  // Monday.
                '2024-01-21',   // Sunday.
            ],
            'current week' => [
                null,
                '2024-01-15',  // Monday.
                '2024-01-21',   // Sunday.
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideYearsData')]
    public function shouldGenerateYears(
        int $totalYears,
        ?int $from,
        array $expected
    ): void {
        if ($from === null) {
            Carbon::setTestNow('2024-01-15');
        }

        $result = Date::generateYears($totalYears, $from);
        $this->assertSame($expected, $result);

        if ($from === null) {
            Carbon::setTestNow();
        }
    }

    public static function provideYearsData(): array
    {
        return [
            'from current year' => [
                3,
                null,
                [2024, 2023, 2022],
            ],
            'specific range' => [
                5,
                2020,
                [2020, 2019, 2018, 2017, 2016],
            ],
            'single year' => [
                1,
                2024,
                [2024],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideDaysInMonthData')]
    public function shouldGetDaysInMonth(int $period, int $expected): void
    {
        $result = Date::daysInPeriod($period);
        $this->assertSame($expected, $result);
    }

    public static function provideDaysInMonthData(): array
    {
        return [
            'January' => [202401, 31],
            'February normal' => [202302, 28],
            'February leap' => [202402, 29],
            'April' => [202404, 30],
            'December' => [202412, 31],
        ];
    }

    #[Test]
    #[DataProvider('provideSpanishFormatData')]
    public function shouldFormatDatesInSpanish(
        string $date,
        bool $includeDay,
        string $expected
    ): void {
        $result = Date::formatSpanish($date, $includeDay);
        $this->assertSame($expected, $result);
    }

    public static function provideSpanishFormatData(): array
    {
        return [
            'with day' => [
                '2024-01-15',
                true,
                'Lunes, 15 de Enero del 2024',
            ],
            'without day' => [
                '2024-01-15',
                false,
                '15 de Enero del 2024',
            ],
            'special month' => [
                '2024-02-15',
                true,
                'Jueves, 15 de Febrero del 2024',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providePeriodData')]
    public function shouldFormatPeriods(int $period, ?string $expected): void
    {
        if ($expected === null) {
            $this->expectException(InvalidArgumentException::class);
        }

        $result = Date::formatPeriodSpanish($period);

        if ($expected !== null) {
            $this->assertSame($expected, $result);
        }
    }

    public static function providePeriodData(): array
    {
        return [
            'january' => [202401, 'Enero de 2024'],
            'december' => [202412, 'Diciembre de 2024'],
            'invalid month' => [202413, null],
        ];
    }

    #[Test]
    #[DataProvider('provideNextPeriodData')]
    public function shouldCalculateNextPeriod(?int $period, int $steps, int $expected): void
    {
        // Fix the "now" time for testing.
        Carbon::setTestNow('2024-01-01 00:00:00');

        $result = Date::nextPeriod($period, $steps);
        $this->assertSame($expected, $result);

        Carbon::setTestNow(); // Reset time.
    }

    public static function provideNextPeriodData(): array
    {
        return [
            'one month' => [202401, 1, 202402],
            'multiple months' => [202401, 3, 202404],
            'year change' => [202412, 1, 202501],
            'null period' => [null, 1, 202402], // Assuming current date is 2024-01
            'no movement' => [202401, 0, 202401],
        ];
    }

    #[Test]
    #[DataProvider('providePreviousPeriodData')]
    public function shouldCalculatePreviousPeriod(?int $period, int $steps, int $expected): void
    {
        // Fix the "now" time for testing.
        Carbon::setTestNow('2024-01-01 00:00:00');

        $result = Date::previousPeriod($period, $steps);
        $this->assertSame($expected, $result);

        Carbon::setTestNow(); // Reset time.
    }

    public static function providePreviousPeriodData(): array
    {
        return [
            'one month' => [202402, 1, 202401],
            'multiple months' => [202404, 3, 202401],
            'year change' => [202401, 1, 202312],
            'null period' => [null, 1, 202312], // Assuming current date is 2024-01
            'no movement' => [202401, 0, 202401],
        ];
    }

    #[Test]
    public function shouldGetLastDayOfPeriod(): void
    {
        $this->assertSame('2024-01-31', Date::lastDayPeriod(202401));
        $this->assertSame('2024-02-29', Date::lastDayPeriod(202402)); // Leap year.
        $this->assertSame('2024-04-30', Date::lastDayPeriod(202404));
    }

    #[Test]
    public function shouldCalculateAge(): void
    {
        // Using fixed test date (2024-01-15).
        $this->assertSame(20, Date::calculateAge('2004-01-14'));
        $this->assertSame(20, Date::calculateAge('2004-01-15'));
        $this->assertSame(19, Date::calculateAge('2004-01-16'));
    }

    #[Test]
    public function shouldHandleInvalidPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Date::formatPeriodSpanish(202413);
    }

    #[Test]
    public function shouldHandleInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Date::create('invalid-date');
    }
}
