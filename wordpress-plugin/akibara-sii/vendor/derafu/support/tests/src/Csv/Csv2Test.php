<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Csv;

use Derafu\Support\Csv;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Csv::class)]
class Csv2Test extends TestCase
{
    private string $fixturesDir;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = dirname(__DIR__, 2) . '/fixtures/csv';
        $this->tempDir = sys_get_temp_dir() . '/derafu-support-tests';

        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function shouldLoadCsvFromString(): void
    {
        $csvString = "name;age;city\nJohn;25;New York\nJane;30;London";
        $result = Csv::load($csvString);

        $this->assertCount(3, $result);
        $this->assertSame('John', $result[1][0]);
        $this->assertSame('30', $result[2][1]);
        $this->assertSame('London', $result[2][2]);
    }

    #[Test]
    #[DataProvider('provideCsvData')]
    public function shouldReadCsvFile(
        string $filename,
        string $separator,
        array $expected
    ): void {
        $file = $this->fixturesDir . '/' . $filename;
        $result = Csv::read($file, $separator);
        $this->assertSame($expected, $result);
    }

    public static function provideCsvData(): array
    {
        return [
            'standard csv' => [
                'standard.csv',
                ';',
                [
                    ['name', 'age', 'city'],
                    ['John', '25', 'New York'],
                    ['Jane', '30', 'London'],
                ],
            ],
            'different separator' => [
                'comma.csv',
                ',',
                [
                    ['name', 'age', 'city'],
                    ['John', '25', 'New York'],
                    ['Jane', '30', 'London'],
                ],
            ],
            'quoted values' => [
                'quoted.csv',
                ';',
                [
                    ['name', 'description'],
                    ['John Smith', 'Works in "Tech"'],
                    ['Jane Doe', 'Lives in; London'],
                ],
            ],
        ];
    }

    #[Test]
    public function shouldGenerateCsvContent(): void
    {
        $data = [
            ['name' => 'John', 'age' => '25'],
            ['name' => 'Jane', 'age' => '30'],
        ];

        $result = Csv::generate($data);
        $expected = "name;age\nJohn;25\nJane;30\n";

        // Normalize line endings.
        $result = str_replace("\r\n", "\n", $result);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function shouldWriteCsvFile(): void
    {
        $data = [
            ['name' => 'John', 'age' => '25'],
            ['name' => 'Jane', 'age' => '30'],
        ];

        $outputFile = $this->tempDir . '/output.csv';
        Csv::write($data, $outputFile);

        $this->assertFileExists($outputFile);
        $expected = array_merge(
            [array_keys($data[0])],
            array_map('array_values', $data)
        );
        $this->assertSame($expected, Csv::read($outputFile));
    }

    #[Test]
    public function shouldSendCsvToOutput(): void
    {
        $data = [
            ['name' => 'John', 'age' => '25'],
            ['name' => 'Jane', 'age' => '30'],
        ];

        // Start output buffering.
        ob_start();

        Csv::send($data, 'test.csv', sendHeaders: false);

        $output = ob_get_clean();

        // Verify content.
        $result = Csv::load($output);
        $expected = array_merge(
            [array_keys($data[0])],
            array_map('array_values', $data)
        );
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function shouldHandleEmptyData(): void
    {
        $result = Csv::generate([]);
        $this->assertSame('', $result);
    }

    #[Test]
    public function shouldHandleSpecialCharacters(): void
    {
        $data = [
            ['name' => 'John; Smith', 'note' => 'Contains; semicolon'],
            ['name' => 'Jane "Doe"', 'note' => 'Contains "quotes"'],
        ];

        $csv = Csv::generate($data);
        $result = Csv::load($csv);
        $expected = array_merge(
            [array_keys($data[0])],
            array_map('array_values', $data)
        );
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function shouldFailOnInvalidFile(): void
    {
        $this->expectException(RuntimeException::class);
        Csv::read('nonexistent.csv');
    }

    #[Test]
    public function shouldFailOnUnwritableLocation(): void
    {
        $this->expectException(LogicException::class);

        $data = [['test' => 'value']];
        $invalidPath = '/invalid/path/file.csv';

        Csv::write($data, $invalidPath);
    }

    /**
     * Helper method to recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
