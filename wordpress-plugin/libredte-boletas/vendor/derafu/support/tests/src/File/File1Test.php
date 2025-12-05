<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\File;

use Derafu\Support\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(File::class)]
class File1Test extends TestCase
{
    private string $testDir;

    private string $testFile;

    protected function setUp(): void
    {
        $this->testDir = dirname(__DIR__, 2) . '/testDir';
        $this->testFile = $this->testDir . '/testFile.txt';

        // Create test directory and file.
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->testDir);
        file_put_contents($this->testFile, 'Test content');
    }

    protected function tearDown(): void
    {
        // Clean up test directory and file.
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->testDir)) {
            $filesystem->remove($this->testDir);
        }
    }

    public function testRmdir(): void
    {
        File::rmdir($this->testDir);

        $this->assertDirectoryDoesNotExist($this->testDir);
    }

    public function testMimetype(): void
    {
        $result = File::mimetype($this->testFile);

        $this->assertSame('text/plain', $result);
    }

    public function testMimetypeFileNotFound(): void
    {
        $result = File::mimetype('/path/to/nonexistent/file.txt');

        $this->assertFalse($result);
    }

    public function testCompressFileSuccess(): void
    {
        $compressedFile = $this->testFile . '.zip';

        File::compress($this->testFile, download: false);

        $this->assertFileExists($compressedFile);
        unlink($compressedFile); // Clean up.
    }

    public function testCompressDirSuccess(): void
    {
        $compressedFile = $this->testDir . '.zip';

        File::compress($this->testDir, download: false);

        $this->assertFileExists($compressedFile);
        unlink($compressedFile); // Clean up.
    }

    public function testCompressFileError(): void
    {
        $this->expectException(RuntimeException::class);

        File::compress('/path/to/nonexistent/file.txt', download: false);
    }
}
