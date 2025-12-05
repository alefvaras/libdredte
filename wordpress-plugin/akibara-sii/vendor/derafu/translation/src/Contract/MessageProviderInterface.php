<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation\Contract;

/**
 * Interface for message providers that load translations from different sources.
 */
interface MessageProviderInterface
{
    /**
     * Returns all messages for a given locale and domain.
     *
     * @param string $locale The locale to load messages for.
     * @param string $domain The translation domain.
     * @return array<string, string> Array of messages where key is the message id.
     */
    public function getMessages(string $locale, string $domain = 'messages'): array;

    /**
     * Returns all available locales for a given domain.
     *
     * @param string $domain The translation domain.
     * @return array<string> List of available locales.
     */
    public function getAvailableLocales(string $domain = 'messages'): array;
}
