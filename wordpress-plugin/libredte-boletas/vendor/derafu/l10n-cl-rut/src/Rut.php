<?php

declare(strict_types=1);

/**
 * Derafu: L10n CL RUT - Chilean RUT library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\L10n\Cl\Rut;

use UnexpectedValueException;

/**
 * Class for working with Chilean RUT and RUN identifiers.
 */
class Rut
{
    /**
     * Defines that a RUT cannot be less than 1,000,000.
     *
     * Although legally there could be RUT or RUN numbers lower than this, in
     * practice (as of 2024) there should not be any active RUT or RUN in use
     * that are below this minimum defined here.
     */
    private const RUT_MIN = 1000000;

    /**
     * Defines that a RUT cannot be greater than 99,999,999.
     *
     * Although legally there could be RUT or RUN numbers higher than this, in
     * practice (as of 2024) there should not be any active RUT or RUN in use
     * that are above this maximum defined here.
     */
    private const RUT_MAX = 99999999;

    /**
     * Extracts the 2 parts from a RUT: the RUT itself (just the number) and the
     * verification digit.
     *
     * @param string $rut Complete RUT, with verification digit (dots and dash optional).
     * @return array An array with 2 elements: rut (int) and verification digit (string).
     */
    public static function toArray(string $rut): array
    {
        $rut = self::removeThousandsSeparatorAndDash($rut);
        $dv = strtoupper(substr($rut, -1));
        $rut = substr($rut, 0, -1);

        return [(int) $rut, $dv];
    }

    /**
     * Formats a RUT to the format: 11222333-4.
     *
     * IMPORTANT: This method does not add dots to the RUT. If you want to use
     * the RUT with dots, use formatFull() instead.
     *
     * @param string|int $rut
     * @return string
     */
    public static function format(string|int $rut): string
    {
        // If it's a string, it's expected to come with the verification digit,
        // dash is optional.
        if (is_string($rut)) {
            return self::formatFromString($rut);
        }

        // If it's an int, it's just the numeric part of the RUT (without
        // verification digit).
        return self::formatFromInt($rut);
    }

    /**
     * Formats a RUT to the format: 11.222.333-4.
     *
     * @param string|integer $rut
     * @return string
     */
    public static function formatFull(string|int $rut): string
    {
        $rut = self::format($rut);
        [$rut, $dv] = self::toArray($rut);

        return self::addThousandsSeparator($rut) . '-' . $dv;
    }

    /**
     * Calculates the verification digit for a RUT.
     *
     * @param int $rut RUT for which the verification digit will be calculated.
     * @return string Calculated verification digit for the given RUT.
     */
    public static function calculateDv(int $rut): string
    {
        $s = 1;
        for ($m = 0; $rut != 0; $rut /= 10) {
            $rut = (int) $rut;
            $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
        }
        return strtoupper(chr($s ? $s + 47 : 75));
    }

    /**
     * Validates the entered RUT.
     *
     * @param string $rut RUT with verification digit (dots and dash optional).
     * @throws UnexpectedValueException If any problem was found when
     * validating the RUT.
     */
    public static function validate(string $rut): void
    {
        $originalRut = $rut;
        [$rut, $dv] = self::toArray($rut);

        // Validate RUT minimum.
        if ($rut < self::RUT_MIN) {
            throw new UnexpectedValueException(sprintf(
                'The RUT cannot be less than %s and the value %s was found.',
                self::addThousandsSeparator(self::RUT_MIN),
                self::addThousandsSeparator($rut)
            ));
        }

        // Validate RUT maximum.
        if ($rut > self::RUT_MAX) {
            throw new UnexpectedValueException(sprintf(
                'The RUT cannot be greater than %s and the value %s was found.',
                self::addThousandsSeparator(self::RUT_MAX),
                self::addThousandsSeparator($rut)
            ));
        }

        // Validate that the verification digit is between 0-9 or 'K'.
        if (!preg_match('/^[0-9K]$/', $dv)) {
            throw new UnexpectedValueException(sprintf(
                'The verification digit must be a character between "0" and "9", or the uppercase letter "K". The value "%s" was found.',
                $dv
            ));
        }

        // Validate that the verification digit is correct for the RUT.
        $real_dv = self::calculateDv((int) $rut);
        if ($dv !== $real_dv) {
            throw new UnexpectedValueException(sprintf(
                'The verification digit of the RUT %s is incorrect. The value "%s" was found and for the numeric part %s of the RUT, the verification digit should be "%s".',
                self::formatFull($originalRut),
                $dv,
                self::addThousandsSeparator($rut),
                $real_dv
            ));
        }
    }

    /**
     * Returns the numeric part of the RUT from a RUT that has a verification digit.
     *
     * @param string $rut Complete RUT, with verification digit (dots and dash optional).
     * @return integer Numeric part of the RUT (does not include verification digit).
     */
    public static function removeDv(string $rut): int
    {
        $rut = self::removeThousandsSeparatorAndDash($rut);

        return (int) substr($rut, 0, -1);
    }

    /**
     * Adds the verification digit to the RUT and returns it as a string with
     * the verification digit concatenated at the end (without format).
     *
     * @param int $rut RUT in number format without verification digit.
     * @return string String with the RUT with verification digit, without format.
     */
    public static function addDv(int $rut): string
    {
        return ((string) $rut) . self::calculateDv($rut);
    }

    /**
     * Formats the RUT from a RUT that came as a string (with verification
     * digit).
     *
     * @param string $rut
     * @return string
     */
    private static function formatFromString(string $rut): string
    {
        return self::formatAsString(trim($rut));
    }

    /**
     * Formats the RUT from a RUT that came as a number (without verification
     * digit).
     *
     * @param integer $rut
     * @return string
     */
    private static function formatFromInt(int $rut): string
    {
        $rut = self::addDv($rut);

        return self::formatAsString($rut);
    }

    /**
     * Cleans the RUT by removing the thousands separator (which can come with
     * dots or commas) and the dash.
     *
     * @param string $rut Complete RUT possibly with dots and dash.
     * @return string Returns the RUT in format 112223334.
     */
    private static function removeThousandsSeparatorAndDash(string $rut): string
    {
        return str_replace(['.', ',', '-'], '', $rut);
    }

    /**
     * Adds the thousands separator to the RUT and returns it as a string.
     *
     * @param integer $rut Just the number part of the RUT, without verification digit.
     * @return string Numeric part of the RUT as a string with thousands separator.
     */
    private static function addThousandsSeparator(int $rut): string
    {
        return number_format($rut, 0, '', '.');
    }

    /**
     * Returns the RUT formatted as a string but without dots.
     *
     * Basically, it takes the RUT and:
     *
     *   - Removes the thousands separators.
     *   - Ensures it has a dash if it didn't have one.
     *
     * @param string $rut RUT with verification digit (dots and dash optional).
     * @return string RUT with verification digit and dash, without dots.
     */
    private static function formatAsString(string $rut): string
    {
        [$rut, $dv] = self::toArray($rut);

        return $rut . '-' . $dv;
    }
}
