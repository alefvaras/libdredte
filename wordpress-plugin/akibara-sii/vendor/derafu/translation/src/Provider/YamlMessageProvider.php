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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads messages from YAML files organized in a directory structure.
 *
 * Expected structure:
 *
 *   /translations
 *     /domain1
 *       en.yaml
 *       es.yaml
 *     /domain2
 *       en.yaml
 *       es.yaml
 */
final class YamlMessageProvider extends AbstractMessageProvider implements MessageProviderInterface
{
    /**
     * {@inheritDoc}
     */
    protected function getFileExtension(): string
    {
        return 'yaml';
    }

    /**
     * {@inheritDoc}
     */
    protected function parseFile(string $file): array
    {
        try {
            $yaml = Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new RuntimeException(
                sprintf(
                    'Invalid YAML in file "%s": %s',
                    $file,
                    $e->getMessage()
                )
            );
        }

        if (!is_array($yaml) || isset($yaml[0])) {
            throw new RuntimeException(
                sprintf(
                    'Not a valid messages YAML file "%s": should be key value pairs.',
                    $file
                )
            );
        }

        return $yaml;
    }
}
