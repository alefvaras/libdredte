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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

#[CoversClass(File::class)]
class File2Test extends TestCase
{
    private string $fixturesDir;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesDir = dirname(__DIR__, 2) . '/fixtures';
        $this->tempDir = sys_get_temp_dir() . '/derafu-support-tests';

        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir)) {
            $this->recursiveRemove($this->tempDir);
        }
        parent::tearDown();
    }

    #[Test]
    public function shouldRemoveDirectory(): void
    {
        // Create test directory with files.
        $testDir = $this->tempDir . '/rmdir-test';
        mkdir($testDir);
        file_put_contents($testDir . '/file1.txt', 'test');
        mkdir($testDir . '/subdir');
        file_put_contents($testDir . '/subdir/file2.txt', 'test');

        $this->assertTrue(is_dir($testDir));

        File::rmdir($testDir);

        $this->assertFalse(is_dir($testDir));
    }

    #[Test]
    public function shouldDetectMimeType(): void
    {
        $textFile = $this->fixturesDir . '/files/text.txt';
        $this->assertSame('text/plain', File::mimetype($textFile));

        $imageFile = $this->fixturesDir . '/files/image.jpeg';
        $this->assertSame('image/jpeg', File::mimetype($imageFile));

        $pdfFile = $this->fixturesDir . '/files/document.pdf';
        $this->assertSame('application/pdf', File::mimetype($pdfFile));

        $this->assertFalse(File::mimetype('nonexistent.file'));
    }

    #[Test]
    public function shouldCompressSingleFile(): void
    {
        $source = $this->fixturesDir . '/zip/single/file.txt';
        $dest = $this->tempDir . '/single.zip';

        File::zip($source, $dest);

        $this->assertTrue(file_exists($dest));
        $this->assertGreaterThan(0, filesize($dest));

        // Verify ZIP content.
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($dest));
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('file.txt', $zip->getNameIndex(0));
        $zip->close();
    }

    #[Test]
    public function shouldCompressDirectory(): void
    {
        $source = $this->fixturesDir . '/zip/multiple';
        $dest = $this->tempDir . '/multiple.zip';

        File::zip($source, $dest);

        $this->assertTrue(file_exists($dest));
        $this->assertGreaterThan(0, filesize($dest));

        // Verify ZIP content.
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($dest));
        $this->assertSame(2, $zip->numFiles);
        $this->assertContains('file1.txt', $this->getZipEntries($zip));
        $this->assertContains('file2.txt', $this->getZipEntries($zip));
        $zip->close();
    }

    #[Test]
    public function shouldCompressAndDelete(): void
    {
        // Create test directory with content.
        $source = $this->tempDir . '/to-delete';
        mkdir($source);
        file_put_contents($source . '/test.txt', 'test content');

        File::compress($source, false, true);

        $this->assertFalse(is_dir($source));
        $this->assertTrue(file_exists($source . '.zip'));
    }

    #[Test]
    public function shouldSendFile(): void
    {
        $file = $this->fixturesDir . '/files/text.txt';

        // Start output buffering to capture content.
        $this->expectOutputString(file_get_contents($file));

        File::send($file, sendHeaders: false);
    }

    #[Test]
    public function shouldFailOnNonexistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        File::send('nonexistent.file');
    }

    #[Test]
    public function shouldFailOnUnreadableFile(): void
    {
        $file = $this->tempDir . '/unreadable.txt';
        file_put_contents($file, 'test');
        chmod($file, 0000);

        $this->expectException(RuntimeException::class);
        File::send($file);

        chmod($file, 0644); // Restore permissions for cleanup.
    }

    /**
     * Helper method to recursively remove a directory.
     */
    private function recursiveRemove(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveRemove($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Helper method to get all entries in a ZIP file.
     */
    private function getZipEntries(ZipArchive $zip): array
    {
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        return $entries;
    }
}
