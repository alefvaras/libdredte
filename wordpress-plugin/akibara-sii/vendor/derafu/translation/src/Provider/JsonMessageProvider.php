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

final class JsonMessageProvider extends AbstractMessageProvider implements MessageProviderInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getFileExtension(): string
    {
        return 'json';
    }

    /**
     * {@inheritDoc}
     */
    protected function parseFile(string $file): array
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new RuntimeException(
                sprintf('Could not read file "%s".', $file)
            );
        }

        $messages = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($messages)) {
            throw new RuntimeException(
                sprintf(
                    'Invalid JSON in file "%s": %s',
                    $file,
                    json_last_error_msg()
                )
            );
        }

        return $messages;
    }
}
