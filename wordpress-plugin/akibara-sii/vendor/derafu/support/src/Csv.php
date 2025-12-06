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

use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use League\Csv\Writer;
use LogicException;
use RuntimeException;

/**
 * CSV file manipulation utilities.
 *
 * Provides a simplified interface for reading and writing CSV files using the
 * League/CSV library. Supports various operations like loading from string,
 * reading from file, generating CSV content, and sending CSV responses.
 */
final class Csv
{
    /**
     * Loads CSV data from a string.
     *
     * @param string $content CSV content as a string.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @param string $encoding Input encoding (default: UTF-8).
     * @return array Array of CSV records.
     * @throws RuntimeException If CSV parsing fails.
     */
    public static function load(
        string $content,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\',
        string $encoding = 'UTF-8'
    ): array {
        try {
            // Convert encoding if needed.
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }

            $csv = Reader::createFromString($content);
            $csv->setDelimiter($separator);
            $csv->setEnclosure($enclosure);
            $csv->setEscape($escape);

            return iterator_to_array($csv->getRecords());
        } catch (CsvException $e) {
            throw new RuntimeException(
                "Failed to parse CSV content: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Reads a CSV file.
     *
     * @param string $file Path to the CSV file.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @param string $encoding Input encoding (default: UTF-8).
     * @return array Array of CSV records.
     * @throws RuntimeException If file cannot be read or CSV parsing fails.
     */
    public static function read(
        string $file,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\',
        string $encoding = 'UTF-8'
    ): array {
        if (!is_readable($file)) {
            throw new RuntimeException("Cannot read file: {$file}");
        }

        try {
            $content = file_get_contents($file);
            if ($content === false) {
                throw new RuntimeException("Failed to read file: {$file}");
            }

            return self::load($content, $separator, $enclosure, $escape, $encoding);
        } catch (CsvException $e) {
            throw new RuntimeException(
                "Failed to read CSV file {$file}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Writes data to a CSV file.
     *
     * @param array $data Array of records to write.
     * @param string $file Path where the CSV file should be written.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @throws LogicException If file cannot be written.
     * @throws RuntimeException If CSV generation fails.
     */
    public static function write(
        array $data,
        string $file,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\'
    ): void {
        $dirname = dirname($file);
        if (!is_writable($dirname)) {
            throw new LogicException("Cannot use directory: {$dirname}");
        }

        $content = self::generate($data, $separator, $enclosure, $escape);

        if (file_put_contents($file, $content) === false) {
            throw new LogicException("Failed to write CSV file: {$file}");
        }
    }

    /**
     * Generates CSV content from an array.
     *
     * @param array $data Array of records to convert to CSV.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @param string $encoding Output encoding (default: UTF-8).
     * @return string Generated CSV content.
     * @throws RuntimeException If CSV generation fails.
     */
    public static function generate(
        array $data,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\',
        string $encoding = 'UTF-8'
    ): string {
        try {
            $csv = Writer::createFromString('');
            $csv->setDelimiter($separator);
            $csv->setEnclosure($enclosure);
            $csv->setEscape($escape);

            // If first element has keys, use them as header.
            if (!empty($data) && is_array(reset($data))) {
                $firstRow = reset($data);
                if (!array_is_list($firstRow)) {
                    $csv->insertOne(array_keys($firstRow));
                }
            }

            $csv->insertAll($data);

            $content = $csv->toString();

            // Convert encoding if needed.
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, $encoding, 'UTF-8');
            }

            return $content;
        } catch (CsvException $e) {
            throw new RuntimeException(
                "Failed to generate CSV: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Sends CSV content as a download response.
     *
     * @param array $data Array of records to send.
     * @param string $filename Suggested filename for the download.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @param bool $sendHeaders Whether to send HTTP headers (default: true).
     * @throws RuntimeException If CSV generation fails.
     */
    public static function send(
        array $data,
        string $filename,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\',
        bool $sendHeaders = true
    ): void {
        $content = self::generate($data, $separator, $enclosure, $escape);

        if ($sendHeaders) {
            if (headers_sent()) {
                throw new RuntimeException('Headers have already been sent.');
            }

            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        echo $content;
    }

    /**
     * Validates if a file appears to be a valid CSV.
     *
     * This method performs basic validation by:
     *
     *   1. Checking if the file exists and is readable.
     *   2. Attempting to parse the first few lines.
     *   3. Verifying consistent column count.
     *
     * @param string $file Path to the CSV file.
     * @param string $separator Column delimiter (default: ;).
     * @param string $enclosure Field enclosure character (default: ").
     * @param string $escape Escape character (default: \).
     * @param int $sampleSize Number of rows to check for validation (default: 5).
     * @return bool True if the file appears to be a valid CSV.
     */
    public static function validate(
        string $file,
        string $separator = ';',
        string $enclosure = '"',
        string $escape = '\\',
        int $sampleSize = 5
    ): bool {
        if (!is_readable($file)) {
            return false;
        }

        try {
            $csv = Reader::createFromPath($file, 'r');
            $csv->setDelimiter($separator);
            $csv->setEnclosure($enclosure);
            $csv->setEscape($escape);

            // Get the first few records.
            $records = [];
            $iterator = $csv->getRecords();
            $count = 0;

            foreach ($iterator as $record) {
                $records[] = $record;
                $count++;
                if ($count >= $sampleSize) {
                    break;
                }
            }

            if (empty($records)) {
                return false;
            }

            // Check for consistent column count.
            $columnCount = count($records[0]);
            foreach ($records as $record) {
                if (count($record) !== $columnCount) {
                    return false;
                }
            }

            return true;
        } catch (CsvException $e) {
            return false;
        }
    }
}
