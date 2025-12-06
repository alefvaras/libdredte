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

use InvalidArgumentException;

/**
 * String manipulation and utilities class.
 *
 * This class provides a collection of static methods for string manipulation,
 * including UTF-8 handling, placeholder replacement, string normalization for
 * URLs, and various other string utility functions.
 */
final class Str
{
    /**
     * Default width for word wrapping operations.
     */
    public const WORDWRAP_WIDTH = 64;

    /**
     * Wraps text at a specified width.
     *
     * @param string $string String to wrap.
     * @param int $characters Maximum line length.
     * @param string $break Line break character.
     * @param bool $cutLongWords Cut words longer than length.
     * @return string The wrapped string.
     */
    public static function wordWrap(
        string $string,
        int $characters = self::WORDWRAP_WIDTH,
        string $break = "\n",
        bool $cutLongWords = true
    ): string {
        return wordwrap($string, $characters, $break, $cutLongWords);
    }

    /**
     * Replaces placeholders in a string with corresponding values.
     *
     * Supports multiple placeholder styles:
     *
     *   - Mustache/Handlebars: {{ key }}
     *   - Simple: { key }
     *   - SQL params: :key
     *   - Python style: %(key)s
     *   - Dollar sign: $key
     *
     * @param string $template String containing placeholders.
     * @param array $data Values to replace placeholders with.
     * @param string $style Placeholder style or styles (pipe-separated,
     * default: 'mustache').
     * @return string String with placeholders replaced.
     * @throws InvalidArgumentException If any style is not supported.
     */
    public static function format(
        string $template,
        array $data,
        string $style = 'mustache'
    ): string {
        $flatData = Arr::dot($data);

        // Define placeholder patterns.
        $patterns = [
            'mustache' => '/\{\{\s*([^}]+?)\s*\}\}/',   // {{ key }} with optional spaces.
            'simple' => '/\{\s*([^}]+?)\s*\}/',         // { key } with optional spaces.
            'sql' => '/:([a-zA-Z_][a-zA-Z0-9_]*)/',     // :key
            'python' => '/%\(([^)]+)\)s/',              // %(key)s
            'dollar' => '/\$([a-zA-Z_][a-zA-Z0-9_]*)/', // $key
        ];

        // Process each requested style.
        $styles = array_map('trim', explode('|', $style));
        $activePatterns = [];

        foreach ($styles as $requestedStyle) {
            if (!isset($patterns[$requestedStyle])) {
                throw new InvalidArgumentException(
                    "Unsupported placeholder style: {$requestedStyle}."
                );
            }
            $activePatterns[] = $patterns[$requestedStyle];
        }

        // Apply all patterns in sequence.
        foreach ($activePatterns as $pattern) {
            $template = preg_replace_callback(
                $pattern,
                function ($matches) use ($flatData) {
                    $key = trim($matches[1]);
                    return $flatData[$key] ?? $matches[0];
                },
                $template
            );
        }

        return $template;
    }

    /**
     * Convert a string to StudlyCase.
     *
     * @param string $value The string to convert.
     * @return string
     */
    public static function studly(string $value): string
    {
        // First convert camelCase to space-separated.
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value);

        // Then replace delimiters with spaces.
        $value = str_replace(['-', '_'], ' ', $value);

        // Convert to studly.
        return str_replace(' ', '', ucwords(strtolower($value)));
    }

    /**
     * Convert a string to camelCase.
     *
     * @param string $value The string to convert.
     * @return string
     */
    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    /**
     * Convert a string to snake_case.
     *
     * @param string $value The string to convert.
     * @param string $delimiter The delimiter to use (default: _).
     * @return string
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        // Convert camelCase to spaces.
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value);

        // Replace hyphens and underscores with spaces.
        $value = str_replace(['-', '_'], ' ', $value);

        // Convert to lowercase.
        $value = strtolower($value);

        // Replace spaces with delimiter.
        return str_replace(' ', $delimiter, $value);
    }

    /**
     * Converts a string to a URL-friendly slug.
     *
     * Creates a slug that:
     *
     *   - Is lowercase.
     *   - Contains only alphanumeric characters and hyphens.
     *   - Has no consecutive hyphens.
     *   - Has no leading or trailing hyphens.
     *   - Properly handles UTF-8 characters.
     *
     * @param string $string The string to convert to slug.
     * @param string $separator The separator to use (default: -).
     * @param string $encoding The character encoding to use (default: UTF-8).
     * @return string The generated slug.
     */
    public static function slug(
        string $string,
        string $separator = '-',
        string $encoding = 'UTF-8'
    ): string {
        // Convert to lowercase and trim.
        $string = mb_strtolower(trim($string), $encoding);

        // Common character replacements.
        $replacements = [
            // Latin.
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y', 'ñ' => 'n', 'ç' => 'c',
            // Symbols.
            '@' => 'at', '&' => 'and', '%' => 'percent',
            // Spaces and punctuation.
            ' ' => $separator, '_' => $separator, '.' => $separator,
            ',' => $separator, ':' => $separator, ';' => $separator,
            '/' => $separator, '\\' => $separator,
        ];

        // Apply replacements.
        $string = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $string
        );

        // Remove any character that is not alphanumeric or the separator.
        $string = preg_replace(
            '/[^a-z0-9' . preg_quote($separator) . ']/',
            '',
            $string
        );

        // Replace multiple separators with a single one.
        $string = preg_replace(
            '/' . preg_quote($separator) . '+/',
            $separator,
            $string
        );

        // Remove leading and trailing separators.
        return trim($string, $separator);
    }

    /**
     * Pads a multibyte string to a certain length.
     *
     * This method works like str_pad but with multibyte support.
     *
     * @param string $string The string to pad.
     * @param int $length The length to pad to.
     * @param string $padStr The string to pad with.
     * @param int $padType The padding type (STR_PAD_RIGHT, STR_PAD_LEFT, or
     * STR_PAD_BOTH).
     * @param string|null $encoding The character encoding to use.
     * @return string The padded string.
     */
    public static function pad(
        string $string,
        int $length,
        string $padStr = ' ',
        int $padType = STR_PAD_RIGHT,
        ?string $encoding = null
    ): string {
        $encoding = $encoding ?? mb_internal_encoding();
        $padBefore = $padType === STR_PAD_BOTH || $padType === STR_PAD_LEFT;
        $padAfter = $padType === STR_PAD_BOTH || $padType === STR_PAD_RIGHT;
        $targetLength = $length - mb_strlen($string, $encoding);

        if ($targetLength <= 0) {
            return $string;
        }

        $leftPadding = '';
        $rightPadding = '';

        if ($padBefore && $padAfter) {
            $leftLength = (int) round(floor($targetLength / 2));
            $rightLength = (int) round(ceil($targetLength / 2));
            $leftPadding = str_repeat($padStr, $leftLength);
            $rightPadding = str_repeat($padStr, $rightLength);
        } elseif ($padBefore) {
            $leftPadding = str_repeat($padStr, $targetLength);
        } else {
            $rightPadding = str_repeat($padStr, $targetLength);
        }

        return $leftPadding . $string . $rightPadding;
    }

    /**
     * Extracts a substring between two delimiters.
     *
     * @param string $text The text to extract from.
     * @param string $startDelimiter The starting delimiter.
     * @param string $endDelimiter The ending delimiter.
     * @param int $offset Starting position for the search.
     * @return array{string: string, start: int, end: int}|null Extracted data
     * or `null` if not found.
     */
    public static function extract(
        string $text,
        string $startDelimiter,
        string $endDelimiter,
        int $offset = 0
    ): ?array {
        $start = strpos($text, $startDelimiter, $offset);
        if ($start === false) {
            return null;
        }

        $start += strlen($startDelimiter);
        $end = strpos($text, $endDelimiter, $start);
        if ($end === false) {
            return null;
        }

        $length = $end - $start;

        return [
            'string' => trim(substr($text, $start, $length)),
            'start' => $start,
            'end' => $end - 1,
            'length' => $length,
        ];
    }

    /**
     * Splits parameters string handling escaped delimiters.
     *
     * @param string $parametersString String containing parameters.
     * @param string $delimiter Character used to separate parameters.
     * @return array Array of parameters.
     */
    public static function splitParameters(
        string $parametersString,
        string $delimiter = ','
    ): array {
        $escapedDelimiter = preg_quote($delimiter, '/');
        $tempMarker = '{{[[DELIMITER]]}}';
        $tempString = preg_replace(
            '/\\\\' . $escapedDelimiter . '/',
            $tempMarker,
            $parametersString
        );

        $parts = explode($delimiter, $tempString);

        return array_map(fn ($part) => str_replace(
            $tempMarker,
            $delimiter,
            trim($part)
        ), $parts);
    }

    /**
     * Converts a string from UTF-8 to ISO-8859-1.
     *
     * Only converts if the input string is actually UTF-8 encoded.
     *
     * @param string $string The string to convert.
     * @return string The converted string or original if conversion not possible.
     */
    public static function utf8decode(string $string): string
    {
        if (empty($string) || !mb_detect_encoding($string, 'UTF-8', true)) {
            return $string;
        }

        $result = mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');

        return $result !== false ? $result : $string;
    }

    /**
     * Converts a string from ISO-8859-1 to UTF-8.
     *
     * Only converts if the input string is actually ISO-8859-1 encoded.
     *
     * @param string $string The string to convert.
     * @return string The converted string or original if conversion not possible.
     */
    public static function utf8encode(string $string): string
    {
        if (empty($string) || !mb_detect_encoding($string, 'ISO-8859-1', true)) {
            return $string;
        }

        $result = mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');

        return $result !== false ? $result : $string;
    }

    /**
     * Generates a UUID v4 with RFC 4122 variant.
     *
     * @return string The generated UUID.
     */
    public static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Version 4.
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Variant RFC 4122.

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generates a random string of specified length.
     *
     * @param int $length The length of the string to generate.
     * @param bool $useUppercase Include uppercase letters.
     * @param bool $useNumbers Include numbers.
     * @param bool $useSpecial Include special characters.
     * @return string The generated random string.
     * @throws InvalidArgumentException If length is less than 1.
     */
    public static function random(
        int $length = 12,
        bool $useUppercase = true,
        bool $useNumbers = true,
        bool $useSpecial = false
    ): string {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be at least 1.');
        }

        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        $requiredChars = [
            $lowercase[random_int(0, strlen($lowercase) - 1)],
        ];

        if ($useUppercase) {
            $requiredChars[] = $uppercase[random_int(0, strlen($uppercase) - 1)];
        }
        if ($useNumbers) {
            $requiredChars[] = $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if ($useSpecial) {
            $requiredChars[] = $special[random_int(0, strlen($special) - 1)];
        }

        $chars = $lowercase;
        if ($useUppercase) {
            $chars .= $uppercase;
        }
        if ($useNumbers) {
            $chars .= $numbers;
        }
        if ($useSpecial) {
            $chars .= $special;
        }

        while (count($requiredChars) < $length) {
            $requiredChars[] = $chars[random_int(0, strlen($chars) - 1)];
        }

        shuffle($requiredChars);

        return mb_substr(implode('', $requiredChars), 0, $length);
    }
}
