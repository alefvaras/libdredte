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
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Csv::class)]
class Csv1Test extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = __DIR__ . '/test.csv';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testReadCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        Csv::write($data, $this->testFile);

        $result = Csv::read($this->testFile);

        $this->assertSame($data, $result);
    }

    public function testReadCsvFileNotFound(): void
    {
        $this->expectException(RuntimeException::class);

        Csv::read('/path/to/nonexistent/file.csv');
    }

    public function testGenerateCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        $result = Csv::generate($data);

        $expectedOutput = 'column1;column2' . "\n" .
                          'value1;value2' . "\n" .
                          'value3;value4' . "\n";

        $this->assertSame($expectedOutput, $result);
    }

    public function testWriteCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        Csv::write($data, $this->testFile);

        $this->assertFileExists($this->testFile);
        $this->assertSame($data, Csv::read($this->testFile));
    }

    public function testWriteCsvFileError(): void
    {
        $this->expectException(LogicException::class);

        Csv::write([], '/invalid/path/to/file.csv');
    }

    public function testSendCsvSuccess(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        ob_start();
        Csv::send($data, $this->testFile, sendHeaders: false);
        $result = ob_get_clean();

        $this->assertSame($data, Csv::load($result));
    }

    public function testGenerateCsvWithSpecialCharacters(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value;"with;semicolon"', 'value2'],
            ['value "with quotes"', 'value with spaces'],
            ['value with newline', "value\nwith\nnewlines"],
        ];

        $result = Csv::generate($data, ';', '"');

        $expectedOutput = 'column1;column2' . "\n" .
                        '"value;""with;semicolon""";value2' . "\n" .
                        '"value ""with quotes""";"value with spaces"' . "\n" .
                        '"value with newline";"value' . "\n" . 'with' . "\n" . 'newlines"' . "\n";

        $this->assertSame($expectedOutput, $result);
    }

    public function testReadCsvWithSpecialCharacters(): void
    {
        $csvContent = 'column1;column2' . "\n" .
                    '"value;""with;semicolon""";value2' . "\n" .
                    '"value ""with quotes""";value with spaces' . "\n" .
                    'value with newline;"value' . "\n" . 'with' . "\n" . 'newlines"' . "\n";

        file_put_contents($this->testFile, $csvContent);

        $expectedData = [
            ['column1', 'column2'],
            ['value;"with;semicolon"', 'value2'],
            ['value "with quotes"', 'value with spaces'],
            ['value with newline', "value\nwith\nnewlines"],
        ];

        $result = Csv::read($this->testFile);

        $this->assertSame($expectedData, $result);
    }

    public function testGenerateCsvWithEmptyFields(): void
    {
        $data = [
            ['column1', 'column2'],
            ['value1', ''],
            ['', 'value2'],
        ];

        $result = Csv::generate($data, ';', '"');

        $expectedOutput = 'column1;column2' . "\n" .
                        'value1;' . "\n" .
                        ';value2' . "\n";

        $this->assertSame($expectedOutput, $result);
    }

    public function testReadCsvWithEmptyFields(): void
    {
        $csvContent = 'column1;column2' . "\n" .
                    'value1;' . "\n" .
                    ';value2' . "\n";

        file_put_contents($this->testFile, $csvContent);

        $expectedData = [
            ['column1', 'column2'],
            ['value1', ''],
            ['', 'value2'],
        ];

        $result = Csv::read($this->testFile);

        $this->assertSame($expectedData, $result);
    }
}
