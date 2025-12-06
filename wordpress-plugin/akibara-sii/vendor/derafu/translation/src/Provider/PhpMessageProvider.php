<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation\Provider;

use Derafu\Translation\Abstract\AbstractMessageProvider;
use Derafu\Translation\Contract\MessageProviderInterface;
use RuntimeException;

final class PhpMessageProvider extends AbstractMessageProvider implements MessageProviderInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getFileExtension(): string
    {
        return 'php';
    }

    /**
     * {@inheritDoc}
     */
    protected function parseFile(string $file): array
    {
        $messages = require $file;

        if (!is_array($messages)) {
            throw new RuntimeException(
                sprintf(
                    'File "%s" must return an array, got %s.',
                    $file,
                    gettype($messages)
                )
            );
        }

        return $messages;
    }
}
