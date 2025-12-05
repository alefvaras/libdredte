<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation\Abstract;

use Derafu\Translation\Contract\MessageProviderInterface;
use RuntimeException;

/**
 * Base class for file-based message providers.
 *
 * Expected directory structure:
 *
 *   /translations
 *     /domain1
 *       en.{ext}
 *       es.{ext}
 *     /domain2
 *       en.{ext}
 *       es.{ext}
 */
abstract class AbstractMessageProvider implements MessageProviderInterface
{
    /**
     * @param string $directory The base directory containing translation files.
     *
     * @throws RuntimeException If the directory does not exist.
     */
    public function __construct(
        protected readonly string $directory
    ) {
        if (!is_dir($directory)) {
            throw new RuntimeException(
                sprintf('Directory "%s" does not exist.', $directory)
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages(string $locale, string $domain = 'messages'): array
    {
        $file = $this->getFilePath($locale, $domain);

        if (!file_exists($file)) {
            return [];
        }

        return $this->parseFile($file);
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableLocales(string $domain = 'messages'): array
    {
        $pattern = sprintf(
            '%s/%s/*.%s',
            $this->directory,
            $domain,
            $this->getFileExtension()
        );

        return array_map(
            fn ($file) => pathinfo($file, PATHINFO_FILENAME),
            glob($pattern) ?: []
        );
    }

    /**
     * Returns the file extension for the message files (without dot).
     *
     * @return string
     */
    abstract protected function getFileExtension(): string;

    /**
     * Reads and parses the content of a message file.
     *
     * @param string $file The full path to the file
     * @return array<string, string> The parsed messages
     * @throws RuntimeException If the file cannot be read or parsed
     */
    abstract protected function parseFile(string $file): array;

    /**
     * Returns the full path to a message file.
     *
     * @param string $locale
     * @param string $domain
     * @return string
     */
    protected function getFilePath(string $locale, string $domain): string
    {
        return sprintf(
            '%s/%s/%s.%s',
            $this->directory,
            $domain,
            $locale,
            $this->getFileExtension()
        );
    }
}
